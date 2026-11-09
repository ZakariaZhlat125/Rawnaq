<?php

namespace App\Services\Gifts;

use App\Models\GiftCard;
use App\Models\GiftCardRedemption;
use App\Models\User;
use App\Services\Wallet\WalletLedger;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Booking\Models\Booking;
use Modules\Booking\Models\BookingService;
use Modules\Booking\Models\BookingTransaction;
use Modules\Service\Models\Service as ServiceModel;

/**
 * Using a gift.
 *  - Value cards: the face value moves into the recipient's wallet as promotional balance
 *    (spendable on services and the store, not withdrawable), valid until the card's expiry.
 *  - Service / package gifts: claimed to the recipient's account, then booked online
 *    (services) or applied to a booking at the branch.
 * Every path locks the gift row, so the same code can only be used once.
 */
class GiftRedemptionService
{
    public function __construct(private GiftCodeGenerator $codes, private GiftCardService $gifts) {}

    /** Customer enters a code in the app/site. */
    public function redeemCode(string $code, User $user): GiftCard
    {
        return DB::transaction(function () use ($code, $user) {
            $gift = $this->lockByCode($code);

            return $this->claimOrCredit($gift, $user, 'online', null, null);
        });
    }

    /** A gift sent to the customer's own mobile number can be claimed without typing the code. */
    public function claimForRecipient(int $giftId, User $user): GiftCard
    {
        return DB::transaction(function () use ($giftId, $user) {
            $gift = GiftCard::whereKey($giftId)->lockForUpdate()->first();
            if (! $gift || ! self::samePhone($gift->recipient_phone, $user->mobile)) {
                throw ValidationException::withMessages(['gift' => __('gift_system.not_found')]);
            }

            return $this->claimOrCredit($gift, $user, 'online', null, null);
        });
    }

    /**
     * Reception applies a code for a customer. Value cards go to the customer's wallet;
     * service/package gifts pay for the given booking.
     */
    public function redeemAtBranch(string $code, User $customer, int $staffId, ?Booking $booking = null): GiftCard
    {
        return DB::transaction(function () use ($code, $customer, $staffId, $booking) {
            $gift = $this->lockByCode($code);

            if ($gift->isValue()) {
                return $this->claimOrCredit($gift, $customer, 'branch', $staffId, $booking?->branch_id);
            }

            $this->assertUsable($gift, $customer);
            if (! $booking || (int) $booking->user_id !== (int) $customer->id || in_array($booking->status, ['cancelled', 'canceled', 'completed'], true)) {
                throw ValidationException::withMessages(['booking_id' => __('gift_system.booking_required')]);
            }
            if (BookingTransaction::where('booking_id', $booking->id)->where('payment_status', 1)->exists()) {
                throw ValidationException::withMessages(['booking_id' => __('gift_system.booking_already_paid')]);
            }

            BookingTransaction::create([
                'booking_id' => $booking->id,
                'external_transaction_id' => 'GIFT-'.$gift->id,
                'transaction_type' => 'gift_card',
                'payment_status' => 1,
            ]);
            if ($booking->status === 'pending') {
                $booking->update(['status' => 'confirmed']);
            }

            $this->finishServiceGift($gift, $customer, [$booking->id], 'branch', $staffId, $booking->branch_id);

            return $gift;
        });
    }

    /**
     * Recipient books the gifted services online. $appointments: [{service_id, employee_id, date, time}]
     * covering exactly the gift's services. Package gifts are used at the branch.
     */
    public function bookOnline(GiftCard $gift, User $user, array $appointments): array
    {
        return DB::transaction(function () use ($gift, $user, $appointments) {
            $gift = GiftCard::whereKey($gift->id)->lockForUpdate()->firstOrFail();
            if ((int) $gift->recipient_user_id !== (int) $user->id) {
                throw ValidationException::withMessages(['gift' => __('gift_system.not_found')]);
            }
            $this->assertUsable($gift, $user);
            if ($gift->type !== GiftCard::TYPE_SERVICE) {
                throw ValidationException::withMessages(['gift' => __('gift_system.use_at_branch')]);
            }
            if (! $gift->branch_id) {
                throw ValidationException::withMessages(['gift' => __('gift_system.no_branch')]);
            }

            $expected = collect($gift->requested_services ?? [])->map(fn ($id) => (int) $id)->sort()->values()->all();
            $given = collect($appointments)->pluck('service_id')->map(fn ($id) => (int) $id)->sort()->values()->all();
            if ($expected !== $given) {
                throw ValidationException::withMessages(['appointments' => __('gift_system.services_mismatch')]);
            }

            $bookingIds = [];
            foreach ($appointments as $appointment) {
                $service = ServiceModel::findOrFail((int) $appointment['service_id']);
                $employeeId = (int) $appointment['employee_id'];
                $eligible = DB::table('service_employees')->where('service_id', $service->id)->where('employee_id', $employeeId)->exists()
                    && DB::table('branch_employee')->where('branch_id', (int) $gift->branch_id)->where('employee_id', $employeeId)->exists()
                    && User::whereKey($employeeId)->where('status', 1)->exists();
                if (! $eligible) {
                    throw ValidationException::withMessages(['appointments' => __('gift_system.employee_unavailable')]);
                }

                $start = Carbon::createFromFormat('Y-m-d H:i', $appointment['date'].' '.$appointment['time'], 'Asia/Riyadh');
                if ($gift->expires_at && $start->greaterThan($gift->expires_at)) {
                    throw ValidationException::withMessages(['appointments' => __('gift_system.after_expiry')]);
                }

                $booking = Booking::create([
                    'status' => 'confirmed',
                    'start_date_time' => $start->format('Y-m-d H:i:s'),
                    'user_id' => $user->id,
                    'branch_id' => (int) $gift->branch_id,
                    'note' => 'Gift card #'.$gift->id,
                    'created_by' => $user->id,
                    'payment_type' => 'gift_card',
                ]);
                BookingService::create([
                    'booking_id' => $booking->id,
                    'service_id' => $service->id,
                    'employee_id' => $employeeId,
                    'start_date_time' => $start->format('Y-m-d H:i:s'),
                    'service_price' => (float) ($service->default_price ?? 0),
                    'duration_min' => (int) ($service->duration_min ?? 0),
                    'sequance' => 1,
                    'created_by' => $user->id,
                ]);
                BookingTransaction::create([
                    'booking_id' => $booking->id,
                    'external_transaction_id' => 'GIFT-'.$gift->id,
                    'transaction_type' => 'gift_card',
                    'payment_status' => 1,
                ]);
                $bookingIds[] = $booking->id;
            }

            $this->finishServiceGift($gift, $user, $bookingIds, 'online', null, (int) $gift->branch_id);

            return $bookingIds;
        });
    }

    /** Lookup by code with the row locked; unknown codes all give the same answer. */
    public function lockByCode(string $code): GiftCard
    {
        $gift = $this->codes->looksValid($code)
            ? GiftCard::where('redeem_code_hash', $this->codes->hash($code))->lockForUpdate()->first()
            : null;
        if (! $gift) {
            throw ValidationException::withMessages(['code' => __('gift_system.invalid_code')]);
        }

        return $gift;
    }

    public function assertUsable(GiftCard $gift, ?User $user = null): void
    {
        $message = match (true) {
            (int) $gift->payment_status !== 1 => 'gift_system.invalid_code',
            in_array($gift->gift_status, [GiftCard::STATUS_CANCELLED, GiftCard::STATUS_REFUNDED], true) => 'gift_system.cancelled',
            (bool) $gift->redeemed_at || $gift->gift_status === GiftCard::STATUS_REDEEMED => 'gift_system.already_used',
            $gift->isExpired() => 'gift_system.expired',
            $user && $gift->recipient_user_id && (int) $gift->recipient_user_id !== (int) $user->id => 'gift_system.claimed_by_other',
            default => null,
        };
        if ($message) {
            throw ValidationException::withMessages(['code' => __($message, ['date' => $gift->expires_at?->toDateString()])]);
        }
    }

    private function claimOrCredit(GiftCard $gift, User $user, string $channel, ?int $staffId, ?int $branchId): GiftCard
    {
        $this->assertUsable($gift, $user);

        if (! $gift->isValue()) {
            if (! $gift->recipient_user_id) {
                $gift->forceFill([
                    'recipient_user_id' => $user->id,
                    'claimed_at' => $gift->claimed_at ?? now(),
                    'gift_status' => GiftCard::STATUS_CLAIMED,
                ])->save();
                $this->gifts->record($gift, 'claimed', $staffId ?? $user->id, ['channel' => $channel]);
            }

            return $gift;
        }

        // A value card bought by a customer is for someone else; otherwise expiring promotional
        // balance could be "renewed" by buying a fresh card for oneself.
        if ($gift->source === 'customer' && (int) $gift->user_id === (int) $user->id) {
            throw ValidationException::withMessages(['code' => __('gift_system.own_gift')]);
        }

        $amount = (float) ($gift->remaining_amount ?? $gift->value_amount);
        $entry = app(WalletLedger::class)->credit($user->id, $amount, 'gift', [
            'bucket' => 'bonus',
            'expires_at' => $gift->expires_at,
            'activity_type' => 'gift_card',
            'message' => __('gift_system.wallet_message', ['code' => $gift->redeem_code_last4]),
            'source_type' => 'gift_card',
            'source_id' => $gift->id,
            'idempotency_key' => 'gift:'.$gift->id,
            'created_by' => $staffId,
        ]);

        GiftCardRedemption::create([
            'gift_card_id' => $gift->id,
            'user_id' => $user->id,
            'channel' => $channel,
            'amount' => $amount,
            'wallet_history_id' => $entry->id,
            'branch_id' => $branchId,
            'staff_id' => $staffId,
        ]);
        $gift->forceFill([
            'recipient_user_id' => $user->id,
            'claimed_at' => $gift->claimed_at ?? now(),
            'redeemed_at' => now(),
            'remaining_amount' => 0,
            'gift_status' => GiftCard::STATUS_REDEEMED,
        ])->save();
        $this->gifts->record($gift, 'claimed', $staffId ?? $user->id, ['channel' => $channel]);
        $this->gifts->record($gift, 'redeemed', $staffId ?? $user->id, ['channel' => $channel, 'amount' => $amount, 'to' => 'wallet']);

        return $gift;
    }

    private function finishServiceGift(GiftCard $gift, User $user, array $bookingIds, string $channel, ?int $staffId, ?int $branchId): void
    {
        foreach ($bookingIds as $bookingId) {
            GiftCardRedemption::create([
                'gift_card_id' => $gift->id,
                'user_id' => $user->id,
                'channel' => $channel,
                'amount' => 0,
                'booking_id' => $bookingId,
                'branch_id' => $branchId,
                'staff_id' => $staffId,
            ]);
        }
        $gift->forceFill([
            'recipient_user_id' => $user->id,
            'claimed_at' => $gift->claimed_at ?? now(),
            'redeemed_at' => now(),
            'booking_ids' => $bookingIds,
            'gift_status' => GiftCard::STATUS_REDEEMED,
        ])->save();
        $this->gifts->record($gift, 'booked', $staffId ?? $user->id, ['booking_ids' => $bookingIds, 'channel' => $channel]);
        $this->gifts->record($gift, 'redeemed', $staffId ?? $user->id, ['channel' => $channel]);
    }

    /** Compare Saudi mobile numbers regardless of 05.. / 9665.. / +966 formats. */
    public static function samePhone(?string $a, ?string $b): bool
    {
        $tail = fn (?string $p) => substr(preg_replace('/\D/', '', (string) $p), -9);

        return strlen($tail($a)) === 9 && $tail($a) === $tail($b);
    }
}
