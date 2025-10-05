<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Tool Invocation Telemetry Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration for TELEMETRY-005: Enhanced Tool 
    | Invocation Correlation system that provides comprehensive observability 
    | into the tool execution ecosystem.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Enable Telemetry
    |--------------------------------------------------------------------------
    |
    | Enable or disable the tool telemetry system. When disabled, no 
    | telemetry data will be collected, but tools will still function normally.
    |
    */
    'enabled' => env('TOOL_TELEMETRY_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Configure which log channels to use for different types of telemetry data.
    |
    */
    'log_channels' => [
        'tool_execution' => env('TOOL_TELEMETRY_EXECUTION_CHANNEL', 'tool-telemetry'),
        'tool_performance' => env('TOOL_TELEMETRY_PERFORMANCE_CHANNEL', 'tool-telemetry'),
        'tool_errors' => env('TOOL_TELEMETRY_ERROR_CHANNEL', 'tool-telemetry'),
        'tool_health' => env('TOOL_TELEMETRY_HEALTH_CHANNEL', 'tool-telemetry'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Thresholds
    |--------------------------------------------------------------------------
    |
    | Define performance thresholds for tool execution alerting and classification.
    | All values are in milliseconds.
    |
    */
    'performance' => [
        'tool_thresholds' => [
            'fast' => 50,       // < 50ms
            'normal' => 200,    // 50-200ms
            'slow' => 1000,     // 200ms-1s
            'very_slow' => 3000, // 1s-3s
            // > 3s = critical
        ],
        'alert_thresholds' => [
            'slow_tool' => 3000,        // Alert if tool takes > 3s
            'memory_usage' => 128,      // Alert if memory usage > 128MB
            'payload_size' => 10,       // Alert if payload > 10MB
            'error_rate' => 0.05,       // Alert if error rate > 5%
            'consecutive_failures' => 3, // Alert after 3 consecutive failures
        ],
        'data_size_thresholds' => [
            'small_payload' => 1024,     // < 1KB
            'medium_payload' => 102400,  // 1KB-100KB  
            'large_payload' => 1048576,  // 100KB-1MB
            // > 1MB = very_large
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Tool-Specific Configuration
    |--------------------------------------------------------------------------
    |
    | Configure telemetry collection for specific tools.
    |
    */
    'tool_types' => [
        'db.query' => [
            'track_query_performance' => true,
            'track_result_counts' => true,
            'track_filter_complexity' => true,
        ],
        'memory.search' => [
            'track_search_performance' => true,
            'track_result_relevance' => true,
            'track_query_complexity' => true,
        ],
        'memory.write' => [
            'track_write_performance' => true,
            'track_data_size' => true,
            'track_embedding_time' => true,
        ],
        'export.generate' => [
            'track_generation_time' => true,
            'track_output_size' => true,
            'track_format_complexity' => true,
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
            '/api_key/i',
            '/private/i',
        ],
        'max_parameter_length' => 500,
        'max_output_length' => 1000,
        'hash_sensitive_values' => true,
        'redact_user_data' => true,
        'parameter_allowlist' => [
            'entity',
            'limit',
            'offset', 
            'format',
            'scope',
            'kind',
        ],
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
        'tool_execution' => env('TOOL_TELEMETRY_EXECUTION_SAMPLING', 1.0),
        'performance_events' => env('TOOL_TELEMETRY_PERFORMANCE_SAMPLING', 1.0),
        'success_events' => env('TOOL_TELEMETRY_SUCCESS_SAMPLING', 0.1),
        'error_events' => env('TOOL_TELEMETRY_ERROR_SAMPLING', 1.0),
        'health_checks' => env('TOOL_TELEMETRY_HEALTH_SAMPLING', 1.0),
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
        'include_tool_version' => true,
        'include_environment' => true,
        'include_git_commit' => false,
        'track_tool_chains' => true,
        'include_caller_context' => true,
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
        'tool_popularity' => true,
        'success_failure_rates' => true,
        'performance_percentiles' => true,
        'resource_utilization' => true,
        'error_categorization' => true,
        'usage_patterns' => true,
        'tool_correlation' => true,
        'parameter_patterns' => true,
        'availability_tracking' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Correlation Tracking
    |--------------------------------------------------------------------------
    |
    | Configure tool call correlation and chaining detection.
    |
    */
    'correlation' => [
        'track_tool_chains' => true,
        'track_nested_calls' => true,
        'chain_timeout_minutes' => 60,
        'max_chain_depth' => 10,
        'correlation_window_seconds' => 300, // 5 minutes
    ],

    /*
    |--------------------------------------------------------------------------
    | Health Monitoring
    |--------------------------------------------------------------------------
    |
    | Configure tool health and availability monitoring.
    |
    */
    'health' => [
        'enabled' => true,
        'check_interval_minutes' => 5,
        'failure_threshold' => 3,
        'recovery_threshold' => 2,
        'health_check_timeout_ms' => 1000,
        'monitor_dependencies' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Alert Configuration
    |--------------------------------------------------------------------------
    |
    | Configure alerting for tool issues and anomalies.
    |
    */
    'alerts' => [
        'enabled' => env('TOOL_TELEMETRY_ALERTS_ENABLED', true),
        'channels' => ['log', 'slack'], // Available: log, slack, email
        'rate_limiting' => [
            'max_alerts_per_hour' => 10,
            'duplicate_alert_window_minutes' => 30,
        ],
        'conditions' => [
            'high_error_rate' => true,
            'performance_degradation' => true,
            'availability_issues' => true,
            'resource_exhaustion' => true,
        ],
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
            'enabled' => env('TOOL_TELEMETRY_METRICS_ENABLED', false),
            'driver' => env('TOOL_TELEMETRY_METRICS_DRIVER', 'log'),
            'namespace' => 'tools.execution',
        ],
        'traces' => [
            'enabled' => env('TOOL_TELEMETRY_TRACES_ENABLED', false),
            'service_name' => 'tool-execution-pipeline',
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
        'enabled' => env('TOOL_TELEMETRY_DEBUG', false),
        'log_telemetry_overhead' => false,
        'verbose_parameter_logging' => false,
        'include_stack_traces' => false,
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
        'tool_chain_tracking' => true,
        'performance_alerts' => true,
        'health_monitoring' => true,
        'usage_analytics' => true,
        'parameter_analysis' => true,
        'error_pattern_detection' => true,
        'resource_tracking' => true,
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
        'detailed_logs_days' => env('TOOL_TELEMETRY_DETAILED_RETENTION', 7),
        'summary_logs_days' => env('TOOL_TELEMETRY_SUMMARY_RETENTION', 30),
        'metrics_days' => env('TOOL_TELEMETRY_METRICS_RETENTION', 90),
        'health_logs_days' => env('TOOL_TELEMETRY_HEALTH_RETENTION', 14),
    ],
];