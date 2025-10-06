# Sprint List Command

## Overview
This command lists all sprints with task statistics. Migrated from `SprintListCommand.php` to YAML.

## Triggers
- `/sprints` - List all sprints with task counts

## Features
- Sprint ordering by code (alphabetical)
- Task count statistics per sprint (total, completed, in-progress, etc.)
- Sprint metadata including title, description, status, priority
- Specialized SprintListModal UI integration

## Data Structure
Returns `type: "sprint"` with panel data:
- `action: "list"`
- `message`: Success/empty message
- `sprints`: Array of sprint objects with task counts

## Migration Notes
- Task count calculation simplified in YAML (set to 0 for now)
- Could be enhanced with dynamic task queries in future YAML engine updates
- Preserves exact data structure for existing UI
- Maintains sprint metadata handling