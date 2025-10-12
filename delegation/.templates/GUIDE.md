# Agent Delegation System - Complete Guide

**Version**: 1.0  
**Created**: October 12, 2025  
**Purpose**: Everything you need to create and manage agent-delegated work

---

## Table of Contents

1. [Quick Start](#quick-start)
2. [Core Concepts](#core-concepts)
3. [Folder Structure](#folder-structure)
4. [Creating a Sprint](#creating-a-sprint)
5. [Creating Tasks](#creating-tasks)
6. [Agent Configuration](#agent-configuration)
7. [Tracing & Telemetry](#tracing--telemetry)
8. [Best Practices](#best-practices)
9. [Examples](#examples)
10. [Troubleshooting](#troubleshooting)

---

## Quick Start

### For Humans (Creating Work)

```bash
# 1. Create a new sprint
cd delegation/.templates/scripts
./create-sprint.sh my-sprint "My Sprint Name"

# 2. Create first task
./create-task.sh my-sprint task-1 "Task 1 Name" null task-2

# 3. Create second task
./create-task.sh my-sprint task-2 "Task 2 Name" task-1 task-3

# 4. Customize tasks
# Edit sprints/my-sprint/task-1/TASK.md
# Edit sprints/my-sprint/task-1/AGENT.yml

# 5. Delegate to agent
"Agent, execute task task-1 in delegation/sprints/my-sprint/task-1/"
```

### For Agents (Executing Work)

```bash
# 1. Navigate to your task
cd delegation/sprints/<sprint-id>/<task-id>/

# 2. Read AGENT.yml for:
#    - Your capabilities (what you can do)
#    - Safety rails (where you can work)
#    - Context (what docs to read)

# 3. Read TASK.md for:
#    - Objectives (what to achieve)
#    - Deliverables (what to create)
#    - Acceptance criteria (when you're done)

# 4. Execute the work

# 5. Update progress in TASK.md status section

# 6. On completion:
#    - Mark task complete in TASK.md
#    - Update agent_steps in AGENT.yml
#    - Move to next task (agent_steps.next)
```

---

## Core Concepts

### 1. Sprints

**What**: A collection of related tasks with a common goal  
**When**: Use for multi-week efforts requiring multiple phases  
**Structure**: Contains tasks, documentation, and coordination files

### 2. Tasks

**What**: Individual units of work with clear objectives  
**When**: Break sprints into tasks of 1-2 weeks each  
**Structure**: Has AGENT.yml (config) and TASK.md (instructions)

### 3. Agent Configuration (AGENT.yml)

**What**: Defines what an agent can do, where it can work, and how to behave  
**Contains**:
- **Capabilities**: Allowed actions and tools
- **Safety Rails**: File system scope and command whitelist
- **Telemetry**: Events to emit and correlation IDs
- **Context**: Documentation and constraints
- **Tracing**: Links to previous/next tasks

### 4. Tracing Infrastructure

**Purpose**: Link tasks together for navigation and telemetry  
**How**: Each task has `agent_steps.last` and `agent_steps.next`  
**Why**: Resume from any point, track lineage, correlate events

### 5. Hashing

**Purpose**: Unique identification and content addressing  
**Format**: SHA-256 of `<id>-<date>`  
**Used For**: Task identification, telemetry correlation, artifact CAS

---

## Folder Structure

```
delegation/
├── .templates/                    # THIS DIRECTORY - all templates
│   ├── agent-base/
│   │   ├── AGENT_BASE.yml        # Base agent config template
│   │   └── AGENT_TASK.yml        # Task-level agent template
│   │
│   ├── sprint-template/
│   │   ├── SPRINT_TEMPLATE.md    # Sprint overview template
│   │   └── README_TEMPLATE.md    # Sprint README template
│   │
│   ├── task-template/
│   │   └── TASK_TEMPLATE.md      # Task instructions template
│   │
│   ├── docs/
│   │   └── ADR_TEMPLATE.md       # Architecture Decision Record template
│   │
│   ├── scripts/
│   │   ├── create-sprint.sh      # Script to create sprint
│   │   └── create-task.sh        # Script to create task
│   │
│   ├── GUIDE.md                  # THIS FILE
│   └── QUICKSTART.md             # 5-minute getting started
│
├── sprints/                       # All sprint directories
│   ├── sprint-1/
│   │   ├── SPRINT.md             # Sprint overview
│   │   ├── README.md             # Sprint navigation
│   │   ├── TASK_TEMPLATE.md      # Copy of task template
│   │   ├── AGENT_TEMPLATE.yml    # Copy of agent template
│   │   │
│   │   ├── task-1/
│   │   │   ├── AGENT.yml         # Task-specific config
│   │   │   ├── TASK.md           # Task instructions
│   │   │   └── .hash             # Task hash (auto-generated)
│   │   │
│   │   └── task-2/
│   │       └── ...
│   │
│   └── sprint-2/
│       └── ...
│
└── [other delegation files]
```

---

## Creating a Sprint

### Method 1: Using Script (Recommended)

```bash
cd delegation/.templates/scripts
./create-sprint.sh <sprint-id> "<Sprint Name>"

# Example
./create-sprint.sh auth-system "Authentication System Implementation"
```

**This creates**:
- Sprint directory: `delegation/sprints/auth-system/`
- SPRINT.md (from template)
- README.md (from template)
- Copies of TASK_TEMPLATE.md and AGENT_TEMPLATE.yml
- Sprint hash auto-generated

### Method 2: Manual

```bash
# 1. Create directory
mkdir -p delegation/sprints/<sprint-id>

# 2. Copy templates
cp .templates/sprint-template/SPRINT_TEMPLATE.md delegation/sprints/<sprint-id>/SPRINT.md
cp .templates/sprint-template/README_TEMPLATE.md delegation/sprints/<sprint-id>/README.md
cp .templates/task-template/TASK_TEMPLATE.md delegation/sprints/<sprint-id>/
cp .templates/agent-base/AGENT_TASK.yml delegation/sprints/<sprint-id>/AGENT_TEMPLATE.yml

# 3. Generate hash
echo -n "<sprint-id>-$(date +%Y%m%d)" | sha256sum | cut -d' ' -f1

# 4. Edit templates
# Replace placeholders: <sprint-id>, [Sprint Name], <sprint-hash>, dates
```

### What to Edit

**SPRINT.md**:
- Sprint goal and context
- Success metrics
- Phases and tasks
- Dependencies and risks

**README.md**:
- Overview
- Task index
- Current status

---

## Creating Tasks

### Method 1: Using Script (Recommended)

```bash
cd delegation/.templates/scripts
./create-task.sh <sprint-id> <task-id> "<Task Name>" [last-task] [next-task]

# First task (no previous)
./create-task.sh auth-system auth-phase-1 "Phase 1: Setup" null auth-phase-2

# Middle task
./create-task.sh auth-system auth-phase-2 "Phase 2: Core" auth-phase-1 auth-phase-3

# Last task (no next)
./create-task.sh auth-system auth-phase-3 "Phase 3: Testing" auth-phase-2 null
```

**This creates**:
- Task directory: `delegation/sprints/<sprint-id>/<task-id>/`
- AGENT.yml (configured with hash and links)
- TASK.md (ready to customize)
- .hash file (for reference)

### Method 2: Manual

```bash
# 1. Create directory
mkdir -p delegation/sprints/<sprint-id>/<task-id>

# 2. Copy templates
cp .templates/task-template/TASK_TEMPLATE.md delegation/sprints/<sprint-id>/<task-id>/TASK.md
cp .templates/agent-base/AGENT_TASK.yml delegation/sprints/<sprint-id>/<task-id>/AGENT.yml

# 3. Generate hash
TASK_HASH=$(echo -n "<task-id>-$(date +%Y%m%d)" | sha256sum | cut -d' ' -f1)
echo $TASK_HASH > delegation/sprints/<sprint-id>/<task-id>/.hash

# 4. Edit AGENT.yml
# Set: task_id, task_hash, parent.sprint, agent_steps.last, agent_steps.next

# 5. Edit TASK.md
# Fill in: objectives, deliverables, acceptance criteria, tasks
```

### Task Linking

**Important**: After creating a task, update the previous task's `agent_steps.next`:

```bash
# If you created task-2 that follows task-1:
# Edit delegation/sprints/<sprint-id>/task-1/AGENT.yml
# Change: agent_steps.next: null
# To:     agent_steps.next: "task-2"
```

---

## Agent Configuration

### AGENT.yml Structure

```yaml
# Identity
task_id: "unique-task-id"
task_hash: "sha256-hash"

# Tracing
agent_steps:
  last: "previous-task-id"  # or null
  next: "next-task-id"      # or null

# Task definition
task:
  title: "Human-readable title"
  status: "pending"
  priority: "P0"
  estimated_duration: "2 weeks"
  objectives: [...]
  deliverables: [...]
  acceptance_criteria: [...]

# What the agent can do
capabilities:
  allowed_actions: ["scaffold.*", "test.*"]
  allowed_tools: ["fs", "git", "composer"]
  policies:
    must_use_command_router: false
    dry_run_supported: true
    commit_changes: false

# Where the agent can work
safety_rails:
  fs_scope:
    - "app/Core/"
    - "tests/"
  tool_whitelist:
    - "composer"
    - "php artisan test"
  timeouts:
    action_seconds: 90
    plan_seconds: 60

# Telemetry
telemetry:
  correlate_ids: true
  emit_events: ["task.started", "task.completed"]
  sinks: ["db"]

# Context
context:
  reference_docs: ["path/to/spec.md"]
  constraints: ["Follow PSR-12"]

# Guidance
prompts:
  system:
    - id: "task.guidance"
      content: "Instructions for this task"

# Lifecycle
hooks:
  on_start: ["context.load", "telemetry.emit.start"]
  on_complete: ["telemetry.emit.complete", "agent_steps.update"]

# Tools
mcp_tools:
  - id: "fs"
    capability: "read-write"
```

### Key Sections Explained

#### Capabilities
**What**: Defines what the agent is allowed to do  
**Use**: Restrict actions to specific categories for safety  
**Examples**:
- `scaffold.*` - Can create files from templates
- `test.*` - Can write and run tests
- `docs.*` - Can write documentation
- `refactor.*` - Can modify existing code

#### Safety Rails
**What**: Hard limits on where agent can work and what commands it can run  
**Use**: Prevent accidental damage to unrelated code  
**fs_scope**: Only these paths accessible  
**tool_whitelist**: Only these commands executable

#### Telemetry
**What**: Events emitted during task execution  
**Use**: Track progress, correlate across tasks, debugging  
**correlate_ids**: Links all events from this task together

#### Hooks
**What**: Actions to take at lifecycle events  
**Use**: Ensure consistent behavior (load docs, emit events, update tracing)

---

## Tracing & Telemetry

### Hash-Based Identification

Every task gets a unique SHA-256 hash:

```bash
echo -n "task-id-YYYYMMDD" | sha256sum | cut -d' ' -f1
```

**Used For**:
- Unique task identification
- Telemetry event correlation
- Content-addressable storage (future)
- Artifact lineage tracking

### Task Chain Linking

Tasks form a doubly-linked list:

```
null ← task-1 ← task-2 ← task-3 → null
       [next]→  [last]←  [next]→
```

**Benefits**:
- Navigate forward/backward through tasks
- Resume from any point
- Track dependencies
- Correlate related work

### Agent Steps Update

When a task completes, the agent updates tracing:

```yaml
# Before (in task-1/AGENT.yml)
agent_steps:
  last: null
  next: "task-2"

# After task-1 completes (still in task-1/AGENT.yml)
agent_steps:
  last: null
  next: "task-2"  # No change - this is permanent

# In task-2/AGENT.yml (when task-2 starts)
agent_steps:
  last: "task-1"  # Already set
  next: "task-3"  # Already set
```

### Telemetry Events

Standard events emitted by all tasks:

```
task.started
  → task_id, task_hash, timestamp

task.deliverable.completed
  → task_id, task_hash, deliverable_name, timestamp

task.completed
  → task_id, task_hash, deliverables_count, timestamp
```

**Correlation**: All events from a task share the same `task_hash`

### Querying Telemetry

```sql
-- Find all events for a task
SELECT * FROM telemetry_events 
WHERE metadata->>'task_hash' = '<hash>';

-- Find task completion order
SELECT task_id, completed_at 
FROM telemetry_events 
WHERE event_type = 'task.completed'
ORDER BY completed_at;

-- Trace backward from a task
-- (Follow agent_steps.last chain)
```

---

## Best Practices

### Sprint Planning

1. **Clear Goal**: One sentence sprint objective
2. **Success Metrics**: Measurable outcomes (time, quality, success rate)
3. **Right-Sized Phases**: 2-4 weeks per phase
4. **Task Granularity**: 1-2 weeks per task
5. **Documentation First**: Write specs before creating tasks

### Task Design

1. **Single Responsibility**: One clear objective per task
2. **Measurable Criteria**: Concrete acceptance criteria (tests pass, file exists, etc.)
3. **Proper Dependencies**: List what must be done first
4. **Safety First**: Narrow fs_scope to minimum needed
5. **Context Links**: Always link to specs/ADRs

### Agent Configuration

1. **Principle of Least Privilege**: Grant minimum capabilities needed
2. **Explicit Constraints**: Document all coding standards and constraints
3. **Clear Guidance**: Use prompts.system to provide specific instructions
4. **Telemetry**: Emit events for all major milestones
5. **Hooks**: Use lifecycle hooks for consistency

### Tracing

1. **Always Link**: Every task (except first/last) has both last and next
2. **Update Previous**: When creating new task, update previous task's next
3. **Hash Everything**: Generate hashes for all tasks and sprints
4. **Correlate Events**: Use task_hash in all telemetry

### Documentation

1. **README First**: Create sprint README as navigation hub
2. **ADRs for Decisions**: Document architectural choices
3. **Examples**: Include concrete examples in all templates
4. **Status Updates**: Agents should update TASK.md status section
5. **Notes Section**: Capture learnings and gotchas

---

## Examples

### Example 1: Simple Two-Task Sprint

**Goal**: Add authentication to application

```bash
# Create sprint
./create-sprint.sh auth-simple "Simple Authentication"

# Create task 1
./create-task.sh auth-simple auth-setup "Setup Auth Scaffolding" null auth-implement

# Create task 2
./create-task.sh auth-simple auth-implement "Implement Auth Logic" auth-setup null

# Customize tasks
cd ../../sprints/auth-simple/
# Edit auth-setup/TASK.md and AGENT.yml
# Edit auth-implement/TASK.md and AGENT.yml

# Delegate
"Agent, execute task auth-setup in delegation/sprints/auth-simple/auth-setup/"
```

### Example 2: Multi-Phase Sprint

**Goal**: Complete module refactor (6 tasks, 3 phases)

```bash
# Create sprint
./create-sprint.sh module-refactor "Module System Refactor"

# Phase 1: Foundation (2 tasks)
./create-task.sh module-refactor phase-1-contracts "Phase 1: Contracts" null phase-1-registry
./create-task.sh module-refactor phase-1-registry "Phase 1: Registry" phase-1-contracts phase-2-builders

# Phase 2: Builders (2 tasks)
./create-task.sh module-refactor phase-2-builders "Phase 2: Builders" phase-1-registry phase-2-manifests
./create-task.sh module-refactor phase-2-manifests "Phase 2: Manifests" phase-2-builders phase-3-migration

# Phase 3: Migration (2 tasks)
./create-task.sh module-refactor phase-3-migration "Phase 3: Migration" phase-2-manifests phase-3-testing
./create-task.sh module-refactor phase-3-testing "Phase 3: Testing" phase-3-migration null

# Edit sprint overview
cd ../../sprints/module-refactor/
# Edit SPRINT.md with phases, metrics, risks
# Edit README.md with task index

# Delegate phase by phase
"Agent, execute all tasks in phase 1..."
```

---

## Troubleshooting

### Problem: Agent can't find task

**Solution**: Check file path and AGENT.yml location
```bash
# Should be:
delegation/sprints/<sprint-id>/<task-id>/AGENT.yml

# Verify:
ls -la delegation/sprints/<sprint-id>/<task-id>/
```

### Problem: Agent says "not authorized"

**Solution**: Check `capabilities` and `safety_rails` in AGENT.yml
```yaml
capabilities:
  allowed_actions:
    - "scaffold.*"  # Add needed action category

safety_rails:
  fs_scope:
    - "path/agent/needs/access/"  # Add needed path
```

### Problem: Task hash collision

**Solution**: Hashes include date, so wait a day or change task-id slightly
```bash
# If collision occurs on same day:
echo -n "task-id-v2-$(date +%Y%m%d)" | sha256sum | cut -d' ' -f1
```

### Problem: Broken task chain

**Solution**: Verify agent_steps.last and agent_steps.next form valid chain
```bash
# Check each task
for dir in delegation/sprints/my-sprint/*/; do
  echo "=== $(basename $dir) ==="
  grep -A2 "agent_steps:" "$dir/AGENT.yml"
done

# Should form: null → task-1 → task-2 → task-3 → null
```

### Problem: Agent can't find documentation

**Solution**: Check `context.reference_docs` paths are correct
```yaml
context:
  reference_docs:
    - "delegation/specs/my-spec.md"  # Relative to project root
```

---

## Advanced Topics

### Custom Event Types

Add task-specific events:
```yaml
telemetry:
  emit_events:
    - "task.started"
    - "task.migration.executed"  # Custom event
    - "task.tests.passed"
    - "task.completed"
```

### Conditional Execution

Use prompts to guide agent behavior:
```yaml
prompts:
  system:
    - id: "conditional.logic"
      content: |
        IF tests fail:
          - Fix failing tests
          - Re-run tests
          - Only proceed if all pass
        
        IF migration needed:
          - Create migration file
          - Test migration up/down
          - Document schema changes
```

### Parallel Tasks

For independent tasks, both can have same `agent_steps.last`:
```yaml
# task-2a/AGENT.yml
agent_steps:
  last: "task-1"
  next: "task-3"

# task-2b/AGENT.yml
agent_steps:
  last: "task-1"
  next: "task-3"

# Both can run in parallel
```

---

## Quick Reference

### File Locations

- **Templates**: `delegation/.templates/`
- **Scripts**: `delegation/.templates/scripts/`
- **Sprints**: `delegation/sprints/<sprint-id>/`
- **Tasks**: `delegation/sprints/<sprint-id>/<task-id>/`

### Commands

```bash
# Create sprint
cd delegation/.templates/scripts
./create-sprint.sh <id> "<name>"

# Create task
./create-task.sh <sprint> <task> "<name>" [last] [next]

# Generate hash
echo -n "<id>-$(date +%Y%m%d)" | sha256sum | cut -d' ' -f1
```

### Agent Delegation

```bash
# Single task
"Agent, execute task <task-id> in delegation/sprints/<sprint>/<task>/"

# Entire phase
"Agent, execute all tasks in phase <N> of sprint <sprint-id>"

# Continue from interruption
"Agent, check agent_steps.next in <task> and continue from there"
```

---

## Additional Resources

- **Examples**: See `delegation/sprints/fe3-migration/` for real-world example
- **Templates**: All templates in `delegation/.templates/`
- **Scripts**: Automation scripts in `delegation/.templates/scripts/`

---

**Guide Version**: 1.0  
**Last Updated**: October 12, 2025  
**Maintainer**: [Your Name/Team]

For questions or improvements, create an issue or PR.
