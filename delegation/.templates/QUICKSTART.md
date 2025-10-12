# Quick Start - Agent Delegation System

**Time**: 5 minutes  
**Goal**: Create your first sprint and task

---

## Step 1: Create a Sprint (1 minute)

```bash
cd delegation/.templates/scripts
./create-sprint.sh my-first-sprint "My First Sprint"
```

**Output**:
```
✅ Sprint created successfully!
Sprint ID: my-first-sprint
Directory: delegation/sprints/my-first-sprint/
Sprint hash: [generated hash]
```

---

## Step 2: Create Your First Task (1 minute)

```bash
./create-task.sh my-first-sprint task-1 "My First Task" null task-2
```

**Output**:
```
✅ Task created successfully!
Task ID: task-1
Task hash: [generated hash]
Links: null → task-1 → task-2
```

---

## Step 3: Customize the Task (2 minutes)

Open `delegation/sprints/my-first-sprint/task-1/TASK.md`:

```markdown
## Objective
[Write what this task should achieve]

## Tasks
- [ ] Subtask 1
- [ ] Subtask 2

## Acceptance Criteria
- ✅ [When is this done?]
```

Open `delegation/sprints/my-first-sprint/task-1/AGENT.yml`:

```yaml
task:
  objectives:
    - "[What to achieve]"
  deliverables:
    - "[What to create]"

capabilities:
  allowed_tools:
    - "fs"
    - "git"

safety_rails:
  fs_scope:
    - "app/MyFeature/"  # Where agent can work
```

---

## Step 4: Delegate to Agent (1 minute)

```
"Agent, execute task task-1 in delegation/sprints/my-first-sprint/task-1/"
```

**Agent will**:
1. Read AGENT.yml for capabilities and constraints
2. Read TASK.md for objectives and acceptance criteria
3. Execute the work
4. Update status in TASK.md
5. Mark task complete

---

## What's Next?

### Create More Tasks

```bash
# Task 2
./create-task.sh my-first-sprint task-2 "Second Task" task-1 task-3

# Task 3
./create-task.sh my-first-sprint task-3 "Third Task" task-2 null
```

### Learn More

- **Full Guide**: Read [GUIDE.md](./GUIDE.md) for complete documentation
- **Templates**: Explore templates in `delegation/.templates/`
- **Real Example**: See `delegation/sprints/fe3-migration/` for full sprint

---

## Common Commands

```bash
# Create sprint
cd delegation/.templates/scripts
./create-sprint.sh <id> "<name>"

# Create task
./create-task.sh <sprint-id> <task-id> "<name>" [prev] [next]

# Generate hash manually
echo -n "my-id-$(date +%Y%m%d)" | sha256sum | cut -d' ' -f1
```

---

## Folder Structure Created

```
delegation/
├── .templates/              # Templates (you are here)
└── sprints/
    └── my-first-sprint/     # Your sprint
        ├── SPRINT.md        # Sprint overview
        ├── README.md        # Navigation
        └── task-1/          # Your task
            ├── AGENT.yml    # Agent config
            ├── TASK.md      # Task instructions
            └── .hash        # Task hash
```

---

## Help

- **Full Documentation**: [GUIDE.md](./GUIDE.md)
- **Examples**: `delegation/sprints/fe3-migration/`
- **Templates**: Browse `delegation/.templates/`

---

**You're ready!** Create sprints, delegate to agents, and track progress with tracing.
