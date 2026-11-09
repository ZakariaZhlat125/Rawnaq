<?php

namespace App\Services\Payment;

use App\Models\PaymentGateway;
use App\Services\Payment\Gateways\GatewayConfig;
use App\Services\Payment\Gateways\PaymentGatewayDriver;

/**
 * Resolves gateways from the payment_gateways table and builds their drivers with the
 * credentials stored there.
 */
class PaymentGatewayManager
{
    /** Driver key => class, from config/payment_gateways.php. */
    public function drivers(): array
    {
        return config('payment_gateways.drivers', []);
    }

    public function find(string $code): ?PaymentGateway
    {
        return PaymentGateway::with('fields')->where('code', $code)->first();
    }

    public function isAvailable(string $code): bool
    {
        return in_array($code, $this->availableCodes(), true);
    }

    /**
     * Codes customers can currently pay with: active, with every required value set.
     * A value that stopped decrypting (APP_KEY changed) counts as missing.
     */
    public function availableCodes(): array
    {
        return PaymentGateway::active()->ordered()->with('fields')->get()
            ->filter(fn (PaymentGateway $gateway) => ! $gateway->missingFields())
            ->pluck('code')
            ->values()
            ->all();
    }

    /** Driver for starting a payment: the gateway must be active and fully configured. */
    public function forCheckout(string $code): PaymentGatewayDriver
    {
        $gateway = $this->find($code);

        if (! $gateway || ! $gateway->is_active) {
            throw new \RuntimeException("Payment gateway \"{$code}\" is not available.");
        }

        $config = new GatewayConfig($gateway);
        $config->ensureComplete();

        return $this->build($gateway->driver, $config);
    }

    /**
     * Driver for verifying a payment that was already started. A gateway switched off
     * after the customer was redirected must still be able to confirm that payment.
     */
    public function forCallback(string $code): ?PaymentGatewayDriver
    {
        $gateway = $this->find($code);

        return $gateway ? $this->build($gateway->driver, new GatewayConfig($gateway)) : null;
    }

    public function config(string $code): GatewayConfig
    {
        $gateway = $this->find($code);

        if (! $gateway) {
            throw new \RuntimeException("Payment gateway \"{$code}\" does not exist.");
        }

        return new GatewayConfig($gateway);
    }

    public function driverOf(string $code): ?string
    {
        return PaymentGateway::where('code', $code)->value('driver');
    }

    /** Field definitions a driver declares; empty for an unknown driver. */
    public function fieldTemplate(string $driver): array
    {
        $class = $this->drivers()[$driver] ?? null;

        return $class ? $class::fields() : [];
    }

    /**
     * Adds the driver's fields the gateway does not have yet. Existing fields, their
     * values and anything an admin added by hand are left alone.
     *
     * @param  array  $values  initial values for new fields, keyed by field key
     */
    public function syncFields(PaymentGateway $gateway, array $values = []): void
    {
        $existing = $gateway->fields()->pluck('key')->all();
        $order = 0;

        foreach ($this->fieldTemplate($gateway->driver) as $key => $field) {
            $order += 10;
            if (in_array($key, $existing, true)) {
                continue;
            }

            $value = $values[$key] ?? null;
            $gateway->fields()->create([
                'key' => $key,
                'label' => $field['label'] ?? $key,
                'type' => $field['type'] ?? 'textarea',
                'options' => $field['options'] ?? null,
                'value' => filled($value) ? (string) $value : ($field['default'] ?? null),
                'is_required' => (bool) ($field['required'] ?? false),
                'is_secret' => (bool) ($field['secret'] ?? false),
                'help_text' => $field['help'] ?? null,
                'sort_order' => $order,
            ]);
        }
    }

    private function build(string $driver, GatewayConfig $config): PaymentGatewayDriver
    {
        $class = $this->drivers()[$driver] ?? null;

        if (! $class) {
            throw new \RuntimeException("Unknown payment gateway driver \"{$driver}\".");
        }

        return app()->makeWith($class, ['config' => $config]);
    }
}
