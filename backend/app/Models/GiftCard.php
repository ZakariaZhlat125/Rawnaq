<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Modules\Package\Models\Package;
use Modules\Service\Models\Service;

class GiftCard extends Model
{
    public const STATUS_PENDING_PAYMENT = 'pending_payment';
    public const STATUS_PAID = 'paid';
    public const STATUS_SENT = 'sent';
    public const STATUS_CLAIMED = 'claimed';
    public const STATUS_REDEEMED = 'redeemed';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REFUNDED = 'refunded';

    public const TYPE_SERVICE = 'service';
    public const TYPE_PACKAGE = 'package';
    public const TYPE_VALUE = 'value';

    /** Statuses in which the gift can still be claimed or used. */
    public const USABLE_STATUSES = [self::STATUS_PAID, self::STATUS_SENT, self::STATUS_CLAIMED];

    protected $fillable = [
        'recipient_name',
        'recipient_phone',
        'sender_name',
        'design',
        'message',
        'requested_services',
        'requested_packages',
        'user_id',
        'branch_id',
        'booking_ids',
        'options_amount',
        'subtotal',
        'payment_status',
        'claim_token',
        'gift_status',
        'send_channel',
        'send_status',
        'send_error',
        'sent_at',
        'claimed_at',
        'redeemed_at',
        'type',
        'source',
        'batch_id',
        'created_by',
        'value_amount',
        'remaining_amount',
        'redeem_code_hash',
        'redeem_code_encrypted',
        'redeem_code_last4',
        'recipient_user_id',
        'expires_at',
        'opened_at',
        'cancelled_at',
        'cancel_reason',
        'refunded_at',
        'expiry_reminded_at',
    ];

    protected $hidden = ['redeem_code_hash', 'redeem_code_encrypted', 'claim_token'];

    protected $casts = [
        'requested_services' => 'array',
        'requested_packages' => 'array',
        'booking_ids' => 'array',
        'sent_at' => 'datetime',
        'claimed_at' => 'datetime',
        'redeemed_at' => 'datetime',
        'expires_at' => 'datetime',
        'opened_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'refunded_at' => 'datetime',
        'expiry_reminded_at' => 'datetime',
        'value_amount' => 'double',
        'remaining_amount' => 'double',
    ];

    public function recipient()
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }

    public function events()
    {
        return $this->hasMany(GiftCardEvent::class)->orderBy('id');
    }

    public function redemptions()
    {
        return $this->hasMany(GiftCardRedemption::class);
    }

    public function batch()
    {
        return $this->belongsTo(GiftCardBatch::class, 'batch_id');
    }

    public function isValue(): bool
    {
        return $this->type === self::TYPE_VALUE;
    }

    public function isExpired(): bool
    {
        return $this->gift_status === self::STATUS_EXPIRED
            || ($this->expires_at && $this->expires_at->isPast());
    }

    /** Paid, not used up, not cancelled and still within its validity. */
    public function isUsable(): bool
    {
        return (int) $this->payment_status === 1
            && in_array($this->gift_status, self::USABLE_STATUSES, true)
            && ! $this->redeemed_at
            && ! $this->isExpired();
    }

    public function redeemCode(): ?string
    {
        if (blank($this->redeem_code_encrypted)) {
            return null;
        }

        try {
            return \Illuminate\Support\Facades\Crypt::decryptString($this->redeem_code_encrypted);
        } catch (\Throwable) {
            return null;
        }
    }

    public function maskedCode(): ?string
    {
        return $this->redeem_code_last4 ? 'SAMI-••••-'.$this->redeem_code_last4 : null;
    }

    /** What the gift is worth to the recipient (value cards: the face value). */
    public function faceValue(): float
    {
        return $this->isValue() ? (float) $this->value_amount : (float) $this->subtotal;
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function getServicesListAttribute()
    {
        $serviceIds = $this->requested_services ?? '[]';

        if (! is_array($serviceIds)) {
            return collect();
        }

        return Service::whereIn('id', $serviceIds)->get();
    }

    public function getPackagesListAttribute()
    {
        $packageIds = $this->requested_packages ?? '[]';

        if (! is_array($packageIds)) {
            return collect();
        }

        return Package::whereIn('id', $packageIds)->get();
    }

    public function getClaimUrlAttribute(): ?string
    {
        if (blank($this->claim_token)) {
            return null;
        }

        return route('gift.claim', ['token' => $this->claim_token]);
    }

    public function getShareUrlAttribute(): ?string
    {
        if (blank($this->claim_token)) {
            return null;
        }

        return rtrim(config('app.frontend_url'), '/') . '/gift-recipient?token=' . urlencode($this->claim_token);
    }

    public function ensureClaimToken(): string
    {
        if (filled($this->claim_token)) {
            return $this->claim_token;
        }

        do {
            $token = Str::random(64);
        } while (self::where('claim_token', $token)->exists());

        $this->forceFill(['claim_token' => $token])->save();

        return $token;
    }

    public function markSmsSent(bool $sent, ?string $error = null): void
    {
        $this->forceFill([
            'send_channel' => 'sms',
            'send_status' => $sent ? 'sent' : 'failed',
            'send_error' => $sent ? null : $error,
            'sent_at' => $sent ? now() : $this->sent_at,
            // Never move a claimed/used/cancelled gift back to "sent".
            'gift_status' => $sent && $this->gift_status === self::STATUS_PAID ? self::STATUS_SENT : ($this->gift_status ?: self::STATUS_PAID),
        ])->save();

        app(\App\Services\Gifts\GiftCardService::class)->record($this, $sent ? 'sent' : 'send_failed', null, [
            'channel' => 'sms',
            'error' => $sent ? null : $error,
        ]);
    }
}
