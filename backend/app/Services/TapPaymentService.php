<?php

namespace App\Services;

use App\Services\Payment\PaymentGatewayManager;
use Illuminate\Support\Facades\Http;

class TapPaymentService
{
    protected $baseUrl = "https://api.tap.company/v2/";

    public function __construct(
        protected ?string $secretKey,
        protected string $statementDescriptor = 'JOSPA Store'
    ) {
    }

    /** Client built from the credentials of a Tap gateway in the dashboard. */
    public static function forGateway(string $code = 'card'): self
    {
        $config = app(PaymentGatewayManager::class)->config($code);

        return new self($config->get('secret_key'), $config->get('statement_descriptor', 'JOSPA Store'));
    }

    public function createCharge($amount, $customerData, $redirectUrl)
    {
        $payload = [
            "amount" => $amount,
            "currency" => "SAR",
            "statement_descriptor" => $this->statementDescriptor,
            "customer" => [
                "first_name" => $customerData['name'],
                "phone"      => [
                    "country_code" => $customerData['country_code'],
                    "number"       => $customerData['phone']
                ]
            ],
            "source" => [
                "id" => $customerData['method']
            ],

            "redirect" => [
                "url" => $redirectUrl
            ]
        ];

        $response = Http::withToken((string) $this->secretKey)
            ->acceptJson()
            ->post($this->baseUrl . "charges", $payload);

        return $response->json();
    }

    public function getCharge($chargeId)
    {
        $response = Http::withToken((string) $this->secretKey)
            ->acceptJson()
            ->get($this->baseUrl . "charges/" . $chargeId);

        return $response->json();
    }
}
