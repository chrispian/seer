# Task Detail Command

## Overview
This command shows detailed information about a specific task including assignments and content. Migrated from `TaskDetailCommand.php` to YAML.

## Triggers
- `/task-detail <id>` - Show detailed task information
- Accepts task codes (TASK-001) or UUIDs

## Features
- Task resolution by code or UUID
- Assignment history with agent information
- Current assignment identification
- Task content sections (agent, plan, context, todo, summary)
- Error handling for missing tasks
- Specialized TaskDetailModal UI integration

## Data Structure
Returns `type: "task"` with panel data:
- `action: "detail"`
- `task`: Complete task object with metadata
- `current_assignment`: Active assignment if any
- `assignments`: Assignment history (last 20)
- `content`: Task content sections

## Migration Notes
- Uses TaskOrchestrationService logic but implemented in YAML
- Preserves exact assignment handling and content retrieval
- Maintains error handling and user feedback
- Limited to 20 assignments (same as original)