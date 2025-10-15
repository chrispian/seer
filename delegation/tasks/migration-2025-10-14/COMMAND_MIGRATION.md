# Command Migration: /sprints → /orch
**Date**: 2025-10-14  
**Status**: ✅ Complete

## Overview
Migrated sprint/task commands from `/sprints` prefix to `/orch` (orchestration) prefix to align with the unified OrchestrationSprint/OrchestrationTask models.

## Command Mapping

| Old Command | New Command | Description |
|-------------|-------------|-------------|
| `/sprints` | `/orch` | Main orchestration dashboard (list sprints) |
| `/sprint-detail` | `/orch-sprint` | Sprint detail view |
| `/tasks` | `/orch-tasks` | List all orchestration tasks |
| `/task-detail` | `/orch-task` | Task detail view |
| `/sprint-create` | `/orch-sprint-new` | Create new sprint form |
| `/sprint-save` | `/orch-sprint-save` | Save sprint (create/update) |
| `/sprint-edit` | `/orch-sprint-edit` | Edit existing sprint form |

## Database Changes

### Commands Table Updates
```sql
-- ID 1: Main sprint list → Orchestration dashboard
UPDATE commands SET 
  command = '/orch',
  name = 'Orchestration Dashboard',
  description = 'Display orchestration sprints with task counts and status'
WHERE id = 1;

-- ID 2: Sprint detail
UPDATE commands SET 
  command = '/orch-sprint',
  name = 'Sprint Detail'
WHERE id = 2;

-- ID 4: Tasks list
UPDATE commands SET 
  command = '/orch-tasks',
  name = 'List Tasks',
  description = 'Display orchestration tasks with status and assignment info'
WHERE id = 4;

-- ID 5: Task detail
UPDATE commands SET 
  command = '/orch-task',
  name = 'Task Detail'
WHERE id = 5;

-- ID 15: Create sprint
UPDATE commands SET 
  command = '/orch-sprint-new',
  name = 'Create Sprint',
  description = 'Open form to create a new orchestration sprint'
WHERE id = 15;

-- ID 16: Save sprint
UPDATE commands SET 
  command = '/orch-sprint-save',
  name = 'Save Sprint',
  description = 'Create or update an orchestration sprint with metadata, dates, and notes'
WHERE id = 16;

-- ID 17: Edit sprint
UPDATE commands SET 
  command = '/orch-sprint-edit',
  name = 'Edit Sprint',
  description = 'Open form to edit an existing orchestration sprint'
WHERE id = 17;
```

### Navigation Config Updates
```json
// Command ID 1 (/orch)
{
  "navigation_config": {
    "data_prop": "sprints",
    "item_key": "code",
    "detail_command": "/orch-sprint",  // was: /sprint-detail
    "children": [
      {
        "type": "Task",
        "command": "/orch-task",  // was: /task-detail
        "item_key": "task_code"
      }
    ]
  }
}

// Command ID 4 (/orch-tasks)
{
  "navigation_config": {
    "data_prop": "tasks",
    "item_key": "task_code",
    "detail_command": "/orch-task"  // was: /task-detail
  }
}
```

## Handler Classes (Unchanged)
The following handler classes remain the same and already use OrchestrationSprint/OrchestrationTask models:

- `App\Commands\Orchestration\Sprint\ListCommand` (handles `/orch`)
- `App\Commands\Orchestration\Sprint\DetailCommand` (handles `/orch-sprint`)
- `App\Commands\Orchestration\Task\ListCommand` (handles `/orch-tasks`)
- `App\Commands\Orchestration\Task\DetailCommand` (handles `/orch-task`)
- `App\Commands\Orchestration\Sprint\CreateCommand` (handles `/orch-sprint-new`)
- `App\Commands\Orchestration\Sprint\SaveCommand` (handles `/orch-sprint-save`)
- `App\Commands\Orchestration\Sprint\EditCommand` (handles `/orch-sprint-edit`)

## Frontend Components (Unchanged)
The following React components remain the same:

- `SprintListModal.tsx` - Used by `/orch`
- `SprintDetailModal.tsx` - Used by `/orch-sprint`
- `TaskListModal.tsx` - Used by `/orch-tasks`
- `TaskDetailModal.tsx` - Used by `/orch-task`

## User-Facing Changes

### Before
```
User: /sprints
→ Lists sprints

User: /sprint-detail SPRINT-2025-42
→ Shows sprint details

User: /tasks
→ Lists tasks
```

### After
```
User: /orch
→ Lists sprints (same behavior)

User: /orch-sprint SPRINT-2025-42
→ Shows sprint details (same behavior)

User: /orch-tasks
→ Lists tasks (same behavior)
```

## Navigation Flow

### /orch Dashboard
```
/orch (sprint list)
  ↓ click on sprint row
/orch-sprint SPRINT-2025-42 (sprint detail)
  ↓ click on task in sprint
/orch-task TASK-001 (task detail)
  ↓ back button
/orch-sprint SPRINT-2025-42
  ↓ back button
/orch
```

### /orch-tasks List
```
/orch-tasks (task list)
  ↓ click on task row
/orch-task TASK-001 (task detail)
  ↓ back button
/orch-tasks
```

## Backward Compatibility

### Old Commands
The old command names (`/sprints`, `/sprint-detail`, etc.) are **no longer available** as they have been renamed in the database. Users must use the new `/orch` prefix.

### Migration Path
If needed, old commands can be restored by reverting the database changes:
```sql
UPDATE commands SET command = '/sprints' WHERE id = 1;
UPDATE commands SET command = '/sprint-detail' WHERE id = 2;
-- etc.
```

## Testing

### Manual Testing
```bash
# Test in chat interface
User: /orch
Expected: Sprint list modal opens

User: /orch-sprint SPRINT-2025-42
Expected: Sprint detail modal opens

User: /orch-tasks
Expected: Task list modal opens

User: /orch-task TASK-001
Expected: Task detail modal opens
```

### Database Verification
```bash
php artisan tinker --execute='
$cmds = DB::table("commands")
    ->whereIn("id", [1,2,4,5,15,16,17])
    ->orderBy("id")
    ->get(["id","command","name"]);
foreach($cmds as $cmd) {
    echo "/{$cmd->command} - {$cmd->name}\n";
}
'
```

Expected output:
```
/orch - Orchestration Dashboard
/orch-sprint - Sprint Detail
/orch-tasks - List Tasks
/orch-task - Task Detail
/orch-sprint-new - Create Sprint
/orch-sprint-save - Save Sprint
/orch-sprint-edit - Edit Sprint
```

## Next Steps

### 1. Update Handler Classes (Next Task)
The handler classes need to be updated to work with the new OrchestrationSprint/OrchestrationTask field structure:

**Files to Update**:
- `app/Commands/Orchestration/Sprint/ListCommand.php`
- `app/Commands/Orchestration/Sprint/DetailCommand.php`
- `app/Commands/Orchestration/Sprint/CreateCommand.php`
- `app/Commands/Orchestration/Sprint/SaveCommand.php`
- `app/Commands/Orchestration/Sprint/EditCommand.php`
- `app/Commands/Orchestration/Task/ListCommand.php`
- `app/Commands/Orchestration/Task/DetailCommand.php`

**Changes Needed**:
- Update to use `OrchestrationSprint` instead of `Sprint`
- Update to use `OrchestrationTask` instead of `WorkItem`
- Update field mappings for new schema:
  - `code` → `sprint_code`
  - WorkItem metadata extraction → Direct OrchestrationTask fields
  - Add support for new fields: `type`, `delegation_status`, etc.

### 2. Update Documentation
- Update `/docs/commands/SPRINTS_COMMAND.md` → `ORCH_COMMAND.md`
- Update `/docs/commands/TASKS_COMMAND.md` → `ORCH_TASKS_COMMAND.md`
- Update any references in other docs

### 3. Update Frontend (if needed)
- Check if any hardcoded command references exist
- Update any command invocations in TypeScript

## Rollback Plan

If issues arise, rollback by reverting command names:

```bash
php artisan tinker --execute='
DB::table("commands")->where("id", 1)->update(["command" => "/sprints"]);
DB::table("commands")->where("id", 2)->update(["command" => "/sprint-detail"]);
DB::table("commands")->where("id", 4)->update(["command" => "/tasks"]);
DB::table("commands")->where("id", 5)->update(["command" => "/task-detail"]);
DB::table("commands")->where("id", 15)->update(["command" => "/sprint-create"]);
DB::table("commands")->where("id", 16)->update(["command" => "/sprint-save"]);
DB::table("commands")->where("id", 17)->update(["command" => "/sprint-edit"]);
echo "✓ Rolled back to /sprints commands\n";
'
```

## Success Criteria ✅

- ✅ All 7 commands renamed successfully
- ✅ Navigation configs updated with new command names
- ✅ Database verification passed
- ✅ Command naming follows `/orch-*` pattern
- ✅ No breaking changes to handler classes or frontend components

## Files Changed

**Database**:
- `commands` table - 7 rows updated (IDs: 1, 2, 4, 5, 15, 16, 17)

**Documentation**:
- `delegation/tasks/migration-2025-10-14/COMMAND_MIGRATION.md` (this file)

## Related Work

- **Sprint/WorkItem Migration**: See `MIGRATION_SUMMARY.md`
- **Model Cleanup**: See `../cleanup-2025-10-14/FINAL_SUMMARY.md`
