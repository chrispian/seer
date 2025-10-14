# Orchestration System Documentation

**Last Updated**: 2025-10-13  
**Version**: 2.0

## Overview

The Fragments Engine Orchestration System provides comprehensive task management, agent coordination, activity logging, and context storage for AI-driven development workflows. Version 2.0 adds phase-driven workflows, bug tracking, context search, and git integration.

## 📚 Documentation Index

### Start Here
- **[ARCHITECTURE.md](./ARCHITECTURE.md)** - Complete system architecture guide (read this first!)
- **[Session State Machine](./SESSION_STATE_MACHINE_IMPLEMENTATION.md)** - Phase-driven workflow implementation
- **[Task Session Lifecycle](./TASK_SESSION_LIFECYCLE.md)** - Detailed session flow analysis

### Reference
- **[API Reference](./API_REFERENCE.md)** - Complete API documentation
- **[Agent Command Reference](./AGENT_COMMAND_REFERENCE.md)** - CLI commands for agents
- **[Agent Workflow Process](./AGENT_WORKFLOW_PROCESS.md)** - Step-by-step agent guidance
- **[Performance Considerations](./PERFORMANCE_CONSIDERATIONS.md)** - Optimization and scaling

### Implementation Guides
- [Task Context & Activity Logging](./task-context-and-activity-logging.md)
- [Postmaster & Agent INIT](./postmaster-and-init.md)
- [Documentation Knowledge Base](./documentation-knowledge-base.md)

## Core Components (v2.0)

### 1. Session State Machine ✅ NEW
**Status**: ✅ Production Ready  
**Docs**: [ARCHITECTURE.md#1-session-state-machine](./ARCHITECTURE.md#1-session-state-machine)

- 6-phase workflow: Intake → Research → Plan → Execute → Review → Close
- Template-driven configuration via workflow.yaml
- Phase validation with user override
- Next-step instructions for agents
- **CLI**: `session-start`, `session-end`, `phase-complete`

### 2. Bug Tracking System ✅ NEW
**Status**: ✅ Production Ready  
**Docs**: [ARCHITECTURE.md#2-bug-tracking-system](./ARCHITECTURE.md#2-bug-tracking-system)

- Smart hashing for automatic deduplication
- Occurrence tracking (single record per bug)
- Interactive user prompts
- Error type classification
- **CLI**: `bug-log`

### 3. Context Search ✅ NEW
**Status**: ✅ Production Ready  
**Docs**: [ARCHITECTURE.md#3-context-search](./ARCHITECTURE.md#3-context-search)

- Full-text search across orchestration events
- Scope filtering (SESSION/TASK/SPRINT/PROJECT)
- Relevance scoring + caching
- Summary statistics
- **CLI**: `context-search`

### 4. Git Integration ✅ NEW
**Status**: ✅ Production Ready  
**Docs**: [ARCHITECTURE.md#4-git-integration](./ARCHITECTURE.md#4-git-integration)

- Automatic commit tracking
- PR linking with metadata
- CHANGES.md generation
- Configurable auto-commit
- **CLI**: `git-link-pr`

### 5. Task Context & Activity Logging
**Status**: ✅ Implemented  
**Docs**: [task-context-and-activity-logging.md](./task-context-and-activity-logging.md)

- Self-contained tasks with database storage
- Complete activity audit trail
- Automatic logging on changes
- Large content overflow to Artifacts

### 6. Postmaster & Agent INIT
**Status**: ✅ Implemented  
**Docs**: [postmaster-and-init.md](./postmaster-and-init.md)

- Content-addressable storage
- PM→Agent messaging
- Agent initialization protocol
- Secret redaction

### 7. Documentation Knowledge Base
**Status**: 📋 Planned  
**Docs**: [documentation-knowledge-base.md](./documentation-knowledge-base.md)

- Semantic tagging system
- Multi-dimensional organization
- Agent research workflows

## Quick Start

### Creating Tasks

**Via Tinker**:
```php
$task = WorkItem::create([
    'type' => 'task',
    'status' => 'todo',
    'priority' => 'high',
    'estimated_hours' => 3.5,
    'metadata' => [
        'task_code' => 'T-FEAT-001',
        'title' => 'Implement feature X',
        'sprint' => 'SPRINT-CURRENT',
        'description' => 'Build the thing...',
    ],
    'plan_content' => '## Plan\n\n1. Step 1\n2. Step 2',
    'context_content' => '## Context\n\nBackground info...',
]);
```

**Via CLI**:
```bash
# Import from delegation folder
php artisan orchestration:import-delegation

# Create sprint
php artisan orchestration:sprint:save SPRINT-CODE --title "Sprint Title"

# Attach tasks to sprint
php artisan orchestration:sprint:tasks:attach SPRINT-CODE task-uuid-1 task-uuid-2
```

### Logging Activities

**Via Code**:
```php
use App\Models\TaskActivity;

// Status change (automatic via TaskOrchestrationService)
TaskActivity::logStatusChange($taskId, 'todo', 'in_progress', $agentId);

// Content update
TaskActivity::logContentUpdate($taskId, 'plan_content', 'updated', $agentId);

// Progress note
TaskActivity::logNote($taskId, 'Completed Step 3, moving to Step 4', metadata: [
    'files_changed' => ['app/Services/MyService.php']
]);

// Error
TaskActivity::logError($taskId, 'Test failed: ...', metadata: [
    'test_file' => 'tests/Feature/MyTest.php'
]);
```

**Via MCP Tools**:
```
task-activities:log task_id="T-FEAT-001" description="Progress update"
task-activities:list task_id="T-FEAT-001" type="note" limit=10
task-content:update task_id="T-FEAT-001" field="plan_content" content="Updated plan"
```

### Querying Activities

**Via API**:
```http
GET /api/orchestration/tasks/{id}/activities?type=note&limit=20
POST /api/orchestration/tasks/{id}/activities
GET /api/orchestration/tasks/{id}/activities/summary
```

**Via Database**:
```sql
-- Recent activities for a task
SELECT * FROM task_activities
WHERE task_id = 'task-uuid'
ORDER BY created_at DESC
LIMIT 20;

-- Activity summary
SELECT activity_type, COUNT(*) as count
FROM task_activities
WHERE task_id = 'task-uuid'
GROUP BY activity_type;
```

## Architecture Diagrams

### Task Lifecycle

```
CREATE TASK
  ↓ activity: task_created
POPULATE CONTENT (agent_content, plan_content, context_content, todo_content)
  ↓ activity: content_initialized
ASSIGN TO AGENT
  ↓ activity: assignment
AGENT STARTS WORK
  ↓ activity: status_change (todo → in_progress)
AGENT UPDATES PLAN
  ↓ activity: content_update (field=plan_content)
AGENT LOGS PROGRESS
  ↓ activity: note
AGENT ATTACHES LARGE FILE
  ↓ Postmaster → Artifact
  ↓ activity: artifact_attached
AGENT COMPLETES
  ↓ activity: status_change (in_progress → completed)
  ↓ activity: content_update (field=summary_content)
```

### Content Storage Strategy

```
Content Size Check
  ↓
< 10MB? → Store in work_items.{field}_content
  ↓
> 10MB? → Store via ContentStore
          ↓
          OrchestrationArtifact created
          ↓
          work_items.{field}_content = "fe://..."
          ↓
          activity: artifact_attached
```

## Database Schema

### Core Tables

- `work_items` - Task records with content fields
- `task_activities` - Activity audit trail
- `task_assignments` - Agent assignments
- `orchestration_artifacts` - Large file references
- `messages` - PM→Agent messaging
- `sprints` - Sprint metadata

### Key Relationships

```sql
task_activities.task_id → work_items.id
task_activities.agent_id → agent_profiles.id
task_assignments.work_item_id → work_items.id
orchestration_artifacts.task_id → work_items.id
```

## Migration Guide

### From Delegation Folder to Database

**Step 1**: Review existing tasks
```bash
php artisan orchestration:import-delegation --dry-run
```

**Step 2**: Import
```bash
php artisan orchestration:import-delegation
```

**Step 3**: Verify
```bash
php artisan orchestration:tasks --sprint SPRINT-67
php artisan orchestration:task:detail T-OBS-16
```

**Step 4**: Archive old files
```bash
# Files have been moved to delegation-archive/
# Original delegation/ folder can be removed or kept as read-only
```

## Best Practices

### Task Content Organization

- **agent_content**: Agent instructions, profile, capabilities (1-5KB)
- **plan_content**: Implementation plan, steps, technical approach (2-10KB)
- **context_content**: Background, links, research, decisions (5-20KB)
- **todo_content**: Checklist, acceptance criteria, testing requirements (1-5KB)
- **summary_content**: Outcome, what was built, lessons learned (1-5KB)

### When to Use Artifacts

✅ **Use Artifacts** for:
- Large files >100KB (datasets, reports, schemas)
- Binary files (PDFs, images, archives)
- External context packs from PM
- Generated artifacts (test results, build logs)

❌ **Don't Use Artifacts** for:
- Task descriptions, plans, context
- Small markdown files
- Inline code snippets
- Status updates, notes

### Activity Logging Tips

1. **Be specific**: "Implemented UserService with authentication" not "Made changes"
2. **Include metadata**: File paths, test results, error messages
3. **Log errors immediately**: Don't wait until task completion
4. **Use appropriate types**: `note` for progress, `error` for failures
5. **Add context**: Link related tasks, PRs, documentation

## Troubleshooting

### Import Issues

**Problem**: Task not found during import
```
Task not found in database: T-OBS-001
```
**Solution**: Task must exist in `work_items` table first. Create via Tinker or ensure task code matches.

**Problem**: Content already exists
```
Skipping (has content, use --overwrite to replace)
```
**Solution**: Use `--overwrite` flag to replace existing content.

### Large Content Issues

**Problem**: Content >10MB not being stored
**Check**: Ensure `TaskContentService` is being used for content updates
**Solution**: Use `$contentService->updateContent()` instead of direct field assignment

### Activity Logging Issues

**Problem**: Activities not appearing in API
**Check**: Task ID is correct, activities are being created
**Solution**: Query `task_activities` table directly to verify entries exist

## Related Documentation

- [Postmaster & Agent INIT](./postmaster-and-init.md) - Large file handling
- [Documentation Knowledge Base](./documentation-knowledge-base.md) - Semantic tagging system
- [Task Context & Activity Logging](./task-context-and-activity-logging.md) - Detailed implementation guide

## API Reference

See individual documentation files for complete API specifications:
- Task Activities API: [task-context-and-activity-logging.md#api-endpoints](./task-context-and-activity-logging.md#api-endpoints)
- Artifacts API: [postmaster-and-init.md#5-artifacts-api](./postmaster-and-init.md#5-artifacts-api)
- Messaging API: [postmaster-and-init.md#4-messaging-api](./postmaster-and-init.md#4-messaging-api)

## Command Reference

```bash
# Sprint Management
php artisan orchestration:sprint:save CODE --title "Title" --status active
php artisan orchestration:sprint:detail CODE
php artisan orchestration:sprints

# Task Management
php artisan orchestration:tasks --sprint CODE
php artisan orchestration:task:detail TASK_CODE
php artisan orchestration:task:status TASK_CODE completed
php artisan orchestration:task:assign TASK_CODE AGENT_SLUG

# Content Migration
php artisan orchestration:import-delegation [--dry-run] [--overwrite]

# Postmaster
php artisan postmaster:run [--queue=postmaster]
```

## Support

For issues or questions:
1. Check this documentation
2. Review activity logs: `GET /api/orchestration/tasks/{id}/activities`
3. Check database directly for debugging
4. Review related documentation in `docs/orchestration/`
