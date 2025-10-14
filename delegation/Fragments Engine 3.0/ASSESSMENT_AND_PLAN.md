# Fragments Engine 3.0 - Assessment & Migration Plan

**Date**: October 12, 2025  
**Status**: Planning Phase  
**Agent**: Claude Code via delegation/Fragments Engine 3.0/AGENT.yml

---

## Executive Summary

This document assesses the current state of the Seer codebase against the Fragments Engine 3.0 specification and provides a concrete migration plan. The goal is to transform the current Fragment-based system into a fully modular, API-first, config-driven engine optimized for human + agent collaboration.

**Key Finding**: We have ~40% of the foundation in place, but significant architectural shifts are needed to reach FE 3.0.

---

## Current State Assessment

### ✅ What We Have (Strong Foundation)

#### 1. **Fragment System (Established)**
- **Location**: `app/Models/Fragment.php`
- **Capabilities**:
  - Polymorphic data model with type system
  - State validation framework (hooks ready)
  - Event emissions (Created/Updated/Deleted)
  - Rich scoping (vault, project, tags, metadata)
  - Inbox workflow (pending/accepted/archived)
  - Soft deletes and activity logging
  - Full-text search with relevance scoring
- **Gap**: Not yet modular; no module boundaries or manifests

#### 2. **Type System**
- **Location**: `app/Models/Type.php`, `config/fragments.php`
- **Capabilities**:
  - Fragment-backed types with schema validation
  - Type registry table (`types_registry`)
  - Hot fields for performance optimization
  - Search paths for type packs
- **Gap**: No module-scoped type definitions; no hash pinning

#### 3. **Command/Orchestration System**
- **Location**: `app/Commands/Orchestration/`, `app/Services/Orchestration/`
- **Capabilities**:
  - Slash command pattern (`/sprints`, `/tasks`, etc.)
  - Command handlers with navigation stack
  - Database-backed command registry (`commands` table)
  - Session context stack
  - Tool-aware pipeline (router → tool selector → runner → composer)
  - Validation framework (Sprint/Task completion validators)
- **Gap**: Not generalized to module system; no Command Router contract

#### 4. **UI Components (React/TypeScript)**
- **Location**: `resources/js/`
- **Capabilities**:
  - React + TypeScript + shadcn/ui
  - Modal navigation system
  - Component registry pattern (`CommandResultModal`)
  - Hooks and contexts (`resources/js/hooks`, `resources/js/contexts`)
  - Islands architecture
- **Gap**: Hardcoded component map; no JSON schema → React renderer; no plane stack

#### 5. **Services & Infrastructure**
- **Location**: `app/Services/`
- **Services Available**:
  - AI (enrichment, classification, embeddings)
  - Orchestration (sessions, memory, context, time tracking)
  - Search (hybrid search with vector embeddings)
  - Telemetry (metrics, logging, tracking)
  - Security (credential storage, secret redaction)
  - Tools (MCP integration, tool runner)
- **Gap**: Not exposed as core contracts; no module packaging

#### 6. **Contracts (Partial)**
- **Location**: `app/Contracts/`
- **Existing Contracts**:
  - `AIProviderInterface`
  - `CredentialStorageInterface`
  - `EmbeddingStoreInterface`
  - `HandlesCommand`
  - `HybridSearchInterface`
  - `ToolContract`
- **Gap**: Missing FE 3.0 core contracts (Context, Agent, Prompt, Rules, Widgets, CommandRouter, Templates)

#### 7. **Module Architecture (Documentation Only)**
- **Location**: `docs/MODULE_ARCHITECTURE.md`
- **Status**: Planning/design document
- **Content**: Fluent builder API design, CLI scaffolding, migration path
- **Gap**: Not implemented; no module registry, no loader

#### 8. **Configuration System**
- **Location**: `config/fragments.php`, `config/orchestration.php`, etc.
- **Capabilities**:
  - AI model configuration (provider/model per operation)
  - Tool configuration (exec, shell, fs, mcp)
  - Type system configuration
  - Credential storage configuration
- **Gap**: No module manifests; no engine-level config (`config/engine.php`)

#### 9. **Database Schema**
- **Tables Available**:
  - `fragments` (core data model)
  - `types_registry` (type definitions)
  - `commands` (command registry)
  - `work_items` (polymorphic tasks/sprints)
  - `fragment_tags`, `fragment_links`, `article_fragments`
  - `chat_sessions`, `chat_messages`
  - `llm_telemetry`, `command_telemetry`, `fragment_telemetry`
- **Gap**: No `modules`, `module_types`, `module_commands`, `module_permissions` tables

#### 10. **Telemetry & Observability**
- **Location**: `app/Services/Telemetry/`, database telemetry tables
- **Capabilities**:
  - LLM usage tracking (tokens, costs, providers)
  - Command execution telemetry
  - Fragment lifecycle telemetry
  - Activity logs via Spatie
- **Gap**: No correlation IDs across module boundaries; no SSE event stream

---

### ❌ What We're Missing (Critical Gaps)

#### 1. **Module System**
- No `Module`, `Type`, `Field`, `Command` builders
- No `ModuleRegistry` or `ModuleServiceProvider`
- No module manifests (`module.json`)
- No plugin/package strategy
- No version gating or dependency resolution

#### 2. **Core Contracts (FE 3.0)**
- No `ContextRepository` / `ContextManager`
- No `AgentRegistry` / `AgentRunner`
- No `PromptStore` / `PromptRenderer`
- No `RuleEngine` / `RuleEvaluator`
- No `WidgetRegistry` / `WidgetDefinition`
- No `CommandRouter` (unified action entry point)
- No `TemplateRegistry` / `ComponentRegistry`

#### 3. **UI DSL (Fluent PHP → JSON → React)**
- No server-side layout builders (`Layout::make()`, `Row::make()`, `Component::make()`)
- No JSON schema generation
- No contract tests / snapshot tests
- No typed client binding

#### 4. **Navigation Model (Planes/Stack)**
- Current: Modal stack (implicit)
- Target: Declarative plane stack (dashboard → detail → task) with Esc/Back/Close semantics
- No overlay focus policies

#### 5. **Action System**
- Current: Direct API calls from components
- Target: All actions route through Command Router with policies, idempotency, correlation IDs
- No action schemas or DTOs
- No action audit trail

#### 6. **Hashing & Pinning**
- No content-addressable storage (CAS) for artifacts
- No hash pinning for prompts/layouts/templates/actions
- No lineage tracking

#### 7. **Agent System**
- Current: Ad-hoc agent interactions via chat/commands
- Target: Agent profiles with capabilities, sandbox profiles, Postmaster for artifact routing
- No agent registry
- No SSE dashboard for live agent events

#### 8. **Prompt Management**
- Current: Inline prompts in code
- Target: Versioned prompt objects with variables, labels, evaluations, hash pinning
- No prompt store

#### 9. **Rules Engine**
- Current: Rule-based tagging (in config)
- Target: Composable rules for policies, validations, automation routing with "explain" traces
- No rule definitions or evaluator

#### 10. **Scaffolding CLI**
- No `php artisan fe:make:module`
- No `php artisan fe:make:action`
- No `php artisan fe:make:widget`
- No deterministic generators

#### 11. **Documentation (FE 3.0 Standards)**
- Current: Partial ADRs, planning docs, scattered guides
- Target: Best-in-class docs (quickstart, modules, ui-dsl, agents, prompts, rules, flows, observability, scaffolding, ADR index)
- No unified docs structure

---

## Gap Analysis Matrix

| Component | Current State | Target State (FE 3.0) | Gap Size | Priority |
|-----------|---------------|------------------------|----------|----------|
| **Module System** | None | Full registry + manifests | 🔴 Large | P0 |
| **Core Contracts** | 6/13 | 13/13 | 🟡 Medium | P0 |
| **UI DSL** | None | Fluent PHP → JSON → React | 🔴 Large | P0 |
| **Command Router** | Partial | Unified with policies | 🟡 Medium | P0 |
| **Fragment Model** | Strong | Module-scoped | 🟢 Small | P1 |
| **Type System** | Good | Hash-pinned + module-aware | 🟡 Medium | P1 |
| **Navigation Stack** | Implicit | Explicit planes | 🟡 Medium | P1 |
| **Action System** | Direct | Command Router only | 🟡 Medium | P1 |
| **Hashing/CAS** | None | Full lineage | 🔴 Large | P2 |
| **Agent System** | Ad-hoc | Registry + profiles | 🔴 Large | P2 |
| **Prompt Manager** | None | Versioned store | 🔴 Large | P2 |
| **Rules Engine** | Minimal | Composable + explain | 🟡 Medium | P2 |
| **Telemetry** | Good | Correlation IDs + SSE | 🟢 Small | P1 |
| **Scaffolding** | None | Full CLI suite | 🔴 Large | P3 |
| **Documentation** | Partial | Best-in-class | 🟡 Medium | P3 |

**Legend**: 🔴 Large (>4 weeks), 🟡 Medium (2-4 weeks), 🟢 Small (<2 weeks)

---

## Migration Strategy

### Approach: **Incremental Evolution** (Not Big Bang Rewrite)

**Rationale**:
- Preserve working Fragment system
- Introduce FE 3.0 concepts progressively
- Run dual-mode during transition
- Migrate modules one-by-one
- Keep production stable

### Guiding Principles
1. **Backward Compatibility**: Existing fragments continue to work
2. **Feature Flags**: Gate new system behind config
3. **Reference Module**: `module.project-manager` (Sprint/Task) is the first conversion
4. **Documentation First**: Write the contract, then implement
5. **Test Coverage**: Pest tests for all new abstractions

---

## Phased Migration Plan

### **Phase 0: Foundation & Planning** (Week 1-2)

#### Milestone: M0-SETUP
**Goal**: Establish FE 3.0 infrastructure and documentation scaffolding

**Tasks**:
- [x] Read and analyze FE 3.0 spec (this document)
- [ ] Create `docs/fragments-engine-v3/` directory structure
- [ ] Write ADR-001: Module-Based Architecture
- [ ] Write ADR-002: Fluent PHP → JSON UI Contracts
- [ ] Write ADR-003: Command Router as Single Entry
- [ ] Write ADR-004: Hash Pinning for Artifacts
- [ ] Create `config/engine.php` (global engine config)
- [ ] Create database migration for `modules`, `module_types`, `module_commands`, `module_permissions` tables
- [ ] Set up feature flags in `.env` (`FE3_ENABLED=false`)

**Deliverables**:
- ADR documents (4)
- Config file skeleton
- Database schema (not yet migrated)
- Documentation structure

---

### **Phase 1: Core Contracts & Registry** (Week 3-4)

#### Milestone: M3-CORE-01
**Goal**: Implement FE 3.0 core contracts and module registry

**Tasks**:
- [ ] Create `app/Core/` directory for FE 3.0 abstractions
- [ ] Implement core contracts:
  - [ ] `Core/Contracts/ContextRepositoryInterface.php`
  - [ ] `Core/Contracts/AgentRegistryInterface.php`
  - [ ] `Core/Contracts/PromptStoreInterface.php`
  - [ ] `Core/Contracts/RuleEngineInterface.php`
  - [ ] `Core/Contracts/WidgetRegistryInterface.php`
  - [ ] `Core/Contracts/CommandRouterInterface.php`
  - [ ] `Core/Contracts/TemplateRegistryInterface.php`
- [ ] Implement `Core/ModuleRegistry.php` (loads manifests, registers modules)
- [ ] Implement `Core/ModuleLoader.php` (scans packages, validates versions)
- [ ] Implement `Core/DTOs/` (ModuleDTO, TypeDTO, CommandDTO, etc.)
- [ ] Create `app/Providers/FragmentsEngineServiceProvider.php`
- [ ] Write Pest tests for registry and loader

**Deliverables**:
- 7 core contracts
- Module registry + loader
- DTOs for module definitions
- Service provider
- 20+ Pest tests

---

### **Phase 2: Module Definition API** (Week 5-6)

#### Milestone: M3-MODULE-02
**Goal**: Build fluent module definition API (builder pattern)

**Tasks**:
- [ ] Implement `Core/Builders/Module.php` (fluent builder)
- [ ] Implement `Core/Builders/Type.php`
- [ ] Implement `Core/Builders/Field.php`
- [ ] Implement `Core/Builders/Command.php`
- [ ] Implement `Core/Builders/Action.php`
- [ ] Implement `Core/Builders/Filter.php`
- [ ] Implement `Core/Builders/Container.php`
- [ ] Create JSON schema for `module.json` manifests
- [ ] Implement manifest validation
- [ ] Write builder examples in docs
- [ ] Write Pest tests for all builders

**Deliverables**:
- 7 builder classes
- JSON schema for manifests
- Validation logic
- Documentation with examples
- 30+ Pest tests

---

### **Phase 3: Reference Module (Project Manager)** (Week 7-8)

#### Milestone: M3-SPEC-01 (from seed_tasks)
**Goal**: Convert Sprint/Task system to FE 3.0 module

**Tasks**:
- [ ] Create `modules/project-manager/module.json`
- [ ] Create `modules/project-manager/ProjectManagerModule.php`
- [ ] Define Sprint and Task types using fluent API
- [ ] Register commands (`/sprints`, `/sprint-detail`, `/tasks`, `/task-detail`)
- [ ] Implement List/Detail/Task planes with Esc/Back/Close behaviors
- [ ] Route all UI actions through Command Router
- [ ] Emit telemetry events with correlation IDs
- [ ] Write acceptance tests
- [ ] Document module as reference example

**Deliverables**:
- `module.project-manager` fully migrated
- Manifest + module definition
- Command Router integration
- Telemetry with correlation IDs
- Reference documentation

**Acceptance Criteria** (from spec):
- ✅ List/Detail/Task planes with Esc/Back/Close behaviors
- ✅ All UI actions route through Command Router
- ✅ Telemetry events emitted with correlation IDs

---

### **Phase 4: UI DSL (Fluent PHP → JSON)** (Week 9-10)

#### Milestone: M3-DSL-02 (from seed_tasks)
**Goal**: Implement server-side layout compiler and snapshot tests

**Tasks**:
- [ ] Create `Core/UI/` directory
- [ ] Implement `UI/Builders/Layout.php` (fluent layout builder)
- [ ] Implement `UI/Builders/Row.php`, `UI/Builders/Component.php`, `UI/Builders/Slot.php`, `UI/Builders/Primitive.php`
- [ ] Implement `UI/Compilers/LayoutCompiler.php` (PHP → JSON)
- [ ] Create JSON schema for UI layouts (`docs/ui-dsl/schema.json`)
- [ ] Implement versioning and hashing for layouts
- [ ] Write snapshot tests (compile layouts, assert JSON output)
- [ ] Document UI DSL in `docs/ui-dsl/`

**Deliverables**:
- UI builder API
- Layout compiler
- JSON schema
- Snapshot tests
- UI DSL documentation

---

### **Phase 5: React Renderer (JSON → Components)** (Week 11-12)

#### Milestone: M3-CLIENT-03
**Goal**: Build React renderer that consumes JSON layouts

**Tasks**:
- [ ] Create `resources/js/fe3/` directory for FE 3.0 components
- [ ] Implement `fe3/LayoutRenderer.tsx` (consumes JSON, renders components)
- [ ] Implement `fe3/PlaneStack.tsx` (dashboard/detail/task plane management)
- [ ] Implement `fe3/ComponentRegistry.tsx` (maps JSON types → React components)
- [ ] Implement generic components:
  - [ ] `UniversalListModal.tsx`
  - [ ] `UniversalDetailModal.tsx`
  - [ ] `UniversalFormModal.tsx`
- [ ] Implement navigation behaviors (Esc/Back/Close)
- [ ] Implement overlay focus policies (modal/drawer/slideover)
- [ ] Update project-manager module to use JSON layouts
- [ ] Write E2E tests for renderer

**Deliverables**:
- React layout renderer
- Plane stack system
- Generic components
- Navigation behaviors
- E2E tests

---

### **Phase 6: Command Router** (Week 13-14)

#### Milestone: M3-ROUTER-04
**Goal**: Unified command router with policies and telemetry

**Tasks**:
- [ ] Implement `Core/Services/CommandRouter.php`
- [ ] Implement `Core/DTOs/CommandDTO.php`, `CommandResultDTO.php`
- [ ] Implement `Core/Policies/CommandPolicy.php` (capability checks)
- [ ] Implement idempotency tracking (deduplication by correlation ID)
- [ ] Emit command execution events
- [ ] Refactor all UI actions to route through CommandRouter
- [ ] Add correlation ID propagation
- [ ] Write Pest tests for router and policies

**Deliverables**:
- Command router service
- Policy enforcement
- Idempotency tracking
- Event emissions
- Full test coverage

---

### **Phase 7: Agent System & Postmaster** (Week 15-16)

#### Milestone: M3-AGENT-03 (from seed_tasks)
**Goal**: Agent profiles, capabilities, Postmaster, SSE dashboard

**Tasks**:
- [ ] Implement `Core/Services/AgentRegistry.php`
- [ ] Implement `Core/Services/AgentRunner.php`
- [ ] Create `agents` table (id, profile, capabilities, tools, sandbox_profile)
- [ ] Implement sandbox profiles (FS scope, tool whitelist, timeouts)
- [ ] Implement Postmaster agent (artifact routing to CAS)
- [ ] Implement Content-Addressable Storage (CAS) for artifacts
- [ ] Create SSE endpoint (`/api/v1/events`) for live agent events
- [ ] Build `AgentDashboard.tsx` (live event stream)
- [ ] Write Pest tests for agent system

**Deliverables**:
- Agent registry + runner
- Postmaster agent
- CAS for artifacts
- SSE event stream
- Agent dashboard UI

**Acceptance Criteria** (from spec):
- ✅ Artifacts routed to CAS
- ✅ Live events visible in dashboard

---

### **Phase 8: Prompt Manager** (Week 17-18)

#### Milestone: M3-PROMPT-05
**Goal**: Versioned prompt store with hash pinning

**Tasks**:
- [ ] Implement `Core/Services/PromptStore.php`
- [ ] Implement `Core/Services/PromptRenderer.php` (PHP + TS)
- [ ] Create `prompts` table (id, hash, version, template, variables, labels, evaluations)
- [ ] Implement hash pinning (content-based addressing)
- [ ] Implement context-pack injection
- [ ] Implement labels for discovery
- [ ] Implement evaluation notes (success/failure)
- [ ] Create `PromptEditor.tsx` UI component
- [ ] Write Pest tests for prompt store and renderer

**Deliverables**:
- Prompt store service
- Prompt renderer (PHP + TS)
- Database schema
- Hash pinning
- Editor UI
- Test coverage

---

### **Phase 9: Rules Engine** (Week 19-20)

#### Milestone: M3-RULES-06
**Goal**: Composable rules with explain traces

**Tasks**:
- [ ] Implement `Core/Contracts/RuleInterface.php`
- [ ] Implement `Core/Services/RuleEngine.php`
- [ ] Implement `Core/Services/RuleEvaluator.php`
- [ ] Create `rules` table (id, slug, conditions, actions, metadata)
- [ ] Implement rule composition (AND, OR, NOT)
- [ ] Implement "explain" traces (why did this rule match?)
- [ ] Implement rule hydration from config
- [ ] Build `RuleBuilder.tsx` UI component
- [ ] Write Pest tests for rules

**Deliverables**:
- Rule engine + evaluator
- Rule composition
- Explain traces
- UI builder
- Test coverage

---

### **Phase 10: Scaffolding CLI** (Week 21-22)

#### Milestone: M3-SCAFFOLD-07
**Goal**: Artisan commands for scaffolding modules, actions, widgets

**Tasks**:
- [ ] Create `app/Console/Commands/Fe/` directory
- [ ] Implement `FeMakeModuleCommand.php` (scaffold full module)
- [ ] Implement `FeMakeActionCommand.php` (scaffold action class)
- [ ] Implement `FeMakeWidgetCommand.php` (scaffold widget component)
- [ ] Create stub templates in `resources/templates/fe3/`
- [ ] Implement deterministic naming conventions
- [ ] Add `--with=crud,actions,ui` flags
- [ ] Write CLI tests
- [ ] Document CLI usage in quickstart

**Deliverables**:
- 3 Artisan commands
- Stub templates
- CLI tests
- Quickstart documentation

---

### **Phase 11: Documentation & Quickstart** (Week 23-24)

#### Milestone: M3-DOCS-08
**Goal**: Best-in-class documentation

**Tasks**:
- [ ] Write `docs/fragments-engine-v3/intro.md` (What/Why, mental model)
- [ ] Write `docs/fragments-engine-v3/quickstart.md` (10-min to first module)
- [ ] Write `docs/fragments-engine-v3/modules/` (manifest, lifecycle, versioning)
- [ ] Write `docs/fragments-engine-v3/ui-dsl/` (Fluent API, JSON schema, client binding)
- [ ] Write `docs/fragments-engine-v3/agents/` (capabilities, sandbox, telemetry)
- [ ] Write `docs/fragments-engine-v3/prompts/` (authoring, variables, evaluation)
- [ ] Write `docs/fragments-engine-v3/rules/` (authoring rules, explain traces)
- [ ] Write `docs/fragments-engine-v3/flows/` (n8n integration, security)
- [ ] Write `docs/fragments-engine-v3/observability/` (events, logs, metrics)
- [ ] Write `docs/fragments-engine-v3/scaffolding/` (deterministic generation)
- [ ] Create ADR index template
- [ ] Publish to internal wiki

**Deliverables**:
- 10+ documentation pages
- ADR index
- Quickstart guide
- API reference

---

### **Phase 12: Second Module (Validation)** (Week 25-26)

#### Milestone: M3-MODULE-09
**Goal**: Build second domain module to validate abstractions

**Tasks**:
- [ ] Choose domain (CRM or Inventory)
- [ ] Design module manifest
- [ ] Implement using FE 3.0 module API
- [ ] Test feature parity with reference module
- [ ] Document lessons learned
- [ ] Refine abstractions based on feedback

**Deliverables**:
- Second domain module
- Validation report
- Abstraction refinements

---

## Open Questions & Decisions Needed

### Architecture Decisions
1. **Module Packaging**: Monorepo vs separate Composer packages for domain modules?
   - **Recommendation**: Start with monorepo (`modules/` directory), extract to packages later
2. **State Hydration**: Local store vs server state for React components?
   - **Recommendation**: Server state (React Query) for consistency
3. **Schema Registry**: Central vs per-module?
   - **Recommendation**: Per-module with central index
4. **Vector Search**: Opt-in or always enabled?
   - **Recommendation**: Opt-in via config flag

### Technical Challenges
1. **Migration of Existing Fragments**: Bulk migration script or lazy conversion?
2. **Backward Compatibility**: How long to support pre-FE3 patterns?
3. **Performance**: JSON schema validation overhead?
4. **Type Safety**: How to enforce TS types on JSON contracts?

### Team Coordination
1. **Feature Flags**: When to enable FE 3.0 by default?
2. **Breaking Changes**: How to communicate deprecations?
3. **Training**: Onboarding docs for new module authors?

---

## Success Metrics (from PRD)

### MVP Metrics
- ✅ Time to scaffold a new module: **< 5 min** to first UI/API response
- ✅ Deterministic generator success rate: **>95%**
- ✅ Agent task success on generated modules without human fix: **>80%**

### Post-MVP Metrics
- Module reusability score (how many modules use common components)
- Developer satisfaction (survey after building first module)
- System performance (latency, throughput)
- Code coverage (target: >80% for core)

---

## Risks & Mitigations

| Risk | Impact | Likelihood | Mitigation |
|------|--------|------------|------------|
| **Config sprawl** | High | Medium | Strong schemas + generators + ADR discipline |
| **Agent misuse** | High | Medium | Capability whitelists + sandbox + dry-run |
| **UI drift** | Medium | Medium | Contract tests + snapshot tests |
| **Performance overhead** | Medium | Low | Lazy loading, caching, hot fields |
| **Adoption resistance** | Medium | Low | Excellent docs + CLI + reference module |
| **Scope creep** | High | High | Strict phase gates; no feature adds mid-phase |
| **Backward compat breaks** | High | Medium | Feature flags + dual-mode + gradual migration |

---

## Next Immediate Actions

### This Week (Week 1)
1. **Review this document** with team/stakeholders
2. **Create Jira/Linear tickets** for Phase 0 tasks
3. **Set up `docs/fragments-engine-v3/` directory**
4. **Draft ADR-001** (Module-Based Architecture)
5. **Create `config/engine.php` skeleton**

### Next Week (Week 2)
1. **Complete Phase 0** (Foundation & Planning)
2. **Begin Phase 1** (Core Contracts & Registry)
3. **Write first Pest tests** for module registry

---

## Appendix A: File Structure (Target State)

```
/app
  /Core                       # FE 3.0 engine
    /Builders                 # Fluent API builders
      Module.php
      Type.php
      Field.php
      Command.php
      Action.php
      Filter.php
      Container.php
    /Contracts                # Core interfaces
      ContextRepositoryInterface.php
      AgentRegistryInterface.php
      PromptStoreInterface.php
      RuleEngineInterface.php
      WidgetRegistryInterface.php
      CommandRouterInterface.php
      TemplateRegistryInterface.php
    /DTOs                     # Data transfer objects
      ModuleDTO.php
      TypeDTO.php
      CommandDTO.php
      ActionDTO.php
    /Services                 # Core services
      ModuleRegistry.php
      ModuleLoader.php
      CommandRouter.php
      AgentRegistry.php
      PromptStore.php
      RuleEngine.php
    /UI                       # UI compiler
      /Builders
        Layout.php
        Row.php
        Component.php
        Slot.php
        Primitive.php
      /Compilers
        LayoutCompiler.php
    /Support                  # Utilities
      HashGenerator.php
      IdGenerator.php
      TelemetryHelper.php

/modules                      # Domain modules
  /project-manager
    module.json               # Manifest
    ProjectManagerModule.php  # Module definition
    /Commands
      SprintListCommand.php
      SprintDetailCommand.php
    /Controllers
      SprintController.php
    /Models
      Sprint.php
      Task.php
    /UI
      SprintListModal.tsx
      SprintDetailModal.tsx
      TaskDetailModal.tsx
    /Docs
      README.md

/config
  engine.php                  # Global engine config

/docs
  /fragments-engine-v3
    intro.md
    quickstart.md
    /modules
    /ui-dsl
    /agents
    /prompts
    /rules
    /flows
    /observability
    /scaffolding
    /adr
      README.md
      ADR-001-module-based-architecture.md
      ADR-002-fluent-php-json-ui-contracts.md
      ADR-003-command-router-single-entry.md
      ADR-004-hash-pinning.md

/resources
  /js
    /fe3                      # FE 3.0 React components
      LayoutRenderer.tsx
      PlaneStack.tsx
      ComponentRegistry.tsx
      UniversalListModal.tsx
      UniversalDetailModal.tsx
      UniversalFormModal.tsx
  /templates
    /fe3                      # Scaffolding stubs
      module.stub
      command.stub
      action.stub
      widget.stub

/database
  /migrations
    2025_10_12_create_modules_table.php
    2025_10_12_create_module_types_table.php
    2025_10_12_create_module_commands_table.php
    2025_10_12_create_module_permissions_table.php
    2025_10_12_create_agents_table.php
    2025_10_12_create_prompts_table.php
    2025_10_12_create_rules_table.php
```

---

## Appendix B: Key Dependencies

### Existing (Keep)
- Laravel 12+
- React + TypeScript
- shadcn/ui
- Prism PHP (LLM integration)
- Spatie Activity Log
- Laravel Horizon (queues)

### New (Add)
- JSON Schema validator (justinrainbow/json-schema - already installed ✅)
- SSE library (for event streaming)
- Content-addressable storage library (or custom)

---

## Appendix C: Estimated Timeline

**Total Duration**: 26 weeks (~6 months)

**Breakdown**:
- **Phase 0-2** (Foundation → Module API): 6 weeks
- **Phase 3-5** (Reference Module → React Renderer): 6 weeks
- **Phase 6-7** (Command Router → Agent System): 4 weeks
- **Phase 8-9** (Prompt Manager → Rules Engine): 4 weeks
- **Phase 10-11** (Scaffolding → Documentation): 4 weeks
- **Phase 12** (Second Module Validation): 2 weeks

**Critical Path**: Phase 1 → Phase 2 → Phase 3 → Phase 4 → Phase 5

**Parallelizable**:
- Phase 6 can overlap with Phase 5 (80%)
- Phase 8-9 can overlap (50%)
- Phase 10-11 can overlap (75%)

**Resource Requirements**: 1-2 senior engineers full-time

---

## Conclusion

We have a **strong foundation** with the current Fragment system, orchestration layer, and React UI. The migration to Fragments Engine 3.0 is **ambitious but achievable** with a disciplined, phased approach.

**Key Success Factors**:
1. ✅ Incremental migration (not rewrite)
2. ✅ Reference module first (project-manager)
3. ✅ Strong abstractions with fluent APIs
4. ✅ Excellent documentation and CLI tools
5. ✅ Test coverage at every phase

**Next Step**: Review this document, approve the plan, and begin Phase 0.

---

**Document Version**: 1.0  
**Last Updated**: October 12, 2025  
**Owner**: Fragments Engine Team  
**Status**: ✅ Ready for Review
``