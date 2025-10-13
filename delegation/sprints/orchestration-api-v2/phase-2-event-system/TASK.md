# Task: Event System Enhancement - Event-Driven Orchestration

**Task ID**: `phase-2-event-system`  
**Sprint**: `orchestration-api-v2`  
**Phase**: 2  
**Status**: Pending  
**Priority**: P0  
**Estimated Duration**: 3-5 days

---

## Objective

Enhance the orchestration event system with advanced event hooks, automation triggers, aggregation queries, and replay capabilities to enable comprehensive observability and event-driven workflows.

---

## Context

Phase 1 delivered basic event emission on CRUD operations. Phase 2 expands this into a production-grade event system that supports:
1. **Enhanced event hooks** - More granular events (field changes, validation failures, etc.)
2. **Event-driven automation** - Trigger actions based on event patterns
3. **Event aggregation** - Query events by correlation, session, time ranges
4. **Replay capabilities** - Reconstruct state from event log

This enables full observability of agent actions, debugging workflows, and building automation rules like "when task status → completed, update sprint progress".

Reference:
- Phase 1 implementation (`app/Services/OrchestrationEventService.php`)
- Existing event patterns in `app/Events/`
- Laravel event broadcasting patterns

---

## Tasks

### 1. Enhanced Event Types
- [ ] Add granular event types to `OrchestrationEventService`
  - `orchestration.sprint.metadata_updated` - When metadata JSON changes
  - `orchestration.sprint.status_changed` - When status transitions
  - `orchestration.task.assigned` - When task assigned to agent
  - `orchestration.task.blocked` - When task status → blocked
  - `orchestration.task.priority_changed` - When priority changes
  - `orchestration.task.progress_updated` - When progress % changes
  - `orchestration.session.started` - When agent initializes
  - `orchestration.session.resumed` - When agent resumes work
  - `orchestration.context.assembled` - When context broker runs
- [ ] Add event metadata standardization
  - `actor` (user_id or agent_id who triggered)
  - `timestamp` (ISO 8601)
  - `changes` (before/after values for updates)
  - `correlation_chain` (array of related correlation IDs)

### 2. Event Listeners & Automation
- [ ] Create `OrchestrationEventListener` class
  - Listen to all `orchestration.*` events
  - Implement automation rules engine
- [ ] Add automation rules
  - **Rule**: When all tasks in sprint → completed, update sprint status → completed
  - **Rule**: When task → blocked, create notification event
  - **Rule**: When task priority → P0, emit alert event
  - **Rule**: When sprint status → active, emit session initialization event
- [ ] Create `OrchestrationAutomationService`
  - `registerRule($trigger, $action)` - Add automation rule
  - `evaluateRules($event)` - Check if event triggers any rules
  - `executeAction($action, $context)` - Run automation action

### 3. Event Aggregation Queries
- [ ] Add query methods to `OrchestrationEvent` model
  - `scopeByCorrelation($query, $correlationId)` - All events in correlation chain
  - `scopeBySession($query, $sessionKey)` - All events in session
  - `scopeByDateRange($query, $start, $end)` - Events in time window
  - `scopeByActor($query, $actorId)` - Events by user/agent
  - `scopeByEntityChain($query, $entityType, $entityId)` - Events for entity + related entities
- [ ] Create `OrchestrationEventController` enhancements
  - `GET /api/orchestration/events/correlation/{id}` - Get event chain
  - `GET /api/orchestration/events/session/{key}` - Get session events
  - `GET /api/orchestration/events/timeline` - Aggregated timeline view
  - `GET /api/orchestration/events/stats` - Event statistics (counts by type, actor)

### 4. Event Replay & State Reconstruction
- [ ] Create `OrchestrationReplayService`
  - `replayEvents($correlationId)` - Replay events in correlation chain
  - `reconstructState($entityType, $entityId, $timestamp)` - Rebuild entity state at point in time
  - `validateEventChain($correlationId)` - Verify event chain integrity
- [ ] Add replay endpoints
  - `POST /api/orchestration/events/replay` - Replay event chain (dry-run mode)
  - `GET /api/orchestration/sprints/{code}/history` - Show sprint state at different points
  - `GET /api/orchestration/tasks/{code}/history` - Show task state history

### 5. Event Broadcasting (Optional)
- [ ] Configure Laravel broadcasting for real-time events
  - Add `ShouldBroadcast` interface to orchestration events
  - Configure channels in `routes/channels.php`
  - Emit to `orchestration.{entity_type}.{entity_id}` channel
- [ ] Frontend integration preparation
  - Document WebSocket connection patterns
  - Example event listeners for UI updates

### 6. Event Archiving & Cleanup
- [ ] Create event archiving system
  - Add `archived_at` column to `orchestration_events`
  - Command: `orchestration:archive-events --older-than=90d`
  - Move old events to cold storage (JSON files or archive table)
- [ ] Add event retention policies
  - Keep P0 events indefinitely
  - Archive P1/P2 events after 90 days
  - Delete archived events after 1 year

---

## Deliverables

1. **Enhanced Event Service**
   - 9+ new event types with standardized metadata
   - Event chaining with correlation tracking

2. **Automation System**
   - Event listener with rules engine
   - 4+ automation rules implemented
   - Service for registering/evaluating rules

3. **Aggregation Queries**
   - 5+ new query scopes on OrchestrationEvent
   - 4+ new API endpoints for event querying
   - Timeline and stats views

4. **Replay System**
   - Replay service with state reconstruction
   - 3+ replay endpoints
   - Event chain validation

5. **Archiving System**
   - Archive command with scheduling
   - Retention policy configuration
   - Migration for archived_at column

---

## Acceptance Criteria

- ✅ Event emitted when task status changes includes before/after values in payload
- ✅ When all tasks in sprint complete, sprint status auto-updates via automation rule
- ✅ Can query all events in correlation chain via `/api/orchestration/events/correlation/{id}`
- ✅ Can reconstruct task state at specific timestamp
- ✅ Event replay validates chain integrity (no gaps, correct sequence)
- ✅ Archive command successfully moves events older than 90 days
- ✅ All tests pass (`php artisan test --filter=OrchestrationEvent`)
- ✅ Event statistics endpoint returns counts by type, actor, date range

---

## Testing

### Manual Testing
1. **Event Chain Tracking**:
   ```php
   // Create sprint, create 3 tasks, update task statuses
   $sprint = OrchestrationSprint::create(['sprint_code' => 'TEST-001', ...]);
   $task1 = OrchestrationTask::create(['task_code' => 'TASK-001', 'sprint_id' => $sprint->id, ...]);
   $task1->update(['status' => 'in_progress']);
   $task1->update(['status' => 'completed']);
   
   // Query event chain
   $events = OrchestrationEvent::byCorrelation($task1->correlation_id)->get();
   // Should show: task.created → task.status_changed (pending → in_progress) → task.status_changed (in_progress → completed)
   ```

2. **Automation Rule**:
   ```php
   // Create sprint with 2 tasks
   // Mark both tasks completed
   // Verify sprint status auto-updates to 'completed'
   ```

3. **State Reconstruction**:
   ```php
   $service = app(OrchestrationReplayService::class);
   $state = $service->reconstructState('task', $task->id, now()->subHour());
   // Should return task state as it was 1 hour ago
   ```

### Automated Testing
```bash
php artisan test --filter=OrchestrationEvent
php artisan test tests/Feature/OrchestrationEventAggregationTest.php
php artisan test tests/Feature/OrchestrationAutomationTest.php
php artisan test tests/Unit/OrchestrationReplayServiceTest.php
```

**Expected Results**:
- All event hooks fire correctly
- Automation rules trigger on matching events
- Aggregation queries return correct results
- Replay reconstructs accurate state

---

## Notes

- **Correlation Chains**: Use UUIDs, nest correlation IDs for multi-step workflows
- **Performance**: Index `correlation_id`, `session_key`, `emitted_at` for fast queries
- **Event Volume**: Monitor orchestration_events table size, archive aggressively
- **Automation Safety**: Add dry-run mode, undo capabilities for automation rules
- **Broadcasting**: Skip for Phase 2 if no real-time UI requirements yet
- **State Reconstruction**: Only works if event log is complete (no gaps)

---

## Dependencies

- Phase 1 complete (OrchestrationEvent model, basic emission)
- Laravel event system
- PostgreSQL JSON query support for aggregations
- Optional: Laravel broadcasting setup (Pusher/Soketi)

---

## References

- `app/Services/OrchestrationEventService.php` - Phase 1 event service
- `app/Events/` - Existing Laravel event patterns
- `app/Models/OrchestrationEvent.php` - Event model from Phase 1
- Laravel Events documentation: https://laravel.com/docs/events
- Event sourcing patterns

---

## Status Updates

**Started**: TBD  
**Progress**: 0/6 task groups  
**Blockers**: None  
**Completed**: TBD

---

**Task Hash**: TBD (will be generated on creation)
