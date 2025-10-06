# Orchestration CLI Commands

## Task Management
- `php artisan frag:command "/task-create 'Title' --priority=medium --estimate='2 days'"` - Create new task
- `php artisan frag:command "/tasks"` - List all tasks
- `php artisan frag:command "/task-detail <id>"` - View task details
- `php artisan frag:command "/backlog-list"` - List backlog items

## Sprint Management  
- `php artisan frag:command "/sprints"` - List all sprints
- `php artisan frag:command "/sprint-detail <code>"` - View sprint details

## Agent Management
- `php artisan frag:command "/agents"` - List all agents
- `php artisan frag:command "/task-assign <task> <agent>"` - Assign task to agent

## AI Logs
- `php artisan frag:command "/ailogs"` - View AI interaction logs

## Aliases (use with php artisan frag:command)
- `/tc` = `/task-create`
- `/tl` = `/task-list`
- `/bl` = `/backlog-list` 
- `/sl` = `/sprint-list`
- `/td` = `/task-detail`
- `/sd` = `/sprint-detail`
- `/ta` = `/task-assign`
- `/al` = `/agent-list`

**Note**: All commands use `php artisan frag:command "/<command>"` format.

See `ORCH_SYSTEM_DETAILS.md` for complete system documentation and examples.