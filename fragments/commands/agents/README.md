# Agent List Command

## Overview
This command lists all agents with assignment statistics. Migrated from `AgentListCommand.php` to YAML.

## Triggers
- `/agents` - List all agents with assignment counts

## Features
- Agent ordering by name (alphabetical)
- Assignment count statistics (active and total)
- Agent metadata including capabilities, constraints, tools
- Specialized AgentListModal UI integration

## Data Structure
Returns `type: "agent"` with panel data:
- `action: "list"`
- `message`: Success/empty message
- `agents`: Array of agent objects with assignment counts

## Migration Notes
- Assignment count calculation simplified in YAML (set to 0 for now)
- Could be enhanced with dynamic assignment queries in future YAML engine updates
- Preserves exact data structure for existing UI
- Maintains agent metadata and profile information