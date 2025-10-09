# TELEMETRY-006: Local Telemetry Sink & Query Interface

## Overview

TELEMETRY-006 implements a unified telemetry data management system that provides:

1. **Local Telemetry Data Storage** - Centralized database storage for all telemetry data
2. **Fast Querying Capabilities** - Optimized queries for debugging and analysis
3. **Time-Series Data Management** - Efficient handling of time-based telemetry data
4. **Aggregation & Analytics** - Built-in metrics aggregation and analysis tools
5. **Data Retention & Cleanup** - Automated data lifecycle management
6. **Query Interface** - CLI commands for data access and analysis
7. **Export Capabilities** - Data export for external analysis tools

## Architecture

### Database Schema

The system uses 6 main tables:

- **telemetry_events** - Main events from all telemetry systems (tools, commands, fragments, chat)
- **telemetry_metrics** - Aggregated metrics and counters
- **telemetry_health_checks** - System health monitoring results
- **telemetry_performance_snapshots** - Performance measurements and trends
- **telemetry_correlation_chains** - Correlation tracking across components
- **telemetry_alerts** - Alert definitions and status (future use)

### Core Services

1. **TelemetrySink** - Central data storage service with buffering and async processing
2. **TelemetryQueryService** - Advanced querying and analytics service
3. **TelemetryAdapter** - Adapter layer for existing telemetry systems
4. **TelemetryServiceProvider** - Service registration and configuration

## CLI Commands

### Data Querying

```bash
# Query recent events
php artisan telemetry:query --time-range=24h --limit=100

# Query specific component errors
php artisan telemetry:query --component=tool_telemetry --level=error

# Query by correlation ID
php artisan telemetry:query --correlation-id=corr_12345

# Export query results
php artisan telemetry:query --export=/path/to/results.json --format=json

# Query metrics
php artisan telemetry:query --type=metrics --component=fragment_telemetry

# Query health status
php artisan telemetry:query --type=health

# Query performance data
php artisan telemetry:query --type=performance --time-range=6h

# Query correlation chains
php artisan telemetry:query --type=chains --correlation-id=chain_id
```

### Health Monitoring

```bash
# Single health report
php artisan telemetry:health

# Watch mode (continuous monitoring)
php artisan telemetry:health --watch

# Focus on specific component
php artisan telemetry:health --component=tool_telemetry

# JSON output for automation
php artisan telemetry:health --format=json
```

### Data Export

```bash
# Export recent data
php artisan telemetry:export --time-range=7d --format=json

# Export with compression
php artisan telemetry:export --compress --format=csv

# Split export by component
php artisan telemetry:export --split-by=component --time-range=24h

# Include all data types
php artisan telemetry:export --include-metrics --include-health --include-performance

# Export specific component
php artisan telemetry:export --component=fragment_telemetry --format=csv
```

### Data Cleanup

```bash
# Dry run cleanup with default retention policies
php artisan telemetry:cleanup --dry-run

# Cleanup data older than custom period
php artisan telemetry:cleanup --older-than=30d --force

# Cleanup specific component
php artisan telemetry:cleanup --component=tool_telemetry --dry-run

# View current table sizes
php artisan telemetry:cleanup --dry-run
```

### Test Data Generation

```bash
# Generate sample data for testing
php artisan telemetry:test --count=50

# Generate data for specific components
php artisan telemetry:test --count=25 --components=tool,command
```

## Configuration

### Main Configuration (`config/telemetry.php`)

Key configuration options:

```php
'enabled' => true,                    // Enable/disable telemetry sink
'storage.driver' => 'database',       // Storage backend
'storage.async_processing' => true,   // Async vs sync processing
'storage.batch_size' => 100,          // Batch processing size

'retention' => [
    'raw_events_days' => 14,           // Raw event retention
    'aggregated_metrics_days' => 90,   // Metrics retention
    'health_checks_days' => 30,        // Health check retention
    'performance_snapshots_days' => 30, // Performance data retention
],

'query' => [
    'default_limit' => 100,            // Default query limit
    'max_limit' => 10000,              // Maximum query limit
    'cache_ttl_minutes' => 15,         // Query result caching
],

'export' => [
    'formats' => ['json', 'csv'],      // Available export formats
    'max_export_size_mb' => 100,       // Maximum export file size
    'compression' => true,             // Enable compression
]
```

### Integration with Existing Systems

The system automatically captures telemetry from existing systems:

- **Tool Telemetry** (TELEMETRY-005) - Tool execution metrics
- **Command Telemetry** (TELEMETRY-004) - Command execution data
- **Fragment Telemetry** (TELEMETRY-003) - Fragment processing data
- **Chat Telemetry** - AI chat interaction data

## Usage Examples

### 1. Debugging Tool Performance Issues

```bash
# Find slow tool executions
php artisan telemetry:query --type=performance --time-range=24h | grep slow

# Get detailed tool events
php artisan telemetry:query --event-type=tool --time-range=6h --format=json

# Export tool performance data
php artisan telemetry:export --component=tool_telemetry --include-performance
```

### 2. System Health Monitoring

```bash
# Check overall system health
php artisan telemetry:health

# Monitor system in real-time
php artisan telemetry:health --watch

# Check health for automation
php artisan telemetry:health --format=json | jq .overall_health
```

### 3. Error Analysis

```bash
# Find all recent errors
php artisan telemetry:query --level=error --time-range=24h

# Analyze error patterns
php artisan telemetry:query --type=stats --time-range=7d

# Export error data for analysis
php artisan telemetry:export --level=error --format=csv
```

### 4. Correlation Tracking

```bash
# Follow a specific correlation chain
php artisan telemetry:query --correlation-id=corr_abc123 --format=json

# Analyze correlation chains
php artisan telemetry:query --type=chains --time-range=1h
```

## Performance Considerations

### Async Processing
- Events are buffered and processed asynchronously by default
- Buffer size and flush intervals are configurable
- Synchronous processing available for testing and low-traffic scenarios

### Database Optimization
- Comprehensive indexing on frequently queried columns
- Time-based partitioning for large datasets (future enhancement)
- Query result caching for expensive analytics queries

### Storage Management
- Automated cleanup based on retention policies
- Configurable data retention by component and data type
- Storage usage monitoring and reporting

## Monitoring & Alerting

### Built-in Health Checks
- Buffer overflow detection
- Processing lag monitoring  
- Storage space monitoring
- Error rate tracking

### Scheduled Tasks
- Daily cleanup of old data
- Periodic buffer flushing
- System health checks every 5 minutes

## Future Enhancements

1. **Real-time Dashboard** - Web-based monitoring interface
2. **Advanced Analytics** - Machine learning for anomaly detection
3. **External Integrations** - Export to APM tools, metrics systems
4. **Alerting System** - Configurable alerts and notifications
5. **Data Visualization** - Charts and graphs for trend analysis

## Testing

The system includes comprehensive testing capabilities:

```bash
# Generate test data
php artisan telemetry:test --count=100

# Test all CLI commands
php artisan telemetry:health
php artisan telemetry:query --limit=10
php artisan telemetry:export --time-range=1h
php artisan telemetry:cleanup --dry-run
```

## Database Setup

```bash
# Run migrations
php artisan migrate

# Check table creation
php artisan tinker --execute="
echo 'Events: ' . \App\Models\TelemetryEvent::count() . '\n';
echo 'Metrics: ' . \App\Models\TelemetryMetric::count() . '\n';
"
```

This implementation provides a robust, scalable foundation for unified telemetry data management across the entire application.