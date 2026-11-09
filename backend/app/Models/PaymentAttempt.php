<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentAttempt extends Model
{
    public const STATUS_INITIATED = 'initiated';
    public const STATUS_REDIRECTED = 'redirected';
    public const STATUS_PAID = 'paid';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    public const PURPOSE_CHECKOUT = 'checkout';
    public const PURPOSE_WALLET_TOPUP = 'wallet_topup';

    protected $fillable = [
        'token',
        'user_id',
        'gateway',
        'purpose',
        'page_type',
        'currency',
        'gross_amount',
        'amount',
        'tax_amount',
        'discount_amount',
        'coupon_code',
        'cart_ids',
        'gift_ids',
        'submethods',
        'status',
        'external_id',
        'payment_url',
        'invoice_id',
        'meta',
    ];

    protected $casts = [
        'cart_ids' => 'array',
        'gift_ids' => 'array',
        'submethods' => 'array',
        'meta' => 'array',
    ];
}
