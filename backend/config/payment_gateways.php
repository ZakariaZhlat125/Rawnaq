<?php

use App\Services\Payment\Gateways\ArbGateway;
use App\Services\Payment\Gateways\TabbyGateway;
use App\Services\Payment\Gateways\TamaraGateway;
use App\Services\Payment\Gateways\TapGateway;
use App\Services\Payment\Gateways\TelrGateway;

return [

    /*
    |--------------------------------------------------------------------------
    | Payment gateway drivers
    |--------------------------------------------------------------------------
    |
    | Gateways and their credentials live in the database and are managed at
    | /app/payment-gateways. This list only maps a gateway's `driver` to the class
    | that talks to the provider. Supporting a new provider means writing one class
    | that implements App\Services\Payment\Gateways\PaymentGatewayDriver and adding
    | it here; its fields() then appear in the dashboard automatically.
    |
    */

    'drivers' => [
        'tap' => TapGateway::class,
        'tabby' => TabbyGateway::class,
        'tamara' => TamaraGateway::class,
        'telr' => TelrGateway::class,
        'arb' => ArbGateway::class,
    ],

];
