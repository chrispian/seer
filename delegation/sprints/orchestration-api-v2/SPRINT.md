# Sprint: Orchestration API v2 - Database-Backed PM System

**Sprint ID**: `orchestration-api-v2`  
**Status**: Planning  
**Start Date**: 2025-10-12  
**Duration**: 3-4 weeks  
**Owner**: Engineering Team  
**Hash**: `fce04a99a907e7f2b2f6ebd587c80de3eac4d94b5c129368537d019fcfba6119`

---

## Sprint Goal

Migrate file-based project management system (sprints, tasks, agents) to database-backed API with dual-mode support (file + DB) for versioning, observability, event tracking, and seamless agent initialization/handoff workflows.

---

## Context

Currently, our orchestration system uses:
- **File-based**: YAML/Markdown in `delegation/sprints/` for human/agent task management
- **Database**: `work_sessions`, `work_items`, `agent_profiles` for runtime tracking
- **MCP Tools**: Direct service calls for agent orchestration

**Problems**:
1. No observability - can't track agent actions across file system operations
2. No event history - file edits don't emit trackable events
3. Manual setup - agents must manually navigate folder structures
4. No versioning - file changes aren't tracked with correlation IDs
5. Context fragmentation - session state separate from task definitions
6. Difficult handoffs - no API to resume/transfer work between agents

**Solution**: Build API layer that mirrors file system, emits events, stores hashes, enables agent INIT with full context assembly.

Reference:
- `docs/orchestration/` - Current system architecture
- `delegation/.templates/` - File-based template system
- `delegation/tasks/COMMAND-GENERATOR-SYSTEM.md` - Generator spec (inspiration)

---

## Success Metrics

- ✅ Agent can call `AGENT INIT S:orchestration-api-v2` and receive full context
- ✅ Agent can call `AGENT INIT T:phase-1-api-foundation` and receive task details
- ✅ All file operations emit events to `orchestration_events` table
- ✅ Sprint/task creation from templates via API call (no manual folder creation)
- ✅ Hash tracking enables rollback/replay of agent work
- ✅ Context broker assembles agent profile + task + session memory in one call
- ✅ Dual-mode: File system still works, DB is source of truth

---

## Phases & Tasks

### Phase 1: API Foundation (Week 1)
- **phase-1-api-foundation**: Create API endpoints for sprint/task CRUD with hash tracking

### Phase 2: Event Emission & Observability (Week 1-2)
- **phase-2-event-system**: Implement event emission for all PM operations

### Phase 3: Template Generation API (Week 2)
- **phase-3-template-generation**: Build API to create sprint/task folders from templates

### Phase 4: Agent Initialization & Context Broker (Week 2-3)
- **phase-4-agent-init**: Implement AGENT INIT command with context assembly

### Phase 5: PM Command Tools (Week 3)
- **phase-5-pm-commands**: Add MCP/CLI tools for ADR, bug reports, status updates

### Phase 6: Integration & Testing (Week 3-4)
- **phase-6-integration**: End-to-end testing, documentation, handoff workflows

---

## Dependencies

- Existing `work_sessions`, `work_items`, `agent_profiles` tables
- MCP orchestration server (`app/Servers/OrchestrationServer.php`)
- Template system in `delegation/.templates/`
- Context broker service (to be created)
- Event emission infrastructure (spatie/laravel-activitylog or custom)

---

## Risks & Mitigations

| Risk | Impact | Likelihood | Mitigation |
|------|--------|------------|------------|
| File/DB sync issues | High | Medium | Dual-mode validation tests, DB as source of truth |
| Event storm (too many events) | Medium | Medium | Configurable event levels, sampling |
| Context broker performance | High | Low | Caching, lazy loading, pagination |
| Breaking existing workflows | High | Low | Maintain file system compatibility, gradual rollout |
| Hash collision | Low | Very Low | SHA-256 with timestamp + unique IDs |

---

## Architecture Overview

```
┌─────────────────────────────────────────────────────────┐
│                    MCP/CLI Interface                     │
│  orchestration_agent_init, orchestration_sprint_create  │
└────────────────────┬────────────────────────────────────┘
                     │
┌────────────────────▼────────────────────────────────────┐
│                  API Layer (New)                         │
│  - SprintController: CRUD + template generation          │
│  - TaskController: CRUD + hash tracking                  │
│  - AgentInitController: Context assembly                 │
│  - PMCommandController: ADR, bug reports, status         │
└────────────────────┬────────────────────────────────────┘
                     │
┌────────────────────▼────────────────────────────────────┐
│               Context Broker (New)                       │
│  Assembles: Agent Profile + Task + Session + Files      │
└────────────────────┬────────────────────────────────────┘
                     │
┌────────────────────▼────────────────────────────────────┐
│            Event Emission Layer (New)                    │
│  Emits: sprint.created, task.updated, agent.started     │
└────────────────────┬────────────────────────────────────┘
                     │
┌────────────────────▼────────────────────────────────────┐
│              Database (Enhanced)                         │
│  - orchestration_sprints (with hash, metadata JSON)      │
│  - orchestration_tasks (with hash, state tracking)       │
│  - orchestration_events (event log)                      │
│  - work_sessions (existing, linked)                      │
└──────────────────────────────────────────────────────────┘
                     │
┌────────────────────▼────────────────────────────────────┐
│              File System (Mirror)                        │
│  delegation/sprints/<sprint>/<task>/                     │
│  - SPRINT.md, TASK.md, AGENT.yml generated from DB      │
└──────────────────────────────────────────────────────────┘
```

---

## Key Features

### 1. Agent Initialization
```bash
# Initialize agent with sprint context
AGENT INIT S:orchestration-api-v2

# Initialize agent with specific task
AGENT INIT T:phase-1-api-foundation

# Response includes:
# - Agent profile (capabilities, safety rails)
# - Task definition (objectives, deliverables, acceptance criteria)
# - Sprint context (related tasks, dependencies)
# - Session memory (if resuming)
# - Reference docs (from context field)
```

### 2. Event Tracking
Every action emits events:
- `orchestration.sprint.created`
- `orchestration.task.started`
- `orchestration.task.status_updated`
- `orchestration.agent.initialized`
- `orchestration.file.synced`
- `orchestration.context.assembled`

### 3. Template Generation
```bash
# Create sprint from template
orchestration_sprint_create --template=default --id=my-sprint

# Creates:
# - Database record in orchestration_sprints
# - File structure in delegation/sprints/my-sprint/
# - SPRINT.md from template
# - .hash file with tracking ID
```

### 4. PM Commands
```bash
# Create ADR via MCP tool
orchestration_adr_create --title="ADR-006: Use Event Sourcing"

# Report bug (creates backlog item)
orchestration_bug_report --title="..." --priority=P1

# Update task status
orchestration_task_status_update --task=phase-1 --status=in_progress
```

---

## Database Schema (New Tables)

### `orchestration_sprints`
```sql
- id (bigint)
- sprint_code (string, unique)
- title (string)
- status (enum: planning, active, completed)
- hash (string, sha256)
- metadata (json: owner, dates, goals)
- file_path (string: delegation/sprints/...)
- created_at, updated_at
```

### `orchestration_tasks`
```sql
- id (bigint)
- task_code (string, unique)
- sprint_id (foreign key)
- title (string)
- status (enum: pending, in_progress, completed, blocked)
- priority (enum: P0, P1, P2, P3)
- hash (string, sha256)
- metadata (json: objectives, deliverables, acceptance_criteria)
- agent_config (json: from AGENT.yml)
- file_path (string)
- created_at, updated_at
```

### `orchestration_events`
```sql
- id (bigint)
- event_type (string)
- entity_type (enum: sprint, task, agent, session)
- entity_id (bigint)
- correlation_id (uuid)
- session_key (string, nullable)
- agent_id (bigint, nullable)
- payload (json)
- emitted_at (timestamp)
```

---

## Tasks

See individual task directories:
- `phase-1-api-foundation/`
- `phase-2-event-system/`
- `phase-3-template-generation/`
- `phase-4-agent-init/`
- `phase-5-pm-commands/`
- `phase-6-integration/`

---

## Notes

- **Dual-mode is temporary**: Once stable, file system becomes read-only view
- **Hash strategy**: `sha256(entity_id + timestamp + content)` for tracking
- **Event volume**: Monitor orchestration_events table size, implement archiving
- **Context broker caching**: Cache assembled contexts for 5 minutes
- **Backward compatibility**: Existing MCP tools continue to work during migration

---

**Sprint Hash**: `fce04a99a907e7f2b2f6ebd587c80de3eac4d94b5c129368537d019fcfba6119`  
**Sprint Status**: Planning
