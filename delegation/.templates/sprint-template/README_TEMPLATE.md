# [Sprint Name]

**Sprint Status**: ⏸️ Status  
**Created**: YYYY-MM-DD  
**Duration**: X weeks  
**Owner**: [Owner]

---

## Overview

[Brief description of what this sprint accomplishes]

---

## Documentation

- **[SPRINT.md](./SPRINT.md)** - Sprint overview, phases, metrics
- **[../../.templates/](../../.templates/)** - Templates and guides

---

## Sprint Structure

### Phases (N total)

```
Phase 1: [Name] (Week X-Y)
  ├─ task-id-1
  └─ task-id-2

Phase 2: [Name] (Week X-Y)
  ├─ task-id-3
  └─ task-id-4
```

---

## Task Tracking

### Telemetry & Tracing

Each task includes:
- **task_hash**: SHA-256 hash for unique identification
- **agent_steps.last**: Previous task ID (for tracing backward)
- **agent_steps.next**: Next task ID (for tracing forward)

### Task States

- `pending` - Not yet started
- `in_progress` - Currently being worked on
- `completed` - All acceptance criteria met
- `blocked` - Waiting on dependencies

---

## Getting Started

### For Agents

1. Read sprint overview: [SPRINT.md](./SPRINT.md)
2. Navigate to first task: `[first-task-id]/`
3. Read task AGENT.yml for capabilities
4. Follow task TASK.md for instructions
5. Update status as you progress
6. On completion, update `agent_steps` in AGENT.yml

### For Humans

1. Review sprint documentation
2. Approve phases to begin
3. Monitor progress via task status updates
4. Review deliverables before phase transitions

---

## Task Index

### Phase 1: [Name] (Week X-Y)
- 📝 [task-id-1](./task-id-1/) - [Title]
- 🔜 task-id-2 - [Title]

[Repeat for each phase]

---

**Legend**: ✅ Completed | 📝 In Progress | 🔜 Not Started

---

## Current Status

**Phase**: [N]  
**Status**: [Status]  
**Tasks Created**: X/Y  
**Tasks Completed**: X/Y

---

**Sprint Hash**: `<sprint-hash>`
