# YAML Command System Cleanup

**Date**: October 11, 2025  
**Status**: Partial Cleanup Complete

## Problem

The YAML-based command system (`fragments/commands/` and `CommandRegistry` model) was deprecated in favor of the PHP-based unified Type+Command system (`Command` model and `CommandRegistry` service). However, some YAML commands remained in the database, causing conflicts and errors.

## Symptoms

When running `/sprints`, the system showed error:
```
Command Failed: /orchestration:sprints
An error occurred while executing this command.
```

This occurred because:
1. Both YAML and PHP versions of `/sprints` existed
2. `CommandController` checks PHP commands first (line 61)
3. But the YAML command in `CommandRegistry` table caused routing confusion
4. The error message referenced the wrong command name

## Cleanup Performed

### Deleted Duplicate YAML Commands
Removed YAML commands that have PHP equivalents:
- ✅ `sprints` → Uses `App\Commands\Orchestration\Sprint\ListCommand`
- ✅ `agents` → Uses `App\Commands\Orchestration\Agent\ListCommand`
- ✅ `sprint-detail` → Uses `App\Commands\Orchestration\Sprint\DetailCommand`
- ✅ `task-detail` → Uses `App\Commands\Orchestration\Task\DetailCommand`
- ✅ `tasks` → Uses `App\Commands\Orchestration\Task\ListCommand`

### Removed YAML Files
```bash
rm -rf fragments/commands/sprints/
```

### Cleared Caches
```bash
php artisan cache:clear
App\Services\CommandRegistry::clearCache();
```

## Remaining YAML Commands (32)

These commands still use the legacy YAML system and need PHP migration:

**Orchestration (4)**
- `backlog-list`
- `task-assign`
- `task-create`
- `recall`

**Scheduling (5)**
- `schedule-list`
- `schedule-create`
- `schedule-delete`
- `schedule-detail`
- `schedule-pause`
- `schedule-resume`
- `scheduler-ui`

**Fragment Management (3)**
- `frag`
- `frag-simple`
- `search`

**Communication (3)**
- `accept`
- `join`
- `link`

**User/Session (6)**
- `settings`
- `setup`
- `session`
- `todo`
- `name`
- `remind`

**Data (5)**
- `bookmark`
- `channels`
- `inbox`
- `note`
- `news-digest`

**System (3)**
- `clear`
- `help`
- `routing`
- `types-ui`

## Verification Script

Check for YAML/PHP conflicts:
```bash
php artisan tinker --execute="
\$yaml = App\Models\CommandRegistry::pluck('slug')->toArray();
\$php = App\Models\Command::pluck('command')->map(fn(\$c) => ltrim(\$c, '/'))->toArray();
\$duplicates = array_intersect(\$yaml, \$php);
echo 'Conflicts: ' . implode(', ', \$duplicates);
"
```

Should return: `Conflicts: ` (empty)

## Next Steps

### Immediate (Done ✅)
- [x] Delete duplicate YAML commands
- [x] Clear caches
- [x] Test `/sprints` command works

### Short Term
- [ ] Document which YAML commands are actively used
- [ ] Create migration plan for remaining 32 YAML commands
- [ ] Add warning when YAML commands are detected
- [ ] Update CommandController to log when falling back to YAML

### Long Term
- [ ] Migrate all 32 remaining YAML commands to PHP
- [ ] Remove YAML command system entirely
- [ ] Delete `CommandRegistry` model and `fragments/commands/` directory
- [ ] Remove YAML DSL execution code

## Testing

After cleanup, test these commands work:
```bash
# Should use PHP commands (no errors)
/sprints
/tasks
/agents
/sprint-detail 46
/task-detail TASK-001

# Should still use YAML (will be migrated later)
/backlog-list
/schedule-list
/todo
```

## Related Documentation

- `docs/TYPE_COMMAND_UNIFICATION_COMPLETE.md` - PHP command system
- `docs/type-command-ui/` - Config-driven UI system
- `app/Services/CommandRegistry.php` - PHP command registry
- `app/Models/CommandRegistry.php` - YAML command model (deprecated)
- `app/Services/Commands/DSL/CommandRunner.php` - YAML executor (deprecated)

## Notes

- The `CommandController` checks PHP commands FIRST (line 61), so duplicates cause PHP to win
- But having both in the system causes confusion and potential routing errors
- This cleanup ensures clean separation until full YAML deprecation
- The error message bug (showing wrong command name) was due to modal title generation, not the command itself
