<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | API Rate Limiting Configuration
    |--------------------------------------------------------------------------
    |
    | Configure rate limits for different user tiers. Format: 'requests:minutes'
    | Example: '100:1' = 100 requests per 1 minute
    |
    */

    'api' => [
        'free' => [
            'limit' => 100,
            'decay_minutes' => 1,
        ],
        'pro' => [
            'limit' => 500,
            'decay_minutes' => 1,
        ],
        'enterprise' => [
            'limit' => 2000,
            'decay_minutes' => 1,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Rate limits for webhook deliveries to prevent abuse
    |
    */

    'webhooks' => [
        'max_retries' => 5,
        'retry_delay_seconds' => [2, 4, 8, 16, 32], // Exponential backoff
        'timeout_seconds' => 10,
    ],

    /*
    |--------------------------------------------------------------------------
    | Public Routes Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Rate limits for public/unauthenticated routes
    |
    */

    'public' => [
        'limit' => 60,
        'decay_minutes' => 1,
    ],

    /*
    |--------------------------------------------------------------------------
    | Auth Routes Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Stricter rate limits for authentication endpoints to prevent brute force
    |
    */

    'auth' => [
        'limit' => 10,
        'decay_minutes' => 1,
    ],
];
