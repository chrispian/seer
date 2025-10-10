# Sprint Workflow Process

This document defines our workflow for executing sprints with proper orchestration system tracking.

---

## Overview

We use the orchestration system to track:
- **Sprints**: Groupings of related tasks
- **Tasks**: Individual work items with status, content, and metadata
- **Agents**: AI agents assigned to tasks (when applicable)

---

## Sprint Lifecycle

### 1. Sprint Planning (Before Starting)

#### Create Sprint
```bash
php artisan orchestration:sprint:save SPRINT-CODE \
  --title="Sprint Title" \
  --status="Planned" \
  --estimate="X-Y hours"
```

#### Attach Tasks to Sprint
Use MCP tool or command:
```bash
# Via MCP (preferred for multiple tasks)
orchestration_orchestration_sprints_attach_tasks(
  sprint: "SPRINT-CODE",
  tasks: ["T-TASK-01", "T-TASK-02", "T-TASK-03"]
)
```

#### View Sprint Overview
```bash
php artisan orchestration:sprint:detail SPRINT-CODE
```

---

### 2. Sprint Execution (During Work)

#### Start Sprint
```bash
php artisan orchestration:sprint:status SPRINT-CODE --status="In Progress"
```

#### For Each Task in Sprint:

##### A. Start Task
```bash
# Mark task as in_progress
php artisan orchestration:task:status T-TASK-01 --status=in_progress

# View task details
php artisan orchestration:task:detail T-TASK-01
```

##### B. Update Task Content (As You Work)

**agent_content** - Detailed context for AI agents:
```bash
orchestration_orchestration_tasks_save(
  task_code: "T-TASK-01",
  agent_content: "Detailed technical approach, file locations, dependencies, etc."
)
```

**plan_content** - Implementation plan/checklist:
```bash
orchestration_orchestration_tasks_save(
  task_code: "T-TASK-01",
  plan_content: "## Implementation Steps\n1. Create migration\n2. Add indexes\n3. Test"
)
```

**context_content** - Reference materials, links, notes:
```bash
orchestration_orchestration_tasks_save(
  task_code: "T-TASK-01",
  context_content: "See docs/UNIFIED_ARCHITECTURE.md for schema details"
)
```

**todo_content** - JSON array of sub-tasks (for complex tasks):
```bash
orchestration_orchestration_tasks_save(
  task_code: "T-TASK-01",
  todo_content: '[
    {"id": "1", "content": "Create migration file", "status": "completed"},
    {"id": "2", "content": "Add indexes", "status": "in_progress"},
    {"id": "3", "content": "Run migration", "status": "pending"}
  ]'
)
```

##### C. Complete Task
```bash
# Mark complete with summary
orchestration_orchestration_tasks_save(
  task_code: "T-TASK-01",
  status: "completed",
  summary_content: "Created types_registry migration with storage_type enum, model_class, schema fields. Added indexes for performance."
)
```

---

### 3. Sprint Completion (After All Tasks Done)

#### Review Sprint
```bash
php artisan orchestration:sprint:detail SPRINT-CODE
```

#### Mark Sprint Complete
```bash
php artisan orchestration:sprint:status SPRINT-CODE \
  --status="Completed" \
  --note="All tasks completed. DB schema implemented and tested."
```

#### Create PR (If Applicable)
```bash
# After creating PR on GitHub
orchestration_orchestration_tasks_save(
  task_code: "T-TASK-01",
  pr_url: "https://github.com/owner/repo/pull/123"
)
```

---

## Task Content Fields Reference

### agent_content
**Purpose**: Detailed instructions for AI agents  
**Format**: Markdown  
**Use When**: Task requires AI agent execution  
**Example**:
```markdown
## Technical Approach
Create migration in `database/migrations/YYYY_MM_DD_create_types_registry_table.php`

## Schema Details
- storage_type: ENUM('model', 'fragment')
- model_class: VARCHAR(255) NULL
- schema: JSON NULL

## Dependencies
- Must run after fragment_type_registry migration
- Requires fresh Type model

## Testing
```bash
php artisan migrate:fresh --seed
php artisan tinker --execute="Type::count()"
```
```

### plan_content
**Purpose**: Implementation checklist/plan  
**Format**: Markdown (often numbered list)  
**Use When**: Breaking down task into steps  
**Example**:
```markdown
## Implementation Plan

1. Create migration file
   - Add storage_type enum
   - Add model_class VARCHAR(255) NULL
   - Add schema JSON NULL
   - Add default_card_component, default_detail_component
   - Add indexes

2. Test migration
   - Run migrate:fresh
   - Verify table structure
   - Check indexes exist

3. Update existing data (if needed)
   - Migrate from fragment_type_registry
   - Set storage_type values
```

### context_content
**Purpose**: Reference materials and notes  
**Format**: Markdown  
**Use When**: Providing background, links, related docs  
**Example**:
```markdown
## Related Documentation
- See `docs/UNIFIED_ARCHITECTURE.md` section "Database Schema"
- See `docs/UNIFICATION_SPRINT_SUMMARY.md` for overview

## Key Decisions
- storage_type distinguishes model-backed vs fragment-backed types
- model_class NULL for fragment-backed types
- schema JSON for fragment-backed types only

## Database Location
Table: `types_registry`
Model: `App\Models\Type` (to be created in T-UNIFY-03)
```

### todo_content
**Purpose**: Sub-task tracking for complex tasks  
**Format**: JSON array  
**Use When**: Task has multiple discrete steps  
**Example**:
```json
[
  {
    "id": "1",
    "content": "Create migration file",
    "status": "completed",
    "priority": "high"
  },
  {
    "id": "2",
    "content": "Add storage_type enum field",
    "status": "completed",
    "priority": "high"
  },
  {
    "id": "3",
    "content": "Add indexes for performance",
    "status": "in_progress",
    "priority": "medium"
  },
  {
    "id": "4",
    "content": "Test migration rollback",
    "status": "pending",
    "priority": "low"
  }
]
```

### summary_content
**Purpose**: Final summary of what was accomplished  
**Format**: Markdown (brief)  
**Use When**: Marking task completed  
**Example**:
```markdown
Created types_registry migration with full schema:
- storage_type enum (model/fragment) ✅
- model_class and schema fields ✅
- UI component configuration fields ✅
- Indexes for performance ✅
- Migration tested and working ✅

Files created: `database/migrations/2025_10_10_create_types_registry_table.php`
```

### pr_url
**Purpose**: Link to GitHub pull request  
**Format**: URL string  
**Use When**: Task changes pushed to PR  
**Example**: `https://github.com/owner/repo/pull/123`

---

## Quick Reference Commands

### View All Sprint Tasks
```bash
php artisan orchestration:tasks --sprint=SPRINT-CODE
```

### View Specific Task
```bash
php artisan orchestration:task:detail T-TASK-01
```

### Update Task Status
```bash
php artisan orchestration:task:status T-TASK-01 --status=in_progress
php artisan orchestration:task:status T-TASK-01 --status=completed
php artisan orchestration:task:status T-TASK-01 --status=blocked
```

### Assign Task to Agent
```bash
php artisan orchestration:task:assign T-TASK-01 backend-engineer
```

### List All Sprints
```bash
php artisan orchestration:sprints
```

---

## MCP Tools Reference

### Save/Update Task
```typescript
orchestration_orchestration_tasks_save({
  task_code: "T-TASK-01",
  status: "in_progress",
  agent_content: "...",
  plan_content: "...",
  context_content: "...",
  todo_content: "[...]",
  summary_content: "...",
  pr_url: "https://..."
})
```

### Attach Tasks to Sprint
```typescript
orchestration_orchestration_sprints_attach_tasks({
  sprint: "SPRINT-CODE",
  tasks: ["T-TASK-01", "T-TASK-02", "T-TASK-03"]
})
```

### Update Sprint Status
```typescript
orchestration_orchestration_sprints_status({
  sprint: "SPRINT-CODE",
  status: "In Progress",
  note: "Starting Sprint 1: Schema & DB Foundation"
})
```

---

## Best Practices

### ✅ DO

1. **Update task status immediately** when starting/completing
2. **Write detailed agent_content** for AI-executable tasks
3. **Document decisions** in context_content
4. **Use todo_content** for complex tasks with multiple steps
5. **Write clear summaries** when completing tasks
6. **Update PR links** as soon as PR is created
7. **Keep sprint status current** (Planned → In Progress → Completed)

### ❌ DON'T

1. **Don't forget to mark tasks in_progress** before starting work
2. **Don't leave tasks hanging** - always update status
3. **Don't skip summary_content** when completing
4. **Don't forget to update sprint status** at start/end
5. **Don't use CLI commands for bulk updates** - use MCP tools instead

---

## Workflow Example

### Starting Sprint 1

```bash
# 1. Start sprint
orchestration_orchestration_sprints_status(
  sprint: "SPRINT-UNIFY-1",
  status: "In Progress",
  note: "Beginning Schema & DB Foundation sprint"
)

# 2. Start first task
php artisan orchestration:task:status T-UNIFY-01 --status=in_progress

# 3. Add detailed plan
orchestration_orchestration_tasks_save(
  task_code: "T-UNIFY-01",
  plan_content: "## Steps\n1. Create migration\n2. Add fields\n3. Test"
)

# 4. Work on task...
# (create migration, write code, test)

# 5. Complete task with summary
orchestration_orchestration_tasks_save(
  task_code: "T-UNIFY-01",
  status: "completed",
  summary_content: "Created types_registry migration with storage_type enum..."
)

# 6. Move to next task
php artisan orchestration:task:status T-UNIFY-02 --status=in_progress

# ... repeat for all tasks ...

# 7. Complete sprint
orchestration_orchestration_sprints_status(
  sprint: "SPRINT-UNIFY-1",
  status: "Completed",
  note: "All 5 tasks completed. DB schema ready."
)
```

---

## Status Values Reference

### Task Status
- `todo` - Not started
- `in_progress` - Currently working on
- `blocked` - Blocked by dependency or issue
- `completed` - Finished successfully
- `cancelled` - No longer needed

### Sprint Status
- `Planned` - Created but not started
- `In Progress` - Currently executing
- `Completed` - All tasks done
- `On Hold` - Paused temporarily
- `Cancelled` - No longer pursuing

### Delegation Status (Tasks)
- `unassigned` - No agent assigned
- `assigned` - Agent assigned but not started
- `in_progress` - Agent working on task
- `blocked` - Agent blocked by dependency
- `completed` - Agent finished task

---

**Document Version:** 1.0  
**Last Updated:** 2025-10-10  
**Status:** Ready for Use
