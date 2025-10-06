# Task List Command

## Overview
This command lists tasks with optional sprint filtering. Migrated from `TaskListCommand.php` to YAML.

## Triggers
- `/tasks` - List all sprint tasks
- `/tasks <sprint>` - Filter by specific sprint code

## Features
- Sprint code normalization (e.g., `1` → `SPRINT-01`, `sprint-1` → `SPRINT-01`)
- Task ordering by status priority (todo > backlog > others) then creation date
- Rich task data including metadata, assignment info, and content flags
- Specialized TaskListModal UI integration

## Data Structure
Returns `type: "task"` with panel data:
- `action: "list"`
- `message`: Success/empty message
- `tasks`: Array of task objects
- `sprint_filter`: Applied sprint filter

## Migration Notes
- Preserves exact filtering logic from PHP version
- Maintains compatible data structure for existing UI
- Includes sprint code normalization function
- Limited to 50 tasks (same as original)