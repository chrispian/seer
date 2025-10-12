# Fragments Engine 3.0 Migration Sprint

**Sprint Status**: ⏸️ Planning Complete - Awaiting Approval  
**Created**: October 12, 2025  
**Estimated Duration**: 26 weeks (6 months)  
**Owner**: Fragments Engine Team

---

## Overview

This sprint implements the comprehensive migration to Fragments Engine 3.0 - transforming the current Fragment-based system into a fully modular, API-first, config-driven engine optimized for human + agent collaboration.

**Current State**: ~40% foundation (Fragment model, orchestration, React UI, services)  
**Target State**: Full FE 3.0 with module system, core contracts, UI DSL, Command Router, Agent system

---

## Documentation

- **[SPRINT.md](./SPRINT.md)** - Sprint overview, phases, success metrics
- **[TASK_TEMPLATE.md](./TASK_TEMPLATE.md)** - Template for creating new tasks
- **[AGENT_TEMPLATE.yml](./AGENT_TEMPLATE.yml)** - Template for task AGENT.yml files
- **[../../Fragments Engine 3.0/ASSESSMENT_AND_PLAN.md](../../Fragments%20Engine%203.0/ASSESSMENT_AND_PLAN.md)** - Comprehensive assessment and migration plan
- **[../../Fragments Engine 3.0/fragments_engine_v_3_spec_prd_adrs_and_quickstart.md](../../Fragments%20Engine%203.0/fragments_engine_v_3_spec_prd_adrs_and_quickstart.md)** - FE 3.0 specification

---

## Sprint Structure

### Phases (12 total)

```
Phase 0: Foundation & Planning (Week 1-2)
  └─ fe3-phase-0-setup

Phase 1: Core Contracts & Registry (Week 3-4)
  ├─ fe3-phase-1-contracts
  └─ fe3-phase-1-registry

Phase 2: Module Definition API (Week 5-6)
  ├─ fe3-phase-2-builders
  └─ fe3-phase-2-manifests

Phase 3: Reference Module (Week 7-8)
  └─ fe3-phase-3-project-manager (M3-SPEC-01)

Phase 4: UI DSL (Week 9-10)
  ├─ fe3-phase-4-ui-compiler (M3-DSL-02)
  └─ fe3-phase-4-snapshot-tests

Phase 5: React Renderer (Week 11-12)
  ├─ fe3-phase-5-renderer
  └─ fe3-phase-5-generic-components

Phase 6: Command Router (Week 13-14)
  └─ fe3-phase-6-router

Phase 7: Agent System (Week 15-16)
  ├─ fe3-phase-7-agents (M3-AGENT-03)
  └─ fe3-phase-7-sse-dashboard

Phase 8: Prompt Manager (Week 17-18)
  └─ fe3-phase-8-prompts

Phase 9: Rules Engine (Week 19-20)
  └─ fe3-phase-9-rules

Phase 10: Scaffolding CLI (Week 21-22)
  └─ fe3-phase-10-cli

Phase 11: Documentation (Week 23-24)
  └─ fe3-phase-11-docs

Phase 12: Validation (Week 25-26)
  └─ fe3-phase-12-second-module
```

---

## Task Tracking

### Telemetry & Tracing

Each task includes:
- **task_hash**: SHA-256 hash for unique identification
- **agent_steps.last**: Previous task ID (for tracing backward)
- **agent_steps.next**: Next task ID (for tracing forward)

This enables:
- Full lineage tracking
- Resume from any point
- Correlation across telemetry sinks

### Task States

- `pending` - Not yet started
- `in_progress` - Currently being worked on
- `completed` - All acceptance criteria met
- `blocked` - Waiting on dependencies

---

## Getting Started

### For Agents

1. Read the sprint overview: [SPRINT.md](./SPRINT.md)
2. Read the assessment plan: [ASSESSMENT_AND_PLAN.md](../../Fragments%20Engine%203.0/ASSESSMENT_AND_PLAN.md)
3. Navigate to first task: `fe3-phase-0-setup/`
4. Read task AGENT.yml for capabilities and constraints
5. Follow task TASK.md for detailed instructions
6. Update status as you progress
7. On completion, update `agent_steps` in AGENT.yml

### For Humans

1. Review [ASSESSMENT_AND_PLAN.md](../../Fragments%20Engine%203.0/ASSESSMENT_AND_PLAN.md) for full context
2. Approve Phase 0 to begin
3. Monitor progress via task status updates
4. Review deliverables before phase transitions
5. Approve migrations before running them

---

## Creating New Tasks

Use the templates provided:

```bash
# 1. Create task directory
mkdir -p fe3-phase-[N]-[name]

# 2. Generate task hash
echo -n "fe3-phase-[N]-[name]-$(date +%Y%m%d)" | sha256sum | cut -d' ' -f1

# 3. Copy templates
cp TASK_TEMPLATE.md fe3-phase-[N]-[name]/TASK.md
cp AGENT_TEMPLATE.yml fe3-phase-[N]-[name]/AGENT.yml

# 4. Fill in placeholders
# - Replace [task-id], [hash], [phase], etc.
# - Update objectives, deliverables, acceptance criteria
# - Set agent_steps.last and agent_steps.next

# 5. Update previous task's agent_steps.next
# Edit previous task's AGENT.yml and set next = new task ID
```

---

## Success Metrics

### MVP Metrics (from PRD)
- ⏱️ Time to scaffold new module: **< 5 min** to first UI/API response
- ✅ Deterministic generator success rate: **>95%**
- 🤖 Agent task success on generated modules: **>80%** without human fix

### Quality Metrics
- 🧪 Code coverage: **>80%** for core
- 📚 Documentation coverage: **100%** of public APIs
- 🔒 Security: Zero critical vulnerabilities
- ⚡ Performance: No regressions vs baseline

---

## Risks & Mitigations

| Risk | Impact | Mitigation |
|------|--------|------------|
| Config sprawl | High | Strong schemas + generators + ADR discipline |
| Agent misuse | High | Capability whitelists + sandbox + dry-run |
| UI drift | Medium | Contract tests + snapshot tests |
| Scope creep | High | Strict phase gates; no feature adds mid-phase |
| Backward compat breaks | High | Feature flags + dual-mode + gradual migration |

---

## Current Status

**Phase**: 0 (Foundation & Planning)  
**Status**: ⏸️ Awaiting approval to begin  
**Tasks Created**: 2/18  
**Tasks Completed**: 0/18

### Completed Tasks
- None yet

### In Progress
- None yet

### Next Up
- `fe3-phase-0-setup` (Foundation & Planning)

---

## Communication

### Status Updates
- **Weekly**: Summary in `SPRINT.md` status section
- **Per Task**: Update task TASK.md status section
- **Per Deliverable**: Emit `task.deliverable.completed` event

### Blockers
- Report blockers in task TASK.md
- Escalate critical blockers immediately
- Include: blocker description, impact, proposed resolution

### Questions
- Document in task TASK.md notes section
- Tag with `[QUESTION]` for visibility
- Reference relevant ADRs or specs

---

## References

- [FE 3.0 Specification](../../Fragments%20Engine%203.0/fragments_engine_v_3_spec_prd_adrs_and_quickstart.md)
- [Assessment & Migration Plan](../../Fragments%20Engine%203.0/ASSESSMENT_AND_PLAN.md)
- [Parent AGENT.yml](../../Fragments%20Engine%203.0/AGENT.yml)
- [Module Architecture Doc](../../../docs/MODULE_ARCHITECTURE.md)

---

## Task Index

### Phase 0: Foundation (Week 1-2)
- ✅ [fe3-phase-0-setup](./fe3-phase-0-setup/) - Foundation & Planning

### Phase 1: Core Contracts (Week 3-4)
- 📝 [fe3-phase-1-contracts](./fe3-phase-1-contracts/) - Core Contracts Implementation
- 🔜 fe3-phase-1-registry - Module Registry & Loader

### Phase 2: Module API (Week 5-6)
- 🔜 fe3-phase-2-builders - Fluent Builder API
- 🔜 fe3-phase-2-manifests - Manifest Validation

### Phase 3: Reference Module (Week 7-8)
- 🔜 fe3-phase-3-project-manager - Sprint/Task Module Conversion (M3-SPEC-01)

### Phase 4: UI DSL (Week 9-10)
- 🔜 fe3-phase-4-ui-compiler - Layout Compiler (M3-DSL-02)
- 🔜 fe3-phase-4-snapshot-tests - Snapshot Tests

### Phase 5: React Renderer (Week 11-12)
- 🔜 fe3-phase-5-renderer - JSON → React Renderer
- 🔜 fe3-phase-5-generic-components - Universal Components

### Phase 6: Command Router (Week 13-14)
- 🔜 fe3-phase-6-router - Unified Command Router

### Phase 7: Agent System (Week 15-16)
- 🔜 fe3-phase-7-agents - Agent Registry & Postmaster (M3-AGENT-03)
- 🔜 fe3-phase-7-sse-dashboard - SSE Event Dashboard

### Phase 8: Prompt Manager (Week 17-18)
- 🔜 fe3-phase-8-prompts - Versioned Prompt Store

### Phase 9: Rules Engine (Week 19-20)
- 🔜 fe3-phase-9-rules - Composable Rules Engine

### Phase 10: Scaffolding (Week 21-22)
- 🔜 fe3-phase-10-cli - Artisan Scaffolding Commands

### Phase 11: Documentation (Week 23-24)
- 🔜 fe3-phase-11-docs - Documentation & Quickstart

### Phase 12: Validation (Week 25-26)
- 🔜 fe3-phase-12-second-module - Second Module Validation

---

**Legend**: ✅ Completed | 📝 In Progress | 🔜 Not Started

---

**Sprint Hash**: `ca9c2bd8b2f63e18e23855042d05b6e817da0240fe77970c2bf97ba63e8b4dbc`
