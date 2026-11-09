<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class PaymentGatewayField extends Model
{
    public const TYPE_TEXTAREA = 'textarea';
    public const TYPE_SELECT = 'select';

    protected $fillable = [
        'payment_gateway_id',
        'key',
        'label',
        'type',
        'options',
        'value',
        'is_required',
        'is_secret',
        'help_text',
        'sort_order',
    ];

    protected $casts = [
        'options' => 'array',
        'is_required' => 'boolean',
        'is_secret' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected $hidden = ['value'];

    public function gateway(): BelongsTo
    {
        return $this->belongsTo(PaymentGateway::class, 'payment_gateway_id');
    }

    /**
     * Stored encrypted. A value that no longer decrypts (APP_KEY rotated) reads as
     * empty so the gateway reports it as missing instead of sending garbage.
     */
    protected function value(): Attribute
    {
        return Attribute::make(
            get: function (?string $value) {
                if ($value === null || $value === '') {
                    return null;
                }
                try {
                    return Crypt::decryptString($value);
                } catch (DecryptException) {
                    return null;
                }
            },
            set: fn (?string $value) => $value === null || $value === '' ? null : Crypt::encryptString($value),
        );
    }

    /** What the dashboard may show for a secret: only its last four characters. */
    public function maskedValue(): ?string
    {
        $value = (string) $this->value;

        if ($value === '') {
            return null;
        }

        return mb_strlen($value) > 8 ? '••••' . mb_substr($value, -4) : '••••';
    }
}
