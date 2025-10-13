# Orchestration API v2 Sprint

**Created**: 2025-10-12  
**Status**: Planning  
**Sprint Hash**: `fce04a99a907e7f2b2f6ebd587c80de3eac4d94b5c129368537d019fcfba6119`

---

## Quick Start

### For Agents

Initialize with sprint context:
```bash
AGENT INIT S:orchestration-api-v2
```

Or start with specific phase:
```bash
AGENT INIT T:phase-1-api-foundation
```

### For Humans

View sprint details:
```bash
cat delegation/sprints/orchestration-api-v2/SPRINT.md
```

View current task:
```bash
cd delegation/sprints/orchestration-api-v2/phase-1-api-foundation
cat TASK.md
cat AGENT.yml
```

---

## Sprint Structure

```
orchestration-api-v2/
├── SPRINT.md                      # Sprint overview, goals, phases
├── README.md                      # This file
├── phase-1-api-foundation/        # Week 1: Core API
│   ├── TASK.md                   # Task details
│   ├── AGENT.yml                 # Agent configuration
│   └── .hash                     # Task hash for tracking
├── phase-2-event-system/          # Week 1-2: Event emission
├── phase-3-template-generation/   # Week 2: Template API
├── phase-4-agent-init/            # Week 2-3: Context broker
├── phase-5-pm-commands/           # Week 3: PM tools
└── phase-6-integration/           # Week 3-4: Testing & docs
```

---

## What This Sprint Delivers

By completion, you'll be able to:

1. **Create sprints/tasks via API** instead of manual file creation
2. **Track all changes** with SHA-256 hashes and event logs
3. **Initialize agents** with full context via `AGENT INIT` command
4. **Emit trackable events** for every PM operation (ADR creation, bug reports, status updates)
5. **Assemble context** automatically (agent + task + session + files)
6. **Enable handoffs** between agents with full state transfer
7. **Rollback/replay** work using hash-based versioning

---

## Current Phase

**Phase 1: API Foundation** (Week 1)

Building core CRUD endpoints for sprints and tasks with hash tracking and event emission.

See: `phase-1-api-foundation/TASK.md`

---

## Dependencies

- Laravel 12
- PostgreSQL (JSON column support)
- Existing orchestration infrastructure:
  - `work_sessions` table
  - `work_items` table
  - `agent_profiles` table
  - MCP orchestration server

---

## Architecture

```
MCP/CLI → API Layer → Context Broker → Event Layer → Database ⇄ File System
```

Database becomes source of truth; file system becomes generated view.

---

## Key Files

- **SPRINT.md** - Complete sprint specification
- **phase-*/TASK.md** - Individual task requirements
- **phase-*/AGENT.yml** - Agent configuration per task
- **phase-*/.hash** - Task tracking hash

---

## Validation

Sprint is complete when:
- ✅ All 6 phases complete
- ✅ `AGENT INIT S:orchestration-api-v2` works end-to-end
- ✅ File system mirrors database correctly
- ✅ All events captured in orchestration_events table
- ✅ Context broker assembles full agent context in <500ms
- ✅ All tests pass
- ✅ Documentation complete

---

**For detailed information, see SPRINT.md**
