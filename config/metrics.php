<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Metrics Driver
    |--------------------------------------------------------------------------
    |
    | Configure the metrics collection driver:
    | - null: No metrics collection (default)
    | - log: Log metrics to Laravel logs
    |
    */

    'driver' => env('FRAG_METRICS_DRIVER', 'null'),

    /*
    |--------------------------------------------------------------------------
    | Metrics Configuration
    |--------------------------------------------------------------------------
    |
    | Additional configuration for metrics collection.
    |
    */

    'config' => [
        // Log metrics configuration
        'log' => [
            'channel' => 'daily',
        ],
    ],
];
