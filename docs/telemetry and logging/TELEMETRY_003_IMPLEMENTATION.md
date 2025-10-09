# TELEMETRY-003: Fragment Processing Telemetry Decorator

## Overview

The Fragment Processing Telemetry Decorator provides comprehensive observability for all fragment processing operations in the Seer application. This implementation creates a reusable decorator pattern that enhances any fragment processing action with timing, correlation tracking, performance metrics, structured logging, and error handling.

## Architecture

### Core Components

1. **TelemetryPipelineDecorator** (`app/Decorators/TelemetryPipelineDecorator.php`)
   - Wraps individual fragment processing actions
   - Provides timing and correlation context
   - Handles both pipeline and direct execution modes
   - Logs structured telemetry data

2. **FragmentProcessingTelemetry** (`app/Services/Telemetry/FragmentProcessingTelemetry.php`)
   - Centralized telemetry logging service
   - Pipeline lifecycle tracking
   - Performance classification and alerting
   - Fragment state change detection

3. **TelemetryPipelineBuilder** (`app/Services/Telemetry/TelemetryPipelineBuilder.php`)
   - Fluent API for building telemetry-enabled pipelines
   - Preset pipeline configurations
   - Global context management
   - Easy integration with existing code

4. **ProcessFragmentJobWithTelemetry** (`app/Jobs/ProcessFragmentJobWithTelemetry.php`)
   - Enhanced version of ProcessFragmentJob with full telemetry
   - Step-by-step tracking
   - Performance alerts
   - Memory usage monitoring

## Features

### 🎯 Core Capabilities

- **Action Wrapping**: Decorates any fragment processing action with telemetry
- **Pipeline Tracking**: Monitors entire fragment processing pipelines
- **Performance Metrics**: Timing, memory usage, and classification
- **Error Handling**: Comprehensive error context and recovery tracking
- **Correlation Context**: Request/job correlation across distributed processing
- **State Tracking**: Monitors fragment changes through processing steps

### 📊 Telemetry Data

Each processing operation captures:
- Step execution timing
- Memory usage (current and peak)
- Fragment state changes
- Error context and stack traces
- Performance tier classification
- Correlation IDs for distributed tracing

### 🚀 Performance Classification

- **Fast**: < 100ms (steps), < 1s (pipelines)
- **Normal**: 100-500ms (steps), 1-5s (pipelines)
- **Slow**: 500ms-2s (steps), 5-15s (pipelines)
- **Very Slow**: > 2s (steps), > 15s (pipelines)

## Usage Examples

### Basic Action Wrapping

```php
use App\Decorators\TelemetryPipelineDecorator;

$parseAction = app(\App\Actions\ParseAtomicFragment::class);
$decoratedAction = TelemetryPipelineDecorator::wrap($parseAction, 'custom_parse');
$result = $decoratedAction($fragment);
```

### Pipeline Builder (Recommended)

```php
use App\Services\Telemetry\TelemetryPipelineBuilder;

// Standard processing pipeline
$result = TelemetryPipelineBuilder::standard()
    ->withContext(['source' => 'api', 'user_id' => auth()->id()])
    ->process($fragment);

// Custom pipeline
$result = TelemetryPipelineBuilder::create()
    ->addStep(\App\Actions\ParseAtomicFragment::class)
    ->addStep(\App\Actions\EnrichFragmentWithAI::class, 'ai_enrichment', ['model' => 'fast'])
    ->withContext(['pipeline_type' => 'custom'])
    ->process($fragment);

// Single action execution
$result = TelemetryPipelineBuilder::executeAction(
    \App\Actions\GenerateAutoTitle::class,
    $fragment,
    ['operation' => 'title_generation']
);
```

### Preset Pipelines

```php
// Full processing (all 9 steps)
TelemetryPipelineBuilder::standard()->process($fragment);

// Lightweight processing (4 essential steps)
TelemetryPipelineBuilder::lightweight()->process($fragment);

// AI-focused processing
TelemetryPipelineBuilder::aiEnrichment()->process($fragment);
```

### Job Integration

```php
use App\Jobs\ProcessFragmentJobWithTelemetry;

// Dispatch with telemetry
ProcessFragmentJobWithTelemetry::dispatch($fragment)
    ->withCorrelationContext();
```

## Configuration

### Environment Variables

```env
FRAGMENT_TELEMETRY_ENABLED=true
FRAGMENT_TELEMETRY_PIPELINE_CHANNEL=fragment-processing-telemetry
FRAGMENT_TELEMETRY_PIPELINE_SAMPLING=1.0
FRAGMENT_TELEMETRY_STEP_SAMPLING=1.0
FRAGMENT_TELEMETRY_DEBUG=false
```

### Configuration File

The system is configured via `config/fragment-telemetry.php`:

```php
return [
    'enabled' => env('FRAGMENT_TELEMETRY_ENABLED', true),
    'performance' => [
        'alert_thresholds' => [
            'slow_step' => 5000,        // 5s
            'slow_pipeline' => 30000,   // 30s
            'memory_usage' => 512,      // 512MB
        ],
    ],
    'sampling' => [
        'pipeline_events' => 1.0,   // 100% sampling
        'step_events' => 1.0,
    ],
];
```

## Log Output Structure

### Step Execution Logs

```json
{
  "event": "fragment.processing.step.completed",
  "data": {
    "step_id": "uuid",
    "step_name": "ParseAtomicFragment",
    "fragment_id": "fragment-uuid",
    "duration_ms": 45.23,
    "success": true,
    "performance_tier": "fast",
    "memory_usage_mb": 128.5,
    "fragment_changed": true
  },
  "correlation": {
    "correlation_id": "request-uuid",
    "timestamp": "2024-01-01T12:00:00Z"
  }
}
```

### Pipeline Logs

```json
{
  "event": "fragment.processing.pipeline.completed",
  "data": {
    "pipeline_id": "pipeline-uuid",
    "fragment_id": "fragment-uuid",
    "duration_ms": 1234.56,
    "performance_tier": "normal",
    "step_count": 9,
    "slowest_step": {
      "step_name": "EnrichFragmentWithAI",
      "duration_ms": 890.12
    }
  }
}
```

## Integration with Existing Pipeline

The telemetry system is designed to integrate seamlessly with the existing fragment processing infrastructure:

### Current Pipeline (ProcessFragmentJob:65-78)
```php
$processed = app(Pipeline::class)
    ->send($this->fragment)
    ->through([
        \App\Actions\DriftSync::class,
        \App\Actions\ParseAtomicFragment::class,
        // ... other actions
    ])
    ->thenReturn();
```

### Enhanced Pipeline (with telemetry)
```php
$processed = TelemetryPipelineBuilder::standard()
    ->withContext(['job_id' => $this->job->getJobId()])
    ->process($this->fragment);
```

## File Structure

```
app/
├── Decorators/
│   └── TelemetryPipelineDecorator.php          # Core decorator
├── Services/Telemetry/
│   ├── FragmentProcessingTelemetry.php         # Telemetry service
│   └── TelemetryPipelineBuilder.php            # Builder API
├── Jobs/
│   └── ProcessFragmentJobWithTelemetry.php     # Enhanced job
config/
└── fragment-telemetry.php                      # Configuration
examples/
└── fragment_processing_telemetry_usage.php     # Usage examples
tests/Feature/
└── FragmentProcessingTelemetryTest.php         # Comprehensive tests
docs/
└── TELEMETRY_003_IMPLEMENTATION.md             # This document
```

## Performance Impact

The telemetry system is designed for minimal performance overhead:

- **Decorator Pattern**: Low-overhead wrapping of existing actions
- **Sampling Support**: Configurable sampling rates for high-traffic environments
- **Async Logging**: Non-blocking log operations
- **Memory Efficient**: Minimal memory footprint during processing
- **Conditional Execution**: Easy enable/disable for testing/production

## Testing

Run the comprehensive test suite:

```bash
php artisan test tests/Feature/FragmentProcessingTelemetryTest.php
```

The test suite covers:
- Single action wrapping
- Pipeline execution
- Error handling
- Performance metrics
- Correlation context
- Memory tracking
- State change detection

## Production Considerations

### Sampling in High-Traffic Environments

```php
// config/fragment-telemetry.php (production)
'sampling' => [
    'pipeline_events' => 0.1,  // 10% sampling
    'step_events' => 0.05,     // 5% sampling
],
```

### Log Rotation

The system uses Laravel's daily log rotation:
- Logs rotate daily
- Configurable retention (default: 14 days)
- Separate channel for fragment processing telemetry

### Monitoring Integration

The structured log format enables easy integration with:
- ELK Stack (Elasticsearch, Logstash, Kibana)
- Splunk
- DataDog
- Custom metrics collectors

## Benefits

1. **Enhanced Observability**: Complete visibility into fragment processing
2. **Performance Monitoring**: Identify bottlenecks and optimization opportunities
3. **Error Tracking**: Comprehensive error context for debugging
4. **Correlation Tracking**: Trace fragments across distributed processing
5. **Zero Code Changes**: Wrap existing actions without modification
6. **Flexible Configuration**: Adaptable to different environments and requirements
7. **Production Ready**: Designed for high-traffic production environments

## Future Enhancements

- Metrics export to Prometheus/StatsD
- Distributed tracing integration (Jaeger/Zipkin)
- Real-time performance dashboards
- Automated alerting for SLA violations
- ML-based anomaly detection
- A/B testing framework for pipeline optimization