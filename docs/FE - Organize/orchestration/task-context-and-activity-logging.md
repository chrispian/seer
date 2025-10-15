# Task Context & Activity Logging System

**Status**: Planned  
**Sprint**: SPRINT-ORCH-CONTEXT  
**Created**: 2025-10-07

## Overview

Comprehensive task context storage and activity logging system that eliminates the need for delegation folder files by storing all task context in the database with full audit trail capabilities.

### Core Principles

1. **Self-Contained Tasks** - Everything stored in database, queryable via SQL
2. **Activity Audit Trail** - Complete lifecycle tracking of task execution
3. **Database-First** - New tasks created directly in database, no markdown files
4. **Artifacts for Large Files** - Use existing Postmaster/Artifacts for files >100KB
5. **Dispositioned Tasks** - Clear completion state with summary content

---

## Architecture

### Task Context Storage

**Location**: `work_items` table (existing)

**Content Fields** (already exist):
```php
work_items:
  - agent_content (TEXT)       // Agent instructions, profile
  - plan_content (TEXT)        // Implementation plan, steps  
  - context_content (TEXT)     // Background, links, research
  - todo_content (TEXT)        // Checklist, acceptance criteria
  - summary_content (TEXT)     // Completion summary, outcomes
  - metadata (JSONB)           // Task code, sprint, description
  - delegation_context (JSONB) // Agent-specific context
  - delegation_history (JSONB) // Legacy status change log
```

**Content Guidelines**:
| Field | Purpose | Typical Size | Legacy Source |
|-------|---------|--------------|---------------|
| `agent_content` | Agent instructions | 1-5KB | delegation/AGENT.md |
| `plan_content` | Implementation plan | 2-10KB | delegation/PLAN.md |
| `context_content` | Background info | 5-20KB | delegation/CONTEXT.md |
| `todo_content` | Checklist items | 1-5KB | delegation/TODO.md |
| `summary_content` | Task outcome | 1-5KB | Created at completion |

### Activity Logging

**New Table**: `task_activities`

```sql
CREATE TABLE task_activities (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    task_id UUID NOT NULL REFERENCES work_items(id) ON DELETE CASCADE,
    agent_id UUID REFERENCES agent_profiles(id) ON DELETE SET NULL,
    user_id BIGINT REFERENCES users(id) ON DELETE SET NULL,
    
    -- Activity classification
    activity_type VARCHAR(50) NOT NULL,  -- status_change, content_update, assignment, note, error
    action VARCHAR(100) NOT NULL,        -- started, completed, updated_plan, added_note
    
    -- Activity details
    description TEXT,                    -- Human-readable description
    changes JSONB,                       -- Before/after for updates
    metadata JSONB,                      -- Additional context (tool calls, file changes, etc.)
    
    -- Timestamp
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    
    -- Indexes
    INDEX idx_task_activities_task (task_id, created_at DESC),
    INDEX idx_task_activities_type (activity_type, created_at DESC),
    INDEX idx_task_activities_agent (agent_id, created_at DESC)
);
```

### Activity Types

#### `status_change`
Status transitions (todo → in_progress → completed)

**Example**:
```json
{
  "activity_type": "status_change",
  "action": "started",
  "description": "Agent started working on task",
  "changes": {
    "from": "todo",
    "to": "in_progress"
  },
  "metadata": {
    "delegation_status": "in_progress"
  }
}
```

#### `content_update`
Updates to agent_content, plan_content, context_content, etc.

**Example**:
```json
{
  "activity_type": "content_update",
  "action": "updated_plan",
  "description": "Updated implementation plan with new approach",
  "changes": {
    "field": "plan_content",
    "from_hash": "abc123...",
    "to_hash": "def456...",
    "diff_summary": "Added Step 5: Database migration"
  }
}
```

#### `assignment`
Agent/user assignments and reassignments

**Example**:
```json
{
  "activity_type": "assignment",
  "action": "assigned",
  "description": "Task assigned to backend-engineer",
  "changes": {
    "agent_id": "agent-uuid",
    "agent_name": "backend-engineer-template"
  }
}
```

#### `note`
Progress updates, observations, decisions

**Example**:
```json
{
  "activity_type": "note",
  "action": "progress_update",
  "description": "Completed database migration, now working on API endpoints",
  "metadata": {
    "files_changed": ["database/migrations/...", "app/Http/Controllers/..."],
    "tests_passing": true
  }
}
```

#### `error`
Errors encountered during execution

**Example**:
```json
{
  "activity_type": "error",
  "action": "test_failure",
  "description": "Integration tests failing due to missing database seed",
  "metadata": {
    "test_file": "tests/Feature/TaskApiTest.php",
    "error_message": "Database table 'tasks' not found"
  }
}
```

#### `artifact_attached`
Large files stored via Postmaster/Artifacts

**Example**:
```json
{
  "activity_type": "artifact_attached",
  "action": "file_uploaded",
  "description": "Attached SQL schema dump",
  "metadata": {
    "artifact_id": "artifact-uuid",
    "fe_uri": "fe://artifacts/by-task/task-uuid/schema.sql",
    "filename": "schema.sql",
    "size_bytes": 2048576
  }
}
```

---

## Task Lifecycle with Activity Logging

### Creation
```
User/PM creates task
  ↓ activity: task_created
Initialize agent_content, plan_content, context_content, todo_content
  ↓ activity: content_initialized
```

### Assignment
```
Assign to agent
  ↓ activity: assignment (agent_id, status=assigned)
Agent starts work
  ↓ activity: status_change (todo → in_progress)
```

### Execution
```
Agent updates plan
  ↓ activity: content_update (field=plan_content, changes={...})
Agent adds progress note
  ↓ activity: note (description="Completed Step 3")
Agent encounters error
  ↓ activity: error (description="Build failed...")
Agent attaches large file
  ↓ Postmaster → OrchestrationArtifact
  ↓ activity: artifact_attached (artifact_id, fe_uri)
```

### Completion
```
Agent completes task
  ↓ activity: status_change (in_progress → completed)
Agent writes summary
  ↓ activity: content_update (field=summary_content)
Task dispositioned
```

---

## When to Use Artifacts vs Database

### Store in Database (work_items table)
- ✅ Task descriptions, plans, context (<10MB total)
- ✅ Markdown content, code snippets
- ✅ Acceptance criteria, checklists
- ✅ Progress notes, summaries
- ✅ Most task-related text content

### Store in Artifacts (via Postmaster)
- ✅ Large files (>100KB): datasets, reports, archives
- ✅ Binary files: PDFs, images, videos
- ✅ External context packs (sent via PM→Agent)
- ✅ Generated artifacts: test results, build logs
- ❌ **NOT** for task descriptions or small text files

---

## Delegation Folder Migration

### Current Delegation Structure
```
delegation/
├── sprints/SPRINT-67/
│   ├── SPRINT.md
│   └── T-OBS-16/TASK.md
├── backlog/
│   └── [task-name]/
│       ├── AGENT.md      (~100 lines)
│       ├── CONTEXT.md    (~150 lines)
│       ├── PLAN.md       (~120 lines)
│       └── TODO.md       (~80 lines)
```

### Migration Strategy

**Phase 1: Import Existing Tasks**
```bash
php artisan orchestration:import-delegation
```

- Parse delegation/*.md files
- Populate `work_items` content fields
- Keep files as read-only archive in git
- Mark with `metadata.imported_from_delegation = true`

**Phase 2: New Workflow**
- Create tasks directly via MCP tools or CLI
- All content stored in database
- No new markdown files created
- Use Artifacts for large files only

**Phase 3: Archive** (optional)
- Move `delegation/` to `delegation-archive/`
- Keep for historical reference
- All new work database-only

---

## API Endpoints

### Task Activities

#### List Activities
```http
GET /api/orchestration/tasks/{taskId}/activities?type=note&page=1
```

**Response**:
```json
{
  "data": [
    {
      "id": "activity-uuid",
      "activity_type": "note",
      "action": "progress_update",
      "description": "Completed database migration",
      "agent": {
        "id": "agent-uuid",
        "name": "backend-engineer"
      },
      "created_at": "2025-10-07T12:34:56Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "total": 15
  }
}
```

#### Create Activity (Add Note)
```http
POST /api/orchestration/tasks/{taskId}/activities
Content-Type: application/json

{
  "activity_type": "note",
  "action": "progress_update",
  "description": "Completed Step 3, moving to Step 4",
  "metadata": {
    "files_changed": ["app/Services/MyService.php"]
  }
}
```

### Task Content

#### Update Content
```http
PUT /api/orchestration/tasks/{taskId}/content
Content-Type: application/json

{
  "field": "plan_content",
  "content": "## Updated Plan\n\n1. Step 1\n2. Step 2..."
}
```

**Auto-logs activity**: `content_update`

---

## MCP Tools

### `task-activities:list`
List activities for a task

**Arguments**:
```json
{
  "task_id": "task-uuid",
  "type": "note",
  "limit": 20
}
```

### `task-activities:log`
Add activity to task

**Arguments**:
```json
{
  "task_id": "task-uuid",
  "activity_type": "note",
  "action": "progress_update",
  "description": "Completed implementation of feature X"
}
```

### `task-content:update`
Update task content field

**Arguments**:
```json
{
  "task_id": "task-uuid",
  "field": "plan_content",
  "content": "Updated plan content..."
}
```

---

## Database Queries

### Get Task with All Context
```sql
SELECT 
  id,
  metadata->>'task_code' as task_code,
  status,
  agent_content,
  plan_content,
  context_content,
  todo_content,
  summary_content
FROM work_items
WHERE metadata->>'task_code' = 'T-OBS-16';
```

### Get Recent Activities
```sql
SELECT 
  ta.*,
  ap.name as agent_name
FROM task_activities ta
LEFT JOIN agent_profiles ap ON ta.agent_id = ap.id
WHERE ta.task_id = 'task-uuid'
ORDER BY ta.created_at DESC
LIMIT 20;
```

### Activity Summary
```sql
SELECT 
  activity_type,
  COUNT(*) as count
FROM task_activities
WHERE task_id = 'task-uuid'
GROUP BY activity_type;
```

---

## Models

### TaskActivity

**Path**: `app/Models/TaskActivity.php`

```php
class TaskActivity extends Model
{
    protected $fillable = [
        'task_id', 'agent_id', 'user_id',
        'activity_type', 'action',
        'description', 'changes', 'metadata'
    ];
    
    protected $casts = [
        'changes' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];
    
    // Relationships
    public function task(): BelongsTo
    public function agent(): BelongsTo
    public function user(): BelongsTo
    
    // Scopes
    public function scopeForTask($query, $taskId)
    public function scopeByType($query, $type)
    public function scopeRecent($query, $days = 7)
    
    // Helpers
    public static function logStatusChange(...)
    public static function logContentUpdate(...)
    public static function logNote(...)
}
```

### WorkItem (Enhanced)

Existing model, no schema changes needed. Content fields already exist:
- `agent_content`, `plan_content`, `context_content`, `todo_content`, `summary_content`

---

## Benefits

1. **Self-Contained Tasks** - Query everything via SQL
2. **Full Audit Trail** - Complete history of task execution
3. **Better Search** - Full-text search across all content
4. **No File Management** - No git commits for task updates
5. **API Access** - RESTful/MCP access to all task data
6. **Artifact Integration** - Large files still supported when needed
7. **Clean Dispositions** - Clear completion state with summary

---

## Related Systems

### Artifacts & Postmaster (SPRINT-51)
- Use for files >100KB, binary files, external context packs
- See: `docs/orchestration/postmaster-and-init.md`
- Tables: `orchestration_artifacts`, `messages`
- Services: `ContentStore`, `ProcessParcel` job

### Delegation Folder (Legacy)
- Location: `delegation/`
- Files: AGENT.md, CONTEXT.md, PLAN.md, TODO.md
- **Status**: Being phased out, will archive after migration

### Task Orchestration (Current)
- Tables: `work_items`, `task_assignments`, `sprints`
- Services: `TaskOrchestrationService`, `AgentOrchestrationService`
- Commands: `orchestration:task:*`, `orchestration:sprint:*`

---

## Implementation Status

- ⏳ Planning phase
- 📋 Tasks created in SPRINT-ORCH-CONTEXT
- 🎯 Estimated: 26-34 hours (3-4 days)
- 🔗 Dependencies: None - builds on SPRINT-51
