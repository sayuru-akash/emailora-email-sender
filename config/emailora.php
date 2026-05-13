<?php

return [
    'provider' => env('EMAIL_PROVIDER', 'resend'),
    'fallback_provider' => env('EMAIL_FALLBACK_PROVIDER'),
    'from_email' => env('EMAIL_FROM_ADDRESS'),
    'from_name' => env('EMAIL_FROM_NAME', env('APP_NAME', 'Emailora')),
    'reply_to' => env('EMAIL_REPLY_TO'),
    'rate_limit_per_minute' => (int) env('EMAIL_RATE_LIMIT_PER_MINUTE', 300),
    'chunk_size' => (int) env('EMAIL_CHUNK_SIZE', 50),
    'timeout' => (int) env('EMAIL_TIMEOUT_SECONDS', 30),
    'tracking' => [
        'opens' => filter_var(env('EMAIL_TRACK_OPENS', true), FILTER_VALIDATE_BOOL),
        'clicks' => filter_var(env('EMAIL_TRACK_CLICKS', true), FILTER_VALIDATE_BOOL),
    ],
    'resend' => [
        'api_key' => env('RESEND_API_KEY'),
        'webhook_secret' => env('RESEND_WEBHOOK_SECRET'),
    ],
    'brevo' => [
        'api_key' => env('BREVO_API_KEY'),
        'webhook_secret' => env('BREVO_WEBHOOK_SECRET'),
    ],
];
