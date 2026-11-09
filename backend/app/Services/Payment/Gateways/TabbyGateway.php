<?php

namespace App\Services\Payment\Gateways;

use App\Models\PaymentAttempt;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TabbyGateway implements PaymentGatewayDriver
{
    public function __construct(private GatewayConfig $config)
    {
    }

    public static function label(): string
    {
        return 'Tabby';
    }

    public static function fields(): array
    {
        return [
            'secret_key' => ['label' => 'Secret key', 'required' => true, 'secret' => true],
            'public_key' => ['label' => 'Public key', 'help' => 'Not used by the server; kept for client integrations.'],
            'merchant_code' => ['label' => 'Merchant code', 'required' => true],
            'base_url' => ['label' => 'API base URL', 'required' => true, 'default' => 'https://api.tabby.ai'],
        ];
    }

    public function create(PaymentAttempt $attempt, array $customer, array $urls, array $input = []): array
    {
        $secretKey = $this->config->get('secret_key');
        $merchantCode = $this->config->get('merchant_code');
        $baseUrl = $this->baseUrl() . '/api/v2/checkout';

        if (!$secretKey || !$merchantCode) {
            throw new \RuntimeException('Tabby configuration error');
        }

        $invoiceRef = 'INV-' . $attempt->id;

        $payload = [
            'merchant_code' => $merchantCode,
            'payment' => [
                'amount' => (float) $attempt->amount,
                'currency' => $attempt->currency ?? 'SAR',
                'description' => "Invoice #{$invoiceRef}",
                'buyer' => [
                    'phone' => $customer['phone'] ?? '',
                    'name' => $customer['name'] ?? '',
                ],
            ],
            'order' => [
                'reference_id' => $invoiceRef,
            ],
            'lang' => app()->getLocale() ?? 'en',
            'merchant_urls' => [
                'success' => $urls['success'] ?? '',
                'fail' => $urls['fail'] ?? '',
                'cancel' => $urls['cancel'] ?? '',
            ],
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $secretKey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->post($baseUrl, $payload);

        $data = $this->ensureSuccessful($response);

        $paymentUrl = $data['configuration']['available_products']['installments'][0]['web_url']
            ?? $data['configuration']['available_products']['pay_later'][0]['web_url']
            ?? null;

        if (!$paymentUrl) {
            throw new \RuntimeException('Tabby payment URL not available');
        }

        $externalId = $data['id'] ?? $data['checkout_id'] ?? $data['payment_id'] ?? null;

        return [
            'external_id' => $externalId,
            'payment_url' => $paymentUrl,
            'raw' => $data,
        ];
    }

    public function verify(PaymentAttempt $attempt, Request $request): array
    {
        $secretKey = $this->config->get('secret_key');
        $checkoutId = $request->get('checkout_id') ?: $attempt->external_id;

        if (!$secretKey || !$checkoutId) {
            return ['status' => 'failed', 'raw' => ['message' => 'Tabby id missing']];
        }

        $baseUrl = $this->baseUrl() . "/api/v2/checkout/{$checkoutId}";

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $secretKey,
        ])->get($baseUrl);

        $data = $this->ensureSuccessful($response);

        $status = strtoupper((string) ($data['status'] ?? ''));

        return [
            'status' => $this->normalizeStatus($status),
            'external_id' => $checkoutId,
            'raw' => $data,
        ];
    }

    private function baseUrl(): string
    {
        return rtrim((string) $this->config->get('base_url', 'https://api.tabby.ai'), '/');
    }

    private function ensureSuccessful(Response $response): array
    {
        if (!$response->successful()) {
            throw new \RuntimeException('Tabby request failed: ' . $response->body());
        }

        return $response->json();
    }

    private function normalizeStatus(string $status): string
    {
        return match ($status) {
            'CAPTURED' => 'paid',
            'AUTHORIZED' => 'paid',
            'FAILED' => 'failed',
            'CANCELLED' => 'cancelled',
            default => 'pending',
        };
    }
}
