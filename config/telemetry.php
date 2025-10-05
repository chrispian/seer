<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Unified Telemetry Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration for TELEMETRY-006: Local Telemetry 
    | Sink & Query Interface system that provides unified telemetry data
    | management across all systems.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Enable Telemetry Sink
    |--------------------------------------------------------------------------
    |
    | Enable or disable the unified telemetry sink system. When disabled, 
    | telemetry data will only be logged, not stored in the database.
    |
    */
    'enabled' => env('TELEMETRY_SINK_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Storage Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the storage backend for telemetry data.
    |
    */
    'storage' => [
        'driver' => env('TELEMETRY_STORAGE_DRIVER', 'database'), // database, sqlite, file
        'database_connection' => env('TELEMETRY_DB_CONNECTION', 'default'),
        'sqlite_path' => env('TELEMETRY_SQLITE_PATH', storage_path('telemetry/telemetry.sqlite')),
        'batch_size' => env('TELEMETRY_BATCH_SIZE', 100),
        'async_processing' => env('TELEMETRY_ASYNC_PROCESSING', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Retention Policies
    |--------------------------------------------------------------------------
    |
    | Configure how long to keep different types of telemetry data.
    |
    */
    'retention' => [
        'raw_events_days' => env('TELEMETRY_RAW_RETENTION', 14),
        'aggregated_metrics_days' => env('TELEMETRY_METRICS_RETENTION', 90),
        'health_checks_days' => env('TELEMETRY_HEALTH_RETENTION', 30),
        'performance_snapshots_days' => env('TELEMETRY_PERFORMANCE_RETENTION', 30),
        'correlation_chains_days' => env('TELEMETRY_CHAINS_RETENTION', 30),
        'alerts_days' => env('TELEMETRY_ALERTS_RETENTION', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Aggregation Configuration
    |--------------------------------------------------------------------------
    |
    | Configure how telemetry data is aggregated for analytics.
    |
    */
    'aggregation' => [
        'enabled' => env('TELEMETRY_AGGREGATION_ENABLED', true),
        'intervals' => ['1m', '5m', '15m', '1h', '6h', '1d'],
        'metrics' => [
            'event_counts' => true,
            'performance_percentiles' => true,
            'error_rates' => true,
            'resource_utilization' => true,
            'success_rates' => true,
        ],
        'batch_size' => env('TELEMETRY_AGGREGATION_BATCH_SIZE', 1000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Query Configuration
    |--------------------------------------------------------------------------
    |
    | Configure query performance and limits.
    |
    */
    'query' => [
        'default_limit' => 100,
        'max_limit' => 10000,
        'default_time_range_hours' => 24,
        'max_time_range_days' => 90,
        'cache_ttl_minutes' => 15,
        'enable_query_optimization' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Export Configuration
    |--------------------------------------------------------------------------
    |
    | Configure data export capabilities.
    |
    */
    'export' => [
        'formats' => ['json', 'csv', 'parquet'],
        'max_export_size_mb' => 100,
        'compression' => env('TELEMETRY_EXPORT_COMPRESSION', true),
        'destinations' => [
            's3' => [
                'enabled' => env('TELEMETRY_EXPORT_S3_ENABLED', false),
                'bucket' => env('TELEMETRY_EXPORT_S3_BUCKET'),
                'prefix' => env('TELEMETRY_EXPORT_S3_PREFIX', 'telemetry/exports/'),
            ],
            'local' => [
                'enabled' => env('TELEMETRY_EXPORT_LOCAL_ENABLED', true),
                'path' => env('TELEMETRY_EXPORT_LOCAL_PATH', storage_path('telemetry/exports')),
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Alert Configuration
    |--------------------------------------------------------------------------
    |
    | Configure unified alerting across all telemetry systems.
    |
    */
    'alerts' => [
        'enabled' => env('TELEMETRY_ALERTS_ENABLED', true),
        'evaluation_interval_minutes' => env('TELEMETRY_ALERT_INTERVAL', 5),
        'notification_channels' => ['log', 'slack', 'webhook'],
        'rate_limiting' => [
            'max_alerts_per_hour' => 20,
            'duplicate_suppression_minutes' => 30,
        ],
        'conditions' => [
            'high_error_rate' => [
                'threshold' => 0.05, // 5%
                'window_minutes' => 10,
                'min_events' => 10,
            ],
            'performance_degradation' => [
                'threshold_multiplier' => 2.0, // 2x slower than baseline
                'window_minutes' => 15,
                'min_events' => 5,
            ],
            'health_check_failures' => [
                'consecutive_failures' => 3,
                'window_minutes' => 15,
            ],
            'resource_exhaustion' => [
                'memory_threshold_mb' => 512,
                'cpu_threshold_percent' => 90,
                'window_minutes' => 5,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Dashboard Configuration
    |--------------------------------------------------------------------------
    |
    | Configure real-time monitoring dashboards.
    |
    */
    'dashboard' => [
        'enabled' => env('TELEMETRY_DASHBOARD_ENABLED', true),
        'refresh_interval_seconds' => 30,
        'widgets' => [
            'system_health' => true,
            'event_rates' => true,
            'performance_trends' => true,
            'error_rates' => true,
            'correlation_chains' => true,
            'resource_utilization' => true,
        ],
        'time_ranges' => ['1h', '6h', '24h', '7d', '30d'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Component Configuration
    |--------------------------------------------------------------------------
    |
    | Configure telemetry collection for different system components.
    |
    */
    'components' => [
        'tool_telemetry' => [
            'enabled' => true,
            'priority' => 'high',
            'retention_days' => 30,
            'metrics' => ['execution_time', 'success_rate', 'error_rate', 'usage_count'],
        ],
        'command_telemetry' => [
            'enabled' => true,
            'priority' => 'high',
            'retention_days' => 30,
            'metrics' => ['execution_time', 'step_performance', 'success_rate', 'template_cache_hits'],
        ],
        'fragment_telemetry' => [
            'enabled' => true,
            'priority' => 'medium',
            'retention_days' => 14,
            'metrics' => ['processing_time', 'pipeline_performance', 'ai_processing_time'],
        ],
        'chat_telemetry' => [
            'enabled' => true,
            'priority' => 'medium',
            'retention_days' => 14,
            'metrics' => ['response_time', 'token_usage', 'user_satisfaction'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Configuration
    |--------------------------------------------------------------------------
    |
    | Configure telemetry system performance settings.
    |
    */
    'performance' => [
        'async_processing' => env('TELEMETRY_ASYNC_PROCESSING', true),
        'queue_connection' => env('TELEMETRY_QUEUE_CONNECTION', 'default'),
        'batch_processing' => true,
        'batch_size' => 50,
        'buffer_size' => 1000,
        'flush_interval_seconds' => 30,
        'enable_compression' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | Configure security settings for telemetry data.
    |
    */
    'security' => [
        'encrypt_sensitive_data' => env('TELEMETRY_ENCRYPT_SENSITIVE', true),
        'anonymize_user_data' => env('TELEMETRY_ANONYMIZE_USERS', false),
        'sensitive_fields' => [
            'password', 'secret', 'token', 'key', 'credential', 'api_key',
            'email', 'phone', 'address', 'credit_card', 'ssn'
        ],
        'max_field_length' => 1000,
        'hash_long_values' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Debug Configuration
    |--------------------------------------------------------------------------
    |
    | Enable debugging for telemetry system development.
    |
    */
    'debug' => [
        'enabled' => env('TELEMETRY_DEBUG', false),
        'log_sink_operations' => false,
        'log_query_performance' => false,
        'log_aggregation_process' => false,
        'verbose_correlation_tracking' => false,
    ],
];