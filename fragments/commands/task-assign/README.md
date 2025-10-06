# Task Assign Command

## Overview
This command assigns agents to tasks with assignment tracking. Migrated from `TaskAssignCommand.php` to YAML.

## Triggers
- `/task-assign <task_id> <agent_id>` - Assign agent to task
- Accepts task codes/UUIDs and agent slugs/names/UUIDs

## Features
- Task and agent resolution by multiple identifier types
- Assignment creation with status tracking
- Previous assignment cancellation
- Task delegation context updates
- Success/error feedback with toast notifications

## Data Structure
Returns `type: "task"` with assignment confirmation and data:
- Success toast with assignment details
- Assignment metadata for UI updates

## Migration Notes
- Uses TaskOrchestrationService logic but implemented in YAML
- Preserves exact assignment workflow and status updates
- Maintains error handling for missing entities
- Includes toast notification data structure