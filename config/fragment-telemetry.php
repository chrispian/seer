<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Fragment Processing Telemetry Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration for the fragment processing telemetry
    | system that provides observability into the fragment processing pipeline.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Enable Telemetry
    |--------------------------------------------------------------------------
    |
    | Enable or disable the telemetry system. When disabled, no telemetry
    | data will be collected, but the pipeline will still function normally.
    |
    */
    'enabled' => env('FRAGMENT_TELEMETRY_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Configure which log channels to use for different types of telemetry data.
    |
    */
    'log_channels' => [
        'pipeline' => env('FRAGMENT_TELEMETRY_PIPELINE_CHANNEL', 'fragment-processing-telemetry'),
        'performance' => env('FRAGMENT_TELEMETRY_PERFORMANCE_CHANNEL', 'fragment-processing-telemetry'),
        'errors' => env('FRAGMENT_TELEMETRY_ERROR_CHANNEL', 'fragment-processing-telemetry'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Thresholds
    |--------------------------------------------------------------------------
    |
    | Define performance thresholds for alerting and classification.
    | All values are in milliseconds.
    |
    */
    'performance' => [
        'step_thresholds' => [
            'fast' => 100,      // < 100ms
            'normal' => 500,    // 100-500ms
            'slow' => 2000,     // 500ms-2s
            // > 2s = very_slow
        ],
        'pipeline_thresholds' => [
            'fast' => 1000,     // < 1s
            'normal' => 5000,   // 1-5s
            'slow' => 15000,    // 5-15s
            // > 15s = very_slow
        ],
        'alert_thresholds' => [
            'slow_step' => 5000,        // Alert if step takes > 5s
            'slow_pipeline' => 30000,   // Alert if pipeline takes > 30s
            'memory_usage' => 512,      // Alert if memory usage > 512MB
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Retention
    |--------------------------------------------------------------------------
    |
    | Configure how long to keep telemetry data and when to aggregate.
    |
    */
    'retention' => [
        'detailed_logs_days' => env('FRAGMENT_TELEMETRY_DETAILED_RETENTION', 7),
        'summary_logs_days' => env('FRAGMENT_TELEMETRY_SUMMARY_RETENTION', 30),
        'aggregation_interval_hours' => 24,
    ],

    /*
    |--------------------------------------------------------------------------
    | Sampling Configuration
    |--------------------------------------------------------------------------
    |
    | Configure sampling rates to reduce telemetry overhead in high-traffic
    | environments. Set to 1.0 to capture all events.
    |
    */
    'sampling' => [
        'pipeline_events' => env('FRAGMENT_TELEMETRY_PIPELINE_SAMPLING', 1.0),
        'step_events' => env('FRAGMENT_TELEMETRY_STEP_SAMPLING', 1.0),
        'state_changes' => env('FRAGMENT_TELEMETRY_STATE_SAMPLING', 1.0),
        'performance_events' => env('FRAGMENT_TELEMETRY_PERFORMANCE_SAMPLING', 1.0),
    ],

    /*
    |--------------------------------------------------------------------------
    | Context Enrichment
    |--------------------------------------------------------------------------
    |
    | Configure what additional context to include in telemetry logs.
    |
    */
    'context' => [
        'include_memory_usage' => true,
        'include_request_id' => true,
        'include_user_id' => false, // Set to true if user context is needed
        'include_environment' => true,
        'include_git_commit' => false,
        'max_message_length' => 1000, // Truncate long messages for logging
    ],

    /*
    |--------------------------------------------------------------------------
    | Export Configuration
    |--------------------------------------------------------------------------
    |
    | Configure exports to external systems like metrics collectors,
    | APM tools, or data warehouses.
    |
    */
    'exports' => [
        'metrics' => [
            'enabled' => env('FRAGMENT_TELEMETRY_METRICS_ENABLED', false),
            'driver' => env('FRAGMENT_TELEMETRY_METRICS_DRIVER', 'log'), // 'log', 'statsd', 'datadog'
            'namespace' => 'fragments.processing',
        ],
        'traces' => [
            'enabled' => env('FRAGMENT_TELEMETRY_TRACES_ENABLED', false),
            'service_name' => 'fragment-processing-pipeline',
            'sample_rate' => 0.1,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Debug Configuration
    |--------------------------------------------------------------------------
    |
    | Enable detailed debugging for telemetry system development.
    |
    */
    'debug' => [
        'enabled' => env('FRAGMENT_TELEMETRY_DEBUG', false),
        'log_decorator_creation' => false,
        'log_pipeline_building' => false,
        'verbose_state_tracking' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    |
    | Enable or disable specific telemetry features.
    |
    */
    'features' => [
        'correlation_tracking' => true,
        'state_change_detection' => true,
        'performance_alerts' => true,
        'automatic_summary_generation' => true,
        'fragment_relationship_tracking' => true,
        'memory_profiling' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Pipeline Presets
    |--------------------------------------------------------------------------
    |
    | Define common pipeline configurations for easy reuse.
    |
    */
    'presets' => [
        'full_processing' => [
            'steps' => [
                \App\Actions\DriftSync::class,
                \App\Actions\ParseAtomicFragment::class,
                \App\Actions\ExtractMetadataEntities::class,
                \App\Actions\GenerateAutoTitle::class,
                \App\Actions\EnrichFragmentWithAI::class,
                \App\Actions\InferFragmentType::class,
                \App\Actions\SuggestTags::class,
                \App\Actions\RouteToVault::class,
                \App\Actions\EmbedFragmentAction::class,
            ],
            'telemetry_enabled' => true,
        ],
        'basic_processing' => [
            'steps' => [
                \App\Actions\ParseAtomicFragment::class,
                \App\Actions\ExtractMetadataEntities::class,
                \App\Actions\GenerateAutoTitle::class,
            ],
            'telemetry_enabled' => true,
        ],
        'ai_only' => [
            'steps' => [
                \App\Actions\EnrichFragmentWithAI::class,
                \App\Actions\InferFragmentType::class,
                \App\Actions\SuggestTags::class,
            ],
            'telemetry_enabled' => true,
        ],
    ],
];