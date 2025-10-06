# Task Create Command

## Overview
This command creates new tasks with automatic code generation and metadata parsing. Migrated from `TaskCreateCommand.php` to YAML.

## Triggers
- `/task-create "Title" --priority=medium --estimate="2 days"` - Create new task with options

## Features
- Task title parsing from input
- Option parsing for priority, estimate, type
- Automatic task code generation with prefixes (TASK, BUG, FEAT, TEST, DOC, TECH)
- Estimate text to hours conversion
- Default task content sections
- Priority validation (low, medium, high)

## Data Structure
Returns `type: "success"` with creation confirmation message.

## Migration Notes
- Preserves exact task code generation logic
- Maintains option parsing and validation
- Uses same estimate calculation algorithm
- Creates identical default content structure
- Handles all error cases from original implementation