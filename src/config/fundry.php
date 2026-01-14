<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Fundry Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration file for the Fundry wallet management package.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    |
    | The default currency code used when no currency is specified.
    |
    */
    'default_currency' => env('FUNDRY_DEFAULT_CURRENCY', 'USD'),

    /*
    |--------------------------------------------------------------------------
    | Precision
    |--------------------------------------------------------------------------
    |
    | Decimal precision for monetary calculations (default: 8).
    |
    */
    'precision' => env('FUNDRY_PRECISION', 8),

    /*
    |--------------------------------------------------------------------------
    | Use Cents Storage
    |--------------------------------------------------------------------------
    |
    | If true, amounts will be stored as integers (cents) in the database.
    | If false, amounts will be stored as decimals.
    |
    */
    'use_cents_storage' => env('FUNDRY_USE_CENTS', false),

    /*
    |--------------------------------------------------------------------------
    | Transaction Limits
    |--------------------------------------------------------------------------
    |
    | Default transaction limits per day.
    |
    */
    'limits' => [
        'daily_deposit' => env('FUNDRY_DAILY_DEPOSIT_LIMIT', 10000),
        'daily_withdrawal' => env('FUNDRY_DAILY_WITHDRAWAL_LIMIT', 10000),
        'daily_transfer' => env('FUNDRY_DAILY_TRANSFER_LIMIT', 50000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Exchange Rate Provider
    |--------------------------------------------------------------------------
    |
    | The service to use for fetching exchange rates.
    | Options: 'manual', 'exchangerate-api'
    |
    */
    'exchange_rate_provider' => env('FUNDRY_EXCHANGE_RATE_PROVIDER', 'manual'),

    /*
    |--------------------------------------------------------------------------
    | Exchange Rate API Key
    |--------------------------------------------------------------------------
    |
    | API key for external exchange rate providers.
    |
    */
    'exchange_rate_api_key' => env('FUNDRY_EXCHANGE_RATE_API_KEY', null),

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | Cache configuration for currencies and exchange rates.
    |
    */
    'cache' => [
        'enabled' => env('FUNDRY_CACHE_ENABLED', true),
        'ttl' => env('FUNDRY_CACHE_TTL', 3600), // 1 hour in seconds
        'prefix' => 'fundry',
    ],

    /*
    |--------------------------------------------------------------------------
    | Export Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for PDF and Excel exports.
    |
    */
    'exports' => [
        'pdf' => [
            'orientation' => 'portrait',
            'paper' => 'a4',
        ],
        'excel' => [
            'format' => 'xlsx',
        ],
    ],
];
