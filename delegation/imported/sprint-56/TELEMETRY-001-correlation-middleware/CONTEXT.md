# TELEMETRY-001: Request Correlation Middleware - Context

## Current State Analysis

### Existing Middleware Stack
- `EnsureDefaultUser` - Auto-login for single user (NativePHP)
- `EnsureUserSetupComplete` - Setup wizard redirection
- No request correlation or distributed tracing infrastructure

### Logging Infrastructure
- Laravel Log facade with Monolog backend
- `app/Services/Metrics/LogMetrics.php` - Basic metrics logging
- Ad-hoc logging throughout controllers and services
- Missing request-scoped context in multi-step operations

### Key Integration Points

**HTTP Controllers**:
- `app/Http/Controllers/ChatApiController.php` - Chat message handling
- `app/Http/Controllers/CommandController.php` - Command execution
- `app/Http/Controllers/FragmentController.php` - Fragment operations

**Async Processing**:
- `app/Jobs/ProcessFragmentJob.php` - Fragment processing pipeline
- `app/Jobs/RunScheduledCommandJob.php` - Scheduled commands
- Need correlation ID propagation to job context

**Current Logging Patterns**:
```php
// Scattered throughout codebase
Log::info('Operation completed', ['fragment_id' => $id]);
Log::error('Command failed', ['error' => $e->getMessage()]);
```

## Requirements

### Functional Requirements
1. **Correlation ID Generation**: Unique UUID per HTTP request
2. **Context Propagation**: Available in all downstream operations
3. **Log Integration**: Automatic inclusion in all log entries
4. **Job Propagation**: Correlation ID passes to queued jobs
5. **Zero Content**: No request content logged, only metadata

### Non-Functional Requirements
- **Performance**: <1ms overhead per request
- **Compatibility**: Works with existing middleware stack
- **Privacy**: No PII or request content captured
- **Reliability**: Must not break existing functionality

### Integration Constraints
- Single local user environment (NativePHP)
- No external telemetry systems
- Must work with existing Laravel 12 infrastructure
- Maintain compatibility with queue workers

## Architecture Approach

### Middleware Design
```php
// app/Http/Middleware/InjectCorrelationId.php
class InjectCorrelationId implements MiddlewareInterface
{
    // Generate UUID and inject into request attributes
    // Add to Laravel Log context for automatic inclusion
    // Handle both web and API routes
}
```

### Context Propagation Strategy
1. **Request Attributes**: Store correlation ID in request
2. **Log Context**: Laravel Log::withContext() for automatic inclusion
3. **Job Dispatch**: Pass correlation ID to job payload
4. **Service Injection**: Available via request() helper globally

### Testing Strategy
- Unit tests for middleware functionality
- Integration tests with existing controllers
- Performance tests for overhead measurement
- End-to-end correlation tracking validation