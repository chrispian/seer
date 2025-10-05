<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Command & DSL Execution Telemetry Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration for TELEMETRY-004: Command & DSL
    | Execution Metrics system that provides comprehensive observability
    | into the command execution pipeline.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Enable Telemetry
    |--------------------------------------------------------------------------
    |
    | Enable or disable the command telemetry system. When disabled, no
    | telemetry data will be collected, but commands will still function normally.
    |
    */
    'enabled' => env('COMMAND_TELEMETRY_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Configure which log channels to use for different types of telemetry data.
    |
    */
    'log_channels' => [
        'command_execution' => env('COMMAND_TELEMETRY_EXECUTION_CHANNEL', 'command-telemetry'),
        'dsl_performance' => env('COMMAND_TELEMETRY_DSL_CHANNEL', 'command-telemetry'),
        'step_metrics' => env('COMMAND_TELEMETRY_STEP_CHANNEL', 'command-telemetry'),
        'errors' => env('COMMAND_TELEMETRY_ERROR_CHANNEL', 'command-telemetry'),
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
        'command_thresholds' => [
            'fast' => 100,      // < 100ms
            'normal' => 500,    // 100-500ms
            'slow' => 2000,     // 500ms-2s
            'very_slow' => 5000, // 2s-5s
            // > 5s = critical
        ],
        'step_thresholds' => [
            'fast' => 50,       // < 50ms
            'normal' => 200,    // 50-200ms
            'slow' => 1000,     // 200ms-1s
            'very_slow' => 3000, // 1s-3s
            // > 3s = critical
        ],
        'template_thresholds' => [
            'fast' => 10,       // < 10ms
            'normal' => 50,     // 10-50ms
            'slow' => 200,      // 50-200ms
            // > 200ms = very_slow
        ],
        'alert_thresholds' => [
            'slow_command' => 5000,     // Alert if command takes > 5s
            'slow_step' => 3000,        // Alert if step takes > 3s
            'memory_usage' => 256,      // Alert if memory usage > 256MB
            'template_rendering' => 500, // Alert if template rendering > 500ms
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Step Type Configuration
    |--------------------------------------------------------------------------
    |
    | Configure telemetry collection for specific DSL step types.
    |
    */
    'step_types' => [
        'ai.generate' => [
            'track_tokens' => true,
            'track_cache_hits' => true,
            'track_provider_latency' => true,
        ],
        'condition' => [
            'track_condition_evaluation' => true,
            'track_branch_execution' => true,
        ],
        'fragment.query' => [
            'track_query_performance' => true,
            'track_result_counts' => true,
        ],
        'database.update' => [
            'track_row_counts' => true,
            'track_query_performance' => true,
        ],
        'tool.call' => [
            'track_external_latency' => true,
            'track_payload_sizes' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Sanitization
    |--------------------------------------------------------------------------
    |
    | Configure what data to sanitize or exclude from telemetry logs.
    |
    */
    'sanitization' => [
        'sensitive_patterns' => [
            '/password/i',
            '/secret/i',
            '/token/i',
            '/key/i',
            '/credential/i',
        ],
        'max_argument_length' => 500,
        'max_output_length' => 1000,
        'hash_sensitive_values' => true,
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
        'command_execution' => env('COMMAND_TELEMETRY_EXECUTION_SAMPLING', 1.0),
        'step_execution' => env('COMMAND_TELEMETRY_STEP_SAMPLING', 1.0),
        'template_rendering' => env('COMMAND_TELEMETRY_TEMPLATE_SAMPLING', 0.1),
        'condition_evaluation' => env('COMMAND_TELEMETRY_CONDITION_SAMPLING', 0.5),
        'performance_events' => env('COMMAND_TELEMETRY_PERFORMANCE_SAMPLING', 1.0),
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
        'include_user_context' => false,
        'include_command_source' => true, // hardcoded vs DSL-based
        'include_environment' => true,
        'include_git_commit' => false,
        'track_command_chains' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Metrics Collection
    |--------------------------------------------------------------------------
    |
    | Configure what metrics to collect and how to store them.
    |
    */
    'metrics' => [
        'command_popularity' => true,
        'success_failure_rates' => true,
        'performance_percentiles' => true,
        'resource_utilization' => true,
        'error_categorization' => true,
        'usage_patterns' => true,
        'template_cache_efficiency' => true,
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
            'enabled' => env('COMMAND_TELEMETRY_METRICS_ENABLED', false),
            'driver' => env('COMMAND_TELEMETRY_METRICS_DRIVER', 'log'),
            'namespace' => 'commands.execution',
        ],
        'traces' => [
            'enabled' => env('COMMAND_TELEMETRY_TRACES_ENABLED', false),
            'service_name' => 'command-execution-pipeline',
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
        'enabled' => env('COMMAND_TELEMETRY_DEBUG', false),
        'log_telemetry_overhead' => false,
        'verbose_step_tracking' => false,
        'template_debug' => false,
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
        'command_chain_tracking' => true,
        'performance_alerts' => true,
        'automatic_summary_generation' => true,
        'step_dependency_tracking' => true,
        'template_performance_tracking' => true,
        'error_pattern_detection' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Retention
    |--------------------------------------------------------------------------
    |
    | Configure how long to keep telemetry data.
    |
    */
    'retention' => [
        'detailed_logs_days' => env('COMMAND_TELEMETRY_DETAILED_RETENTION', 7),
        'summary_logs_days' => env('COMMAND_TELEMETRY_SUMMARY_RETENTION', 30),
        'metrics_days' => env('COMMAND_TELEMETRY_METRICS_RETENTION', 90),
    ],
];
