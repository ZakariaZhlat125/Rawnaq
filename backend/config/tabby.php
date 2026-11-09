<?php

// Payment credentials live in the database (/app/payment-gateways). These .env keys
// are only read once, by PaymentGatewaySeeder, to fill it on an upgraded server.

return [
    'public_key' => env('TABBY_PUBLIC_KEY'),
    'secret_key' => env('TABBY_SECRET_KEY'),
    'merchant_code' => env('TABBY_MERCHANT_CODE'),
];
