<?php

return [
    'recaptcha' => [
        'enabled' => env('RECAPTCHA_ENABLED', false),
        'site_key' => env('RECAPTCHA_SITE_KEY'),
        'secret' => env('RECAPTCHA_SECRET'),
    ],

    'hsts' => [
        'enabled' => env('HSTS_ENABLED', env('APP_ENV') === 'production'),
        'max_age' => env('HSTS_MAX_AGE', 31536000),
        'include_subdomains' => env('HSTS_INCLUDE_SUBDOMAINS', true),
        'preload' => env('HSTS_PRELOAD', false),
    ],
];
