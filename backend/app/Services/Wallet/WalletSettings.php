<?php

namespace App\Services\Wallet;

use App\Models\Setting;

/**
 * Wallet policy values, editable at /app/wallet/policy. Defaults are deliberately
 * conservative placeholders until the business confirms the official policy.
 */
class WalletSettings
{
    public const DEFAULTS = [
        'wallet_refund_on_cancel' => 1,          // refund cancelled paid bookings/orders to the wallet
        'wallet_free_cancel_hours' => 24,        // cancel at least this many hours before the appointment: no fee
        'wallet_late_cancel_fee_percent' => 50,  // fee kept when cancelling later than that
        'wallet_withdrawal_enabled' => 1,
        'wallet_withdrawal_min' => 50,
        'wallet_withdrawal_requires_approval' => 1,
        'wallet_spend_bonus_first' => 1,         // spend promotional credit before paid cash
        'wallet_points_conversion_enabled' => 1,
        'wallet_points_conversion_min' => 100,   // minimum points per conversion
        'wallet_topup_min' => 10,
        'wallet_topup_max' => 5000,
        'wallet_topup_presets' => '100,200,500,1000',
    ];

    public function get(string $key): mixed
    {
        $value = Setting::get($key);

        return $value === null || $value === '' ? self::DEFAULTS[$key] : $value;
    }

    public function bool(string $key): bool
    {
        return (bool) (int) $this->get($key);
    }

    public function float(string $key): float
    {
        return (float) $this->get($key);
    }

    public function presets(): array
    {
        return collect(explode(',', (string) $this->get('wallet_topup_presets')))
            ->map(fn ($v) => (float) trim($v))
            ->filter(fn ($v) => $v > 0)
            ->values()
            ->all();
    }

    /** Value of one loyalty point in SAR, the same rate checkout uses. */
    public function pointValue(): float
    {
        return max((float) (Setting::get('point_value') ?? 0.5), 0.01);
    }

    public function all(): array
    {
        $values = [];
        foreach (array_keys(self::DEFAULTS) as $key) {
            $values[$key] = $this->get($key);
        }

        return $values;
    }
}
