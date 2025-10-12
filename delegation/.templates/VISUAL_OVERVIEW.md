# Visual Overview - Template System

**Quick visual guide to the template system**

---

## 🗂️ What's Where (Tree View)

```
delegation/
│
├── .templates/ ←━━━━━━━━━━━━━ YOU ARE HERE
│   │
│   ├── 📦 agent-base/
│   │   ├── AGENT_BASE.yml      [Base config - all sections]
│   │   └── AGENT_TASK.yml      [Task config - inherits base]
│   │
│   ├── 📦 sprint-template/
│   │   ├── SPRINT_TEMPLATE.md  [Sprint overview]
│   │   └── README_TEMPLATE.md  [Sprint navigation]
│   │
│   ├── 📦 task-template/
│   │   └── TASK_TEMPLATE.md    [Task instructions]
│   │
│   ├── 📦 docs/
│   │   └── ADR_TEMPLATE.md     [Architecture decisions]
│   │
│   ├── 📦 scripts/
│   │   ├── create-sprint.sh    [Sprint automation]
│   │   └── create-task.sh      [Task automation]
│   │
│   └── 📚 Documentation
│       ├── README.md           [Overview]
│       ├── GUIDE.md            [Complete guide - 2500 lines]
│       ├── QUICKSTART.md       [5-minute start]
│       ├── INDEX.md            [Quick reference]
│       ├── VISUAL_OVERVIEW.md  [This file]
│       └── TEMPLATE_SYSTEM_COMPLETE.md
│
└── sprints/ ←━━━━━━━━━━━━━━━━ CREATED SPRINTS
    └── <sprint-id>/
        ├── SPRINT.md           [From template]
        ├── README.md           [From template]
        └── <task-id>/
            ├── AGENT.yml       [Agent config]
            ├── TASK.md         [Task instructions]
            └── .hash           [Task hash]
```

---

## 🔄 How It Works (Flow)

```
┌─────────────────┐
│  HUMAN CREATES  │
│     SPRINT      │
└────────┬────────┘
         │
         ┃ ./create-sprint.sh my-sprint "Name"
         ┃
         ▼
    ┌────────────────────┐
    │   SPRINT CREATED   │
    │  ✓ SPRINT.md       │
    │  ✓ README.md       │
    │  ✓ Templates       │
    │  ✓ Hash generated  │
    └────────┬───────────┘
             │
             ┃ ./create-task.sh my-sprint task-1 "Name" null task-2
             ┃
             ▼
        ┌────────────────────┐
        │   TASK CREATED     │
        │  ✓ AGENT.yml       │
        │  ✓ TASK.md         │
        │  ✓ Tracing set     │
        │  ✓ Hash generated  │
        └────────┬───────────┘
                 │
                 ┃ Human customizes TASK.md & AGENT.yml
                 ┃
                 ▼
            ┌────────────────┐
            │ AGENT EXECUTES │
            │  • Reads config│
            │  • Does work   │
            │  • Updates     │
            │  • Completes   │
            └────────┬───────┘
                     │
                     ┃ agent_steps.next
                     ┃
                     ▼
                [Next Task] → [Next Task] → ... → [Done]
```

---

## 🎯 One File = One Entry Point

Each task has **ONE file** to start:

```
task-1/
├── AGENT.yml  ←━━━ AGENT READS THIS FIRST
│   ├── 🎯 What I can do (capabilities)
│   ├── 🚧 Where I can work (safety_rails)
│   ├── 📊 What to track (telemetry)
│   ├── 📚 What to read (context.reference_docs)
│   ├── ⬅️ Previous task (agent_steps.last)
│   └── ➡️ Next task (agent_steps.next)
│
└── TASK.md    ←━━━ THEN READS THIS
    ├── 🎯 What to achieve (objective)
    ├── ☑️ What to do (tasks checklist)
    ├── 📦 What to create (deliverables)
    └── ✅ When done (acceptance criteria)
```

---

## 🔗 Task Chain (Linked List)

```
null ━━━━━━➤ task-1 ━━━━━━➤ task-2 ━━━━━━➤ task-3 ━━━━━━➤ null
              [hash1]         [hash2]         [hash3]
                 ↓               ↓               ↓
            AGENT.yml       AGENT.yml       AGENT.yml
            agent_steps:    agent_steps:    agent_steps:
              last: null      last: task-1    last: task-2
              next: task-2    next: task-3    next: null
```

**Navigation**:
- Want previous? → Read `agent_steps.last`
- Want next? → Read `agent_steps.next`
- Resume? → Start from any task

---

## 📊 Configuration Layers

```
┌──────────────────────────────────────┐
│       AGENT_BASE.yml                 │ ← Template (reference only)
│  All possible sections documented    │
└────────────────┬─────────────────────┘
                 │
                 ┃ inherits from
                 ┃
                 ▼
┌──────────────────────────────────────┐
│       AGENT_TASK.yml                 │ ← Template (used by scripts)
│  Task-specific sections added        │
└────────────────┬─────────────────────┘
                 │
                 ┃ customized for
                 ┃
                 ▼
┌──────────────────────────────────────┐
│    task-1/AGENT.yml                  │ ← Actual task config
│  Sprint/task-specific values         │
│  Tracing configured                  │
│  Hash generated                      │
└──────────────────────────────────────┘
```

---

## 🛠️ Script Flow

### create-sprint.sh

```
Input: <sprint-id> <sprint-name>
   ↓
[Generate sprint hash]
   ↓
[Create sprint directory]
   ↓
[Copy templates]
   ├─ SPRINT_TEMPLATE.md → SPRINT.md
   ├─ README_TEMPLATE.md → README.md
   ├─ TASK_TEMPLATE.md → TASK_TEMPLATE.md
   └─ AGENT_TASK.yml → AGENT_TEMPLATE.yml
   ↓
[Replace placeholders]
   ├─ <sprint-slug> → actual ID
   ├─ [Sprint Name] → actual name
   ├─ YYYY-MM-DD → today
   └─ <sprint-hash> → generated hash
   ↓
Output: Ready sprint directory ✅
```

### create-task.sh

```
Input: <sprint-id> <task-id> <task-name> [last] [next]
   ↓
[Generate task hash]
   ↓
[Create task directory]
   ↓
[Copy templates]
   ├─ TASK_TEMPLATE.md → TASK.md
   └─ AGENT_TASK.yml → AGENT.yml
   ↓
[Replace placeholders]
   ├─ <task-id> → actual ID
   ├─ <task-hash> → generated hash
   ├─ <sprint-id> → parent sprint
   ├─ <previous-task-id> → last task
   └─ <next-task-id> → next task
   ↓
[Save hash to .hash file]
   ↓
Output: Ready task with tracing ✅
```

---

## 📚 Documentation Map

```
START HERE
    ↓
[README.md] ━━━━━━━━━━━━━━━━━━┓
    │                           ┃
    ┃ "I need quick start"     ┃ "I need complete guide"
    ┃                           ┃
    ▼                           ▼
[QUICKSTART.md]           [GUIDE.md]
    │                           │
    ┃ 5 minutes                ┃ Everything
    ┃ • Create sprint         ┃ • Core concepts
    ┃ • Create task           ┃ • How to create
    ┃ • Delegate              ┃ • Configuration
    ┃                          ┃ • Tracing
    │                          ┃ • Best practices
    │                          ┃ • Troubleshooting
    │                           │
    └──────────┬────────────────┘
               │
               ┃ "I need quick lookup"
               ┃
               ▼
         [INDEX.md]
               │
               ┃ One page
               ┃ • All templates
               ┃ • All scripts
               ┃ • Common commands
               ┃ • Quick reference
               │
```

---

## 🎨 Agent Configuration Sections

```yaml
task_id: "unique-id"                 # Identity
task_hash: "sha256..."               # Identity

agent_steps:                         # Tracing
  last: "previous-task"              # ← Navigation
  next: "next-task"                  # → Navigation

task:                                # Task Definition
  objectives: [...]                  # What to achieve
  deliverables: [...]                # What to create
  acceptance_criteria: [...]         # When done

capabilities:                        # Permissions
  allowed_actions: [...]             # What can do
  allowed_tools: [...]               # What can use

safety_rails:                        # Limits
  fs_scope: [...]                    # Where can work
  tool_whitelist: [...]              # What can run

telemetry:                           # Tracking
  emit_events: [...]                 # What to track
  correlate_ids: true                # Link events

context:                             # Knowledge
  reference_docs: [...]              # What to read
  constraints: [...]                 # Rules to follow

prompts:                             # Guidance
  system:                            # Instructions
    - content: "..."

hooks:                               # Lifecycle
  on_start: [...]                    # Begin task
  on_complete: [...]                 # Finish task
```

---

## 📈 Task Status Flow

```
[PENDING] ━━━━━━━━━➤ [IN_PROGRESS] ━━━━━━━━━➤ [COMPLETED]
    ↓                      ↓                        ↓
Created                Working                  Done ✅
  • AGENT.yml exists      • Agent executing        • All criteria met
  • TASK.md ready         • Progress tracked       • Status updated
  • Tracing set           • Events emitted         • Next task ready
                                                   • Tracing updated
```

---

## 🎯 Quick Commands Cheat Sheet

```bash
# Navigate to scripts
cd delegation/.templates/scripts/

# Create sprint
./create-sprint.sh <id> "<name>"

# Create task
./create-task.sh <sprint> <task> "<name>" [prev] [next]

# Generate hash
echo -n "<id>-$(date +%Y%m%d)" | sha256sum | cut -d' ' -f1

# Delegate to agent
"Agent, execute task <id> in delegation/sprints/<sprint>/<task>/"

# Check tracing
cd delegation/sprints/<sprint>/<task>/
cat AGENT.yml | grep -A2 "agent_steps:"
```

---

## 🔍 Finding What You Need

```
┌─────────────────────────────────────────┐
│ "I want to..."                          │
└─────────────────┬───────────────────────┘
                  │
        ┌─────────┼─────────┬──────────────┬──────────────┐
        ▼         ▼         ▼              ▼              ▼
    [Create]  [Learn]  [Reference]   [Customize]   [Example]
        │         │         │              │              │
        ▼         ▼         ▼              ▼              ▼
    scripts/  GUIDE.md  INDEX.md      templates/   fe3-migration/
```

---

## 📊 File Sizes (Approximate)

```
Small  (< 100 lines)  ● AGENT_TASK.yml, TASK_TEMPLATE.md
Medium (100-500)      ●● AGENT_BASE.yml, SPRINT_TEMPLATE.md
Large  (500-1000)     ●●● INDEX.md, README.md
Huge   (1000+)        ●●●● GUIDE.md (2500 lines!)
```

---

## ✅ What You Get

```
📦 Complete Template System
  ├── ✅ All templates (agent, sprint, task, ADR)
  ├── ✅ Automation scripts (create sprint, create task)
  ├── ✅ Full documentation (4000+ lines)
  ├── ✅ Working example (fe3-migration sprint)
  ├── ✅ Tracing infrastructure
  ├── ✅ Safety configuration
  ├── ✅ Telemetry setup
  └── ✅ Best practices
```

---

**Everything visualized. Everything documented. Everything ready.**

🚀 Start creating!
