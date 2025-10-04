# TELEMETRY-004: Command & DSL Execution Metrics - Context

## Current State Analysis

### Command Controller Issues
**File**: `app/Http/Controllers/CommandController.php:14-220`
- Only logs failures (line 125): `\Log::error('Command execution failed')`
- No structured logging for successful command executions
- Missing command metadata (slug, execution ID, duration)
- No correlation with downstream tool invocations

### DSL Runner Issues  
**File**: `app/Services/Commands/DSL/CommandRunner.php:17-123`
- Only logs slow commands (line 75): `\Log::info('Slow Command Execution')`
- Threshold-based logging (>5000ms) misses most executions
- No per-step performance tracking
- Missing dry-run execution telemetry

### DSL Steps Telemetry Gaps
**Steps with No Telemetry**:
- `DatabaseUpdateStep` - Database mutations untracked
- `FragmentUpdateStep` - Fragment modifications unlogged
- `ListMapStep` - List processing operations silent
- `NotifyStep` - Only basic info logging (line 43)
- `ModelDeleteStep` - Only error logging (line 90)
- `AiGenerateStep` - Has some logging but inconsistent format

**Current Pattern Example**:
```php
// AiGenerateStep - inconsistent logging
\Log::info('AI Generate Step Cache Hit', ['cache_key' => $cacheKey]);
\Log::info('AI Generate Step Performance', [...]);
\Log::error('AI Generate Step Failed', [...]);
```

### Command Scheduling
**File**: `app/Jobs/RunScheduledCommandJob.php`
- Has structured logging but limited context
- Missing correlation with originating schedule
- No command execution telemetry integration

## Target Telemetry Schema

### Command Execution Events
```json
{
  "event": "command.execution.started",
  "correlation_id": "uuid",
  "execution_id": "uuid",
  "command_slug": "fragment.update",
  "dry_run": false,
  "user_id": "local-default",
  "request_source": "api"
}
```

### DSL Step Events
```json
{
  "event": "dsl.step.executed",
  "correlation_id": "uuid",
  "execution_id": "uuid",
  "step_id": "uuid",
  "step_type": "DatabaseUpdateStep",
  "command_slug": "fragment.update",
  "fragment_id": 123,
  "duration_ms": 45.2,
  "mutations_count": 3,
  "dry_run": false,
  "outcome": "success",
  "user_id": "local-default"
}
```

### Command Completion Events
```json
{
  "event": "command.execution.completed",
  "correlation_id": "uuid",
  "execution_id": "uuid", 
  "command_slug": "fragment.update",
  "total_duration_ms": 1250.8,
  "steps_executed": 5,
  "steps_successful": 5,
  "steps_failed": 0,
  "dry_run": false,
  "outcome": "success",
  "user_id": "local-default"
}
```

## Key Integration Points

### CommandController Enhancement
- Wrap command execution with telemetry
- Generate execution IDs for correlation
- Track request source and user context
- Log validation failures and successes

### CommandRunner Enhancement  
- Instrument DSL step execution pipeline
- Track individual step performance
- Aggregate step statistics for command completion
- Handle dry-run mode telemetry

### DSL Step Instrumentation
- Standardize telemetry across all DSL steps
- Track mutations and side effects
- Measure step-specific performance
- Correlate with tool invocations and fragment operations

### Scheduled Command Integration
- Link scheduled executions to command telemetry
- Track schedule trigger context
- Correlate with command execution metrics

## Performance Requirements

### Telemetry Overhead Targets
- **Command Execution**: <2ms overhead per command
- **DSL Step**: <0.5ms overhead per step
- **Total Pipeline**: <3ms for complete command with 5 steps

### Privacy & Content Guidelines
- Log command slugs and execution metadata
- Track fragment IDs and object counts (not content)
- Log step outcomes and timing (not processed data)
- Store execution correlation IDs for debugging

## Dependencies & Integration

### Upstream Dependencies
- **TELEMETRY-001**: Correlation middleware for request tracking
- **TELEMETRY-003**: Processing telemetry decorator patterns

### Downstream Integration
- **Tool Invocations**: Commands trigger tools, need correlation
- **Fragment Processing**: Commands modify fragments, need linkage
- **Scheduled Jobs**: Background command execution telemetry