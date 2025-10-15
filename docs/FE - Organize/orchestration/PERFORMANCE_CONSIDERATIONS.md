# Orchestration API v2 - Performance Considerations

**Date**: 2025-10-13  
**Version**: 1.0  
**Status**: Active

---

## Overview

This document outlines performance considerations, optimizations, and monitoring strategies for the Orchestration API v2 system.

---

## Database Performance

### Event Table Growth
**Concern**: `orchestration_events` table will grow rapidly with high agent activity.

**Mitigations**:
- Event archiving command: `php artisan orchestration:archive-events`
- Default retention: 90 days for non-critical events
- High-priority events (P0, P1) retained indefinitely
- Indexed columns: `entity_type`, `entity_id`, `correlation_id`, `session_key`, `emitted_at`, `archived_at`

**Monitoring**:
```sql
-- Check event table size
SELECT 
    COUNT(*) as total_events,
    COUNT(CASE WHEN archived_at IS NOT NULL THEN 1 END) as archived,
    pg_size_pretty(pg_total_relation_size('orchestration_events')) as table_size
FROM orchestration_events;

-- Events by type (last 7 days)
SELECT event_type, COUNT(*) as count
FROM orchestration_events
WHERE emitted_at >= NOW() - INTERVAL '7 days'
GROUP BY event_type
ORDER BY count DESC;
```

### Query Optimization
**Indexes**:
```sql
-- Existing indexes (from migrations)
CREATE INDEX idx_orchestration_events_entity ON orchestration_events(entity_type, entity_id);
CREATE INDEX idx_orchestration_events_correlation ON orchestration_events(correlation_id);
CREATE INDEX idx_orchestration_events_session ON orchestration_events(session_key);
CREATE INDEX idx_orchestration_events_emitted_at ON orchestration_events(emitted_at);
CREATE INDEX idx_orchestration_events_archived ON orchestration_events(archived_at);

-- Composite indexes for common queries
CREATE INDEX idx_events_entity_emitted 
    ON orchestration_events(entity_type, entity_id, emitted_at DESC);
    
CREATE INDEX idx_events_session_emitted 
    ON orchestration_events(session_key, emitted_at DESC) 
    WHERE session_key IS NOT NULL;
```

**Query Patterns**:
- Use `LIMIT` on timeline/history endpoints
- Filter by date ranges to avoid full table scans
- Leverage `archived_at IS NULL` for active events

---

## API Response Times

### Target Latencies
- **Sprint/Task CRUD**: < 200ms (p95)
- **Event queries**: < 500ms (p95)
- **Context assembly**: < 1s (p95)
- **Template generation**: < 2s (p95)
- **Status reports**: < 1s (p95)

### Optimization Strategies

#### Context Broker Caching
```php
// Cache assembled contexts for 5 minutes
Cache::remember("context:task:{$taskCode}", 300, function () use ($taskCode) {
    return $this->contextBroker->assembleTaskContext($taskCode);
});
```

#### Eager Loading
```php
// Always eager load relationships
$sprint = OrchestrationSprint::with('tasks')->find($id);
$task = OrchestrationTask::with('sprint')->find($id);
```

#### Pagination
```php
// All list endpoints should paginate
$events = OrchestrationEvent::query()
    ->where('entity_type', 'sprint')
    ->orderBy('emitted_at', 'desc')
    ->paginate(50);
```

---

## Event Emission

### Event Volume Estimates
Based on typical agent workflows:

| Workflow | Events/Day | Notes |
|----------|-----------|-------|
| Sprint creation | 5-10 | Low frequency |
| Task lifecycle | 500-1000 | Medium frequency |
| Status updates | 1000-2000 | High frequency |
| Agent sessions | 200-500 | Medium frequency |
| **Total** | **~2000-4000/day** | Varies by team size |

### Throttling Strategies
- **Debounce rapid status changes**: Emit only after 5s of stability
- **Batch updates**: Queue multiple events, emit in batch
- **Sampling**: For verbose event types, sample at 10%

### Async Processing
```php
// Emit events asynchronously when possible
dispatch(function () use ($event) {
    OrchestrationEventService::emit($event);
})->afterResponse();
```

---

## File System Operations

### File Sync Performance
**Concern**: Sync operations block request threads.

**Mitigations**:
- Make file sync optional: `sync_to_file: false`
- Queue file writes: `SyncOrchestrationFilesJob`
- Batch sync operations (sync multiple tasks at once)

**Best Practices**:
```php
// Option 1: Skip sync for bulk operations
$pmTools->updateTaskStatus('task-code', 'completed', [
    'sync_to_file' => false,  // Skip for performance
]);

// Option 2: Queue sync for later
dispatch(new SyncTaskToFileJob($task))->afterResponse();
```

### File System Monitoring
```bash
# Check delegation directory size
du -sh delegation/sprints

# Count markdown files
find delegation/sprints -name "*.md" | wc -l

# Find large files (> 100KB)
find delegation/sprints -type f -size +100k
```

---

## Memory Usage

### Large Result Sets
**Concern**: Loading thousands of events into memory.

**Mitigations**:
- Use `chunk()` for bulk processing
- Implement cursor pagination for large queries
- Stream responses when possible

```php
// Bad: Loads all events into memory
$events = OrchestrationEvent::all();

// Good: Process in chunks
OrchestrationEvent::chunk(500, function ($events) {
    foreach ($events as $event) {
        // Process event
    }
});

// Better: Use cursor for memory efficiency
foreach (OrchestrationEvent::cursor() as $event) {
    // Process event with minimal memory
}
```

### Context Assembly
**Concern**: Assembling full context for large sprints.

**Mitigations**:
- Limit event history to last 100 events
- Paginate task lists in sprint context
- Load file contents lazily

---

## Monitoring & Alerts

### Key Metrics
1. **Event throughput**: events/minute
2. **API latency**: p50, p95, p99 per endpoint
3. **Database query time**: slow query log (> 1s)
4. **Event table size**: rows, disk size
5. **File sync duration**: sync operations > 500ms

### Recommended Tools
- **Application**: Laravel Telescope, Horizon
- **Database**: pg_stat_statements, pg_stat_activity
- **Infrastructure**: DataDog, New Relic, Grafana

### Alert Thresholds
```yaml
alerts:
  - name: High event lag
    condition: event_queue_depth > 1000
    action: Scale queue workers
    
  - name: Slow context assembly
    condition: context_assembly_p95 > 2000ms
    action: Check cache hit rate
    
  - name: Large event table
    condition: event_count > 1000000
    action: Run archive command
    
  - name: API error rate
    condition: error_rate > 5%
    action: Check logs, rollback if needed
```

---

## Load Testing

### Test Scenarios

#### Scenario 1: Sprint Creation
```bash
# 10 sprints/minute for 10 minutes
ab -n 100 -c 10 -T 'application/json' \
   -p sprint.json \
   http://localhost/api/orchestration/sprints
```

#### Scenario 2: Task Status Updates
```bash
# 100 updates/minute
for i in {1..100}; do
  curl -X POST http://localhost/api/orchestration/pm-tools/task-status \
    -d '{"task_code":"task-'$i'","status":"completed"}' &
done
```

#### Scenario 3: Event Queries
```bash
# 50 concurrent event timeline requests
ab -n 500 -c 50 http://localhost/api/orchestration/events/timeline
```

### Expected Results
- **Sprint creation**: 95% < 300ms, 0% errors
- **Task updates**: 95% < 200ms, 0% errors
- **Event queries**: 95% < 500ms, 0% errors

---

## Optimization Checklist

### Before Production
- [ ] Enable query logging for 24h, identify slow queries
- [ ] Set up event archiving cron job
- [ ] Configure cache (Redis recommended)
- [ ] Enable queue workers for async jobs
- [ ] Set up monitoring dashboards
- [ ] Run load tests against staging
- [ ] Establish baseline metrics

### Regular Maintenance
- [ ] Weekly: Review slow query log
- [ ] Weekly: Check event table growth rate
- [ ] Monthly: Archive old events
- [ ] Monthly: Analyze API latency trends
- [ ] Quarterly: Load test new features

---

## Scaling Strategies

### Horizontal Scaling
- **API servers**: Load balance multiple Laravel instances
- **Queue workers**: Scale Horizon workers based on queue depth
- **Database**: Read replicas for query-heavy endpoints

### Vertical Scaling
- **Database**: Increase CPU/memory for PostgreSQL
- **Cache**: Upgrade Redis instance size
- **Application**: Increase PHP memory_limit, opcache

### Optimization Priorities
1. **High impact, low effort**: Index optimization, query fixes
2. **High impact, medium effort**: Caching, eager loading
3. **Medium impact, high effort**: Queue refactoring, sharding

---

## Future Considerations

### Event Streaming
For very high-volume deployments (> 10k events/day):
- Consider Apache Kafka or AWS Kinesis
- Stream events to data warehouse
- Real-time event processing pipelines

### Distributed Caching
For multi-region deployments:
- Implement Redis Cluster
- Use CDN for static file serving
- Cache context at edge locations

### Archive Storage
For long-term retention:
- Move archived events to S3/object storage
- Compress old event data
- Implement cold storage tier

---

**Document Version**: 1.0  
**Last Updated**: 2025-10-13  
**Owner**: Engineering Team
