# Sprint Detail Command

## Overview
This command shows detailed information about a specific sprint including task breakdown. Migrated from `SprintDetailCommand.php` to YAML.

## Triggers
- `/sprint-detail <code>` - Show detailed sprint information
- Accepts various sprint code formats (1, sprint-1, SPRINT-01, etc.)

## Features
- Sprint code normalization (e.g., `1` → `SPRINT-01`)
- Task statistics calculation (total, completed, in-progress, blocked, unassigned)
- Task list with assignment information
- Error handling for missing sprints
- Specialized SprintDetailModal UI integration

## Data Structure
Returns `type: "sprint"` with panel data:
- `action: "detail"`
- `sprint`: Complete sprint object with stats and tasks
- `tasks`: Array of task objects with assignment info
- `stats`: Task statistics breakdown

## Migration Notes
- Uses SprintOrchestrationService logic but implemented in YAML
- Preserves exact task filtering and statistics calculation
- Maintains error handling and user feedback
- Limited to 50 tasks per sprint (same as original)