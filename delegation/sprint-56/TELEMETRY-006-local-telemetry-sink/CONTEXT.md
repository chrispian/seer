# TELEMETRY-006: Local Telemetry Sink & Query Interface - Context

## Current State Analysis

### Existing Telemetry Infrastructure
From previous tasks, we have structured telemetry flowing through:

**TELEMETRY-001**: Request correlation middleware
- Correlation IDs in all request-scoped logs
- Context propagation to jobs and services

**TELEMETRY-002**: Chat pipeline logging  
- Structured chat events: message.sent, stream.completed, etc.
- Token usage and timing data

**TELEMETRY-003**: Processing telemetry decorator
- Fragment processing step timing and outcomes
- Job-level and step-level instrumentation

**TELEMETRY-004**: Command & DSL execution metrics
- Command execution events and DSL step performance
- Success/failure rates and timing data

**TELEMETRY-005**: Enhanced tool invocation correlation
- Tool invocations linked to messages, commands, and fragments
- Database storage with correlation fields

### Current Logging Destinations
**Laravel Log Files**: All structured telemetry events
- JSON-formatted log entries with correlation context
- Scattered across multiple log files (laravel.log, queue.log, etc.)
- Difficult to aggregate and analyze

**Database Tables**: Tool invocations only
- `tool_invocations` table with correlation fields
- Command executions in `command_executions` table
- No centralized telemetry aggregation

### NativePHP Context & Constraints
**Local-First Requirements**:
- Single-user environment, no external telemetry services
- Must work offline without internet connectivity
- Minimal resource usage (memory, CPU, storage)
- No external dependencies or cloud services

**Developer Experience Needs**:
- Quick debugging of request flows and performance issues
- Correlation analysis for complex chat → command → tool flows
- Performance monitoring for AI operations and fragment processing
- Error investigation with complete context

## Target Architecture

### Local Telemetry Storage Options

**Option 1: Enhanced Log File Parsing**
- Parse structured logs for telemetry aggregation
- Pros: No additional storage, works with existing logs
- Cons: Performance overhead, complex parsing logic

**Option 2: SQLite Telemetry Database**
- Dedicated SQLite database for telemetry aggregation
- Pros: Fast queries, structured storage, minimal overhead
- Cons: Additional storage requirements

**Option 3: Memory-Based Aggregation**
- In-memory telemetry storage with periodic persistence
- Pros: Fastest queries, minimal disk I/O
- Cons: Data loss on restart, memory usage concerns

**Recommended: Hybrid Approach**
- Primary: Enhanced database queries (tool_invocations + command_executions)
- Secondary: Recent log parsing for events not in database
- Cache: Memory-based aggregation for current session

### Query Interface Design

### Console Commands
```bash
# Telemetry overview
php artisan telemetry:overview --last=1h

# Correlation analysis  
php artisan telemetry:trace {correlation-id}

# Performance analysis
php artisan telemetry:performance --type=chat --last=24h

# Error analysis
php artisan telemetry:errors --last=6h
```

### Web Dashboard Routes
```
/internal/telemetry          - Overview dashboard
/internal/telemetry/chat     - Chat pipeline telemetry
/internal/telemetry/commands - Command execution telemetry  
/internal/telemetry/tools    - Tool invocation analysis
/internal/telemetry/errors   - Error analysis and debugging
```

## Integration Points

### Data Sources
1. **Database Tables**: tool_invocations, command_executions
2. **Log Files**: Structured JSON logs from all telemetry systems
3. **Real-time**: Current request correlation context
4. **Cache**: Recent session aggregation data

### Query Patterns
1. **Request Tracing**: Follow correlation_id through all systems
2. **Performance Analysis**: Aggregate timing data by operation type
3. **Error Investigation**: Find related events for error correlation
4. **Usage Analytics**: Tool usage, command frequency, chat patterns

### Dashboard Features
1. **Real-time Overview**: Current session telemetry
2. **Historical Analysis**: Telemetry trends over time
3. **Correlation Viewer**: Interactive request flow visualization
4. **Performance Metrics**: Timing histograms and percentiles
5. **Error Dashboard**: Recent errors with full context

## Performance & Resource Constraints

### Memory Usage Targets
- **Telemetry Cache**: <10MB for recent session data
- **Query Buffers**: <5MB for dashboard queries
- **Total Overhead**: <15MB additional memory usage

### Query Performance Targets
- **Dashboard Load**: <500ms for overview
- **Correlation Trace**: <200ms for single correlation analysis
- **Performance Analysis**: <1s for 24h aggregation
- **Real-time Updates**: <100ms for current session data

### Storage Considerations
- **Log Retention**: Configurable (default 7 days)
- **Database Growth**: Tool invocations and command executions
- **Cache Size**: Limited to prevent memory bloat
- **Cleanup Strategy**: Automatic old data archival/deletion