# Task Session Lifecycle - Complete Walkthrough

**Date**: 2025-10-13  
**Version**: 1.0  
**Purpose**: Document the complete lifecycle of an agent task session, including all database writes, file writes, and event emissions

---

## Overview

This document traces a complete agent task session from initialization to completion, highlighting:
- **Database operations**: When and what gets written
- **File system operations**: When markdown files are created/updated
- **Event emissions**: What events are emitted and when
- **API calls**: The sequence of API interactions

---

## Complete Task Session Flow

### Pre-Task: Sprint & Task Setup

**Scenario**: PM creates a new sprint with tasks for agent execution.

#### Step 1: Create Sprint via API
```http
POST /api/orchestration/sprints
{
  "sprint_code": "implement-feature-x",
  "title": "Implement Feature X",
  "status": "planning",
  "owner": "Engineering Team",
  "metadata": {
    "goal": "Build new feature X with tests and docs",
    "start_date": "2025-10-13"
  }
}
```

**Database Writes**:
```sql
-- orchestration_sprints table
INSERT INTO orchestration_sprints (
  sprint_code, title, status, owner, metadata, 
  hash, created_at, updated_at
) VALUES (
  'implement-feature-x',
  'Implement Feature X',
  'planning',
  'Engineering Team',
  '{"goal": "Build new feature X...", "start_date": "2025-10-13"}',
  'abc123...', -- SHA-256 hash
  '2025-10-13 10:00:00',
  '2025-10-13 10:00:00'
);
```

**Event Emitted**:
```json
{
  "event_type": "orchestration.sprint.created",
  "entity_type": "sprint",
  "entity_id": 1,
  "correlation_id": "uuid-1",
  "payload": {
    "actor": "user-123",
    "timestamp": "2025-10-13T10:00:00Z",
    "entity_snapshot": {
      "sprint_code": "implement-feature-x",
      "title": "Implement Feature X",
      "status": "planning"
    }
  },
  "emitted_at": "2025-10-13T10:00:00Z"
}
```

**File System**: No files created yet (optional operation)

---

#### Step 2: Create Task via API
```http
POST /api/orchestration/tasks
{
  "task_code": "phase-1-backend-api",
  "title": "Phase 1: Build Backend API",
  "status": "pending",
  "priority": "P1",
  "sprint_id": 1,
  "metadata": {
    "objectives": [
      "Create RESTful endpoints",
      "Add validation",
      "Write tests"
    ],
    "deliverables": [
      "API controller",
      "Request validation",
      "Unit tests"
    ],
    "acceptance_criteria": [
      "All endpoints return proper status codes",
      "Validation errors are descriptive",
      "Test coverage > 80%"
    ]
  },
  "agent_config": {
    "model": "claude-3-5-sonnet-20241022",
    "temperature": 0.7,
    "max_tokens": 8000
  }
}
```

**Database Writes**:
```sql
-- orchestration_tasks table
INSERT INTO orchestration_tasks (
  task_code, sprint_id, title, status, priority,
  metadata, agent_config, hash, created_at, updated_at
) VALUES (
  'phase-1-backend-api',
  1,
  'Phase 1: Build Backend API',
  'pending',
  'P1',
  '{"objectives": [...], "deliverables": [...], "acceptance_criteria": [...]}',
  '{"model": "claude-3-5-sonnet-20241022", "temperature": 0.7}',
  'def456...',
  '2025-10-13 10:05:00',
  '2025-10-13 10:05:00'
);
```

**Event Emitted**:
```json
{
  "event_type": "orchestration.task.created",
  "entity_type": "task",
  "entity_id": 1,
  "correlation_id": "uuid-2",
  "payload": {
    "actor": "user-123",
    "timestamp": "2025-10-13T10:05:00Z",
    "entity_snapshot": {
      "task_code": "phase-1-backend-api",
      "title": "Phase 1: Build Backend API",
      "status": "pending",
      "priority": "P1"
    }
  },
  "emitted_at": "2025-10-13T10:05:00Z"
}
```

**File System**: No files created yet

---

#### Step 3: (Optional) Sync to File System
```http
POST /api/orchestration/sprints/implement-feature-x/sync
```

**File System Writes**:
```markdown
delegation/sprints/implement-feature-x/SPRINT.md
delegation/sprints/implement-feature-x/.hash
delegation/sprints/implement-feature-x/phase-1-backend-api/TASK.md
delegation/sprints/implement-feature-x/phase-1-backend-api/.hash
```

**Content Example** (`TASK.md`):
```markdown
# Task: Phase 1: Build Backend API

**Task Code**: `phase-1-backend-api`
**Sprint**: `implement-feature-x`
**Status**: pending
**Priority**: P1

## Objectives
- Create RESTful endpoints
- Add validation
- Write tests

## Deliverables
- API controller
- Request validation
- Unit tests

## Acceptance Criteria
- All endpoints return proper status codes
- Validation errors are descriptive
- Test coverage > 80%
```

**Event Emitted**:
```json
{
  "event_type": "orchestration.file.synced",
  "entity_type": "task",
  "entity_id": 1,
  "payload": {
    "file_path": "delegation/sprints/implement-feature-x/phase-1-backend-api/TASK.md",
    "synced_at": "2025-10-13T10:10:00Z"
  }
}
```

**Database**: No additional writes (file sync doesn't update DB)

---

### Agent Session: Task Execution

#### Step 4: Agent Initialization
**Trigger**: Agent (AI or human) starts work on the task

```http
POST /api/orchestration/agent/init
{
  "entity_type": "task",
  "entity_code": "phase-1-backend-api",
  "agent_id": 42
}
```

**Database Writes**:
```sql
-- work_sessions table (existing system)
INSERT INTO work_sessions (
  session_key, agent_profile_id, status,
  started_at, updated_at
) VALUES (
  'agent-session-uuid-abc',
  42,
  'active',
  '2025-10-13 11:00:00',
  '2025-10-13 11:00:00'
);
```

**Event Emitted**:
```json
{
  "event_type": "orchestration.session.started",
  "entity_type": "task",
  "entity_id": 1,
  "correlation_id": "uuid-3",
  "session_key": "agent-session-uuid-abc",
  "agent_id": 42,
  "payload": {
    "actor": 42,
    "timestamp": "2025-10-13T11:00:00Z",
    "session_key": "agent-session-uuid-abc",
    "started_at": "2025-10-13T11:00:00Z"
  },
  "emitted_at": "2025-10-13T11:00:00Z"
}
```

**API Response** (Context assembled for agent):
```json
{
  "session": {
    "key": "agent-session-uuid-abc",
    "entity_type": "task",
    "entity_code": "phase-1-backend-api",
    "agent_id": 42,
    "created_at": "2025-10-13T11:00:00Z"
  },
  "context": {
    "task": {
      "code": "phase-1-backend-api",
      "title": "Phase 1: Build Backend API",
      "status": "pending",
      "priority": "P1",
      "metadata": {
        "objectives": ["Create RESTful endpoints", "..."],
        "deliverables": ["API controller", "..."],
        "acceptance_criteria": ["..."]
      },
      "agent_config": {
        "model": "claude-3-5-sonnet-20241022",
        "temperature": 0.7
      }
    },
    "sprint": {
      "code": "implement-feature-x",
      "title": "Implement Feature X",
      "status": "planning",
      "owner": "Engineering Team",
      "metadata": {
        "goal": "Build new feature X with tests and docs"
      }
    },
    "files": {
      "task_md": "# Task: Phase 1: Build Backend API\n...",
      "agent_yml": null
    },
    "recent_events": [],
    "next_steps": [
      "Review task objectives and acceptance criteria",
      "Create API controller structure",
      "Implement endpoints with validation",
      "Write comprehensive tests"
    ]
  }
}
```

**Database**: Context broker reads from DB, no writes

**File System**: Reads `TASK.md` if it exists, no writes

---

#### Step 5: Task Status Update (Agent Starts Work)
**Trigger**: Agent begins implementation

```http
POST /api/orchestration/pm-tools/task-status
{
  "task_code": "phase-1-backend-api",
  "status": "in_progress",
  "notes": "Starting backend API implementation",
  "session_key": "agent-session-uuid-abc",
  "agent_id": 42,
  "emit_event": true,
  "sync_to_file": true
}
```

**Database Writes**:
```sql
-- orchestration_tasks table (UPDATE)
UPDATE orchestration_tasks
SET 
  status = 'in_progress',
  hash = 'ghi789...',  -- new hash
  updated_at = '2025-10-13 11:05:00'
WHERE task_code = 'phase-1-backend-api';
```

**Event Emitted**:
```json
{
  "event_type": "orchestration.task.status_updated",
  "entity_type": "task",
  "entity_id": 1,
  "correlation_id": "uuid-3",  -- continues correlation chain
  "session_key": "agent-session-uuid-abc",
  "agent_id": 42,
  "payload": {
    "actor": 42,
    "timestamp": "2025-10-13T11:05:00Z",
    "old_status": "pending",
    "new_status": "in_progress",
    "notes": "Starting backend API implementation",
    "entity_snapshot": {
      "task_code": "phase-1-backend-api",
      "status": "in_progress"
    }
  },
  "emitted_at": "2025-10-13T11:05:00Z"
}
```

**File System Writes** (if `sync_to_file: true`):
```markdown
# Updated: delegation/sprints/implement-feature-x/phase-1-backend-api/TASK.md
# Line updated:
**Status**: in_progress

# Updated: delegation/sprints/implement-feature-x/phase-1-backend-api/.hash
ghi789...
```

---

#### Step 6: Agent Work (Multiple Operations)
Agent performs actual work: writing code, running tests, etc.

**Agent Activity Logging** (optional, happens multiple times):
```http
POST /api/orchestration/sessions/agent-session-uuid-abc/activity
{
  "activity_type": "code_generated",
  "details": {
    "files_created": 3,
    "lines_added": 250
  }
}
```

**Database Writes**:
```sql
-- work_items table (existing system, optional)
INSERT INTO work_items (
  session_id, type, description, completed,
  created_at, updated_at
) VALUES (
  1,
  'code_generation',
  'Generated API controller and routes',
  true,
  '2025-10-13 11:30:00',
  '2025-10-13 11:30:00'
);
```

**Event Emitted** (optional, for activity tracking):
```json
{
  "event_type": "orchestration.activity.logged",
  "entity_type": "task",
  "entity_id": 1,
  "session_key": "agent-session-uuid-abc",
  "agent_id": 42,
  "payload": {
    "activity_type": "code_generated",
    "files_created": 3,
    "lines_added": 250
  },
  "emitted_at": "2025-10-13T11:30:00Z"
}
```

**File System**: No orchestration file writes (actual code files written by agent)

---

#### Step 7: Task Completion
**Trigger**: Agent finishes work and marks task complete

```http
POST /api/orchestration/pm-tools/task-status
{
  "task_code": "phase-1-backend-api",
  "status": "completed",
  "notes": "API implemented with full test coverage (85%). All acceptance criteria met.",
  "session_key": "agent-session-uuid-abc",
  "agent_id": 42,
  "emit_event": true,
  "sync_to_file": true
}
```

**Database Writes**:
```sql
-- orchestration_tasks table (UPDATE)
UPDATE orchestration_tasks
SET 
  status = 'completed',
  hash = 'jkl012...',
  updated_at = '2025-10-13 12:30:00'
WHERE task_code = 'phase-1-backend-api';

-- work_sessions table (UPDATE)
UPDATE work_sessions
SET
  status = 'completed',
  completed_at = '2025-10-13 12:30:00',
  updated_at = '2025-10-13 12:30:00'
WHERE session_key = 'agent-session-uuid-abc';
```

**Event Emitted**:
```json
{
  "event_type": "orchestration.task.status_updated",
  "entity_type": "task",
  "entity_id": 1,
  "correlation_id": "uuid-3",
  "session_key": "agent-session-uuid-abc",
  "agent_id": 42,
  "payload": {
    "actor": 42,
    "timestamp": "2025-10-13T12:30:00Z",
    "old_status": "in_progress",
    "new_status": "completed",
    "notes": "API implemented with full test coverage (85%). All acceptance criteria met.",
    "entity_snapshot": {
      "task_code": "phase-1-backend-api",
      "status": "completed",
      "priority": "P1"
    }
  },
  "emitted_at": "2025-10-13T12:30:00Z"
}
```

**File System Writes**:
```markdown
# Updated: delegation/sprints/implement-feature-x/phase-1-backend-api/TASK.md
**Status**: completed
**Completed**: 2025-10-13T12:30:00Z

## Completion Notes
API implemented with full test coverage (85%). All acceptance criteria met.

# Updated: delegation/sprints/implement-feature-x/phase-1-backend-api/.hash
jkl012...
```

---

### Post-Task: Reporting & Analysis

#### Step 8: Sprint Status Report
**Trigger**: PM checks progress

```http
GET /api/orchestration/pm-tools/status-report?sprint_code=implement-feature-x
```

**Database Reads** (no writes):
```sql
SELECT * FROM orchestration_sprints WHERE sprint_code = 'implement-feature-x';
SELECT * FROM orchestration_tasks WHERE sprint_id = 1;
```

**API Response**:
```json
{
  "sprint_code": "implement-feature-x",
  "sprint_title": "Implement Feature X",
  "sprint_status": "active",
  "summary": {
    "total_tasks": 3,
    "completed": 1,
    "in_progress": 1,
    "blocked": 0,
    "pending": 1,
    "progress_percentage": 33.33
  },
  "tasks": [
    {
      "task_code": "phase-1-backend-api",
      "title": "Phase 1: Build Backend API",
      "status": "completed",
      "priority": "P1"
    },
    {
      "task_code": "phase-2-frontend",
      "title": "Phase 2: Build Frontend",
      "status": "in_progress",
      "priority": "P2"
    },
    {
      "task_code": "phase-3-docs",
      "title": "Phase 3: Documentation",
      "status": "pending",
      "priority": "P3"
    }
  ]
}
```

**Event**: None emitted (read-only operation)

**File System**: No writes

---

#### Step 9: Event Timeline Query
**Trigger**: PM reviews what happened during the task

```http
GET /api/orchestration/events/session/agent-session-uuid-abc
```

**Database Reads**:
```sql
SELECT * FROM orchestration_events 
WHERE session_key = 'agent-session-uuid-abc'
ORDER BY emitted_at ASC;
```

**API Response**:
```json
{
  "data": [
    {
      "id": 3,
      "event_type": "orchestration.session.started",
      "emitted_at": "2025-10-13T11:00:00Z",
      "payload": {
        "session_key": "agent-session-uuid-abc",
        "started_at": "2025-10-13T11:00:00Z"
      }
    },
    {
      "id": 4,
      "event_type": "orchestration.task.status_updated",
      "emitted_at": "2025-10-13T11:05:00Z",
      "payload": {
        "old_status": "pending",
        "new_status": "in_progress",
        "notes": "Starting backend API implementation"
      }
    },
    {
      "id": 5,
      "event_type": "orchestration.activity.logged",
      "emitted_at": "2025-10-13T11:30:00Z",
      "payload": {
        "activity_type": "code_generated",
        "files_created": 3,
        "lines_added": 250
      }
    },
    {
      "id": 6,
      "event_type": "orchestration.task.status_updated",
      "emitted_at": "2025-10-13T12:30:00Z",
      "payload": {
        "old_status": "in_progress",
        "new_status": "completed",
        "notes": "API implemented with full test coverage (85%)"
      }
    }
  ]
}
```

---

## Summary: When Does Each Operation Happen?

### Database Writes

| Operation | Table | Trigger | Frequency |
|-----------|-------|---------|-----------|
| Create sprint | `orchestration_sprints` | Manual/API | Once per sprint |
| Create task | `orchestration_tasks` | Manual/API | Once per task |
| Update task status | `orchestration_tasks` | Agent/API | 2-5 times per task |
| Update task hash | `orchestration_tasks` | Any task update | Every task update |
| Start session | `work_sessions` | Agent init | Once per session |
| Complete session | `work_sessions` | Task complete | Once per session |
| Log activity | `work_items` | Agent work | 0-10 times per session (optional) |
| **Emit event** | `orchestration_events` | Every operation | 5-20 per task session |

### File System Writes

| Operation | Files | Trigger | Frequency |
|-----------|-------|---------|-----------|
| Sync sprint | `SPRINT.md`, `.hash` | Manual/explicit sync | 0-1 times per sprint |
| Sync task | `TASK.md`, `.hash` | Manual/explicit sync OR status update | 0-3 times per task |
| Update task status | `TASK.md`, `.hash` | Status update with `sync_to_file: true` | 2-5 times per task |

**Note**: File syncs are **optional** and can be disabled for performance. Database is source of truth.

### Event Emissions

| Event Type | Trigger | Frequency | Purpose |
|------------|---------|-----------|---------|
| `sprint.created` | Create sprint | Once | Track sprint creation |
| `task.created` | Create task | Once | Track task creation |
| `session.started` | Agent init | Once per session | Track when agent begins work |
| `task.status_updated` | Status change | 2-5 per task | Track task progress |
| `activity.logged` | Agent activity | 0-10 per session | Track detailed work (optional) |
| `file.synced` | File sync | 0-3 per task | Track file system operations |
| `session.resumed` | Session resume | 0-1 per session | Track agent handoffs |

---

## Event Volume Analysis

### Typical Single Task Session

**Events Emitted**: 5-10 events
1. `sprint.created` (1)
2. `task.created` (1)
3. `session.started` (1)
4. `task.status_updated` (2-3: pending→in_progress→completed)
5. `file.synced` (0-2, optional)
6. `activity.logged` (0-3, optional)

**Database Writes**: 8-15 operations
- Sprint insert: 1
- Task insert: 1
- Task updates: 2-3 (status changes)
- Session insert: 1
- Session update: 1
- Event inserts: 5-10
- Work items: 0-3 (optional)

**File System Writes**: 0-6 files
- Sprint markdown: 0-1
- Sprint hash: 0-1
- Task markdown: 0-2 (initial + updates)
- Task hash: 0-2

### High-Volume Scenario (20 agents, 50 tasks/day)

**Daily Event Volume**: 250-500 events
- Sprint creation: ~10/day
- Task creation: ~50/day
- Status updates: ~150/day
- Sessions: ~50/day
- Activity logs: ~50/day (optional)

**Daily Database Operations**: 400-750 writes
- Events dominate volume (250-500/day)
- Task updates: 100-150/day
- Other operations: 50-100/day

**Daily File Operations**: 0-150 files
- Only if `sync_to_file: true`
- Can be disabled entirely for performance

---

## Recommendations for Tracking & Logging

### ✅ Keep (High Value)

1. **Sprint/Task Creation Events**
   - Rare, high-value events
   - Essential for audit trail
   - Low volume

2. **Task Status Updates**
   - Critical for progress tracking
   - Enables timeline reconstruction
   - Moderate volume

3. **Session Start/End**
   - Essential for agent handoff
   - Enables work session analysis
   - Low volume

4. **Database Hash Tracking**
   - Enables rollback/replay
   - Minimal overhead
   - High value for debugging

### ⚠️ Consider Making Optional

1. **Activity Logging** (`activity.logged`)
   - Very high frequency potential
   - Adds significant DB load
   - **Recommendation**: Make opt-in per agent, sample at 10%

2. **File Sync Events** (`file.synced`)
   - Duplicates information already in DB
   - Low value for most workflows
   - **Recommendation**: Only emit if `sync_to_file: true`

3. **Detailed Work Items**
   - High volume with limited utility
   - **Recommendation**: Reserve for high-priority tasks or debugging

### 🔄 Optimize

1. **Event Archiving**
   - Automatically archive events > 90 days
   - Reduce query load on hot table
   - **Already implemented**: `orchestration:archive-events`

2. **File Sync Strategy**
   - Default to `sync_to_file: false` for API operations
   - Sync on demand or via scheduled job
   - **Already supported**: Optional parameter

3. **Event Sampling**
   - For high-frequency events (activity logs), sample at 10-20%
   - Full logging only for P0/P1 tasks
   - **Future enhancement**

---

## Configuration Recommendations

### Default Settings (Production)
```php
// config/orchestration.php
return [
    'events' => [
        'emit_activity_logs' => false,  // Disable by default
        'emit_file_syncs' => false,     // Disable by default
        'activity_sample_rate' => 0.1,  // 10% when enabled
    ],
    'file_sync' => [
        'auto_sync' => false,           // Manual sync only
        'sync_on_complete' => true,     // Sync when task completes
    ],
    'archiving' => [
        'retention_days' => 90,
        'high_priority_retention' => null, // Keep P0/P1 forever
    ],
];
```

### Per-Task Override
```json
{
  "task_code": "debug-critical-issue",
  "priority": "P0",
  "agent_config": {
    "emit_activity_logs": true,  // Full logging for P0
    "activity_sample_rate": 1.0,
    "auto_sync": true
  }
}
```

---

## Monitoring Dashboards

### Essential Metrics
1. **Event throughput**: events/minute
2. **Task completion rate**: tasks completed/day
3. **Average session duration**: minutes per task
4. **Event table growth**: rows/day, disk size
5. **File sync operations**: syncs/hour (if enabled)

### Alert Thresholds
- Event rate > 1000/min: Check for runaway agent
- Task failure rate > 10%: Investigate acceptance criteria
- Event table > 1M rows: Run archiving
- Session duration > 4 hours: Check for stuck agent

---

**Document Version**: 1.0  
**Last Updated**: 2025-10-13  
**Owner**: Engineering Team  
**Review Date**: 2025-11-13 (monthly review of tracking strategy)
