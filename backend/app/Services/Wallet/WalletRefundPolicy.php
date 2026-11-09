<?php

namespace App\Services\Wallet;

use App\Models\GiftCard;
use App\Models\Invoice;
use App\Models\LoyaltyPoint;
use App\Models\LoyaltyPointTransaction;
use App\Models\PaymentAttempt;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Booking\Models\Booking;
use Modules\Affiliate\Models\Affiliate;
use Modules\Booking\Models\BookingTransaction;
use Modules\Product\Models\Order;
use Modules\Tracking\Models\Conversion;
use Modules\Wallet\Models\WalletHistory;

/**
 * Refunds cancelled bookings/orders to the wallet according to the wallet policy.
 *
 * Only money the system actually received is refunded:
 *   - invoices created by the payment flow (gateway-verified, sub-methods, or COD deposit), and
 *   - wallet debits the ledger recorded against the booking / order group.
 * A booking merely flagged "paid" (e.g. through save-payment with a non-wallet type)
 * has no such record and is not refunded automatically.
 *
 * Each part goes back where it came from: gateway money and wallet cash -> cash,
 * promotional credit -> bonus, loyalty points -> points. The late-cancellation fee
 * applies to every part. Every credit is idempotent per booking/order.
 */
class WalletRefundPolicy
{
    public function __construct(private WalletLedger $ledger, private WalletSettings $settings) {}

    public function feePercentForBooking(Booking $booking, ?Carbon $at = null): float
    {
        $start = $booking->start_date_time ? Carbon::parse($booking->start_date_time) : null;
        if (! $start) {
            return 0.0;
        }
        $hoursBefore = ($at ?? now())->diffInMinutes($start, false) / 60;

        return $hoursBefore >= $this->settings->float('wallet_free_cancel_hours')
            ? 0.0
            : max(0, min(100, $this->settings->float('wallet_late_cancel_fee_percent')));
    }

    /** @return array{cash: float, bonus: float, points: int, fee_percent: float}|null */
    public function refundBooking(Booking $booking): ?array
    {
        if (! $this->settings->bool('wallet_refund_on_cancel') || ! $booking->user_id) {
            return null;
        }

        $fee = $this->feePercentForBooking($booking);
        $parts = ['cash' => 0.0, 'bonus' => 0.0, 'points' => 0, 'points_amount' => 0.0];

        $invoiceIds = BookingTransaction::where('booking_id', $booking->id)
            ->where('payment_status', 1)
            ->where('external_transaction_id', 'like', 'INV-%')
            ->pluck('external_transaction_id')
            ->map(fn ($ref) => (int) substr($ref, 4))
            ->unique();

        foreach (Invoice::whereIn('id', $invoiceIds)->where('user_id', $booking->user_id)->get() as $invoice) {
            $alone = count($invoice->cart_ids ?? []) === 1 && empty($invoice->gift_ids) && empty($invoice->product_ids);
            $value = $alone ? (float) $invoice->final_total : $this->bookingValue($booking->id) * $this->discountFactor($invoice);
            $this->addInvoiceShare($parts, $invoice, $value, $booking->id);
        }

        $this->addDirectWalletPayments($parts, 'booking', $booking->id);

        return $this->pay($parts, $fee, (int) $booking->user_id, 'booking', $booking->id);
    }

    /** Product orders: no time-based fee; refunded in full when cancelled before delivery. */
    public function refundOrder(Order $order): ?array
    {
        if (! $this->settings->bool('wallet_refund_on_cancel') || ! $order->user_id || $order->payment_status !== 'paid') {
            return null;
        }

        $parts = ['cash' => 0.0, 'bonus' => 0.0, 'points' => 0, 'points_amount' => 0.0];
        $groupId = (int) $order->order_group_id;
        $groupTotal = (float) (DB::table('order_groups')->where('id', $groupId)->value('grand_total_amount') ?? 0);

        $invoices = Invoice::where('user_id', $order->user_id)->get()
            ->filter(fn (Invoice $invoice) => in_array($groupId, array_map('intval', $invoice->product_ids ?? []), true));
        foreach ($invoices as $invoice) {
            $this->addInvoiceShare($parts, $invoice, $groupTotal, null, $order->id);
        }

        $this->addDirectWalletPayments($parts, 'order_group', $groupId);

        return $this->pay($parts, 0.0, (int) $order->user_id, 'order', $order->id);
    }

    /** Gift purchases (cancelled before the recipient claimed them): refund the buyer. */
    public function refundGift(GiftCard $gift): ?array
    {
        if (! $gift->user_id) {
            return null;
        }

        $parts = ['cash' => 0.0, 'bonus' => 0.0, 'points' => 0, 'points_amount' => 0.0];
        $invoices = Invoice::where('user_id', $gift->user_id)->get()
            ->filter(fn (Invoice $invoice) => in_array((int) $gift->id, array_map('intval', $invoice->gift_ids ?? []), true));

        foreach ($invoices as $invoice) {
            $alone = count($invoice->gift_ids ?? []) === 1 && empty($invoice->cart_ids) && empty($invoice->product_ids);
            $value = $alone ? (float) $invoice->final_total : $this->giftGross($gift) * $this->discountFactor($invoice);
            $this->addInvoiceShare($parts, $invoice, $value, null, null, $gift->id);
        }

        return $this->pay($parts, 0.0, (int) $gift->user_id, 'gift', $gift->id);
    }

    /** Price the buyer paid for one gift: value cards carry no VAT, service/package gifts do. */
    private function giftGross(GiftCard $gift): float
    {
        $subtotal = (float) $gift->subtotal;
        if ($gift->type === GiftCard::TYPE_VALUE) {
            return $subtotal;
        }
        try {
            $tax = (float) (getBookingTaxamount($subtotal, 0, null)['total_tax_amount'] ?? 0);
        } catch (\Throwable) {
            $tax = 0.0;
        }

        return $subtotal + $tax;
    }

    /**
     * Add this item's share of one invoice, split by how the invoice was paid.
     * $value is the item's price after the invoice discount.
     */
    private function addInvoiceShare(array &$parts, Invoice $invoice, float $value, ?int $bookingId, ?int $orderId = null, ?int $giftId = null): void
    {
        $total = (float) $invoice->final_total;
        if ($total <= 0 || $value <= 0) {
            return;
        }

        // Do not refund more of this invoice than was paid, across all its items.
        // Look only at refunds of the invoice's other items (indexed columns), then read the JSON in PHP.
        $siblingBookings = array_diff(array_map('intval', $invoice->cart_ids ?? []), [(int) $bookingId]);
        $siblingOrders = DB::table('orders')->whereIn('order_group_id', array_map('intval', $invoice->product_ids ?? []))
            ->where('id', '!=', (int) $orderId)->pluck('id')->all();
        $siblingGifts = array_diff(array_map('intval', $invoice->gift_ids ?? []), [(int) $giftId]);
        $alreadyRefunded = (float) WalletHistory::where('source', 'refund')
            ->where(fn ($q) => $q
                ->where(fn ($b) => $b->where('source_type', 'booking')->whereIn('source_id', $siblingBookings ?: [0]))
                ->orWhere(fn ($o) => $o->where('source_type', 'order')->whereIn('source_id', $siblingOrders ?: [0]))
                ->orWhere(fn ($g) => $g->where('source_type', 'gift')->whereIn('source_id', $siblingGifts ?: [0])))
            ->get(['activity_data'])
            ->map(fn ($row) => json_decode($row->activity_data ?? '', true) ?: [])
            ->filter(fn (array $data) => (int) ($data['invoice_id'] ?? 0) === (int) $invoice->id)
            ->sum(fn (array $data) => (float) ($data['gross_share'] ?? 0));
        $value = min($value, max(0, $total - $alreadyRefunded));
        if ($value <= 0) {
            return;
        }
        $ratio = $value / $total;

        $walletRows = WalletHistory::where('source', 'payment')->where('direction', 'debit')
            ->where('source_type', 'invoice')->where('source_id', $invoice->id)->get();
        $cashUsed = (float) $walletRows->sum('cash_amount');
        $bonusUsed = (float) $walletRows->sum('bonus_amount');

        $pointsRow = LoyaltyPointTransaction::where('user_id', $invoice->user_id)
            ->where('action', 'deduct')->where('source_id', $invoice->id)
            ->where('source', 'Loyalty points payment deduction')->first();
        $pointsUsed = (int) ($pointsRow->points ?? 0);
        $pointsAmount = (float) ($pointsRow?->meta['amount'] ?? 0);

        // For COD only the wallet deposit was charged; otherwise the gateway paid the rest.
        $attempt = PaymentAttempt::where('invoice_id', $invoice->id)->first();
        if ($attempt?->gateway === 'cod' && $cashUsed + $bonusUsed <= 0) {
            // Deposit taken before the ledger existed: only the attempt recorded it.
            $cashUsed = (float) ($attempt->meta['deposit_amount'] ?? 0);
        }
        $charged = $attempt?->gateway === 'cod' ? $cashUsed + $bonusUsed : $total;
        $gatewayPaid = max(0, $charged - $cashUsed - $bonusUsed - $pointsAmount);

        $parts['cash'] += ($gatewayPaid + $cashUsed) * $ratio;
        $parts['bonus'] += $bonusUsed * $ratio;
        $parts['points'] += (int) floor($pointsUsed * $ratio);
        $parts['points_amount'] += $pointsAmount * $ratio;
        $parts['invoice_id'] = $invoice->id;
        $parts['invoices'][] = ['invoice' => $invoice, 'ratio' => $ratio];
        $parts['gross_share'] = ($parts['gross_share'] ?? 0) + $value;
    }

    /** Wallet debits recorded directly against the booking / order group (not via an invoice). */
    private function addDirectWalletPayments(array &$parts, string $sourceType, int $sourceId): void
    {
        $rows = WalletHistory::where('source', 'payment')->where('direction', 'debit')
            ->where('source_type', $sourceType)->where('source_id', $sourceId)->get();
        $parts['cash'] += (float) $rows->sum('cash_amount');
        $parts['bonus'] += (float) $rows->sum('bonus_amount');
    }

    private function pay(array $parts, float $feePercent, int $userId, string $type, int $id): ?array
    {
        // A booking/order is refunded at most once (re-cancelling must not repeat the clawbacks).
        if (WalletHistory::whereIn('idempotency_key', ["refund:$type:$id:cash", "refund:$type:$id:bonus"])->exists()
            || LoyaltyPointTransaction::whereIn('source', ["refund_$type", "refund_clawback_$type"])->where('source_id', $id)->exists()) {
            return null;
        }

        $keep = 1 - $feePercent / 100;
        $cash = round($parts['cash'] * $keep, 2);
        $bonus = round($parts['bonus'] * $keep, 2);
        $points = (int) floor($parts['points'] * $keep);
        if ($cash <= 0 && $bonus <= 0 && $points <= 0) {
            return null;
        }

        $meta = [
            'fee_percent' => $feePercent,
            'invoice_id' => $parts['invoice_id'] ?? null,
            'gross_share' => round($parts['gross_share'] ?? 0, 2),
        ];

        DB::transaction(function () use ($cash, $bonus, $points, $userId, $type, $id, $meta, $parts, $keep) {
            $common = ['source_type' => $type, 'source_id' => $id, 'activity_type' => 'refund', 'meta' => $meta];
            if ($cash > 0) {
                $this->ledger->credit($userId, $cash, 'refund', $common + ['idempotency_key' => "refund:$type:$id:cash"]);
            }
            if ($bonus > 0) {
                // gross_share is recorded once (on the cash row) so the invoice cap is not double counted.
                $this->ledger->credit($userId, $bonus, 'refund', ['bucket' => 'bonus', 'idempotency_key' => "refund:$type:$id:bonus", 'meta' => ['gross_share' => 0] + $meta] + $common);
            }
            if ($points > 0 && ! LoyaltyPointTransaction::where('source', "refund_$type")->where('source_id', $id)->exists()) {
                $loyalty = LoyaltyPoint::where('user_id', $userId)->lockForUpdate()->first() ?? LoyaltyPoint::create(['user_id' => $userId, 'points' => 0]);
                $loyalty->points += $points;
                $loyalty->save();
                LoyaltyPointTransaction::create([
                    'user_id' => $userId,
                    'action' => 'add',
                    'points' => $points,
                    'balance_after' => $loyalty->points,
                    'source' => "refund_$type",
                    'source_id' => $id,
                    'meta' => $meta,
                ]);
            }

            foreach ($parts['invoices'] ?? [] as ['invoice' => $invoice, 'ratio' => $ratio]) {
                $this->reverseRewards($userId, $invoice, $ratio * $keep, $type, $id);
            }
        });

        Log::info('Wallet refund', compact('type', 'id', 'userId', 'cash', 'bonus', 'points', 'feePercent'));

        return ['cash' => $cash, 'bonus' => $bonus, 'points' => $points, 'fee_percent' => $feePercent];
    }

    /**
     * Take back the share of what the purchase earned that matches the refunded money:
     * loyalty points (LoyaltyPointAwardService formula) and the referrer's commission.
     * Without this, buy -> earn -> cancel -> refund could be repeated to farm rewards.
     */
    private function reverseRewards(int $userId, Invoice $invoice, float $share, string $type, int $id): void
    {
        $earned = (int) floor((float) $invoice->final_total / 100) * (int) (Setting::get('points_per_100') ?? 5);
        $points = (int) floor($earned * $share);
        if ($points > 0) {
            $loyalty = LoyaltyPoint::where('user_id', $userId)->lockForUpdate()->first();
            $points = min($points, (int) ($loyalty->points ?? 0));
            if ($loyalty && $points > 0) {
                $loyalty->points -= $points;
                $loyalty->save();
                LoyaltyPointTransaction::create([
                    'user_id' => $userId,
                    'action' => 'deduct',
                    'points' => $points,
                    'balance_after' => $loyalty->points,
                    'source' => "refund_clawback_$type",
                    'source_id' => $id,
                    'meta' => ['invoice_id' => $invoice->id, 'reason' => 'earned points reversed on refund'],
                ]);
            }
        }

        $conversion = Conversion::where('order_id', $invoice->id)->where('referred_user_id', $userId)->where('status', 'approved')->first();
        if (! $conversion) {
            return;
        }
        $reverse = round((float) $conversion->commission * $share, 2);
        if ($reverse <= 0) {
            return;
        }
        $remaining = round((float) $conversion->commission - $reverse, 2);
        $conversion->update(['commission' => max(0, $remaining), 'status' => $remaining <= 0 ? 'rejected' : 'approved']);
        $affiliate = Affiliate::whereKey($conversion->affiliate_id)->lockForUpdate()->first();
        if ($affiliate) {
            $affiliate->wallet_total = max(0, (float) $affiliate->wallet_total - $reverse);
            $affiliate->wallet_available = max(0, (float) $affiliate->wallet_available - $reverse);
            $affiliate->save();
        }
    }

    private function bookingValue(int $bookingId): float
    {
        $services = (float) DB::table('booking_services')->where('booking_id', $bookingId)->whereNull('deleted_at')->sum('service_price');
        $packages = (float) DB::table('booking_packages')->where('booking_id', $bookingId)->whereNull('deleted_at')->sum('package_price');

        return $services + $packages;
    }

    private function discountFactor(Invoice $invoice): float
    {
        $total = (float) $invoice->final_total;
        $discount = (float) $invoice->discount_amount;

        return $total + $discount > 0 ? $total / ($total + $discount) : 1.0;
    }
}
