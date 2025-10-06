# Backlog List Command

## Overview
This command lists backlog items including both active backlog and completed imports. Migrated from `BacklogListCommand.php` to YAML.

## Triggers
- `/backlog-list` - List all backlog items

## Features
- Backlog task filtering (status=backlog OR backlog_import=true with status=done)
- Task ordering by creation date (newest first)
- Rich task data with metadata and content flags
- Specialized BacklogListModal UI integration

## Data Structure
Returns `type: "backlog"` with panel data:
- `action: "backlog_list"`
- `message`: Success/empty message
- `tasks`: Array of backlog task objects

## Migration Notes
- Preserves exact filtering logic for backlog items
- Maintains compatible data structure for existing UI
- Limited to 100 tasks (same as original)
- Includes content availability flags