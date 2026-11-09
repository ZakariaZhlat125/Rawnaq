<?php

// Application flags read from .env. Code must use config('sami.*') instead of
// env() so that `php artisan config:cache` keeps working.
return [
    'is_demo' => env('IS_DEMO'),
    'is_dummy_data' => env('IS_DUMMY_DATA'),
    'is_dummy_data_image' => env('IS_DUMMY_DATA_IMAGE'),
    'is_fake_data' => env('IS_FAKE_DATA'),
    'user_registration' => env('USER_REGISTRATION'),
    'default_language' => env('DEFAULT_LANGUAGE'),
    'slack_notification_webhook' => env('SLACK_NOTIFICATION_WEBHOOK'),
    'taqnyat_api_key' => env('TAQNYAT_API_KEY'),
    'taqnyat_sender_name' => env('TAQNYAT_SENDER_NAME'),
    'telescope_enabled' => env('TELESCOPE_ENABLED', false),
    'telescope_path_live' => env('TELESCOPE_PATH_LIVE'),
    'social' => [
        'google' => env('GOOGLE_ACTIVE'),
        'github' => env('GITHUB_ACTIVE'),
        'facebook' => env('FACEBOOK_ACTIVE'),
    ],
];
