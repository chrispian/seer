# TELEMETRY-004: Command & DSL Execution Metrics Implementation

## Overview

TELEMETRY-004 provides comprehensive observability for the command execution system, tracking performance, usage patterns, and errors across the entire command pipeline including DSL step execution.

## Features

### 🎯 Core Telemetry Capabilities

- **Command Execution Tracking**: Complete lifecycle monitoring from start to completion
- **DSL Step Metrics**: Individual step performance and execution tracking  
- **Template Rendering Performance**: Template engine cache efficiency and rendering times
- **Condition Evaluation**: Tracking condition evaluation performance and branch execution
- **AI Generation Metrics**: Token usage, prompt analysis, and response tracking
- **Error Categorization**: Intelligent error classification for better analytics
- **Performance Alerts**: Automatic alerting for slow operations
- **Resource Monitoring**: Memory usage and system resource tracking

### 📊 Analytics & Reporting

- **Command Popularity Analysis**: Usage patterns and frequency tracking
- **Performance Bottleneck Detection**: Identification of slow commands and steps
- **Success/Failure Rate Monitoring**: Reliability metrics across all commands
- **Template Cache Efficiency**: Cache hit rates and optimization opportunities
- **Error Pattern Analysis**: Trending error categories and hotspots

## Architecture

### Components

1. **CommandTelemetry** - Core telemetry logging service
2. **CommandTelemetryDecorator** - Non-invasive command runner wrapper
3. **StepTelemetryDecorator** - DSL step execution wrapper
4. **TemplateEngineTelemetryDecorator** - Template rendering performance wrapper
5. **CommandMetricsAnalyzer** - Analytics and reporting engine

### Integration Points

The telemetry system integrates seamlessly with existing infrastructure:

- **CommandRunner**: Wrapped with telemetry decorator
- **DSL Steps**: Automatically wrapped when enabled
- **TemplateEngine**: Performance tracking for rendering operations
- **CommandController**: Telemetry for both hardcoded and DSL commands
- **CorrelationContext**: Full correlation tracking across operations

## Configuration

### Enable/Disable Telemetry

```php
// config/command-telemetry.php
'enabled' => env('COMMAND_TELEMETRY_ENABLED', true),
```

### Performance Thresholds

```php
'performance' => [
    'command_thresholds' => [
        'fast' => 100,      // < 100ms
        'normal' => 500,    // 100-500ms
        'slow' => 2000,     // 500ms-2s
        'very_slow' => 5000, // 2s-5s
    ],
    'alert_thresholds' => [
        'slow_command' => 5000,     // Alert if > 5s
        'slow_step' => 3000,        // Alert if > 3s
        'template_rendering' => 500, // Alert if > 500ms
    ],
]
```

### Sampling Configuration

```php
'sampling' => [
    'command_execution' => 1.0,    // 100% sampling
    'step_execution' => 1.0,       // 100% sampling
    'template_rendering' => 0.1,   // 10% sampling
    'condition_evaluation' => 0.5, // 50% sampling
],
```

### Data Sanitization

```php
'sanitization' => [
    'sensitive_patterns' => [
        '/password/i',
        '/secret/i',
        '/token/i',
        '/key/i',
    ],
    'max_argument_length' => 500,
    'hash_sensitive_values' => true,
],
```

## Usage Examples

### Manual Telemetry Logging

```php
use App\Services\Telemetry\CommandTelemetry;

// Log command execution
CommandTelemetry::logCommandStart('my-command', $arguments);
CommandTelemetry::logCommandComplete('my-command', $arguments, $duration, $success);

// Log step execution
CommandTelemetry::logStepStart('ai.generate', 'step-123', $config);
CommandTelemetry::logStepComplete('ai.generate', 'step-123', $duration, $success);

// Log template rendering
CommandTelemetry::logTemplateRendering($template, $duration, $cacheHit, $stats);

// Log condition evaluation
CommandTelemetry::logConditionEvaluation($condition, $result, $duration, $branch);
```

### Analytics and Reporting

```php
use App\Services\Telemetry\CommandMetricsAnalyzer;

$analyzer = new CommandMetricsAnalyzer();

// Analyze command popularity
$popularity = $analyzer->analyzeCommandPopularity(7); // Last 7 days

// Find performance bottlenecks
$bottlenecks = $analyzer->analyzePerformanceBottlenecks(7);

// Analyze error patterns
$errors = $analyzer->analyzeErrorPatterns(7);

// Generate comprehensive report
$summary = $analyzer->generateSummary(7);
```

### Artisan Command for Reports

```bash
# Generate table report
php artisan telemetry:command-report --days=7

# Generate JSON report
php artisan telemetry:command-report --days=30 --format=json

# Save to file
php artisan telemetry:command-report --days=7 --output=report.txt
```

## Log Structure

### Command Execution Events

```json
{
  "event": "command.execution.started",
  "data": {
    "command": "fragment-create",
    "arguments": {"title": "Test", "content": "..."},
    "source_type": "dsl",
    "dry_run": false,
    "memory_usage_mb": 45.2
  },
  "meta": {
    "timestamp": "2024-10-04T12:00:00Z",
    "environment": "production",
    "memory_usage_mb": 45.2
  },
  "correlation": {
    "correlation_id": "cmd_abc123",
    "timestamp": "2024-10-04T12:00:00Z",
    "context": {
      "correlation_id": "cmd_abc123",
      "command_slug": "fragment-create",
      "execution_type": "dsl"
    }
  }
}
```

### Step Execution Events

```json
{
  "event": "command.step.completed",
  "data": {
    "step_type": "ai.generate",
    "step_id": "step-123",
    "duration_ms": 1250.5,
    "success": true,
    "performance_category": "slow",
    "memory_usage_mb": 48.7,
    "metrics": {
      "prompt_length": 150,
      "max_tokens": 500,
      "response_length": 420
    }
  }
}
```

### Template Rendering Events

```json
{
  "event": "command.template.rendered",
  "data": {
    "template_hash": "a1b2c3d4",
    "template_length": 245,
    "duration_ms": 15.3,
    "cache_hit": true,
    "performance_category": "fast",
    "stats": {
      "context_keys": ["user", "fragments"],
      "has_variables": true,
      "has_control_structures": false
    }
  }
}
```

## Performance Impact

### Minimized Overhead

- **Decorator Pattern**: No changes to core command logic
- **Sampling**: Configurable sampling rates for high-traffic scenarios
- **Lazy Evaluation**: Metrics calculated only when needed
- **Efficient Logging**: Structured logging with minimal serialization overhead

### Resource Usage

- **Memory**: < 1MB additional memory per command execution
- **CPU**: < 5ms additional processing time per command
- **Disk**: Configurable log retention and rotation

## Monitoring and Alerting

### Performance Alerts

The system automatically logs performance alerts when operations exceed configured thresholds:

- Slow command execution (> 5 seconds)
- Slow step execution (> 3 seconds)  
- Slow template rendering (> 500ms)
- High memory usage (> 256MB)

### Error Tracking

Errors are automatically categorized for better analytics:

- **timeout**: Operation timeouts
- **memory**: Memory-related errors
- **permission**: Authorization failures
- **not_found**: Missing resources
- **validation**: Input validation errors
- **network**: Connection issues
- **database**: Database-related errors

## Security Considerations

### Data Sanitization

- **Sensitive Data**: Automatic detection and redaction of sensitive fields
- **Argument Truncation**: Long arguments are truncated to prevent log bloat
- **Hash Sensitive Values**: Option to hash instead of redact sensitive data

### Access Control

- **Log File Permissions**: Restricted access to telemetry logs
- **Configuration**: Sensitive settings via environment variables
- **Sampling**: Reduced data collection in production environments

## Best Practices

### Configuration

1. **Enable in Development**: Full telemetry for debugging and optimization
2. **Sample in Production**: Reduce overhead with appropriate sampling rates
3. **Monitor Performance**: Regular analysis of bottlenecks and optimization opportunities
4. **Retention Policy**: Configure appropriate log retention based on storage capacity

### Analysis

1. **Regular Reports**: Generate weekly/monthly telemetry reports
2. **Threshold Tuning**: Adjust performance thresholds based on observed patterns
3. **Error Investigation**: Use error categorization to identify systemic issues
4. **Optimization**: Use analytics to guide performance improvements

## Integration with Existing Systems

### Fragment Telemetry

TELEMETRY-004 complements the existing fragment processing telemetry:

- **Shared Infrastructure**: Uses same correlation context and logging patterns
- **Compatible Configuration**: Similar configuration structure and options
- **Unified Analytics**: Can be analyzed together for comprehensive insights

### APM Integration

Ready for integration with Application Performance Monitoring tools:

- **Structured Logs**: JSON format compatible with log aggregators
- **Metrics Export**: Configurable export to metrics systems
- **Trace Correlation**: Full correlation ID support for distributed tracing

## Troubleshooting

### Common Issues

1. **High Log Volume**: Adjust sampling rates or increase retention periods
2. **Performance Impact**: Monitor telemetry overhead and optimize thresholds
3. **Missing Data**: Verify configuration and ensure telemetry is enabled
4. **Correlation Gaps**: Check middleware configuration and context propagation

### Debug Mode

Enable debug mode for detailed telemetry system behavior:

```php
'debug' => [
    'enabled' => true,
    'log_telemetry_overhead' => true,
    'verbose_step_tracking' => true,
],
```

## Future Enhancements

### Planned Features

- **Real-time Dashboards**: Web-based monitoring interface
- **Predictive Analytics**: ML-based performance prediction
- **Auto-optimization**: Automatic threshold adjustment based on patterns
- **Integration APIs**: REST APIs for external monitoring systems
- **Custom Metrics**: User-defined telemetry points and measurements