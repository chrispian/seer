# TELEMETRY-005: Enhanced Tool Invocation Correlation

## Overview

TELEMETRY-005 provides comprehensive observability for the tool execution ecosystem in Fragments Engine. This implementation tracks tool invocation timing, performance, success/failure rates, usage patterns, and provides health monitoring for all tools.

## Key Features

### 1. Comprehensive Tool Tracking
- **Invocation Timing**: Tracks start/end times with microsecond precision
- **Performance Metrics**: Categorizes operations (fast/normal/slow/critical)
- **Memory Usage**: Monitors memory consumption during tool execution
- **Parameter Analysis**: Analyzes input complexity and data size

### 2. Correlation and Chaining
- **Correlation Context**: Links tool calls across requests using correlation IDs
- **Tool Chains**: Tracks sequences of tool invocations
- **Nested Calls**: Detects and monitors nested tool invocations
- **Cross-tool Patterns**: Identifies common tool usage patterns

### 3. Health Monitoring
- **Availability Checks**: Regular health checks for all registered tools
- **Failure Detection**: Tracks consecutive failures and recovery
- **Status Tracking**: Maintains real-time health status for each tool
- **System Health**: Provides overall ecosystem health metrics

### 4. Privacy and Security
- **Sensitive Data Protection**: Automatically redacts/hashes sensitive parameters
- **Configurable Sanitization**: Customizable patterns for sensitive data detection
- **Allowlists**: Explicitly allowed parameters that bypass sanitization
- **Parameter Truncation**: Limits parameter size for privacy and performance

### 5. Performance Analysis
- **Response Time Percentiles**: P50, P95, P99 response time tracking
- **Performance Categorization**: Automatic classification of execution speed
- **Threshold Alerting**: Configurable alerts for slow operations
- **Resource Monitoring**: Memory usage and resource consumption tracking

## Architecture

### Core Components

#### 1. ToolTelemetry Service
**Location**: `app/Services/Telemetry/ToolTelemetry.php`

The main telemetry collection service that:
- Starts and completes invocation tracking
- Sanitizes sensitive parameters
- Tracks performance metrics
- Records health status
- Manages correlation context

#### 2. ToolTelemetryDecorator
**Location**: `app/Decorators/ToolTelemetryDecorator.php`

Transparent decorator that:
- Wraps all tool executions with telemetry
- Maintains tool interface compatibility
- Handles exceptions and error logging
- Provides zero-impact integration

#### 3. ToolHealthMonitor
**Location**: `app/Services/Telemetry/ToolHealthMonitor.php`

Health monitoring service that:
- Performs regular health checks
- Validates tool functionality
- Tracks availability metrics
- Generates health reports

#### 4. ToolMetricsAnalyzer
**Location**: `app/Services/Telemetry/ToolMetricsAnalyzer.php`

Analytics service that:
- Analyzes historical telemetry data
- Generates performance reports
- Identifies usage patterns
- Detects error trends

### Integration Points

#### 1. Automatic Tool Wrapping
**Location**: `app/Support/ToolRegistry.php`

```php
public function register(ToolContract $tool): void
{
    // Automatically wrap tools with telemetry if enabled
    if (config('tool-telemetry.enabled', true)) {
        $tool = ToolTelemetryDecorator::wrap($tool);
    }
    
    $this->tools[$tool->name()] = $tool;
}
```

#### 2. Middleware Integration
**Location**: `app/Http/Middleware/ToolTelemetryMiddleware.php`

Middleware that:
- Sets up correlation context for each request
- Adds request metadata to correlation
- Ensures proper context cleanup

#### 3. Service Registration
**Location**: `app/Providers/ToolTelemetryServiceProvider.php`

Registers services and:
- Publishes configuration
- Schedules health checks
- Registers middleware

## Configuration

### Main Configuration
**Location**: `config/tool-telemetry.php`

Key configuration sections:

#### Performance Thresholds
```php
'performance' => [
    'tool_thresholds' => [
        'fast' => 50,       // < 50ms
        'normal' => 200,    // 50-200ms
        'slow' => 1000,     // 200ms-1s
        'very_slow' => 3000, // 1s-3s
    ],
    'alert_thresholds' => [
        'slow_tool' => 3000,        // Alert if tool takes > 3s
        'memory_usage' => 128,      // Alert if memory usage > 128MB
        'error_rate' => 0.05,       // Alert if error rate > 5%
    ],
]
```

#### Data Sanitization
```php
'sanitization' => [
    'sensitive_patterns' => [
        '/password/i',
        '/secret/i',
        '/token/i',
        '/key/i',
    ],
    'max_parameter_length' => 500,
    'parameter_allowlist' => [
        'entity',
        'limit',
        'offset',
    ],
]
```

#### Health Monitoring
```php
'health' => [
    'enabled' => true,
    'check_interval_minutes' => 5,
    'failure_threshold' => 3,
    'recovery_threshold' => 2,
]
```

## Usage

### Automatic Integration

The telemetry system automatically wraps all tools registered in the ToolRegistry. No code changes are required for existing tools.

### Manual Health Checks

```bash
# Check all tools
php artisan tools:health-check

# Check specific tool
php artisan tools:health-check --tool=db.query

# Output as JSON
php artisan tools:health-check --format=json
```

### Generate Reports

```bash
# Generate comprehensive telemetry report
php artisan telemetry:tool-report

# Analyze last 30 days
php artisan telemetry:tool-report --days=30

# Save to file
php artisan telemetry:tool-report --output=tool-report.txt

# JSON format
php artisan telemetry:tool-report --format=json
```

## Logging

### Log Channels

#### Tool Telemetry Log
**Location**: `storage/logs/tool-telemetry.log`

Contains all tool execution events:
- `tool.invocation.started`
- `tool.invocation.completed`
- `tool.invocation.failed`
- `tool.health.check`
- `tool.health.status_changed`
- `tool.health.summary`

#### Sample Log Entry
```json
{
  "event": "tool.invocation.completed",
  "invocation_id": "01234567-89ab-cdef-0123-456789abcdef",
  "tool_name": "db.query",
  "tool_scope": "read/db.query",
  "started_at": "2025-01-04T10:30:00.000000Z",
  "completed_at": "2025-01-04T10:30:00.150000Z",
  "duration_ms": 150.25,
  "memory_used": 2048576,
  "performance_category": "normal",
  "success": true,
  "parameters": {
    "entity": "work_items",
    "limit": 50,
    "filters": "[TRUNCATED_ARRAY:3_items]"
  },
  "result_stats": {
    "size_bytes": 15420,
    "record_count": 25
  },
  "correlation": {
    "correlation_id": "req-01234567-89ab-cdef-0123-456789abcdef",
    "timestamp": "2025-01-04T10:30:00.000000Z"
  }
}
```

## Monitoring and Alerts

### Performance Alerts

The system automatically generates alerts for:
- Tools exceeding performance thresholds
- High error rates
- Consecutive failures
- Memory usage spikes

### Health Monitoring

Regular health checks verify:
- Tool availability
- Response times
- Functionality validation
- Dependency health

### System Health Metrics

Overall system health includes:
- Percentage of healthy tools
- Total tool availability
- Error rates by tool
- Performance trends

## Performance Impact

### Minimal Overhead
- Sampling rates configurable per event type
- Efficient parameter sanitization
- Asynchronous logging
- Memory-conscious data structures

### Production Recommendations
```php
'sampling' => [
    'tool_execution' => 1.0,        // Always track executions
    'success_events' => 0.1,        // Sample 10% of successes
    'error_events' => 1.0,          // Always track errors
    'performance_events' => 1.0,    // Always track performance
]
```

## Testing

### Test Coverage

The implementation includes comprehensive tests:

#### Unit Tests
- `tests/Feature/Telemetry/ToolTelemetryTest.php`
- `tests/Feature/Telemetry/ToolHealthMonitorTest.php`

#### Test Scenarios
- Tool invocation start/completion tracking
- Error handling and failure logging
- Parameter sanitization
- Correlation context tracking
- Health monitoring
- Performance categorization
- Configuration handling

### Running Tests

```bash
# Run all telemetry tests
php artisan test tests/Feature/Telemetry/

# Run specific test
php artisan test tests/Feature/Telemetry/ToolTelemetryTest.php
```

## Development Guide

### Adding New Tool-Specific Metrics

1. Update configuration in `config/tool-telemetry.php`:
```php
'tool_types' => [
    'new.tool' => [
        'track_custom_metric' => true,
        'track_specific_data' => true,
    ],
]
```

2. Implement metric extraction in `ToolTelemetry::extractToolMetrics()`:
```php
case 'new.tool':
    if ($toolConfig['track_custom_metric'] ?? false) {
        $metrics['custom_value'] = $this->calculateCustomMetric($parameters);
    }
    break;
```

### Custom Health Checks

Add tool-specific health validation in `ToolHealthMonitor::performHealthCheck()`:

```php
case 'new.tool':
    $this->checkNewTool($tool);
    break;
```

### Extending Analytics

Add new analysis methods to `ToolMetricsAnalyzer` for custom insights:

```php
public function analyzeCustomPattern(int $days = 7): array
{
    // Custom analysis logic
}
```

## Security Considerations

### Sensitive Data Protection

1. **Automatic Sanitization**: Configured patterns automatically detect and redact sensitive data
2. **Hash-based Redaction**: Sensitive values are hashed for consistent tracking without exposure
3. **Allowlist-based Filtering**: Only explicitly allowed parameters bypass sanitization
4. **Size Limits**: Parameter and output size limits prevent excessive data logging

### Configuration Security

1. **Environment-based Toggles**: Production settings via environment variables
2. **Sampling Controls**: Reduce data collection in sensitive environments
3. **Channel Separation**: Different log channels for different sensitivity levels

## Troubleshooting

### Common Issues

#### Telemetry Not Working
1. Check `tool-telemetry.enabled` configuration
2. Verify service provider registration
3. Check log channel configuration
4. Validate tool registration in ToolRegistry

#### Performance Impact
1. Adjust sampling rates in configuration
2. Increase parameter length limits if needed
3. Review sensitive pattern complexity
4. Monitor telemetry overhead logs

#### Missing Health Checks
1. Verify `tool-telemetry.health.enabled` setting
2. Check scheduled task registration
3. Validate tool registry contains expected tools
4. Review health check timeout configuration

### Debug Mode

Enable debug mode for detailed telemetry insights:

```php
'debug' => [
    'enabled' => true,
    'log_telemetry_overhead' => true,
    'verbose_parameter_logging' => true,
]
```

## Future Enhancements

### Planned Features

1. **Real-time Dashboards**: Web-based monitoring interface
2. **Predictive Analytics**: ML-based performance prediction
3. **Auto-scaling Triggers**: Integration with infrastructure scaling
4. **Advanced Correlation**: Cross-request tool usage patterns
5. **External Integrations**: Metrics export to APM tools

### Extensibility Points

1. **Custom Telemetry Collectors**: Plugin architecture for additional metrics
2. **Alert Channels**: Support for Slack, email, webhooks
3. **Data Exporters**: Integration with external analytics platforms
4. **Custom Health Checks**: Tool-specific validation logic