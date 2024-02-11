<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Acorn SMTP Mail Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration files provide a great way to customize your package.
    |
    | This file is where you may override the default configuration settings or
    | add your own configuration settings to the package.
    |
    */
    'debug' => env('WP_SMTP_DEBUG', false),

    'log_errors' => env('WP_SMTP_LOG_ERRORS', false),

    'host' => env('WP_SMTP_HOST'),

    'port' => env('WP_SMTP_PORT', 587),

    'secure' => env('WP_SMTP_SECURE', 'ssl'),

    'username' => env('WP_SMTP_USERNAME'),

    'password' => env('WP_SMTP_PASSWORD'),

    'forcefrom' => env('WP_SMTP_FORCEFROM'),

    'forcefromname' => env('WP_SMTP_FORCEFROMNAME'),
];
