<?php

return [
    'enabled' => env('BOOKING_WHATSAPP_ENABLED', false),
    'api_key' => env('TAQNYAT_WHATSAPP_API_KEY'),
    'language' => env('TAQNYAT_WHATSAPP_LANGUAGE', 'ar'),
    // Booking timestamps are entered as Saudi local wall-clock time.
    'timezone' => env('BOOKING_WHATSAPP_TIMEZONE', 'Asia/Riyadh'),
    'templates' => [
        'created' => env('TAQNYAT_WHATSAPP_CREATED_TEMPLATE', 'sami_booking_created'),
        'updated' => env('TAQNYAT_WHATSAPP_UPDATED_TEMPLATE', 'sami_booking_updated'),
        'cancelled' => env('TAQNYAT_WHATSAPP_CANCELLED_TEMPLATE', 'sami_booking_cancelled'),
        'reminder' => env('TAQNYAT_WHATSAPP_REMINDER_TEMPLATE', 'sami_booking_reminder'),
    ],
];
