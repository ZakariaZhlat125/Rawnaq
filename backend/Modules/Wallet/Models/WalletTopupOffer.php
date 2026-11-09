<?php

namespace Modules\Wallet\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** "Top up min_amount, get bonus_amount" — the bonus lands in the bonus bucket. */
class WalletTopupOffer extends Model
{
    protected $table = 'wallet_topup_offers';

    protected $fillable = [
        'title_ar', 'title_en', 'min_amount', 'bonus_amount', 'bonus_expiry_days',
        'max_uses_per_user', 'starts_at', 'ends_at', 'is_active',
    ];

    protected $casts = [
        'min_amount' => 'double',
        'bonus_amount' => 'double',
        'bonus_expiry_days' => 'integer',
        'max_uses_per_user' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function scopeRunning(Builder $query): Builder
    {
        $now = now();

        return $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now));
    }
}
