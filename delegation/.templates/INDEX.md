# Templates Index - One-Page Reference

**Quick Links**: [Guide](#guide) | [Templates](#templates) | [Scripts](#scripts) | [Concepts](#concepts)

---

## 🚀 Getting Started (Choose Your Path)

### I want to create a sprint
→ Run `scripts/create-sprint.sh <id> "<name>"`  
→ Or read: [QUICKSTART.md](./QUICKSTART.md)

### I want to create a task
→ Run `scripts/create-task.sh <sprint> <task> "<name>" [prev] [next]`  
→ Or read: [QUICKSTART.md](./QUICKSTART.md)

### I want to understand the system
→ Read: [GUIDE.md](./GUIDE.md)  
→ See example: `../sprints/fe3-migration/`

### I want to customize templates
→ Edit files in `agent-base/`, `sprint-template/`, `task-template/`  
→ Reference: [GUIDE.md#agent-configuration](./GUIDE.md#agent-configuration)

---

## 📚 Documentation

| File | Purpose | When to Read |
|------|---------|--------------|
| **[README.md](./README.md)** | Templates overview | First time here |
| **[QUICKSTART.md](./QUICKSTART.md)** | 5-minute tutorial | Need to start fast |
| **[GUIDE.md](./GUIDE.md)** | Complete guide | Need full details |
| **INDEX.md** | This file | Quick reference |

---

## 📋 Templates

### Agent Configuration

| Template | Purpose | Usage |
|----------|---------|-------|
| `agent-base/AGENT_BASE.yml` | Base agent config | Reference for all sections |
| `agent-base/AGENT_TASK.yml` | Task-level agent | Used by create-task.sh |

**Key Sections**:
- `capabilities` - What agent can do
- `safety_rails` - Where agent can work
- `telemetry` - Events to emit
- `agent_steps` - Task linking (last/next)

### Sprint Templates

| Template | Purpose | Usage |
|----------|---------|-------|
| `sprint-template/SPRINT_TEMPLATE.md` | Sprint overview | Used by create-sprint.sh |
| `sprint-template/README_TEMPLATE.md` | Sprint navigation | Used by create-sprint.sh |

**Key Sections**:
- Sprint goal and context
- Success metrics
- Phases and tasks
- Risks and dependencies

### Task Templates

| Template | Purpose | Usage |
|----------|---------|-------|
| `task-template/TASK_TEMPLATE.md` | Task instructions | Used by create-task.sh |

**Key Sections**:
- Objective and context
- Subtasks checklist
- Deliverables
- Acceptance criteria

### Documentation

| Template | Purpose | Usage |
|----------|---------|-------|
| `docs/ADR_TEMPLATE.md` | Architecture Decision Record | Copy manually when needed |

---

## 🛠️ Scripts

| Script | Purpose | Example |
|--------|---------|---------|
| `scripts/create-sprint.sh` | Create sprint structure | `./create-sprint.sh auth "Auth System"` |
| `scripts/create-task.sh` | Create task with tracing | `./create-task.sh auth task-1 "Setup" null task-2` |

**Both scripts**:
- ✅ Auto-generate hashes
- ✅ Copy templates
- ✅ Replace placeholders
- ✅ Set up tracing

---

## 💡 Core Concepts

### 1. Sprints
**What**: Collection of related tasks  
**When**: Multi-week efforts  
**Contains**: SPRINT.md, README.md, task directories

### 2. Tasks
**What**: Individual units of work  
**When**: 1-2 week efforts  
**Contains**: AGENT.yml (config), TASK.md (instructions)

### 3. Agent Configuration
**What**: Defines agent capabilities and constraints  
**Where**: `AGENT.yml` in each task  
**Sections**: capabilities, safety_rails, telemetry, context, prompts

### 4. Tracing
**What**: Links tasks together (last/next pointers)  
**Why**: Resume from any point, track lineage  
**How**: `agent_steps.last` and `agent_steps.next`

### 5. Hashing
**What**: Unique SHA-256 ID for each task  
**Why**: Telemetry correlation, CAS  
**How**: `echo -n "task-id-YYYYMMDD" | sha256sum`

### 6. Telemetry
**What**: Events emitted during task execution  
**Types**: task.started, task.deliverable.completed, task.completed  
**Correlation**: All events share task_hash

---

## 📖 Common Workflows

### Create Sprint + 3 Tasks

```bash
cd scripts/

# Create sprint
./create-sprint.sh my-sprint "My Sprint Name"

# Create tasks
./create-task.sh my-sprint task-1 "Task 1" null task-2
./create-task.sh my-sprint task-2 "Task 2" task-1 task-3
./create-task.sh my-sprint task-3 "Task 3" task-2 null

# Customize
cd ../sprints/my-sprint/
# Edit SPRINT.md, task-*/TASK.md, task-*/AGENT.yml

# Delegate
"Agent, execute task task-1 in delegation/sprints/my-sprint/task-1/"
```

### Resume Interrupted Sprint

```bash
# Find last completed task
cd delegation/sprints/my-sprint/
grep -r "status: completed" */AGENT.yml

# Get next task
cat task-2/AGENT.yml | grep "next:"

# Delegate next
"Agent, continue from task-3 in delegation/sprints/my-sprint/task-3/"
```

### Add Task Mid-Sprint

```bash
cd delegation/.templates/scripts/

# Create new task (inserts between task-2 and task-3)
./create-task.sh my-sprint task-2b "New Task" task-2 task-3

# Update previous task's next pointer
# Edit: delegation/sprints/my-sprint/task-2/AGENT.yml
# Change: agent_steps.next: "task-3"
# To:     agent_steps.next: "task-2b"
```

---

## 🎯 Quick Reference

### File Structure

```
delegation/
├── .templates/              ← You are here
│   ├── agent-base/          ← Agent config templates
│   ├── sprint-template/     ← Sprint templates
│   ├── task-template/       ← Task templates
│   ├── docs/                ← Doc templates (ADR)
│   ├── scripts/             ← Automation scripts
│   └── [docs]               ← README, GUIDE, QUICKSTART, INDEX
│
└── sprints/                 ← Created sprints
    └── <sprint-id>/         ← Individual sprint
        └── <task-id>/       ← Individual task
            ├── AGENT.yml    ← Agent config
            ├── TASK.md      ← Task instructions
            └── .hash        ← Task hash
```

### Key Files

| File | What's In It |
|------|--------------|
| `AGENT.yml` | Agent capabilities, safety rails, telemetry, tracing |
| `TASK.md` | Objectives, deliverables, acceptance criteria, subtasks |
| `SPRINT.md` | Sprint goal, phases, metrics, risks |
| `README.md` | Sprint navigation, task index, status |

### Common Commands

```bash
# Navigate
cd delegation/.templates/scripts/

# Create sprint
./create-sprint.sh <id> "<name>"

# Create task
./create-task.sh <sprint> <task> "<name>" [prev] [next]

# Generate hash
echo -n "<id>-$(date +%Y%m%d)" | sha256sum | cut -d' ' -f1

# Delegate
"Agent, execute task <id> in delegation/sprints/<sprint>/<task>/"
```

---

## 🔍 Finding Things

### "I need a template for..."

- **Agent config** → `agent-base/AGENT_TASK.yml`
- **Sprint** → `sprint-template/SPRINT_TEMPLATE.md`
- **Task** → `task-template/TASK_TEMPLATE.md`
- **ADR** → `docs/ADR_TEMPLATE.md`

### "I want to see an example of..."

- **Complete sprint** → `../sprints/fe3-migration/`
- **Task chain** → Check agent_steps in fe3-migration tasks
- **Agent config** → Any task's AGENT.yml
- **Telemetry setup** → fe3-migration Phase 3 (has correlation IDs)

### "I want to understand..."

- **The system** → [GUIDE.md](./GUIDE.md)
- **How to start** → [QUICKSTART.md](./QUICKSTART.md)
- **Agent config** → [GUIDE.md#agent-configuration](./GUIDE.md#agent-configuration)
- **Tracing** → [GUIDE.md#tracing--telemetry](./GUIDE.md#tracing--telemetry)

---

## ⚠️ Common Issues

| Problem | Solution |
|---------|----------|
| Script won't run | `chmod +x scripts/*.sh` |
| Agent can't access file | Add path to `safety_rails.fs_scope` |
| Task chain broken | Verify last/next form valid chain |
| Hash collision | Include version in ID or wait a day |
| Missing docs | Check `context.reference_docs` paths |

---

## 📞 Need Help?

1. **Quick question?** → Check [GUIDE.md#troubleshooting](./GUIDE.md#troubleshooting)
2. **Getting started?** → Read [QUICKSTART.md](./QUICKSTART.md)
3. **Deep dive?** → Read full [GUIDE.md](./GUIDE.md)
4. **See example?** → Explore `../sprints/fe3-migration/`

---

**Everything you need is here.** Pick a path above and start delegating! 🚀
