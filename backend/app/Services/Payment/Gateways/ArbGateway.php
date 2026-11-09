<?php

namespace App\Services\Payment\Gateways;

use App\Models\PaymentAttempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ArbGateway implements PaymentGatewayDriver
{
    public function __construct(private GatewayConfig $config)
    {
    }

    public static function label(): string
    {
        return 'Al Rajhi Bank (ARB / UrPay)';
    }

    public static function fields(): array
    {
        return [
            'tranportal_id' => ['label' => 'Tranportal ID', 'required' => true],
            'tranportal_password' => ['label' => 'Tranportal password', 'required' => true, 'secret' => true],
            'resource_key' => ['label' => 'Resource key (terminal resource key)', 'required' => true, 'secret' => true],
            'mode' => ['label' => 'Mode', 'type' => 'select', 'options' => ['test' => 'Test', 'live' => 'Live'], 'required' => true, 'default' => 'test'],
            'currency_code' => ['label' => 'Currency code (ISO 4217 numeric)', 'required' => true, 'default' => '682', 'help' => '682 = SAR'],
        ];
    }

    /**
     * Create a new ARB / UrPay hosted payment session.
     */
    public function create(PaymentAttempt $attempt, array $customer, array $urls, array $input = []): array
    {
        $this->ensureConfigured();

        $trackId = $this->buildTrackId($attempt);
        $amount = number_format((float) $attempt->amount, 2, '.', '');

        $data = [
            'id' => $this->getConfig('tranportal_id'),
            'password' => $this->getConfig('tranportal_password'),
            'action' => '1',
            'trackId' => $trackId,
            'amt' => $amount,
            'currencyCode' => (string) ($this->getConfig('currency_code') ?: '682'),
            'langid' => app()->getLocale() === 'en' ? 'EN' : 'AR',
            'responseURL' => $urls['success'] ?? '',
            'errorURL' => $urls['fail'] ?? '',
            'udf1' => $attempt->token,
            'udf2' => (string) $attempt->id,
            'udf3' => (string) ($customer['phone'] ?? ''),
        ];

        $encryptedData = $this->encrypt(json_encode([$data]), $this->getConfig('resource_key'));

        $body = json_encode([[
            'id' => $this->getConfig('tranportal_id'),
            'trandata' => $encryptedData,
            'responseURL' => $urls['success'] ?? '',
            'errorURL' => $urls['fail'] ?? '',
        ]]);

        $endpoint = $this->getEndpoint();

        $response = Http::withBody($body, 'application/json')
            ->withOptions(['verify' => false, 'connect_timeout' => 60,'timeout' => 60])
            ->post($endpoint);

        $json = $response->json('0');

        if (! isset($json['status']) || (string) $json['status'] !== '1' || empty($json['result'])) {
            $errorMsg = $json['errorText'] ?? ('Payment initiation failed with status ' . $response->status());
            Log::error('ARB/UrPay initiation failed', ['response' => $json, 'http_status' => $response->status()]);
            throw new \RuntimeException($errorMsg);
        }

        // Result format: "PAYMENTID:URL"
        $parts = explode(':', $json['result'], 2);
        $paymentId = $parts[0];
        $rawUrl = $parts[1] ?? '';
        $baseURL = str_starts_with($rawUrl, 'http') ? $rawUrl : ('https:' . $rawUrl);
        $paymentUrl = $baseURL;
        if (! str_contains(strtolower($paymentUrl), 'paymentid=')) {
            $paymentUrl .= (str_contains($paymentUrl, '?') ? '&' : '?') . 'PaymentID=' . $paymentId;
        }

        return [
            'external_id' => $paymentId ?: $trackId,
            'payment_url' => $paymentUrl,
            'raw' => (array) $json,
        ];
    }

    /**
     * Verify the ARB payment status from callback.
     */
    public function verify(PaymentAttempt $attempt, Request $request): array
    {
        $raw = $request->all();
        $trandata = $request->input('trandata');

        if ($trandata) {
            $decrypted = $this->decrypt($trandata, $this->getConfig('resource_key'));
            if ($decrypted) {
                $dataArr = json_decode($decrypted, true);
                $first = $dataArr[0] ?? [];
                $result = strtoupper((string) ($first['result'] ?? ''));
                $paymentId = $first['paymentid'] ?? $first['PaymentID'] ?? $first['tranid'] ?? null;

                $errorText = strtolower((string) ($first['errorText'] ?? $first['ErrorText'] ?? ''));

                if (in_array($result, ['CAPTURED', 'SUCCESS', 'APPROVED'], true)) {
                    return [
                        'status' => 'paid',
                        'external_id' => $paymentId ?: $attempt->external_id,
                        'raw' => $first,
                    ];
                }

                if (in_array($result, ['CANCEL', 'CANCELLED', 'CANCELED', 'USER CANCELLED'], true)
                    || str_contains($errorText, 'cancel')
                    || str_contains(strtolower($result), 'cancel')
                ) {
                    return [
                        'status' => 'cancelled',
                        'external_id' => $paymentId ?: $attempt->external_id,
                        'raw' => $first,
                    ];
                }

                return [
                    'status' => 'failed',
                    'external_id' => $paymentId ?: $attempt->external_id,
                    'raw' => $first,
                ];
            }
        }

        // Fallback for query params / direct result hints
        $errorText = strtolower((string) ($request->get('ErrorText') ?? $request->get('errorText') ?? ''));
        $status = strtolower((string) ($request->get('status') ?? $request->get('Status') ?? ''));
        $paymentId = $request->get('paymentid') ?? $request->get('PaymentID') ?? $attempt->external_id;
        $result = strtolower((string) ($request->get('result') ?? ''));
        $responseCode = (string) ($request->get('responsecode') ?? $request->get('ResponseCode') ?? '');

        if (str_contains($errorText, 'cancel') || in_array($status, ['cancel', 'cancelled', 'canceled'], true) || in_array($result, ['cancel', 'cancelled', 'canceled'], true)) {
            return [
                'status' => 'cancelled',
                'external_id' => $paymentId ?: $attempt->external_id,
                'raw' => $raw,
            ];
        }

        return [
            'status' => $this->normalizeStatus($status, $result, $responseCode),
            'external_id' => $paymentId ?: $attempt->external_id,
            'raw' => $raw,
        ];
    }

    private function buildTrackId(PaymentAttempt $attempt): string
    {
        return 'arb-' . $attempt->id . '-' . substr((string) $attempt->token, 0, 8);
    }

    private function normalizeStatus(string $status, string $result, string $responseCode): string
    {
        if ($status === 'success' || $status === 'captured') return 'paid';
        if (in_array($status, ['cancelled', 'cancel', 'canceled'], true)) return 'cancelled';

        $resultLower = strtolower($result);
        if (in_array($resultLower, ['captured', 'approved'], true)) return 'paid';
        if (in_array($resultLower, ['cancel', 'cancelled', 'canceled'], true)) return 'cancelled';

        if ($responseCode === '00') return 'paid';

        return 'failed';
    }

    private function getEndpoint(): string
    {
        $mode = strtolower((string) ($this->getConfig('mode') ?: 'test'));
        if ($mode === 'live' || $mode === 'production') {
            return config('arb.live_bank_hosted_endpoint')
                ?: 'https://digitalpayments.alrajhibank.com.sa/pg/payment/tranportal.htm';
        }

        return config('arb.test_bank_hosted_endpoint')
            ?: 'https://securepayments.alrajhibank.com.sa/pg/payment/tranportal.htm';
    }

    /**
     * The bank may call back without our query string; the attempt token then only
     * travels inside the encrypted trandata (udf1).
     */
    public function attemptToken(string $trandata): ?string
    {
        $decrypted = $this->decrypt($trandata, (string) $this->getConfig('resource_key'));
        if (! $decrypted) {
            return null;
        }

        $token = json_decode($decrypted, true)[0]['udf1'] ?? null;

        return is_string($token) && $token !== '' ? $token : null;
    }

    private function getConfig(string $key): ?string
    {
        return $this->config->get($key);
    }

    private function ensureConfigured(): void
    {
        if (! $this->getConfig('tranportal_id') || ! $this->getConfig('tranportal_password') || ! $this->getConfig('resource_key')) {
            throw new \RuntimeException('Al Rajhi Bank (ARB / UrPay) payment gateway configuration is incomplete. Set the Tranportal ID, Tranportal password and resource key at /app/payment-gateways.');
        }
    }

    private function encrypt(string $str, string $key): string
    {
        $blocksize = openssl_cipher_iv_length('AES-256-CBC');
        $pad = $blocksize - (strlen($str) % $blocksize);
        $str = $str . str_repeat(chr($pad), $pad);
        $encrypted = openssl_encrypt($str, 'AES-256-CBC', $key, OPENSSL_ZERO_PADDING, 'PGKEYENCDECIVSPC');
        $encrypted = base64_decode($encrypted);
        $encrypted = unpack('C*', $encrypted);
        $chars = array_map('chr', $encrypted);
        $bin = implode($chars);

        return urlencode(bin2hex($bin));
    }

    private function decrypt(string $code, string $key): false|string
    {
        $string = hex2bin(trim($code));
        if ($string === false) return false;
        $code = unpack('C*', $string);
        $chars = array_map('chr', $code);
        $code = implode($chars);
        $code = base64_encode($code);
        $decrypted = openssl_decrypt($code, 'AES-256-CBC', $key, OPENSSL_ZERO_PADDING, 'PGKEYENCDECIVSPC');
        if ($decrypted === false) return false;
        $pad = ord($decrypted[strlen($decrypted) - 1]);
        if ($pad > strlen($decrypted)) return false;
        if (strspn($decrypted, chr($pad), strlen($decrypted) - $pad) != $pad) return false;

        return urldecode(substr($decrypted, 0, -1 * $pad));
    }
}