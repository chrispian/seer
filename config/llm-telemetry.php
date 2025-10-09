<?php

return [
    /*
    |--------------------------------------------------------------------------
    | LLM Telemetry Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Large Language Model telemetry tracking and analytics.
    | This system provides comprehensive observability for AI operations.
    |
    */

    'enabled' => env('LLM_TELEMETRY_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Log Channel
    |--------------------------------------------------------------------------
    |
    | The log channel to use for LLM telemetry events. This should match
    | the channel defined in config/logging.php
    |
    */
    'log_channel' => env('LLM_TELEMETRY_CHANNEL', 'llm-telemetry'),

    /*
    |--------------------------------------------------------------------------
    | Sampling Rates
    |--------------------------------------------------------------------------
    |
    | Control the percentage of events to log to manage performance impact.
    | Values should be between 0.0 (no logging) and 1.0 (100% logging).
    |
    */
    'sampling' => [
        'llm_calls' => env('LLM_SAMPLING_CALLS', 1.0),           // 100% of LLM calls
        'llm_errors' => env('LLM_SAMPLING_ERRORS', 1.0),          // 100% of errors
        'performance_metrics' => env('LLM_SAMPLING_PERFORMANCE', 1.0), // 100% of performance data
        'cost_tracking' => env('LLM_SAMPLING_COST', 1.0),         // 100% of cost data
        'model_selection' => env('LLM_SAMPLING_SELECTION', 1.0),  // 100% of model selections
        'streaming_events' => env('LLM_SAMPLING_STREAMING', 0.1), // 10% of streaming events
        'quality_metrics' => env('LLM_SAMPLING_QUALITY', 0.5),    // 50% of quality metrics
        'health_checks' => env('LLM_SAMPLING_HEALTH', 1.0),       // 100% of health checks
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Thresholds
    |--------------------------------------------------------------------------
    |
    | Thresholds for categorizing LLM performance and triggering alerts.
    | All times are in milliseconds.
    |
    */
    'performance_thresholds' => [
        'excellent_latency' => 1000,    // < 1s
        'good_latency' => 3000,         // 1-3s
        'fair_latency' => 10000,        // 3-10s
        'poor_latency' => 30000,        // > 30s

        'fast_tokens_per_second' => 50, // > 50 tokens/sec
        'normal_tokens_per_second' => 20, // 20-50 tokens/sec
        'slow_tokens_per_second' => 5,  // < 5 tokens/sec

        'high_cost_threshold' => 0.10,  // Alert if call costs > $0.10
        'budget_alert_threshold' => 0.80, // Alert when 80% of budget used
    ],

    /*
    |--------------------------------------------------------------------------
    | Quality Metrics
    |--------------------------------------------------------------------------
    |
    | Configuration for LLM response quality assessment and monitoring.
    |
    */
    'quality' => [
        'enabled' => env('LLM_QUALITY_ENABLED', true),
        'confidence_threshold' => env('LLM_QUALITY_CONFIDENCE_THRESHOLD', 0.7),
        'min_sample_size' => env('LLM_QUALITY_MIN_SAMPLE_SIZE', 10),

        // Quality scoring weights
        'weights' => [
            'relevance' => 0.3,
            'accuracy' => 0.3,
            'completeness' => 0.2,
            'clarity' => 0.2,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cost Management
    |--------------------------------------------------------------------------
    |
    | Configuration for LLM cost tracking and budget management.
    |
    */
    'cost_management' => [
        'enabled' => env('LLM_COST_MANAGEMENT_ENABLED', true),
        'currency' => env('LLM_COST_CURRENCY', 'USD'),

        // Budget limits (monthly)
        'budgets' => [
            'monthly_limit' => env('LLM_MONTHLY_BUDGET', null), // null = no limit
            'per_user_limit' => env('LLM_PER_USER_BUDGET', null),
            'per_model_limit' => env('LLM_PER_MODEL_BUDGET', null),
        ],

        // Cost alerting
        'alerts' => [
            'enabled' => env('LLM_COST_ALERTS_ENABLED', true),
            'thresholds' => [
                'warning' => 0.75,  // Alert at 75% of budget
                'critical' => 0.90, // Alert at 90% of budget
            ],
            'channels' => ['log', 'notification'], // Where to send alerts
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Analytics & Reporting
    |--------------------------------------------------------------------------
    |
    | Configuration for LLM analytics and automated reporting.
    |
    */
    'analytics' => [
        'enabled' => env('LLM_ANALYTICS_ENABLED', true),

        // Automated report generation
        'reports' => [
            'daily' => [
                'enabled' => env('LLM_DAILY_REPORTS', true),
                'recipients' => env('LLM_DAILY_REPORT_RECIPIENTS', ''),
            ],
            'weekly' => [
                'enabled' => env('LLM_WEEKLY_REPORTS', true),
                'recipients' => env('LLM_WEEKLY_REPORT_RECIPIENTS', ''),
            ],
            'monthly' => [
                'enabled' => env('LLM_MONTHLY_REPORTS', true),
                'recipients' => env('LLM_MONTHLY_REPORT_RECIPIENTS', ''),
            ],
        ],

        // Performance tracking
        'performance_tracking' => [
            'enabled' => env('LLM_PERFORMANCE_TRACKING', true),
            'metrics_retention_days' => env('LLM_METRICS_RETENTION_DAYS', 90),
            'anomaly_detection' => env('LLM_ANOMALY_DETECTION', true),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Privacy & Security
    |--------------------------------------------------------------------------
    |
    | Configuration for protecting sensitive data in LLM telemetry.
    |
    */
    'privacy' => [
        'mask_sensitive_content' => env('LLM_MASK_SENSITIVE_CONTENT', true),
        'max_prompt_length_logging' => env('LLM_MAX_PROMPT_LENGTH_LOGGING', 1000),
        'exclude_fields' => [
            'api_keys',
            'auth_tokens',
            'passwords',
            'personal_data',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Debugging & Development
    |--------------------------------------------------------------------------
    |
    | Configuration for development and debugging features.
    |
    */
    'debug' => [
        'enabled' => env('LLM_DEBUG_ENABLED', false),
        'verbose_logging' => env('LLM_VERBOSE_LOGGING', false),
        'performance_overhead_tracking' => env('LLM_PERFORMANCE_OVERHEAD_TRACKING', false),
        'test_mode' => env('LLM_TEST_MODE', false),
    ],
];