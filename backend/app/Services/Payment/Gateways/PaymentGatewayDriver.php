<?php

namespace App\Services\Payment\Gateways;

use App\Models\PaymentAttempt;
use Illuminate\Http\Request;

/**
 * A payment provider integration. Drivers are built by PaymentGatewayManager with the
 * gateway's credentials from the database and must not read .env or config() for them.
 */
interface PaymentGatewayDriver
{
    /** Human name of the provider, shown when adding a gateway in the dashboard. */
    public static function label(): string;

    /**
     * Fields the driver reads from GatewayConfig, keyed by field key:
     * ['label' => string, 'required' => bool, 'secret' => bool, 'type' => 'textarea'|'select',
     *  'options' => [value => label], 'default' => string, 'help' => string]
     */
    public static function fields(): array;

    /** Returns ['external_id' => ?string, 'payment_url' => string, 'raw' => array]. */
    public function create(PaymentAttempt $attempt, array $customer, array $urls, array $input = []): array;

    /** Returns ['status' => 'paid'|'failed'|'cancelled'|'pending', 'external_id' => ?string, 'raw' => array]. */
    public function verify(PaymentAttempt $attempt, Request $request): array;
}
