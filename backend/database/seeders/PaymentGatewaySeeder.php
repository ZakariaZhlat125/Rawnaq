<?php

namespace Database\Seeders;

use App\Models\PaymentGateway;
use App\Services\Payment\PaymentGatewayManager;
use Illuminate\Database\Seeder;

/**
 * The payment gateways checkout supported before credentials moved to the database.
 * Each gateway gets the fields its driver declares (see the driver's fields()), filled
 * once from the old .env keys so an upgraded server keeps taking payments.
 *
 * Safe to run again: existing gateways, fields and values are never changed.
 */
class PaymentGatewaySeeder extends Seeder
{
    public function run(): void
    {
        $manager = app(PaymentGatewayManager::class);

        foreach ($this->gateways() as $definition) {
            $values = $definition['values'];
            unset($definition['values']);

            $gateway = PaymentGateway::firstOrNew(['code' => $definition['code']]);
            $isNew = ! $gateway->exists;

            if ($isNew) {
                $gateway->fill($definition + ['is_active' => false])->save();
            }

            $manager->syncFields($gateway, $values);

            // A gateway turns on by itself only if .env already had everything it needs;
            // the rest stay off until an admin enters their credentials.
            if ($isNew && ! $gateway->load('fields')->missingFields()) {
                $gateway->update(['is_active' => true]);
            }
        }
    }

    private function gateways(): array
    {
        $arb = [
            'tranportal_id' => config('arb.tranportal_id') ?? config('services.arb.tranportal_id'),
            'tranportal_password' => config('arb.tranportal_password') ?? config('services.arb.tranportal_password'),
            'resource_key' => config('arb.resource_key') ?? config('services.arb.resource_key'),
            'mode' => $this->arbMode(config('arb.mode') ?? config('services.arb.mode')),
            'currency_code' => config('arb.currency_code') ?? config('services.arb.currency_code'),
        ];

        return [
            [
                'code' => 'card',
                'driver' => 'tap',
                'name_ar' => 'بطاقات الائتمان ومدى (تاب)',
                'name_en' => 'Cards & mada (Tap)',
                'sort_order' => 10,
                'values' => [
                    'secret_key' => config('services.tap.secret_key'),
                ],
            ],
            [
                'code' => 'tabby',
                'driver' => 'tabby',
                'name_ar' => 'تابي',
                'name_en' => 'Tabby',
                'sort_order' => 20,
                'values' => [
                    'secret_key' => config('tabby.secret_key'),
                    'public_key' => config('tabby.public_key'),
                    'merchant_code' => config('tabby.merchant_code'),
                ],
            ],
            [
                'code' => 'tamara',
                'driver' => 'tamara',
                'name_ar' => 'تمارا',
                'name_en' => 'Tamara',
                'sort_order' => 30,
                'values' => [
                    'secret_key' => config('tamara.secret_key'),
                    'base_url' => config('tamara.base_url'),
                ],
            ],
            [
                'code' => 'telr',
                'driver' => 'telr',
                'name_ar' => 'تلر',
                'name_en' => 'Telr',
                'sort_order' => 40,
                'values' => [
                    'store_id' => config('services.telr.store_id'),
                    'auth_key' => config('services.telr.auth_key'),
                    'base_url' => config('services.telr.base_url'),
                    'test_mode' => $this->flag(config('services.telr.test_mode')),
                    'framed' => $this->flag(config('services.telr.framed')),
                    'panels' => config('services.telr.panels'),
                ],
            ],
            [
                'code' => 'arb',
                'driver' => 'arb',
                'name_ar' => 'مصرف الراجحي',
                'name_en' => 'Al Rajhi Bank',
                'sort_order' => 50,
                'values' => $arb,
            ],
            [
                // UrPay is paid through the same Al Rajhi Bank tranportal account.
                'code' => 'urpay',
                'driver' => 'arb',
                'name_ar' => 'يورباي',
                'name_en' => 'UrPay',
                'sort_order' => 60,
                'values' => $arb,
            ],
        ];
    }

    private function flag(mixed $value): ?string
    {
        return $value === null || $value === '' ? null : (filter_var($value, FILTER_VALIDATE_BOOLEAN) ? '1' : '0');
    }

    private function arbMode(?string $mode): ?string
    {
        if ($mode === null || $mode === '') {
            return null;
        }

        return in_array(strtolower($mode), ['live', 'production'], true) ? 'live' : 'test';
    }
}
