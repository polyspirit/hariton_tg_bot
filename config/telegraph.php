<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Bot
    |--------------------------------------------------------------------------
    |
    | This option controls the default bot that will be used when no bot
    | is specified.
    |
    */

    'default_bot' => env('TELEGRAPH_DEFAULT_BOT', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Bots
    |--------------------------------------------------------------------------
    |
    | Here you may configure the bots for your application. Each bot has
    | its own token and optional settings.
    |
    */

    'bots' => [
        'default' => [
            'token' => env('TELEGRAPH_BOT_TOKEN', '7640457060:AAG4YN_6IkpWqLD6qIf3XxEmsO_f5hsJQQA'),
            'name' => env('TELEGRAPH_BOT_NAME', 'Hariton Bot'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook URL
    |--------------------------------------------------------------------------
    |
    | The URL where Telegram will send webhook updates. This should be
    | a publicly accessible URL.
    |
    */

    'webhook_url' => env('TELEGRAPH_WEBHOOK_URL'),

    /*
    |--------------------------------------------------------------------------
    | Webhook Secret
    |--------------------------------------------------------------------------
    |
    | A secret token that will be used to verify webhook requests from
    | Telegram. This should be a random string.
    |
    */

    'webhook_secret' => env('TELEGRAPH_WEBHOOK_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    |
    | Middleware to apply to webhook routes.
    |
    */

    'middleware' => [
        'web' => [
            'throttle:60,1',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage
    |--------------------------------------------------------------------------
    |
    | The storage driver to use for storing bot data.
    |
    */

    'storage' => [
        'driver' => env('TELEGRAPH_STORAGE_DRIVER', 'database'),
    ],
];
