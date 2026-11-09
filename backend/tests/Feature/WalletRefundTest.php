<?php

namespace Tests\Feature;

use App\Models\PaymentAttempt;
use App\Services\Wallet\WalletLedger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Booking\Models\Booking;
use Modules\Product\Models\Order;
use Tests\Feature\Concerns\WalletSchema;
use Tests\TestCase;

class WalletRefundTest extends TestCase
{
    use WalletSchema;

    private WalletLedger $ledger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createWalletSchema();
        $this->ledger = app(WalletLedger::class);
    }

    /** A booking paid through the normal checkout: invoice + BookingTransaction INV-x. */
    private function paidBooking(float $price, float $total, string $startsIn = '+48 hours', array $cartIds = null): array
    {
        $booking = Booking::create(['user_id' => 1, 'status' => 'confirmed', 'start_date_time' => now()->modify($startsIn)->format('Y-m-d H:i:s')]);
        DB::table('booking_services')->insert(['booking_id' => $booking->id, 'service_price' => $price]);
        $invoiceId = DB::table('invoices')->insertGetId([
            'user_id' => 1, 'cart_ids' => json_encode($cartIds ?? [$booking->id]), 'gift_ids' => '[]', 'product_ids' => '[]',
            'discount_amount' => 0, 'final_total' => $total,
        ]);
        DB::table('booking_transactions')->insert([
            'booking_id' => $booking->id, 'external_transaction_id' => 'INV-'.$invoiceId, 'transaction_type' => 'tap', 'payment_status' => 1,
        ]);

        return [$booking, $invoiceId];
    }

    private function cancel(Booking $booking): void
    {
        $booking->update(['status' => 'cancelled']);
    }

    public function test_early_cancellation_refunds_gateway_and_wallet_parts_to_cash(): void
    {
        $this->ledger->credit(1, 50, 'topup');
        [$booking, $invoiceId] = $this->paidBooking(200, 200);
        $this->ledger->debit(1, 50, 'payment', ['source_type' => 'invoice', 'source_id' => $invoiceId]); // 150 by card, 50 wallet

        $this->cancel($booking);

        $this->assertSame(['total' => 200.0, 'cash' => 200.0, 'bonus' => 0.0], $this->balances(1));
        $this->assertLedgerMatchesBalance(1);
    }

    public function test_late_cancellation_keeps_the_configured_fee(): void
    {
        [$booking] = $this->paidBooking(200, 200, '+2 hours');

        $this->cancel($booking);

        $this->assertSame(100.0, $this->balances(1)['cash']); // default: 50% fee inside 24h
    }

    public function test_refund_is_applied_once_even_if_the_booking_is_cancelled_again(): void
    {
        [$booking] = $this->paidBooking(120, 120);

        $this->cancel($booking);
        $booking->update(['status' => 'confirmed']);
        $this->cancel($booking);

        $this->assertSame(120.0, $this->balances(1)['total']);
        $this->assertSame(1, DB::table('wallet_histories')->where('source', 'refund')->count());
    }

    public function test_booking_flagged_paid_without_any_payment_is_not_refunded(): void
    {
        $booking = Booking::create(['user_id' => 1, 'status' => 'confirmed', 'start_date_time' => now()->addDays(3)->format('Y-m-d H:i:s')]);
        DB::table('booking_services')->insert(['booking_id' => $booking->id, 'service_price' => 300]);
        DB::table('booking_transactions')->insert(['booking_id' => $booking->id, 'external_transaction_id' => 'cash-123', 'transaction_type' => 'cash', 'payment_status' => 1]);

        $this->cancel($booking);

        $this->assertSame(0.0, $this->balances(1)['total']);
    }

    public function test_bonus_and_points_go_back_to_where_they_came_from(): void
    {
        DB::table('loyalty_points')->insert(['user_id' => 1, 'points' => 5]); // the 5 points this purchase earned
        $this->ledger->credit(1, 40, 'campaign', ['bucket' => 'bonus']);
        [$booking, $invoiceId] = $this->paidBooking(100, 100);
        $this->ledger->debit(1, 40, 'payment', ['source_type' => 'invoice', 'source_id' => $invoiceId]);
        DB::table('loyalty_points_transactions')->insert([
            'user_id' => 1, 'action' => 'deduct', 'points' => 20, 'balance_after' => 0,
            'source' => 'Loyalty points payment deduction', 'source_id' => $invoiceId, 'meta' => json_encode(['amount' => 10]),
        ]); // 40 bonus + 10 via points + 50 by card

        $this->cancel($booking);

        $this->assertSame(['total' => 90.0, 'cash' => 50.0, 'bonus' => 40.0], $this->balances(1));
        $this->assertSame(20, (int) DB::table('loyalty_points')->where('user_id', 1)->value('points')); // +20 spent back, −5 earned
        $this->assertLedgerMatchesBalance(1);
    }

    public function test_cash_on_delivery_refunds_only_the_deposit(): void
    {
        $this->ledger->credit(1, 100, 'topup');
        [$booking, $invoiceId] = $this->paidBooking(200, 200);
        $this->ledger->debit(1, 60, 'payment', ['source_type' => 'invoice', 'source_id' => $invoiceId]);
        PaymentAttempt::create([
            'token' => (string) Str::uuid(), 'user_id' => 1, 'gateway' => 'cod', 'gross_amount' => 200, 'amount' => 0,
            'status' => PaymentAttempt::STATUS_PAID, 'invoice_id' => $invoiceId, 'meta' => ['deposit_amount' => 60],
        ]);

        $this->cancel($booking);

        $this->assertSame(100.0, $this->balances(1)['cash']); // 40 left + 60 deposit back
    }

    public function test_bookings_sharing_an_invoice_never_refund_more_than_was_paid(): void
    {
        $a = Booking::create(['user_id' => 1, 'status' => 'confirmed', 'start_date_time' => now()->addDays(3)->format('Y-m-d H:i:s')]);
        $b = Booking::create(['user_id' => 1, 'status' => 'confirmed', 'start_date_time' => now()->addDays(3)->format('Y-m-d H:i:s')]);
        DB::table('booking_services')->insert([['booking_id' => $a->id, 'service_price' => 150], ['booking_id' => $b->id, 'service_price' => 150]]);
        $invoiceId = DB::table('invoices')->insertGetId([
            'user_id' => 1, 'cart_ids' => json_encode([$a->id, $b->id]), 'gift_ids' => '[]', 'product_ids' => '[]',
            'discount_amount' => 60, 'final_total' => 240,
        ]);
        foreach ([$a, $b] as $booking) {
            DB::table('booking_transactions')->insert(['booking_id' => $booking->id, 'external_transaction_id' => 'INV-'.$invoiceId, 'payment_status' => 1]);
        }

        $this->cancel($a);
        $this->assertSame(120.0, $this->balances(1)['cash']); // 150 after the 20% invoice discount
        $this->cancel($b);
        $this->assertSame(240.0, $this->balances(1)['cash']);
    }

    public function test_cancelled_wallet_paid_order_is_refunded(): void
    {
        $this->ledger->credit(1, 300, 'topup');
        $groupId = DB::table('order_groups')->insertGetId(['user_id' => 1, 'grand_total_amount' => 180]);
        $this->ledger->debit(1, 180, 'payment', ['source_type' => 'order_group', 'source_id' => $groupId]);
        $order = Order::unguarded(fn () => Order::create(['user_id' => 1, 'order_group_id' => $groupId, 'payment_status' => 'paid']));

        $order->delivery_status = 'cancelled';
        $order->save();

        $this->assertSame(300.0, $this->balances(1)['total']);
        $this->assertLedgerMatchesBalance(1);
    }

    public function test_refunds_can_be_switched_off_by_policy(): void
    {
        DB::table('settings')->insert(['name' => 'wallet_refund_on_cancel', 'val' => '0', 'type' => 'string']);
        [$booking] = $this->paidBooking(200, 200);

        $this->cancel($booking);

        $this->assertSame(0.0, $this->balances(1)['total']);
    }

    public function test_refund_takes_back_earned_points_and_the_referrers_commission(): void
    {
        DB::table('settings')->insert(['name' => 'points_per_100', 'val' => '5', 'type' => 'string']);
        DB::table('loyalty_points')->insert(['user_id' => 1, 'points' => 30]); // 10 earned from this purchase + 20 older
        DB::table('affiliates')->insert(['id' => 5, 'user_id' => 2, 'wallet_total' => 40, 'wallet_available' => 40]);
        [$booking, $invoiceId] = $this->paidBooking(200, 200);
        DB::table('affiliate_conversions')->insert(['affiliate_id' => 5, 'referred_user_id' => 1, 'order_id' => $invoiceId, 'amount' => 200, 'commission' => 20, 'status' => 'approved']);

        $this->cancel($booking);
        $booking->update(['status' => 'confirmed']);
        $this->cancel($booking); // second cancel must not reverse twice

        $this->assertSame(200.0, $this->balances(1)['cash']);
        $this->assertSame(20, (int) DB::table('loyalty_points')->where('user_id', 1)->value('points'));
        $this->assertSame('rejected', DB::table('affiliate_conversions')->value('status'));
        $this->assertSame(20.0, (float) DB::table('affiliates')->where('id', 5)->value('wallet_available'));
    }

    public function test_cash_on_delivery_deposit_taken_before_the_ledger_is_still_refunded(): void
    {
        [$booking, $invoiceId] = $this->paidBooking(200, 200);
        PaymentAttempt::create([
            'token' => (string) Str::uuid(), 'user_id' => 1, 'gateway' => 'cod', 'gross_amount' => 200, 'amount' => 0,
            'status' => PaymentAttempt::STATUS_PAID, 'invoice_id' => $invoiceId, 'meta' => ['deposit_amount' => 60],
        ]); // no ledger row: the deposit was debited by the old code

        $this->cancel($booking);

        $this->assertSame(60.0, $this->balances(1)['cash']);
    }
}
