# Task: Agent Initialization & Context Broker

**Task ID**: `phase-4-agent-init`  
**Sprint**: `orchestration-api-v2`  
**Phase**: 4  
**Status**: Pending  
**Priority**: P0  
**Estimated Duration**: 4-6 days

---

## Objective

Implement the AGENT INIT command and context broker service to enable agents to start work on sprints/tasks with complete context assembly, including sprint details, task requirements, related events, and session state.

---

## Context

Phase 3 delivered template generation. Phase 4 enables agents to:
1. **Initialize on a sprint or task** via `AGENT INIT` command
2. **Get complete context** - Sprint/task details, events, session history
3. **Resume work** - Pick up from previous session state
4. **Track progress** - Link work to orchestration entities

This is the core workflow enabling `AGENT INIT T:my-task-code` or `AGENT INIT S:my-sprint-code`.

Reference:
- Existing MCP tools in `app/Tools/Orchestration/`
- Phase 1 API for data retrieval
- Phase 2 events for context

---

## Tasks

### 1. Context Broker Service
- [ ] Create `OrchestrationContextBrokerService`
  - `assembleSprintContext($sprintCode)` - Get full sprint context
  - `assembleTaskContext($taskCode)` - Get full task context  
  - `assembleSessionContext($sessionKey)` - Get session state
  - `mergeContextData($contexts)` - Combine multiple contexts
- [ ] Sprint context includes:
  - Sprint metadata (title, status, owner, goals)
  - All tasks with status
  - Recent events (last 50)
  - Related files paths
  - Success metrics
- [ ] Task context includes:
  - Task metadata (title, priority, status, acceptance criteria)
  - Parent sprint context
  - Task events
  - Agent configuration (AGENT.yml data if exists)
  - Artifacts/deliverables
  - Dependencies

### 2. Session Management Enhancement
- [ ] Add session initialization to `OrchestrationEventService`
  - `initializeSession($entityType, $entityId, $agentId)` - Create session
  - `resumeSession($sessionKey)` - Resume existing session
  - `closeSession($sessionKey)` - End session
- [ ] Session state tracking:
  - `session_key` (UUID)
  - `entity_type` ('sprint' or 'task')
  - `entity_id` (sprint/task ID)
  - `agent_id` (optional)
  - `started_at`, `last_activity_at`
  - `status` ('active', 'paused', 'completed')
  - `context_snapshot` (JSON of assembled context)

### 3. AGENT INIT Command Implementation
- [ ] Parse init syntax:
  - `AGENT INIT T:task-code` - Initialize on task
  - `AGENT INIT S:sprint-code` - Initialize on sprint
  - `AGENT INIT SESSION:session-key` - Resume session
- [ ] Command workflow:
  1. Parse entity identifier (T: / S: / SESSION:)
  2. Validate entity exists
  3. Create or resume session
  4. Assemble complete context via broker
  5. Emit `orchestration.session.started` or `orchestration.session.resumed`
  6. Return context package
- [ ] Response format:
  ```json
  {
    "success": true,
    "session_key": "uuid",
    "entity": { sprint or task data },
    "context": { assembled context },
    "message": "Agent initialized on task-code",
    "next_steps": ["Review acceptance criteria", "Check dependencies"]
  }
  ```

### 4. Context Assembly API
- [ ] Add endpoint: `POST /api/orchestration/agent/init`
  - Body: `{ entity_type, entity_code, agent_id?, resume_session? }`
  - Returns: Full context package
- [ ] Add endpoint: `GET /api/orchestration/sessions/{key}/context`
  - Returns: Current session context
- [ ] Add endpoint: `POST /api/orchestration/sessions/{key}/activity`
  - Update last activity timestamp
  - Log agent actions

### 5. Context Enrichment
- [ ] File system integration:
  - Include TASK.md content if exists
  - Include AGENT.yml configuration
  - Include SPRINT.md content
  - List related files in sprint directory
- [ ] Event timeline:
  - Last 50 events for entity
  - Group by correlation_id
  - Include related entity events
- [ ] Progress tracking:
  - Task completion percentage
  - Sprint progress (tasks completed / total)
  - Blocked dependencies

### 6. Testing
- [ ] Test context assembly for sprint
- [ ] Test context assembly for task
- [ ] Test session initialization
- [ ] Test session resumption
- [ ] Test AGENT INIT command parsing
- [ ] Test context enrichment (files, events)

---

## Deliverables

1. **Context Broker Service**
   - `OrchestrationContextBrokerService` with assembly methods
   - Sprint context assembly (metadata + tasks + events)
   - Task context assembly (metadata + sprint + events)
   - Session context assembly

2. **Session Management**
   - Session initialization/resumption in event service
   - Session state tracking
   - Activity logging

3. **AGENT INIT Command**
   - Command parser for T:/S:/SESSION: syntax
   - Complete workflow implementation
   - Context package response format

4. **API Endpoints**
   - `POST /api/orchestration/agent/init` - Initialize agent
   - `GET /api/orchestration/sessions/{key}/context` - Get context
   - `POST /api/orchestration/sessions/{key}/activity` - Log activity

5. **Tests**
   - Context assembly tests
   - Session management tests
   - AGENT INIT workflow tests

---

## Acceptance Criteria

- ✅ Can execute `AGENT INIT T:my-task-code` successfully
- ✅ Returns complete task context including parent sprint
- ✅ Can execute `AGENT INIT S:my-sprint-code` for sprint work
- ✅ Returns all sprint tasks with status
- ✅ Can resume session with `AGENT INIT SESSION:uuid`
- ✅ Context includes recent events (last 50)
- ✅ Context includes file paths and AGENT.yml config
- ✅ Session tracking records activity timestamps
- ✅ Events emitted: `orchestration.session.started`, `orchestration.session.resumed`
- ✅ All tests pass

---

## Example Usage

### Initialize on Task
```bash
POST /api/orchestration/agent/init
{
  "entity_type": "task",
  "entity_code": "phase-1-setup",
  "agent_id": 123
}
```

**Response:**
```json
{
  "success": true,
  "session_key": "550e8400-e29b-41d4-a716-446655440000",
  "entity": {
    "task_code": "phase-1-setup",
    "title": "Phase 1: Setup",
    "status": "pending",
    "priority": "P0",
    "sprint": {
      "sprint_code": "my-feature",
      "title": "My Feature Sprint",
      "status": "active"
    }
  },
  "context": {
    "acceptance_criteria": ["...", "..."],
    "deliverables": ["...", "..."],
    "recent_events": [...],
    "files": {
      "task_md": "delegation/sprints/my-feature/phase-1-setup/TASK.md",
      "agent_yml": "delegation/sprints/my-feature/phase-1-setup/AGENT.yml"
    },
    "sprint_progress": {
      "total_tasks": 5,
      "completed_tasks": 0,
      "in_progress_tasks": 1
    }
  },
  "message": "Agent initialized on task phase-1-setup",
  "next_steps": [
    "Review acceptance criteria in TASK.md",
    "Check AGENT.yml for configuration",
    "Review recent sprint events"
  ]
}
```

### Resume Session
```bash
POST /api/orchestration/agent/init
{
  "resume_session": "550e8400-e29b-41d4-a716-446655440000"
}
```

**Response:**
```json
{
  "success": true,
  "session_key": "550e8400-e29b-41d4-a716-446655440000",
  "resumed": true,
  "entity": { task data },
  "context": { updated context },
  "message": "Session resumed",
  "session_state": {
    "started_at": "2025-10-13T00:00:00Z",
    "last_activity_at": "2025-10-13T01:00:00Z",
    "duration": "1 hour"
  }
}
```

---

## Notes

- **Session Keys**: UUIDs, stored in orchestration_events or new sessions table
- **Context Size**: May be large, consider pagination for events
- **Caching**: Consider caching assembled context for active sessions
- **File Access**: Ensure paths are secure (no traversal)
- **Agent ID**: Optional, can be anonymous sessions
- **Resume Logic**: Match session_key, verify entity still exists

---

## Dependencies

- Phase 1 (CRUD API) complete
- Phase 2 (Event system) complete
- Phase 3 (Template generation) complete
- File system access to `delegation/sprints/`
- Existing MCP orchestration tools

---

## References

- `app/Tools/Orchestration/SessionStartTool.php` - Existing session start
- `app/Tools/Orchestration/SessionEndTool.php` - Existing session end
- Phase 1 API for sprint/task retrieval
- Phase 2 event service for emission
- Phase 3 file sync service for file paths

---

## Status Updates

**Started**: TBD  
**Progress**: 0/6 task groups  
**Blockers**: None  
**Completed**: TBD

---

**Task Hash**: TBD
