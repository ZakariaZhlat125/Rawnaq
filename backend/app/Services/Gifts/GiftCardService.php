<?php

namespace App\Services\Gifts;

use App\Models\GiftCard;
use App\Models\GiftCardBatch;
use App\Models\GiftCardEvent;
use App\Services\TaqnyatSmsService;
use App\Services\Wallet\WalletRefundPolicy;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Modules\Booking\Models\Booking;
use Modules\Booking\Models\BookingTransaction;

/**
 * Gift lifecycle: activation after payment, admin/bulk issue, (re)sending, opening,
 * extension, cancellation (+ refund), expiry and reminders. Every step writes a
 * gift_card_events row, which is the timeline shown to the buyer and to staff.
 */
class GiftCardService
{
    public function __construct(private GiftCodeGenerator $codes, private GiftSettings $settings) {}

    public function record(GiftCard $gift, string $event, ?int $actorId = null, array $meta = []): GiftCardEvent
    {
        return GiftCardEvent::create([
            'gift_card_id' => $gift->id,
            'event' => $event,
            'actor_id' => $actorId,
            'meta' => array_filter($meta, fn ($v) => $v !== null) ?: null,
            'created_at' => now(),
        ]);
    }

    /** Called once the purchase is paid: code, validity, status. Safe to call twice. */
    public function activate(GiftCard $gift): GiftCard
    {
        $wasPaid = (int) $gift->payment_status === 1;
        $gift->ensureClaimToken();

        $attributes = [
            'payment_status' => 1,
            'gift_status' => $wasPaid ? $gift->gift_status : GiftCard::STATUS_PAID,
            'expires_at' => $gift->expires_at ?? now()->addDays($this->settings->int('gift_validity_days')),
        ];
        if (blank($gift->redeem_code_hash)) {
            $attributes += $this->codes->generateColumns();
        }
        if ($gift->isValue() && $gift->remaining_amount === null) {
            $attributes['remaining_amount'] = $gift->value_amount;
        }
        $gift->forceFill($attributes)->save();

        if (! $wasPaid) {
            $this->record($gift, 'paid', $gift->user_id, ['amount' => (float) $gift->subtotal]);
        }

        return $gift;
    }

    /**
     * Issue a gift from the dashboard (no payment). $data: type, value_amount | requested_services |
     * requested_packages, recipient_name?, recipient_phone?, branch_id?, design?, expires_at?, source, batch_id?
     */
    public function issue(array $data, int $actorId): GiftCard
    {
        $type = $data['type'];
        $expires = isset($data['expires_at'])
            ? Carbon::parse($data['expires_at'])->endOfDay()
            : now()->addDays($this->settings->int('gift_validity_days'));

        $gift = GiftCard::create([
            'user_id' => null,
            'created_by' => $actorId,
            'type' => $type,
            'source' => $data['source'] ?? 'admin',
            'batch_id' => $data['batch_id'] ?? null,
            'branch_id' => $data['branch_id'] ?? null,
            'recipient_name' => (string) ($data['recipient_name'] ?? ''),
            'recipient_phone' => $data['recipient_phone'] ?? null,
            'sender_name' => $data['sender_name'] ?? (setting('app_name') ?: 'رونق'),
            'design' => $data['design'] ?? 'lux-dark',
            'message' => $data['message'] ?? null,
            'requested_services' => $type === GiftCard::TYPE_SERVICE ? array_values($data['requested_services'] ?? []) : [],
            'requested_packages' => $type === GiftCard::TYPE_PACKAGE ? array_values($data['requested_packages'] ?? []) : [],
            'value_amount' => $type === GiftCard::TYPE_VALUE ? $data['value_amount'] : null,
            'remaining_amount' => $type === GiftCard::TYPE_VALUE ? $data['value_amount'] : null,
            'subtotal' => $data['subtotal'] ?? ($type === GiftCard::TYPE_VALUE ? $data['value_amount'] : 0),
            'payment_status' => 1, // issued gifts are live immediately
            'gift_status' => GiftCard::STATUS_PAID,
            'expires_at' => $expires,
            'send_channel' => filled($data['recipient_phone'] ?? null) ? 'sms' : 'link',
        ] + $this->codes->generateColumns());
        $gift->ensureClaimToken();

        $this->record($gift, 'issued', $actorId, ['source' => $gift->source, 'batch_id' => $gift->batch_id]);

        return $gift;
    }

    /** Bulk issue; returns the batch. Codes are exported later as CSV. */
    public function createBatch(array $data, int $actorId): GiftCardBatch
    {
        return DB::transaction(function () use ($data, $actorId) {
            $batch = GiftCardBatch::create([
                'name' => $data['name'],
                'source' => $data['source'] ?? 'admin',
                'type' => $data['type'],
                'quantity' => (int) $data['quantity'],
                'value_amount' => $data['value_amount'] ?? null,
                'requested_services' => $data['requested_services'] ?? null,
                'requested_packages' => $data['requested_packages'] ?? null,
                'branch_id' => $data['branch_id'] ?? null,
                'design' => $data['design'] ?? 'lux-dark',
                'expires_at' => isset($data['expires_at']) ? Carbon::parse($data['expires_at'])->endOfDay() : null,
                'note' => $data['note'] ?? null,
                'created_by' => $actorId,
            ]);

            for ($i = 0; $i < $batch->quantity; $i++) {
                $this->issue([
                    'type' => $batch->type,
                    'source' => $batch->source,
                    'batch_id' => $batch->id,
                    'value_amount' => $batch->value_amount,
                    'subtotal' => $data['subtotal'] ?? null,
                    'requested_services' => $batch->requested_services ?? [],
                    'requested_packages' => $batch->requested_packages ?? [],
                    'branch_id' => $batch->branch_id,
                    'design' => $batch->design,
                    'expires_at' => $batch->expires_at,
                ], $actorId);
            }

            return $batch;
        });
    }

    /** Send (or resend) the claim link + code to the recipient by SMS. */
    public function resend(GiftCard $gift, ?int $actorId): bool
    {
        if (! $gift->isUsable()) {
            throw ValidationException::withMessages(['gift' => __('gift_system.not_usable')]);
        }
        if (blank($gift->recipient_phone)) {
            throw ValidationException::withMessages(['gift' => __('gift_system.no_recipient_phone')]);
        }

        $sms = app(TaqnyatSmsService::class);
        $sent = (bool) $sms->sendGiftCardRecipientMessage($gift);
        $gift->markSmsSent($sent, $sms->getLastError());
        $this->record($gift, 'resent', $actorId, ['sent' => $sent]);

        return $sent;
    }

    public function markOpened(GiftCard $gift): void
    {
        if ($gift->opened_at || (int) $gift->payment_status !== 1) {
            return;
        }
        $gift->forceFill(['opened_at' => now()])->save();
        $this->record($gift, 'opened');
    }

    public function extend(GiftCard $gift, Carbon $until, int $actorId, ?string $reason = null): GiftCard
    {
        if ($gift->redeemed_at || in_array($gift->gift_status, [GiftCard::STATUS_CANCELLED, GiftCard::STATUS_REFUNDED, GiftCard::STATUS_REDEEMED], true)) {
            throw ValidationException::withMessages(['expires_at' => __('gift_system.cannot_extend')]);
        }
        if ($until->isPast()) {
            throw ValidationException::withMessages(['expires_at' => __('gift_system.date_in_past')]);
        }

        $from = $gift->expires_at;
        $status = $gift->gift_status === GiftCard::STATUS_EXPIRED
            ? ($gift->claimed_at ? GiftCard::STATUS_CLAIMED : ($gift->sent_at ? GiftCard::STATUS_SENT : GiftCard::STATUS_PAID))
            : $gift->gift_status;
        $gift->forceFill(['expires_at' => $until->copy()->endOfDay(), 'gift_status' => $status, 'expiry_reminded_at' => null])->save();
        $this->record($gift, 'extended', $actorId, ['from' => $from?->toDateString(), 'to' => $gift->expires_at->toDateString(), 'reason' => $reason]);

        return $gift;
    }

    /**
     * Cancel a gift that nobody has claimed yet. A claimed or used gift cannot be cancelled.
     * Customer purchases are refunded to the buyer's wallet when $refund (and the policy) allow it.
     */
    public function cancel(GiftCard $gift, ?int $actorId, string $reason, bool $refund = true): GiftCard
    {
        return DB::transaction(function () use ($gift, $actorId, $reason, $refund) {
            $locked = GiftCard::whereKey($gift->id)->lockForUpdate()->firstOrFail();
            $cancellable = [GiftCard::STATUS_PENDING_PAYMENT, GiftCard::STATUS_PAID, GiftCard::STATUS_SENT, GiftCard::STATUS_EXPIRED];
            if (! in_array($locked->gift_status, $cancellable, true) || $locked->claimed_at || $locked->redeemed_at) {
                throw ValidationException::withMessages(['gift' => __('gift_system.cannot_cancel')]);
            }

            $locked->forceFill(['gift_status' => GiftCard::STATUS_CANCELLED, 'cancelled_at' => now(), 'cancel_reason' => $reason])->save();
            $this->record($locked, 'cancelled', $actorId, ['reason' => $reason]);

            if ($refund && $this->settings->bool('gift_refund_on_cancel')) {
                $this->refundToBuyer($locked, $actorId);
            }

            return $locked;
        });
    }

    /** Daily: mark gifts past their validity as expired (and refund them if the policy says so). */
    public function expireDue(): int
    {
        $count = 0;
        GiftCard::whereIn('gift_status', GiftCard::USABLE_STATUSES)
            ->whereNull('redeemed_at')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->orderBy('id')
            ->chunkById(200, function ($gifts) use (&$count) {
                foreach ($gifts as $gift) {
                    DB::transaction(function () use ($gift) {
                        $gift->forceFill(['gift_status' => GiftCard::STATUS_EXPIRED])->save();
                        $this->record($gift, 'expired');
                        if ($this->settings->bool('gift_refund_on_expiry') && ! $gift->claimed_at) {
                            $this->refundToBuyer($gift, null);
                        }
                    });
                    $count++;
                }
            });

        return $count;
    }

    /** Daily: SMS the recipient N days before the gift expires (once). */
    public function remindExpiring(): int
    {
        $days = $this->settings->int('gift_expiry_reminder_days');
        if ($days <= 0) {
            return 0;
        }
        $count = 0;
        $sms = app(TaqnyatSmsService::class);

        $gifts = GiftCard::whereIn('gift_status', GiftCard::USABLE_STATUSES)
            ->whereNull('redeemed_at')
            ->whereNull('expiry_reminded_at')
            ->whereNotNull('recipient_phone')
            ->whereBetween('expires_at', [now(), now()->addDays($days)])
            ->get();

        foreach ($gifts as $gift) {
            $message = strtr((string) $this->settings->get('gift_expiry_reminder_sms'), [
                '[[recipient_name]]' => (string) $gift->recipient_name,
                '[[expires_at]]' => $gift->expires_at->toDateString(),
                '[[gift_code]]' => (string) $gift->redeemCode(),
                '[[gift_url]]' => (string) $gift->share_url,
            ]);
            try {
                $sent = (bool) $sms->sendSms($gift->recipient_phone, $message);
            } catch (\Throwable $e) {
                Log::warning('Gift expiry reminder failed', ['gift_card_id' => $gift->id, 'error' => $e->getMessage()]);
                $sent = false;
            }
            $gift->forceFill(['expiry_reminded_at' => now()])->save();
            $this->record($gift, 'reminder_sent', null, ['sent' => $sent]);
            $count++;
        }

        return $count;
    }

    /**
     * A booking made with a service gift was cancelled: once all of the gift's bookings are
     * cancelled, the gift becomes usable again instead of being lost.
     */
    public function onBookingCancelled(Booking $booking): void
    {
        $reference = BookingTransaction::where('booking_id', $booking->id)
            ->where('external_transaction_id', 'like', 'GIFT-%')
            ->value('external_transaction_id');
        if (! $reference) {
            return;
        }
        $gift = GiftCard::find((int) substr($reference, 5));
        if (! $gift || $gift->gift_status !== GiftCard::STATUS_REDEEMED || $gift->isValue()) {
            return;
        }

        $open = Booking::whereIn('id', $gift->booking_ids ?? [])
            ->whereNotIn('status', ['cancelled', 'canceled'])
            ->exists();
        if ($open) {
            return;
        }

        $gift->forceFill([
            'gift_status' => $gift->isExpired() ? GiftCard::STATUS_EXPIRED : GiftCard::STATUS_CLAIMED,
            'redeemed_at' => null,
            'booking_ids' => null,
        ])->save();
        $this->record($gift, 'booking_cancelled', null, ['booking_id' => $booking->id]);
    }

    private function refundToBuyer(GiftCard $gift, ?int $actorId): void
    {
        if ($gift->source !== 'customer' || ! $gift->user_id || (int) $gift->payment_status !== 1) {
            return;
        }
        $result = app(WalletRefundPolicy::class)->refundGift($gift);
        if ($result) {
            $gift->forceFill(['gift_status' => GiftCard::STATUS_REFUNDED, 'refunded_at' => now()])->save();
            $this->record($gift, 'refunded', $actorId, ['cash' => $result['cash'], 'bonus' => $result['bonus'], 'points' => $result['points']]);
        }
    }
}
