# DASH-002: Dashboard Backend APIs

## Status: Backlog (Depends on DASH-001)

## Priority: High

## Problem
Need RESTful APIs to serve dashboard data from various sources (telemetry, audit logs, orchestration, system metrics). APIs must be performant, cacheable, and provide flexible filtering.

## Objectives
Implement all backend API endpoints defined in DASH-001 architecture.

## Scope

### Controllers to Create
```
app/Http/Controllers/Api/Dashboard/
├── SystemController.php       # System health & metrics
├── TelemetryController.php    # Telemetry data
├── OrchestrationController.php # Tasks, sprints, agents
├── AuditController.php        # Audit logs
└── AgentController.php        # Agent-specific metrics
```

### API Endpoints

#### System Overview
```php
// GET /api/dashboard/system/overview
public function overview(): JsonResponse
{
    return response()->json([
        'uptime' => $this->getSystemUptime(),
        'active_users' => User::count(),
        'database' => $this->getDatabaseHealth(),
        'queue' => $this->getQueueHealth(),
        'cache' => $this->getCacheHealth(),
        'storage' => $this->getStorageUsage(),
        'recent_errors' => $this->getRecentErrors(10),
    ]);
}

// GET /api/dashboard/system/health
public function health(): JsonResponse
// Returns: green/yellow/red status for each component

// GET /api/dashboard/system/metrics?timeframe=24h
public function metrics(Request $request): JsonResponse
// Returns: time-series data for CPU, memory, requests, etc.
```

#### Telemetry
```php
// GET /api/dashboard/telemetry/summary
public function summary(): JsonResponse
// Returns: High-level telemetry stats

// GET /api/dashboard/telemetry/events?component=llm&limit=100&page=1
public function events(Request $request): JsonResponse
// Returns: Paginated telemetry events with filters

// GET /api/dashboard/telemetry/traces/{correlation_id}
public function trace(string $correlationId): JsonResponse
// Returns: Full correlation chain for tracing

// GET /api/dashboard/telemetry/performance?timeframe=1h
public function performance(Request $request): JsonResponse
// Returns: Performance metrics aggregated
```

#### Orchestration
```php
// GET /api/dashboard/orchestration/overview
public function overview(): JsonResponse
// Returns: Sprint status, task counts, agent activity

// GET /api/dashboard/orchestration/tasks?status=in_progress&sprint=current
public function tasks(Request $request): JsonResponse
// Returns: Filtered task list

// GET /api/dashboard/orchestration/sprints?active=true
public function sprints(Request $request): JsonResponse
// Returns: Sprint list with progress

// GET /api/dashboard/orchestration/velocity?sprints=5
public function velocity(Request $request): JsonResponse
// Returns: Velocity metrics (tasks/sprint)
```

#### Audit Logs (extends existing from TASK-0003)
```php
// Use endpoints defined in TASK-0003
// Add summary endpoint:
// GET /api/dashboard/audit/stats
public function stats(): JsonResponse
// Returns: Destructive command count, user activity heatmap, etc.
```

#### Agent Activity
```php
// GET /api/dashboard/agents/summary
public function summary(): JsonResponse
// Returns: All agents with recent activity counts

// GET /api/dashboard/agents/{id}/activity?limit=50
public function activity(int $id, Request $request): JsonResponse
// Returns: Recent agent log entries

// GET /api/dashboard/agents/{id}/decisions
public function decisions(int $id): JsonResponse
// Returns: Agent decision history

// GET /api/dashboard/agents/{id}/metrics?timeframe=7d
public function metrics(int $id, Request $request): JsonResponse
// Returns: Agent-specific performance metrics
```

## Implementation Checklist

### 1. Create Controllers
- [ ] Create `SystemController` with health methods
- [ ] Create `TelemetryController` with event queries
- [ ] Create `OrchestrationController` with task/sprint queries
- [ ] Create `AuditController` (or extend existing from TASK-0003)
- [ ] Create `AgentController` with agent metrics

### 2. Add Routes
- [ ] Add routes to `routes/api.php`
- [ ] Group under `/api/dashboard` prefix
- [ ] Add auth middleware (sanctum/session)
- [ ] Add rate limiting (60 req/min)

### 3. Create API Resources
```
app/Http/Resources/Dashboard/
├── SystemHealthResource.php
├── TelemetryEventResource.php
├── TaskResource.php
├── SprintResource.php
├── AgentActivityResource.php
└── MetricResource.php
```

### 4. Implement Caching
- [ ] Cache expensive queries (system health, stats)
- [ ] Cache TTL: 30-60 seconds
- [ ] Cache tags for invalidation
- [ ] Add `Cache-Control` headers

### 5. Add Validation
- [ ] Request validation for filters
- [ ] Date range validation
- [ ] Pagination limits (max 500)
- [ ] Enum validation for status fields

### 6. Optimize Queries
- [ ] Use `select()` to limit columns
- [ ] Add eager loading for relationships
- [ ] Use query scopes for reusability
- [ ] Add database indexes if needed
- [ ] Consider read replicas for heavy queries

### 7. Add Tests
- [ ] Feature tests for each endpoint
- [ ] Test filtering and pagination
- [ ] Test error responses
- [ ] Test auth requirements
- [ ] Performance tests (response time < 500ms)

## Technical Details

### Database Queries

**System Health:**
```php
// Database health
DB::connection()->getPdo()->getAttribute(PDO::ATTR_SERVER_INFO);
DB::select('SELECT COUNT(*) FROM pg_stat_activity');

// Queue health (Horizon)
Horizon::jobs()->pending()->count();
Horizon::jobs()->failed()->count();
Redis::info('stats');

// Cache health
Cache::getStore()->getMemcached()->getStats();
```

**Telemetry Queries:**
```php
// Recent events
TelemetryEvent::query()
    ->when($component, fn($q) => $q->where('component', $component))
    ->when($eventType, fn($q) => $q->where('event_type', $eventType))
    ->latest('timestamp')
    ->paginate(100);

// Performance aggregation
TelemetryPerformanceSnapshot::query()
    ->where('created_at', '>=', $timeframeStart)
    ->selectRaw('component, AVG(duration_ms) as avg_duration, MAX(duration_ms) as max_duration')
    ->groupBy('component')
    ->get();
```

**Orchestration Queries:**
```php
// Task counts by status
TaskActivity::query()
    ->selectRaw('status, COUNT(*) as count')
    ->groupBy('status')
    ->get();

// Sprint progress
Sprint::with(['items' => function($q) {
    $q->selectRaw('sprint_id, status, COUNT(*) as count')
      ->groupBy('sprint_id', 'status');
}])->get();
```

### Caching Strategy
```php
// Cache expensive aggregations
Cache::remember('dashboard:system:health', 60, function() {
    return [
        'database' => $this->getDatabaseHealth(),
        'queue' => $this->getQueueHealth(),
        'cache' => $this->getCacheHealth(),
    ];
});

// Cache tags for selective invalidation
Cache::tags(['dashboard', 'telemetry'])->remember('telemetry:summary', 60, fn() => ...);

// Invalidate on model events
protected static function booted()
{
    static::created(fn() => Cache::tags(['dashboard', 'telemetry'])->flush());
}
```

### Response Format
```json
{
  "data": {
    "metric_name": "value",
    "nested": { ... }
  },
  "meta": {
    "cached": true,
    "cache_expires_at": "2025-10-09T13:00:00Z",
    "query_time_ms": 45
  }
}
```

### Error Handling
```php
try {
    $data = $this->fetchData($request);
    return response()->json(['data' => $data]);
} catch (QueryException $e) {
    Log::error('Dashboard query failed', ['error' => $e->getMessage()]);
    return response()->json(['error' => 'Database error'], 500);
} catch (\Exception $e) {
    return response()->json(['error' => 'Internal error'], 500);
}
```

## Acceptance Criteria
- [ ] All endpoints return proper JSON responses
- [ ] All endpoints have validation
- [ ] All endpoints are authenticated
- [ ] Expensive queries are cached
- [ ] Response times < 500ms (95th percentile)
- [ ] API resources format data consistently
- [ ] Error responses follow standard format
- [ ] Feature tests pass for all endpoints
- [ ] API documentation generated (OpenAPI/Swagger)

## Estimated Effort
- Controllers: 1 day
- Resources & validation: 0.5 days
- Caching implementation: 0.5 days
- Testing: 0.5 days
- Documentation: 0.5 days

**Total: 3 days**

## Dependencies
- DASH-001 complete (architecture defined)
- Existing models and database tables
- Laravel sanctum/session auth configured

## Next Steps
After completion:
- DASH-003: Build System Overview dashboard UI
- DASH-004: Build Telemetry dashboard UI
- DASH-005: Build Orchestration dashboard UI
- DASH-006: Build Audit dashboard UI
