# Sprint: Fragments Engine 3.0 Migration

**Sprint ID**: `fe3-migration`  
**Status**: Planning  
**Start Date**: TBD  
**Duration**: 26 weeks (6 months)  
**Owner**: Fragments Engine Team

---

## Sprint Goal

Transform the current Fragment-based system into Fragments Engine 3.0 - a fully modular, API-first, config-driven engine optimized for human + agent collaboration.

---

## Context

This sprint implements the comprehensive migration plan documented in:
- `delegation/Fragments Engine 3.0/ASSESSMENT_AND_PLAN.md`
- `delegation/Fragments Engine 3.0/fragments_engine_v_3_spec_prd_adrs_and_quickstart.md`

Current state: ~40% foundation (Fragment model, orchestration, React UI, services)  
Target state: Full FE 3.0 with module system, core contracts, UI DSL, Command Router, Agent system

---

## Success Metrics

- ✅ Time to scaffold new module: **< 5 min** to first UI/API response
- ✅ Deterministic generator success rate: **>95%**
- ✅ Agent task success on generated modules: **>80%** without human fix
- ✅ Module reusability score: High (common components shared)
- ✅ Code coverage: **>80%** for core

---

## Phases & Tasks

### Phase 0: Foundation & Planning (Week 1-2)
- **fe3-phase-0-setup**: Foundation setup, ADRs, config skeleton

### Phase 1: Core Contracts & Registry (Week 3-4)
- **fe3-phase-1-contracts**: Implement 7 core contracts
- **fe3-phase-1-registry**: Module registry and loader

### Phase 2: Module Definition API (Week 5-6)
- **fe3-phase-2-builders**: Fluent builder API (Module, Type, Field, etc.)
- **fe3-phase-2-manifests**: JSON schema validation for module.json

### Phase 3: Reference Module (Week 7-8)
- **fe3-phase-3-project-manager**: Convert Sprint/Task to FE 3.0 module (M3-SPEC-01)

### Phase 4: UI DSL (Week 9-10)
- **fe3-phase-4-ui-compiler**: Fluent PHP → JSON compiler (M3-DSL-02)
- **fe3-phase-4-snapshot-tests**: Snapshot tests for layouts

### Phase 5: React Renderer (Week 11-12)
- **fe3-phase-5-renderer**: JSON → React renderer with plane stack
- **fe3-phase-5-generic-components**: UniversalListModal, UniversalDetailModal, UniversalFormModal

### Phase 6: Command Router (Week 13-14)
- **fe3-phase-6-router**: Unified Command Router with policies

### Phase 7: Agent System (Week 15-16)
- **fe3-phase-7-agents**: Agent registry, runner, Postmaster (M3-AGENT-03)
- **fe3-phase-7-sse-dashboard**: SSE event stream and dashboard

### Phase 8: Prompt Manager (Week 17-18)
- **fe3-phase-8-prompts**: Versioned prompt store with hash pinning

### Phase 9: Rules Engine (Week 19-20)
- **fe3-phase-9-rules**: Composable rules with explain traces

### Phase 10: Scaffolding CLI (Week 21-22)
- **fe3-phase-10-cli**: Artisan commands (fe:make:module, fe:make:action, fe:make:widget)

### Phase 11: Documentation (Week 23-24)
- **fe3-phase-11-docs**: Best-in-class documentation and quickstart

### Phase 12: Validation (Week 25-26)
- **fe3-phase-12-second-module**: Build second module (CRM or Inventory) to validate abstractions

---

## Dependencies

- Laravel 12+
- React + TypeScript + shadcn/ui
- Prism PHP (LLM integration)
- JSON Schema validator (justinrainbow/json-schema)
- SSE library (for event streaming)

---

## Risks

| Risk | Mitigation |
|------|------------|
| Config sprawl | Strong schemas + generators + ADR discipline |
| Agent misuse | Capability whitelists + sandbox + dry-run |
| UI drift | Contract tests + snapshot tests |
| Scope creep | Strict phase gates; no feature adds mid-phase |
| Backward compat breaks | Feature flags + dual-mode + gradual migration |

---

## Tasks

See individual task directories:
- `fe3-phase-0-setup/`
- `fe3-phase-1-contracts/`
- `fe3-phase-1-registry/`
- `fe3-phase-2-builders/`
- `fe3-phase-2-manifests/`
- `fe3-phase-3-project-manager/`
- `fe3-phase-4-ui-compiler/`
- `fe3-phase-4-snapshot-tests/`
- `fe3-phase-5-renderer/`
- `fe3-phase-5-generic-components/`
- `fe3-phase-6-router/`
- `fe3-phase-7-agents/`
- `fe3-phase-7-sse-dashboard/`
- `fe3-phase-8-prompts/`
- `fe3-phase-9-rules/`
- `fe3-phase-10-cli/`
- `fe3-phase-11-docs/`
- `fe3-phase-12-second-module/`

---

## Notes

- This is an **incremental migration**, not a rewrite
- Feature flags gate new functionality (`FE3_ENABLED`)
- All phases have exit criteria and deliverables
- Reference module (project-manager) validates all abstractions first
- Each phase includes Pest tests and documentation

---

**Sprint Status**: ⏸️ Awaiting approval to begin Phase 0
