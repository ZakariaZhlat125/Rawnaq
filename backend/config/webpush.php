<?php
return [
    'enabled' => env('WEBPUSH_ENABLED', false),
    'public_key' => env('WEBPUSH_PUBLIC_KEY'),
    'private_key' => env('WEBPUSH_PRIVATE_KEY'),
    'subject' => env('WEBPUSH_SUBJECT', 'mailto:admin@example.com'),
    'connection' => env('WEBPUSH_QUEUE_CONNECTION', 'webpush'),
];
