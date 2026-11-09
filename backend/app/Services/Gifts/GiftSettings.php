<?php

namespace App\Services\Gifts;

use App\Models\Setting;

/** Gift policy, editable at /app/gifts/settings. */
class GiftSettings
{
    public const DEFAULTS = [
        'gift_validity_days' => 180,          // the gifts page advertises "valid for 6 months"
        'gift_refund_on_cancel' => 1,         // cancelling an unclaimed customer gift refunds the buyer's wallet
        'gift_refund_on_expiry' => 0,         // expired, unused gifts are not refunded
        'gift_value_min' => 50,
        'gift_value_max' => 5000,
        'gift_value_presets' => '100,200,300,500',
        'gift_expiry_reminder_days' => 7,
        'gift_expiry_reminder_sms' => 'مرحباً [[recipient_name]]، هديتك من رونق تنتهي بتاريخ [[expires_at]]. استخدمها بالكود [[gift_code]] أو عبر الرابط: [[gift_url]]',
    ];

    public function get(string $key): mixed
    {
        $value = Setting::get($key);

        return $value === null || $value === '' ? self::DEFAULTS[$key] : $value;
    }

    public function int(string $key): int
    {
        return (int) $this->get($key);
    }

    public function float(string $key): float
    {
        return (float) $this->get($key);
    }

    public function bool(string $key): bool
    {
        return (bool) (int) $this->get($key);
    }

    public function presets(): array
    {
        return collect(explode(',', (string) $this->get('gift_value_presets')))
            ->map(fn ($v) => (float) trim($v))
            ->filter(fn ($v) => $v > 0)
            ->values()
            ->all();
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
