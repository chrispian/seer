# Agent Delegation Templates

**Version**: 1.0  
**Purpose**: Reusable templates and scripts for agent-delegated work

---

## What's Here

This directory contains everything you need to create and manage agent-delegated sprints and tasks:

### 📁 Directories

- **agent-base/** - Base AGENT.yml templates
  - `AGENT_BASE.yml` - Foundational agent configuration
  - `AGENT_TASK.yml` - Task-level agent template

- **sprint-template/** - Sprint scaffolding
  - `SPRINT_TEMPLATE.md` - Sprint overview template
  - `README_TEMPLATE.md` - Sprint README template

- **task-template/** - Task scaffolding
  - `TASK_TEMPLATE.md` - Task instructions template

- **docs/** - Documentation templates
  - `ADR_TEMPLATE.md` - Architecture Decision Record template

- **scripts/** - Automation scripts
  - `create-sprint.sh` - Create new sprint from template
  - `create-task.sh` - Create new task from template

### 📄 Documentation

- **[GUIDE.md](./GUIDE.md)** - Complete guide (read this!)
- **[QUICKSTART.md](./QUICKSTART.md)** - 5-minute getting started
- **README.md** - This file

---

## Quick Start

### Create a Sprint

```bash
cd scripts/
./create-sprint.sh <sprint-id> "<Sprint Name>"
```

### Create a Task

```bash
cd scripts/
./create-task.sh <sprint-id> <task-id> "<Task Name>" [previous-task] [next-task]
```

### Read the Docs

- **New to system?** → Read [QUICKSTART.md](./QUICKSTART.md)
- **Need details?** → Read [GUIDE.md](./GUIDE.md)
- **Have questions?** → Check [GUIDE.md Troubleshooting](./GUIDE.md#troubleshooting)

---

## What Gets Created

### When you create a sprint:

```
sprints/<sprint-id>/
├── SPRINT.md          # Sprint overview (phases, metrics, risks)
├── README.md          # Navigation hub (task index, status)
├── TASK_TEMPLATE.md   # Copy of task template
└── AGENT_TEMPLATE.yml # Copy of agent template
```

### When you create a task:

```
sprints/<sprint-id>/<task-id>/
├── AGENT.yml          # Agent configuration (capabilities, safety, telemetry)
├── TASK.md            # Task instructions (objectives, deliverables, criteria)
└── .hash              # Task hash (for reference)
```

---

## Key Concepts

### 1. Agent Configuration (AGENT.yml)
Defines what an agent can do, where it can work, and how to behave.

**Sections**:
- `capabilities` - Allowed actions and tools
- `safety_rails` - File system scope and command whitelist
- `telemetry` - Events to emit
- `agent_steps` - Links to previous/next tasks (tracing)

### 2. Task Instructions (TASK.md)
Human-readable instructions for what to accomplish.

**Sections**:
- `Objective` - What this task achieves
- `Tasks` - Checklist of subtasks
- `Deliverables` - Concrete outputs
- `Acceptance Criteria` - When it's done

### 3. Tracing Infrastructure
Tasks form a linked chain with `agent_steps.last` and `agent_steps.next`.

**Benefits**:
- Resume from any point
- Track task lineage
- Correlate telemetry events

### 4. Hashing
Every task gets a unique SHA-256 hash for identification and correlation.

```bash
echo -n "<task-id>-$(date +%Y%m%d)" | sha256sum | cut -d' ' -f1
```

---

## Examples

See **real-world examples** in:
- `delegation/sprints/fe3-migration/` - Full 18-task sprint
- Each task has complete AGENT.yml and TASK.md
- Study the tracing chain: null → task-1 → task-2 → ... → null

---

## Templates Summary

### AGENT_BASE.yml
Foundational agent configuration with all possible sections.  
**Use**: As reference when creating custom agents.

### AGENT_TASK.yml
Task-level agent template inheriting from base + task-specific config.  
**Use**: Copied by `create-task.sh` script.

### SPRINT_TEMPLATE.md
Sprint overview with goal, metrics, phases, risks.  
**Use**: Copied by `create-sprint.sh` script.

### README_TEMPLATE.md
Sprint navigation hub with task index and status.  
**Use**: Copied by `create-sprint.sh` script.

### TASK_TEMPLATE.md
Task instructions with objectives, deliverables, acceptance criteria.  
**Use**: Copied by `create-task.sh` script.

### ADR_TEMPLATE.md
Architecture Decision Record with context, decision, consequences, alternatives.  
**Use**: Copy manually when documenting architectural decisions.

---

## Scripts Summary

### create-sprint.sh
Creates complete sprint structure from templates.

**Usage**:
```bash
./create-sprint.sh <sprint-id> "<Sprint Name>"
```

**Creates**:
- Sprint directory
- SPRINT.md and README.md from templates
- Copies of task/agent templates
- Generates sprint hash

### create-task.sh
Creates complete task structure with proper tracing.

**Usage**:
```bash
./create-task.sh <sprint-id> <task-id> "<Task Name>" [last-task] [next-task]
```

**Creates**:
- Task directory
- AGENT.yml and TASK.md from templates
- Configures tracing (last/next pointers)
- Generates task hash

---

## Best Practices

### When Creating Sprints

1. ✅ Clear single-sentence goal
2. ✅ Measurable success metrics
3. ✅ 2-4 week phases
4. ✅ Document risks and mitigations
5. ✅ Link to specs/PRDs

### When Creating Tasks

1. ✅ 1-2 weeks per task
2. ✅ Single clear objective
3. ✅ Concrete acceptance criteria
4. ✅ Narrow fs_scope to minimum needed
5. ✅ Always link to previous/next task

### When Configuring Agents

1. ✅ Principle of least privilege (minimum capabilities)
2. ✅ Explicit constraints (coding standards, patterns)
3. ✅ Clear guidance (use prompts.system)
4. ✅ Comprehensive telemetry (emit all milestones)
5. ✅ Lifecycle hooks (consistent behavior)

---

## File Tree

```
delegation/.templates/
├── agent-base/
│   ├── AGENT_BASE.yml
│   └── AGENT_TASK.yml
│
├── sprint-template/
│   ├── SPRINT_TEMPLATE.md
│   └── README_TEMPLATE.md
│
├── task-template/
│   └── TASK_TEMPLATE.md
│
├── docs/
│   └── ADR_TEMPLATE.md
│
├── scripts/
│   ├── create-sprint.sh
│   └── create-task.sh
│
├── GUIDE.md
├── QUICKSTART.md
└── README.md (this file)
```

---

## Next Steps

1. **New to this?** → Read [QUICKSTART.md](./QUICKSTART.md)
2. **Ready to create?** → Use scripts in `scripts/`
3. **Need help?** → Check [GUIDE.md](./GUIDE.md)
4. **Want examples?** → See `../sprints/fe3-migration/`

---

## Support

- **Documentation**: [GUIDE.md](./GUIDE.md)
- **Quick Start**: [QUICKSTART.md](./QUICKSTART.md)
- **Examples**: `../sprints/fe3-migration/`
- **Troubleshooting**: [GUIDE.md#troubleshooting](./GUIDE.md#troubleshooting)

---

**Version**: 1.0  
**Last Updated**: October 12, 2025  
**Location**: `delegation/.templates/`

Everything you need to delegate work to agents. 🚀
