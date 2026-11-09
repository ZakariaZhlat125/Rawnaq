<?php

// Payment credentials live in the database (/app/payment-gateways). These .env keys
// are only read once, by PaymentGatewaySeeder, to fill it on an upgraded server.

return [
    'secret_key' => env('TAMARA_SECRET_KEY'),
    'base_url'   => env('TAMARA_BASE_URL', 'https://api.tamara.co'),
];
