<?php

namespace App\Services\Payment\Gateways;

use App\Models\PaymentAttempt;
use App\Services\TapPaymentService;
use Illuminate\Http\Request;

class TapGateway implements PaymentGatewayDriver
{
    public function __construct(private GatewayConfig $config)
    {
    }

    public static function label(): string
    {
        return 'Tap Payments (cards, mada, Apple Pay)';
    }

    public static function fields(): array
    {
        return [
            'secret_key' => ['label' => 'Secret key', 'required' => true, 'secret' => true, 'help' => 'sk_live_... or sk_test_... from the Tap dashboard.'],
            'statement_descriptor' => ['label' => 'Statement descriptor', 'default' => 'JOSPA Store', 'help' => 'Text shown on the card statement of the customer.'],
        ];
    }

    public function create(PaymentAttempt $attempt, array $customer, array $urls, array $input = []): array
    {
        $redirectUrl = $urls['success'] ?? '';
        $paymentSource = $input['payment_source'] ?? 'src_card';

        $charge = $this->client()->createCharge(
            amount: $attempt->amount,
            customerData: [
                'name' => $customer['name'] ?? '',
                'country_code' => $customer['country_code'] ?? '966',
                'phone' => $customer['phone'] ?? '',
                'method' => $paymentSource ?: 'src_card',
            ],
            redirectUrl: $redirectUrl
        );

        if (!isset($charge['transaction']['url'])) {
            throw new \RuntimeException('Tap payment URL not found');
        }

        return [
            'external_id' => $charge['id'] ?? null,
            'payment_url' => $charge['transaction']['url'],
            'raw' => $charge,
        ];
    }

    public function verify(PaymentAttempt $attempt, Request $request): array
    {
        $tapId = $request->get('tap_id') ?: $attempt->external_id;
        if (!$tapId) {
            return ['status' => 'failed', 'raw' => ['message' => 'tap_id missing']];
        }

        // The charge must be the one created for this attempt, otherwise any earlier
        // captured charge id could be replayed against a new attempt.
        if ($attempt->external_id && $tapId !== $attempt->external_id) {
            return ['status' => 'failed', 'raw' => ['message' => 'tap_id does not belong to this attempt']];
        }

        $charge = $this->client()->getCharge($tapId);

        $status = $this->normalizeStatus(strtoupper((string) ($charge['status'] ?? '')));

        if ($status === 'paid' && ($mismatch = $this->mismatch($attempt, $charge))) {
            return [
                'status' => 'failed',
                'external_id' => $tapId,
                'raw' => ['message' => $mismatch, 'charge' => $charge],
            ];
        }

        return [
            'status' => $status,
            'external_id' => $tapId,
            'amount' => isset($charge['amount']) ? (float) $charge['amount'] : null,
            'currency' => $charge['currency'] ?? null,
            'raw' => $charge,
        ];
    }

    private function client(): TapPaymentService
    {
        return new TapPaymentService($this->config->get('secret_key'), $this->config->get('statement_descriptor', 'JOSPA Store'));
    }

    /** Compare what Tap captured with what we recorded; the database is the source of truth. */
    private function mismatch(PaymentAttempt $attempt, array $charge): ?string
    {
        if (!isset($charge['amount']) || abs((float) $charge['amount'] - (float) $attempt->amount) > 0.009) {
            return 'amount mismatch';
        }
        if (strtoupper((string) ($charge['currency'] ?? '')) !== strtoupper((string) ($attempt->currency ?: 'SAR'))) {
            return 'currency mismatch';
        }

        return null;
    }

    private function normalizeStatus(string $status): string
    {
        return match ($status) {
            'CAPTURED' => 'paid',
            'FAILED' => 'failed',
            'CANCELLED' => 'cancelled',
            default => 'pending',
        };
    }
}
