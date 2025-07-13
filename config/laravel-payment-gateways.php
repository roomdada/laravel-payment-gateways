<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Payment Gateway
    |--------------------------------------------------------------------------
    |
    | This option controls the default payment gateway that will be used
    | when no specific gateway is specified.
    |
    */
    'default' => env('PAYMENT_DEFAULT_GATEWAY', 'cinetpay'),

    /*
    |--------------------------------------------------------------------------
    | Payment Gateways Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure the payment gateways for your application.
    | Each gateway has its own configuration including API credentials,
    | endpoints, and other settings.
    |
    */
    'gateways' => [
        'cinetpay' => [
            'enabled' => env('CINETPAY_ENABLED', true),
            'priority' => 1,
            'api_key' => env('CINETPAY_API_KEY'),
            'site_id' => env('CINETPAY_SITE_ID'),
            'base_url' => env('CINETPAY_BASE_URL', 'https://api-checkout.cinetpay.com/v2'),
            'currency' => env('CINETPAY_CURRENCY', 'XOF'),
            'timeout' => env('CINETPAY_TIMEOUT', 30),
            'webhook_url' => env('CINETPAY_WEBHOOK_URL'),
        ],

        'bizao' => [
            'enabled' => env('BIZAO_ENABLED', true),
            'priority' => 2,
            'client_id' => env('BIZAO_CLIENT_ID'),
            'client_secret' => env('BIZAO_CLIENT_SECRET'),
            'base_url' => env('BIZAO_BASE_URL', 'https://api.bizao.com'),
            'currency' => env('BIZAO_CURRENCY', 'XOF'),
            'timeout' => env('BIZAO_TIMEOUT', 30),
            'webhook_url' => env('BIZAO_WEBHOOK_URL'),
        ],

        'winipayer' => [
            'enabled' => env('WINIPAYER_ENABLED', true),
            'priority' => 3,
            'merchant_id' => env('WINIPAYER_MERCHANT_ID'),
            'api_key' => env('WINIPAYER_API_KEY'),
            'base_url' => env('WINIPAYER_BASE_URL', 'https://api.winipayer.com'),
            'currency' => env('WINIPAYER_CURRENCY', 'XOF'),
            'timeout' => env('WINIPAYER_TIMEOUT', 30),
            'webhook_url' => env('WINIPAYER_WEBHOOK_URL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Failover Configuration
    |--------------------------------------------------------------------------
    |
    | Configure how the system should handle gateway failures and automatic
    | failover to alternative gateways.
    |
    */
    'failover' => [
        'enabled' => env('PAYMENT_FAILOVER_ENABLED', true),
        'max_retries' => env('PAYMENT_MAX_RETRIES', 3),
        'retry_delay' => env('PAYMENT_RETRY_DELAY', 2), // seconds
        'exponential_backoff' => env('PAYMENT_EXPONENTIAL_BACKOFF', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | Configure webhook handling for payment notifications.
    |
    */
    'webhooks' => [
        'enabled' => env('PAYMENT_WEBHOOKS_ENABLED', true),
        'route_prefix' => env('PAYMENT_WEBHOOK_ROUTE_PREFIX', 'payment/webhook'),
        'middleware' => ['web'],
        'timeout' => env('PAYMENT_WEBHOOK_TIMEOUT', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Configure logging for payment operations.
    |
    */
    'logging' => [
        'enabled' => env('PAYMENT_LOGGING_ENABLED', true),
        'channel' => env('PAYMENT_LOG_CHANNEL', 'payment'),
        'level' => env('PAYMENT_LOG_LEVEL', 'info'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Configuration
    |--------------------------------------------------------------------------
    |
    | Configure database tables for storing payment transactions and
    | gateway configurations.
    |
    */
    'database' => [
        'tables' => [
            'transactions' => 'payment_transactions',
            'gateway_configs' => 'payment_gateway_configs',
        ],
    ],
];
