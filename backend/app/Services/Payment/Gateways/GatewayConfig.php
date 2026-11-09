<?php

namespace App\Services\Payment\Gateways;

use App\Models\PaymentGateway;

/** Credentials of one gateway row, as handed to its driver. */
final class GatewayConfig
{
    private array $values;

    public function __construct(public readonly PaymentGateway $gateway)
    {
        $this->values = $gateway->credentials();
    }

    public function get(string $key, ?string $default = null): ?string
    {
        $value = $this->values[$key] ?? null;

        return filled($value) ? trim((string) $value) : $default;
    }

    public function bool(string $key, bool $default = false): bool
    {
        $value = $this->get($key);

        return $value === null ? $default : filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /** Throws when a required field is empty, naming the fields so the admin can fix them. */
    public function ensureComplete(): void
    {
        if ($missing = $this->gateway->missingFields()) {
            throw new \RuntimeException(sprintf(
                'Payment gateway "%s" is not configured: missing %s. Update it at /app/payment-gateways.',
                $this->gateway->code,
                implode(', ', $missing)
            ));
        }
    }
}
