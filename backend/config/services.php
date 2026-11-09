<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */
    

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],
    'hyperpay' => [
        'entity_id' => env('HYPERPAY_ENTITY_ID'),
        'token'     => env('HYPERPAY_TOKEN'),
        'base_url'  => env('HYPERPAY_BASE_URL'),
    ],
    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],
    
    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'facebook' => [
        'client_id' => env('FACEBOOK_CLIENT_ID'),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
        'redirect' => env('FACEBOOK_REDIRECT'),
    ],

    'github' => [
        'client_id' => env('GITHUB_CLIENT_ID'),
        'client_secret' => env('GITHUB_CLIENT_SECRET'),
        'redirect' => env('GITHUB_REDIRECT'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT'),
    ],
    // Import-only: copied once into /app/payment-gateways by PaymentGatewaySeeder.
    'tap' => [
        'secret_key' => env('TAP_SECRET_KEY'),
    ],
    // Import-only: copied once into /app/payment-gateways by PaymentGatewaySeeder.
    'telr' => [
        'base_url' => env('TELR_BASE_URL', 'https://secure.telr.com'),
        'store_id' => env('TELR_STORE_ID'),
        'auth_key' => env('TELR_AUTH_KEY'),
        'test_mode' => env('TELR_TEST_MODE', 0),
        'framed' => env('TELR_FRAMED', 0),
        'panels' => env('TELR_PANELS'),
    ],
    'waiting_device' => [
        'base_url' => env('WAITING_DEVICE_BASE_URL'),
        'timeout' => env('WAITING_DEVICE_TIMEOUT', 10),
    ],

    'taqnyat' => [
        'api_key' => env('TAQNYAT_API_KEY'),
        'sender' => env('TAQNYAT_SENDER_NAME', 'SamiCare'),
    ],

    // Import-only: copied once into /app/payment-gateways by PaymentGatewaySeeder.
    'arb' => [
        'mode' => env('ARB_MODE', 'test'),
        'tranportal_id' => env('ARB_TRANPORTAL_ID'),
        'tranportal_password' => env('ARB_TRANPORTAL_PASSWORD'),
        'resource_key' => env('ARB_RESOURCE_KEY'),
        'currency_code' => env('ARB_CURRENCY_CODE', '682'),
    ],

];