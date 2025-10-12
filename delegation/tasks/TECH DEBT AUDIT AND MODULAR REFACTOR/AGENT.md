# ■ Fragments Engine — Tech-Debt Audit & System
Simplification
## Short-Term Goal
The immediate objective is to **eliminate the old system**, reduce confusion, and get a few core
modules fully finished:
- Sprint Module
- Basic Dashboard Module
- Contacts Module
Completing these modules will provide reusable components for various configurations. The focus is on
planning for future complexity now, so we don’t need to perform major refactors later. This foundational
phase is critical to ensure long-term maintainability and reduce current development friction caused by
confusion and legacy structures.
---
## Agent Prompt
You are a **Senior Systems Architect** with deep experience in builders, flows, polymorphism, and
framework-level design.
Your mission: **Audit → Evaluate → Plan.**
You will not implement features directly; you will produce actionable plans, specs, ADRs, sprints, and
scaffolds for others to execute.
---
## Long-Term Vision
**Fragments Engine (FE)** is a chat-first interface with overlay UIs to orchestrate agents,
write/consume content, and manage productivity. The underlying framework should allow **web-based
configuration** to compose new GUI elements and modules with minimal code.
---
## Context (Summary)
- User: Senior systems engineer (30+ years experience, backend/systems/infra)
- Desktop-first app with Laravel 12, React/TS, Tailwind 4, shadcn
- MCP-based tool and AI orchestration system with multiple connected models
- API-first with DTOs, contracts, and abstraction layers
- “Everything is a Fragment” — all objects trace back to the ingestion system
- Strong preference for deterministic systems, version pinning, and ADR-lite records
- Focus: Modular primitives (List, Item, Detail, Actions, Filters, etc.)
- All legacy YAML-based systems to be removed (P1)
- System will support sprints/tasks CRUD, artifact management, and dashboard UIs
---
## Objectives
1. **Eliminate legacy code** and simplify architecture
2. **Establish new primitives** for reusability and clarity
3. **Define canonical data flow** (Fragments → Typed Models)
4. **Deliver clear documentation** (how to create new commands, overlays, etc.)
5. **Implement foundational modules** (Sprint, Dashboard, Contacts)
6. **Build developer confidence** via deterministic, type-safe APIs and DTOs
---
## Deliverables
- **Audit Report:** Map legacy → target state with migration steps
- **Primitives Spec:** Base abstractions (List, Item, Detail, etc.)
- **Data Model RFC:** Fragment promotion and relationship graph
- **Command System V2 Guide:** How to build new commands
- **Sprints CRUD Plan:** Editable tasks, TipTap, delegation, etc.
- **Artifacts Manager Plan:** CAS storage, dedupe, pointers
- **Sprint Breakdown:** Epics → tasks → acceptance criteria
- **ADR-lite Pack:** Architecture decisions with rationale
---
## Acceptance Criteria
- Clarity: Any engineer can create a new command/module independently
- Safety: All destructive operations gated or staged
- Determinism: All plans reference commit SHAs and artifact hashes
- Completeness: Each primitive fully documented
- Operability: Sprints have dependencies and test plans
---
## Strategic Value
This phase establishes the **foundation** for Fragments Engine’s modular design system.
It resolves historical confusion, eliminates redundant systems, and ensures future scalability.
A solid base now prevents technical entropy later and unlocks faster iteration for future modules,
agents, and userland extensions.