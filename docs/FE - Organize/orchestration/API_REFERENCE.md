# Orchestration API v2 - API Reference

**Version**: 2.0  
**Base URL**: `/api/orchestration`  
**Authentication**: Session-based (inherited from Laravel)

---

## Sprint Endpoints

### List Sprints
```http
GET /api/orchestration/sprints
```

**Query Parameters**:
- `status` (optional): Filter by status (`planning`, `active`, `completed`)
- `page` (optional): Page number for pagination
- `per_page` (optional): Results per page (default: 15, max: 100)

**Response** (200 OK):
```json
{
  "data": [
    {
      "id": 1,
      "sprint_code": "orchestration-api-v2",
      "title": "Orchestration API v2 - Database-Backed PM System",
      "status": "active",
      "owner": "Engineering Team",
      "metadata": {
        "goal": "Build database-backed PM system",
        "start_date": "2025-10-12"
      },
      "file_path": "delegation/sprints/orchestration-api-v2/SPRINT.md",
      "hash": "fce04a99...",
      "created_at": "2025-10-12T10:00:00.000000Z",
      "updated_at": "2025-10-13T15:00:00.000000Z"
    }
  ],
  "links": {...},
  "meta": {...}
}
```

### Create Sprint
```http
POST /api/orchestration/sprints
```

**Request Body**:
```json
{
  "sprint_code": "my-new-sprint",
  "title": "My New Sprint",
  "status": "planning",
  "owner": "Team Name",
  "metadata": {
    "goal": "Sprint objective",
    "start_date": "2025-10-15",
    "duration": "2 weeks"
  }
}
```

**Response** (201 Created):
```json
{
  "id": 2,
  "sprint_code": "my-new-sprint",
  "title": "My New Sprint",
  "status": "planning",
  "hash": "abc123...",
  "created_at": "2025-10-13T16:00:00.000000Z"
}
```

### Get Sprint
```http
GET /api/orchestration/sprints/{code}
```

**Path Parameters**:
- `code`: Sprint code (e.g., `orchestration-api-v2`)

**Response** (200 OK):
```json
{
  "id": 1,
  "sprint_code": "orchestration-api-v2",
  "title": "Orchestration API v2",
  "status": "active",
  "owner": "Engineering Team",
  "metadata": {...},
  "file_path": "delegation/sprints/orchestration-api-v2/SPRINT.md",
  "hash": "fce04a99...",
  "tasks": [
    {
      "id": 1,
      "task_code": "phase-1-api-foundation",
      "title": "Phase 1: API Foundation",
      "status": "completed",
      "priority": "P1"
    }
  ],
  "created_at": "2025-10-12T10:00:00.000000Z",
  "updated_at": "2025-10-13T15:00:00.000000Z"
}
```

### Update Sprint
```http
PUT /api/orchestration/sprints/{code}
```

**Request Body**:
```json
{
  "status": "completed",
  "metadata": {
    "goal": "Updated goal",
    "completion_date": "2025-10-30"
  }
}
```

**Response** (200 OK): Same as Get Sprint

### Delete Sprint
```http
DELETE /api/orchestration/sprints/{code}
```

**Response** (204 No Content)

### Create Sprint from Template
```http
POST /api/orchestration/sprints/from-template
```

**Request Body**:
```json
{
  "template_name": "default",
  "sprint_code": "my-templated-sprint",
  "variables": {
    "sprint_title": "My Sprint from Template",
    "owner": "Team Name",
    "goal": "Sprint objective"
  }
}
```

**Response** (201 Created): Sprint object with generated tasks

### Sync Sprint to File System
```http
POST /api/orchestration/sprints/{code}/sync
```

**Response** (200 OK):
```json
{
  "success": true,
  "file_path": "delegation/sprints/my-sprint/SPRINT.md",
  "synced_at": "2025-10-13T16:30:00.000000Z"
}
```

---

## Task Endpoints

### List Tasks
```http
GET /api/orchestration/tasks
```

**Query Parameters**:
- `sprint_code` (optional): Filter by sprint
- `status` (optional): Filter by status
- `priority` (optional): Filter by priority (`P0`, `P1`, `P2`, `P3`)
- `page`, `per_page`: Pagination

**Response** (200 OK): Paginated task list

### Create Task
```http
POST /api/orchestration/tasks
```

**Request Body**:
```json
{
  "task_code": "new-task",
  "title": "New Task Title",
  "status": "pending",
  "priority": "P2",
  "sprint_id": 1,
  "metadata": {
    "objectives": ["Objective 1", "Objective 2"],
    "deliverables": ["Deliverable 1"],
    "acceptance_criteria": ["Criterion 1"]
  },
  "agent_config": {
    "model": "claude-3-5-sonnet",
    "temperature": 0.7
  }
}
```

**Response** (201 Created): Task object

### Get Task
```http
GET /api/orchestration/tasks/{code}
```

**Response** (200 OK): Task object with sprint relationship

### Update Task
```http
PUT /api/orchestration/tasks/{code}
```

**Request Body**: Partial task object

**Response** (200 OK): Updated task object

### Delete Task
```http
DELETE /api/orchestration/tasks/{code}
```

**Response** (204 No Content)

### Create Tasks from Template
```http
POST /api/orchestration/sprints/{code}/tasks/from-template
```

**Request Body**:
```json
{
  "template_name": "phase-tasks",
  "tasks": [
    {
      "task_code": "phase-1",
      "variables": {
        "title": "Phase 1 Task",
        "priority": "P1"
      }
    }
  ]
}
```

**Response** (201 Created): Array of created tasks

---

## Event Endpoints

### List Events
```http
GET /api/orchestration/events
```

**Query Parameters**:
- `entity_type`: Filter by entity type (`sprint`, `task`, `agent`, `session`)
- `entity_id`: Filter by entity ID
- `event_type`: Filter by event type
- `session_key`: Filter by session
- `from`, `to`: Date range filters (ISO 8601)
- `archived`: Include archived events (`true`/`false`)
- `page`, `per_page`: Pagination

**Response** (200 OK):
```json
{
  "data": [
    {
      "id": 1,
      "event_type": "orchestration.sprint.created",
      "entity_type": "sprint",
      "entity_id": 1,
      "correlation_id": "uuid",
      "session_key": null,
      "agent_id": null,
      "payload": {
        "actor": "system",
        "timestamp": "2025-10-12T10:00:00Z",
        "entity_snapshot": {...}
      },
      "emitted_at": "2025-10-12T10:00:00.000000Z",
      "archived_at": null
    }
  ],
  "links": {...},
  "meta": {...}
}
```

### Get Events by Correlation ID
```http
GET /api/orchestration/events/correlation/{correlationId}
```

**Response** (200 OK): Array of correlated events

### Get Events by Session
```http
GET /api/orchestration/events/session/{sessionKey}
```

**Response** (200 OK): Array of session events

### Get Event Timeline
```http
GET /api/orchestration/events/timeline
```

**Query Parameters**:
- `entity_type`, `entity_id`: Entity filter
- `from`, `to`: Date range
- `limit`: Max events (default: 100)

**Response** (200 OK): Chronological event array

### Get Event Stats
```http
GET /api/orchestration/events/stats
```

**Query Parameters**:
- `from`, `to`: Date range
- `group_by`: Grouping (`hour`, `day`, `week`, `event_type`, `entity_type`)

**Response** (200 OK):
```json
{
  "total_events": 1543,
  "by_type": {
    "orchestration.sprint.created": 5,
    "orchestration.task.created": 23,
    "orchestration.task.status_updated": 456
  },
  "by_entity": {
    "sprint": 15,
    "task": 1528
  },
  "date_range": {
    "from": "2025-10-01",
    "to": "2025-10-13"
  }
}
```

### Replay Events
```http
POST /api/orchestration/events/replay
```

**Request Body**:
```json
{
  "correlation_id": "uuid",
  "dry_run": true,
  "target_timestamp": "2025-10-13T12:00:00Z"
}
```

**Response** (200 OK):
```json
{
  "success": true,
  "dry_run": true,
  "replayed_events": 5,
  "reconstructed_state": {...},
  "validation": {
    "valid": true,
    "errors": []
  }
}
```

### Get Sprint Event History
```http
GET /api/orchestration/sprints/{code}/history
```

**Response** (200 OK): Sprint-specific events

### Get Task Event History
```http
GET /api/orchestration/tasks/{code}/history
```

**Response** (200 OK): Task-specific events

---

## Agent Initialization Endpoints

### Initialize Agent
```http
POST /api/orchestration/agent/init
```

**Request Body**:
```json
{
  "entity_type": "task",
  "entity_code": "phase-1-api-foundation",
  "agent_id": 123,
  "resume_session": null
}
```

**Response** (200 OK):
```json
{
  "session": {
    "key": "agent-session-uuid",
    "entity_type": "task",
    "entity_code": "phase-1-api-foundation",
    "agent_id": 123,
    "created_at": "2025-10-13T16:00:00Z"
  },
  "context": {
    "task": {...},
    "sprint": {...},
    "events": [...],
    "files": {
      "task_md": "...",
      "agent_yml": "..."
    },
    "next_steps": ["Step 1", "Step 2"]
  }
}
```

### Get Session Context
```http
GET /api/orchestration/sessions/{sessionKey}/context
```

**Response** (200 OK): Full context object

### Log Session Activity
```http
POST /api/orchestration/sessions/{sessionKey}/activity
```

**Request Body**:
```json
{
  "activity_type": "code_generated",
  "details": {
    "files_changed": 3,
    "lines_added": 150
  }
}
```

**Response** (200 OK):
```json
{
  "success": true,
  "activity_logged": true,
  "session_key": "uuid"
}
```

---

## PM Tools Endpoints

### Generate ADR
```http
POST /api/orchestration/pm-tools/adr
```

**Request Body**:
```json
{
  "title": "Use PostgreSQL for Vector Search",
  "deciders": "Architecture Team",
  "context": "Need efficient similarity search for embeddings",
  "decision": "Use pgvector extension with PostgreSQL"
}
```

**Response** (200 OK):
```json
{
  "success": true,
  "file_path": "/path/to/docs/adr/ADR-006-use-postgresql-for-vector-search.md",
  "adr_number": 6,
  "file_name": "ADR-006-use-postgresql-for-vector-search.md"
}
```

### Create Bug Report
```http
POST /api/orchestration/pm-tools/bug-report
```

**Request Body**:
```json
{
  "title": "Modal Navigation Bug",
  "priority": "P1",
  "category": "UI/UX",
  "component": "Chat Modals",
  "effort": "2-3 hours",
  "description": "Ghost modals appear when switching commands",
  "reproduction_steps": "1. Open /sprints\n2. Select sprint\n3. Close modal\n4. Open /tasks",
  "expected_behavior": "Only tasks modal appears",
  "actual_behavior": "Sprint detail modal appears first"
}
```

**Response** (200 OK):
```json
{
  "success": true,
  "file_path": "/path/to/delegation/backlog/modal-navigation-bug.md",
  "file_name": "modal-navigation-bug.md",
  "priority": "P1"
}
```

### Update Task Status
```http
POST /api/orchestration/pm-tools/task-status
```

**Request Body**:
```json
{
  "task_code": "phase-5-pm-commands",
  "status": "completed",
  "notes": "All PM tools implemented and tested",
  "agent_id": 123,
  "session_key": "uuid",
  "emit_event": true,
  "sync_to_file": true
}
```

**Response** (200 OK):
```json
{
  "success": true,
  "task_code": "phase-5-pm-commands",
  "old_status": "in_progress",
  "new_status": "completed",
  "updated_at": "2025-10-13T17:00:00.000000Z"
}
```

### Get Sprint Status Report
```http
GET /api/orchestration/pm-tools/status-report?sprint_code=orchestration-api-v2
```

**Response** (200 OK):
```json
{
  "sprint_code": "orchestration-api-v2",
  "sprint_title": "Orchestration API v2 - Database-Backed PM System",
  "sprint_status": "active",
  "summary": {
    "total_tasks": 6,
    "completed": 5,
    "in_progress": 1,
    "blocked": 0,
    "pending": 0,
    "progress_percentage": 83.33
  },
  "tasks": [
    {
      "task_code": "phase-1-api-foundation",
      "title": "Phase 1: API Foundation",
      "status": "completed",
      "priority": "P1"
    }
  ]
}
```

---

## Template Endpoints

### List Templates
```http
GET /api/orchestration/templates
```

**Query Parameters**:
- `type`: Filter by type (`sprint`, `task`, `agent`, `docs`)

**Response** (200 OK):
```json
{
  "templates": [
    {
      "type": "sprint",
      "name": "default",
      "path": "delegation/.templates/sprint-template/SPRINT_TEMPLATE.md"
    },
    {
      "type": "task",
      "name": "default",
      "path": "delegation/.templates/task-template/TASK_TEMPLATE.md"
    }
  ]
}
```

### Get Template
```http
GET /api/orchestration/templates/{type}/{name}
```

**Path Parameters**:
- `type`: Template type (`sprint`, `task`, `agent`, `docs`)
- `name`: Template name (e.g., `default`, `phase-template`)

**Response** (200 OK):
```json
{
  "type": "sprint",
  "name": "default",
  "content": "# Sprint: {{sprint_title}}\n...",
  "variables": ["sprint_title", "owner", "goal"]
}
```

---

## Error Responses

### 400 Bad Request
```json
{
  "message": "Validation error",
  "errors": {
    "sprint_code": ["The sprint code field is required."],
    "status": ["The selected status is invalid."]
  }
}
```

### 404 Not Found
```json
{
  "message": "Sprint not found"
}
```

### 422 Unprocessable Entity
```json
{
  "message": "Invalid input",
  "errors": {
    "priority": ["Priority must be one of: P0, P1, P2, P3"]
  }
}
```

### 500 Internal Server Error
```json
{
  "message": "Internal server error",
  "correlation_id": "uuid"
}
```

---

## Rate Limiting

- **Default**: 60 requests/minute per IP
- **Authenticated**: 1000 requests/minute per user
- **Headers**: `X-RateLimit-Limit`, `X-RateLimit-Remaining`, `X-RateLimit-Reset`

---

## Pagination

All list endpoints support pagination:

**Query Parameters**:
- `page`: Page number (default: 1)
- `per_page`: Items per page (default: 15, max: 100)

**Response Meta**:
```json
{
  "meta": {
    "current_page": 1,
    "from": 1,
    "to": 15,
    "total": 42,
    "per_page": 15,
    "last_page": 3
  },
  "links": {
    "first": "/api/orchestration/sprints?page=1",
    "last": "/api/orchestration/sprints?page=3",
    "prev": null,
    "next": "/api/orchestration/sprints?page=2"
  }
}
```

---

**API Version**: 2.0  
**Last Updated**: 2025-10-13  
**Maintainer**: Engineering Team
