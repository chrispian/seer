# Fragments Engine v3 — Architecture Spec, PRD, ADRs, and Quickstart

> Goal: a config‑backed, API‑first engine for assembling fragments into full business apps (CRUD, reports, automations, integrations, flows, actions) with deterministic scaffolding so agents can extend it safely.

---

## 1) North‑Star & Principles

**North‑Star:** A powerful, flexible engine that lets you declaratively compose Modules (data + UI + actions + flows) into complete business apps, optimized for human + agent collaboration.

**Guiding principles**
- **API‑first:** Everything has a typed contract/DTO and is reachable over HTTP and/or MCP; UI consumes the same APIs.
- **Composition over inheritance:** Modules compose Container → Layout → Components → Primitives. No deep class hierarchies.
- **Config drives behavior:** Deterministic generators scaffold from config; runtime uses the same config.
- **Determinism:** Hashes for schemas, config, assets; stable IDs for relationships (excellent for agents + telemetry).
- **Single source of truth:** Module manifests define routing, policies, views, actions, events.
- **Observability by default:** Events, logs, metrics, traces for humans and agents; reproducible runs.
- **Progressive hardening:** Clear safety rails, capability whitelists, policy checks, and sandboxing options.

---

## 2) Target Stack & Constraints
- **Backend:** Laravel 12+, PHP 8.3+, Eloquent, Events, Jobs, Caching.
- **Frontend:** React + TypeScript + shadcn/ui.
- **Transport:** REST + (later) SSE/WebSockets for live dashboards; MCP for tools; n8n for external flows.
- **Data:** Postgres (primary), SQLite for local/dev snapshots; CAS for artifacts; pgvector optional.
- **Infra:** Queue workers (Horizon), CI/CD (GitHub Actions), feature flags.

---

## 3) High‑Level Architecture

```
/core
  /Contracts        # PHP interfaces for services (Context, Prompt, Agent, Rules, Widgets, CommandRouter)
  /DTOs             # Typed DTOs for API boundaries
  /Services         # Concrete service implementations (stateless where possible)
  /Events           # Domain events (module‑agnostic)
  /Policies         # Global policies/capabilities
  /Scaffolding      # Generators for config→code
  /Support          # Utilities (hashing, id, telemetry)

/modules
  /Project
  /Sprints
  /Agents
  /Prompts
  /Context
  /Rules
  /Widgets
  /Templates
    module.json     # Manifest (id, version, routes, capabilities, deps)
    ModuleServiceProvider.php
    /Config         # module.*.php — layouts, components, actions, flows
    /Domain         # Models, Migrations, Policies, Factories
    /Actions        # App Actions (invocable, typed)
    /Flows          # n8n bindings / local flows
    /Http           # Controllers, Resources
    /UI             # React components (TSX), shadcn composition, schemas
    /Docs           # module docs + ADRs scoped to module

/apps
  /Console          # CLI + MCP wrappers
  /Api              # API kernel, versioning, middleware, rate limits

/resources
  /templates        # Scaffolding stubs (PHP/TS/MD)

/config
  engine.php        # global engine config (safety rails, registry, paths)

```

**Decision:** Use **Module‑based** organization with a thin/core spine. Modules are versioned, self‑contained, and declare dependencies. This supports incremental evolution, isolation, and agent‑safe scaffolding.

---

## 3A) Module Boundaries & Packaging (Revised)
To avoid mixing “business modules” with “building‑block modules,” split the ecosystem into **three module classes**:

1. **Core Modules** (engine‑level; shipped in the monorepo)
   - Namespace: `Fe\Core\*`
   - Examples: `engine.context`, `engine.agents`, `engine.prompts`, `engine.rules`, `engine.widgets`, `engine.command-router`, `engine.telemetry`, `engine.cas`, `engine.vector`.
   - Role: Provide primitives/services and typed contracts. **No business UI.**
   - Dependency: Only on `/core` and other Core Modules.

2. **Component Modules** (building blocks; shipped in the monorepo, but publishable)
   - Namespace: `Fe\Component\*`
   - Examples: `component.widgets.table`, `component.widgets.form`, `component.actions.crud`, `component.rules.common`, `component.templates.object-list`.
   - Role: Reusable UI widgets, actions, rules, templates. **No domain knowledge.**
   - Dependency: Can depend on Core Modules; **must not** depend on Domain Modules.

3. **Domain Modules** (business features; **plugin‑ready**)
   - Namespace: `Fe\Module\ProjectManager`, `Fe\Module\Invoicing`, `Fe\Module\HabitTracker`, etc.
   - Packaging: Prefer separate Composer packages (see below). Installable add‑ons.
   - Role: Compose data models + layouts + flows + actions specific to a domain.
   - Dependency: May depend on Core + Component Modules; avoid coupling to other Domain Modules (communicate via Events/Contracts).

**Why this split?**
- Keeps **Core** small and stable; **Component** surface is the palette; **Domain** is assemble‑and‑ship.
- Agents won’t confuse “Widgets” (component module) with “Project Manager” (domain module) since the namespacing and manifests are explicit.

### Composer Packaging Strategy (Plugins)
- **Composer vendor:** `hollis-labs/fe-<type>-<name>`.
  - Core (mono): part of app repo.
  - Component: may be published but versioned with the engine (e.g., `^3`).
  - Domain: **plugin/addon** packages with semantic versioning and an Engine compatibility constraint.
- **Auto‑discovery:** Each package ships a `ModuleServiceProvider` and a `module.json` manifest. The host scans installed packages for manifests and registers them.
- **Version gating:** `module.json` includes `"engine": ">=3.0 <4.0"` so incompatible modules fail fast.
- **Config surface:** Domain modules expose a typed `SettingsDTO` and publish their config to `/config/modules/<slug>.php`.

### Directory Layout (Revised sketch)
```
/packages
  /core              # (if you break core into packages later)
  /component
    /widgets-table
    /widgets-form
    /actions-crud
    /rules-common
  /modules           # domain plugins (separate repos once published)
    /project-manager
    /invoicing
    /habit-tracker

/app (host)
  /Core (thin)       # optional; or keep using /core from earlier
  /ModuleHost        # scans composer packages, registers providers, routes
```

### Manifests by class
- **Core/Component manifest**: `type: core|component`, exports contracts/registries.
- **Domain manifest**: `type: domain`, declares routes, UI entries, capabilities, seeds; depends on specific component widgets/actions.

### Cross‑module communication
- Prefer **Events + DTOs** over direct calls across Domain Modules.
- If a shared contract is needed, promote it into a **Component** package.

### Naming clarity (for agents & humans)
- Use explicit prefixes:
  - `engine.*` for Core Modules
  - `component.*` for Component Modules
  - `module.*` for Domain Modules
- Example IDs: `engine.rules`, `component.widgets.table`, `module.project-manager`.

## 4) Core Contracts (API‑First)

### 4.1 Context Manager
- **Purpose:** Provide stable, typed access to user/app/agent context, preferences, and state.
- **Interface:** `ContextRepository` (get/set/patch/resolve); `ContextScope` (user, agent, project, session); `ContextDTO`.
- **Features:** Namespaced keys, TTL, provenance (who/what wrote it), hashing, merge policies, export/import.

### 4.2 Agent Manager
- **Purpose:** Register agent profiles, capabilities, tools (MCP), policies; run tasks and capture telemetry.
- **Interface:** `AgentRegistry`, `AgentRunner`, `TaskDTO`, `RunLogDTO`.
- **Notes:** Postmaster agent for artifact routing; sandbox profiles; event stream for live dashboards.

### 4.3 Prompt Manager
- **Purpose:** Versioned prompt objects with variables, templates, labels, and evaluations.
- **Interface:** `PromptStore` (CRUD + versions), `PromptRenderer` (TS+PHP), `PromptDTO`.
- **Notes:** Hash pinning; context‑pack injection; labels for discovery; evaluation notes (success/failure).

### 4.4 Rules Engine
- **Purpose:** Small, composable rules for policies, validations, automation routing.
- **Interface:** `Rule`, `RuleSet`, `RuleEvaluator` → boolean, score, or decision object.
- **Notes:** Deterministic, pure; can be hydrated from config; supports “explain” traces.

### 4.5 Widget System
- **Purpose:** Declarative UI building blocks (table, grid, list, detail, chart, form, etc.).
- **Interface:** `WidgetDefinition`, `WidgetProps`, `WidgetRegistry`.
- **Notes:** Server definitions render to typed JSON → React TS components.

### 4.6 Command Router
- **Purpose:** Resolve command strings/events → actions with policy checks and idempotency.
- **Interface:** `CommandRouter`, `CommandDTO`, `CommandPolicy`.
- **Notes:** Supports link|callback|action; emits events; logs correlation IDs.

### 4.7 Templates/Components Manager
- **Purpose:** Manage reusable layouts/components/primitives with versions and contracts.
- **Interface:** `TemplateRegistry`, `ComponentRegistry`, `PrimitiveRegistry`.
- **Notes:** Supports slots, events, and reactive bindings.

---

## 5) UI Composition DSL (Server‑side Definitions → React)

**Fluent PHP (server)**
```php
Layout::make('Sprints')
  ->columns(1)
  ->rows([
    Row::make()
      ->components([
        Component::make('SprintHeader')
          ->layout('ObjectListHeader')
          ->slots([
            Slot::make('left', Primitive::search('q')),
            Slot::make('right', Primitive::button('New Sprint')->action('sprint.create')),
          ]),
      ]),
    Row::make()
      ->components([
        Component::table('SprintsTable')
          ->dataSource(Model::make(\App\Models\Sprint::class))
          ->onClick(Click::action('sprint.show')),
      ]),
  ])
  ->navigation(Navigation::stack()
    ->planes(['dashboard','detail','task'])
    ->esc(Behavior::back())
    ->close(Behavior::exit())
  );
```

**Generated JSON (client contract)**
```json
{
  "layout": {
    "id": "sprints",
    "columns": 1,
    "rows": [
      {"components": [{
        "type": "SprintHeader",
        "layout": "ObjectListHeader",
        "slots": {
          "left": [{"primitive": "search", "name": "q"}],
          "right": [{"primitive": "button", "label": "New Sprint", "action": "sprint.create"}]
        }
      }]},
      {"components": [{
        "type": "Table",
        "id": "SprintsTable",
        "dataSource": {"model": "App\\Models\\Sprint"},
        "click": {"type": "action", "value": "sprint.show"}
      }]}
    ],
    "navigation": {"planes": ["dashboard","detail","task"], "esc": "back", "close": "exit"}
  }
}
```

**Client (React + TS)** consumes the JSON schema and renders shadcn components. Slots map to props/children. Events wire through a global **Event Bus** with typed payloads.

---

## 6) Navigation Model (Planes/Stack)
- **Planes:** `dashboard` → `detail` → `task` (z‑index hierarchy).
- **Stack semantics:** `Esc` pops plane; `Close` exits module; `Back` returns to previous plane.
- **Declarative behaviors:** per module or per layout.
- **Overlays:** modal/drawer/slideover attach to current plane with focus/escape policy.

---

## 7) Data & Contracts
- **DTOs everywhere:** RequestDTO, ResponseDTO, ErrorDTO, EventDTO, WidgetDTO.
- **Stable IDs & hashes:** Every config artifact hashed; relationships stored with pinned references.
- **Versioning:** `/api/v1`+ semantic version on module manifests; deprecations tracked.
- **Policies/Capabilities:** Role/actor + resource + capability (view, edit, run‑action, call‑tool).

---

## 8) Actions & Automations
- **Action shape:** id, inputs schema, effects (events), policies, idempotency key, audit hash.
- **Execution:** Synchronous (UI) or async (queue).
- **Automations:** Trigger (event/rule/timer) → Conditions (RuleSet) → Steps (Actions/Flows).
- **n8n:** First‑class connectors; signed webhooks; action stubs generated from config.

---

## 9) Observability & Telemetry
- **Run logs:** per agent/task/action with correlation IDs.
- **Event bus:** internal events → subscribers (UI, dashboards, file sinks).
- **Metrics:** task throughput, error rates, latency, token usage (per model/provider).
- **Traces:** action→tool→artifact lineage; export as JSON for articles.

---

## 10) Security & Safety Rails
- **Capability whitelist:** per agent/user/module.
- **Sandbox profiles:** restricted FS, subprocess limits, timeouts.
- **Validation:** schema‑validated inputs; dry‑run; explain mode.
- **Secrets:** Laravel vault/.env layering; per‑module secret bindings.

---

## 11) Testing & CI/CD
- **Generators:** `artisan fe:make:module`, `fe:make:action`, `fe:make:widget` (tests + stubs).
- **Pest tests:** contracts, policies, E2E JSON rendering snapshots, TS type tests.
- **Contracts CI:** break on schema drift; regenerate clients.

---

## 12) Module Manifest (module.json)
```json
{
  "id": "sprints",
  "version": "0.1.0",
  "routes": [
    {"method": "GET", "path": "/sprints", "handler": "ListSprints"}
  ],
  "capabilities": ["view","edit","run-actions"],
  "dependencies": ["rules", "widgets"],
  "ui": {"entry": "Sprints.layout.json"}
}
```

---

## 13) Quickstart — Bootstrap for Humans & Agents

### 13.1 Human Quickstart
1. **Install:** `composer install && pnpm install` → copy `.env` → `php artisan migrate --seed`.
2. **Enable Modules:** `php artisan fe:module:enable sprints prompts agents`.
3. **Run Dev:** `php artisan serve` + `pnpm dev`.
4. **Create a Module:** `php artisan fe:make:module Invoicing --with=crud,actions,ui`.
5. **Open UI:** Navigate to Invoicing → see generated Table/List/Detail + actions.

### 13.2 Agent Quickstart
1. **Register Agent:** `POST /api/v1/agents { profile, tools, capabilities }`.
2. **Get Context:** `GET /api/v1/context?scope=agent:<id>`.
3. **Plan Task:** `POST /api/v1/tasks { goal, inputs }` → returns `task_id`.
4. **Emit Artifacts:** `POST /api/v1/artifacts { task_id, content, type }`.
5. **Run Action:** `POST /api/v1/actions/{id}/execute`.
6. **Stream Events:** `GET /api/v1/events?task_id=...` (SSE).

---

## 14) PRD — Fragments Engine v3 (MVP → v1)

**Problem**
- Building internal tools and agent‑assisted apps is slow, inconsistent, and brittle.

**Users**
- Primary: You (architect/engineer); Secondary: future teams/clients; Agents as first‑class users.

**Goals**
- Compose CRUD+Actions+Automations from config; scaffold deterministically; agent‑safe extension.

**Non‑Goals**
- One‑size‑fits‑all studio; visual editor v1; multi‑tenant SaaS (later).

**MVP Scope**
- Module system (manifests, enable/disable, versioning).
- UI DSL → React TS renderer with shadcn.
- Core services: Context, Agents, Prompts, Rules, Widgets, Command Router.
- Project/Sprints module fully implemented.
- Telemetry stream + dashboard.

**Success Metrics**
- Time to scaffold a new module (< 5 min to first UI/API response).
- Deterministic generator success rate (>95%).
- Agent task success on generated modules without human fix (>80%).

**Risks & Mitigations**
- **Config sprawl** → Strong schemas + generators + ADR discipline.
- **Agent misuse** → capability whitelists + sandbox + dry‑run.
- **UI drift** → contract tests + snapshot tests.

---

## 15) ADRs (selected)

**ADR‑001: Module‑based architecture**
- *Decision:* Modules with manifests over Domain‑only layering.
- *Why:* Isolation, versioning, agent‑compatible scaffolding, incremental evolution.
- *Consequences:* Slight duplication between modules; mitigated by shared core contracts.

**ADR‑002: Fluent PHP → JSON UI contracts**
- *Decision:* Author layouts in PHP, compile to JSON schema for React.
- *Why:* Leverage PHP types + IDE; runtime delivers typed contracts to client.
- *Consequences:* Requires codegen step + schema tests; enables multi‑client rendering.

**ADR‑003: Command Router as single entry for UI actions**
- *Decision:* All UI clicks route to a command, not direct controller methods.
- *Why:* Policies, idempotency, telemetry, and consistent agent execution.
- *Consequences:* Slight boilerplate; huge observability/safety win.

**ADR‑004: Hash pinning for all artifacts**
- *Decision:* Hash configs, prompts, layouts, templates; store lineage.
- *Why:* Reproducibility for agents + audits; dedupe.
- *Consequences:* Need migration path when schemas change (map file).

---

## 16) Design Doc (Sprints Module as Reference)

**Context**: Two models (Sprint, Task), three planes (dashboard/detail/task), table→detail→task with Esc/Back/Close semantics.

**Requirements**
- List/Filter/Search sprints; click→detail with overview widgets; click→task detail; modals for edits.

**API**
- `GET /sprints`, `GET /sprints/{id}`, `GET /tasks/{id}`, actions: `sprint.create`, `task.update`.

**UI**
- Header (title, filters, actions), Body (table/grid), Footer (pagination/stats).
- Overlays: modal for quick add; drawer for task edit.

**Telemetry**
- Events: `sprint.viewed`, `task.opened`, `action.executed` (correlation id).

**Risks**
- Focus traps in overlays → shadcn primitives + escape policy tested.

---

## 17) Missing Considerations & Suggestions
- **Schema migration strategy for UI contracts** (layout JSON versioning + compatibility layer).
- **State hydration across planes** (cache key by plane + route params; SSR optional).
- **Offline snapshots** (SQLite pack with CAS artifacts for travel/dev demos).
- **Permission modeling for Agents vs Humans** (separate capability matrices).
- **Resource packs** (zip modules + manifests + seeds for fast import/export).
- **Evaluation harness** (prompt A/B, action latency reports, regression dashboards).
- **Semantic search** hooks (optional pgvector on Fragments/Artifacts).

---

## 18) Roadmap & Milestones

**Milestone 1 — v3 Core (2–3 weeks)**
- Core contracts + module loader + manifests.
- Fluent UI DSL → JSON → React renderer (Table/List/Detail).
- Command Router + Policies + Telemetry events.
- Sprints module end‑to‑end.

**Milestone 2 — Agent & Prompt Systems (2–3 weeks)**
- Agent registry/runner + Postmaster + SSE events stream.
- Prompt store + renderer + labels + pinning.

**Milestone 3 — Rules & Automations (2–3 weeks)**
- Rule engine + explain traces; n8n connectors + signed hooks.
- First automation: triage Git issues → Tasks.

**Milestone 4 — Docs & Generators (ongoing)**
- Best‑in‑class docs (Quickstarts, How‑tos, ADR index).
- Scaffolding commands for modules/actions/widgets.

---

## 19) Docs Structure (for “best in class”)
- **/docs/intro.md** — What/Why, core concepts, mental model.
- **/docs/quickstart.md** — 10‑min to first module.
- **/docs/modules/** — Manifest, lifecycle, versioning.
- **/docs/ui‑dsl/** — Fluent API, JSON schema, client binding.
- **/docs/agents/** — Capabilities, sandbox, telemetry.
- **/docs/prompts/** — Authoring, variables, evaluation.
- **/docs/rules/** — Authoring rules, explain traces.
- **/docs/flows/** — n8n integration, security.
- **/docs/observability/** — Events, logs, metrics.
- **/docs/scaffolding/** — Deterministic generation.
- **/docs/adr/** — Index + templates.

---

## 20) Templates & Stubs (snippets)

**ADR Template (MD)**
```
# ADR-XXX: <Title>
Date: YYYY-MM-DD
Status: Proposed | Accepted | Superseded by ADR-YYY
Context:
Decision:
Consequences:
Alternatives:
```

**Action Stub (PHP)**
```php
final class CreateSprint implements ActionContract
{
  public function __construct(private SprintRepository $repo) {}
  public function inputs(): Schema { /* typed schema */ }
  public function authorize(Actor $actor): bool { /* capability */ }
  public function __invoke(CreateSprintDTO $dto): SprintDTO {
    $sprint = $this->repo->create($dto);
    event(new ActionExecuted('sprint.create', $sprint->id));
    return SprintDTO::from($sprint);
  }
}
```

**Widget Definition (PHP)**
```php
Widget::table('SprintsTable')
  ->columns([
    Col::text('name'), Col::date('starts_at'), Col::date('ends_at'),
    Col::badge('status'),
  ])
  ->rowClick(Action::make('sprint.show'));
```

**Scaffold Command**
```
php artisan fe:make:module Invoicing --with=crud,actions,ui
php artisan fe:make:action invoicing.invoice.create
php artisan fe:make:widget invoicing.invoice.table
```

---

## 21) Evolution Journal (for future articles)
- **Refactor payoff:** 32 partial → 4 solid systems; determinism boosts agent success.
- **Module turn:** Choosing modules over pure domain layers for versioned isolation.
- **UI DSL unification:** Fluent PHP → JSON contracts; one source for server+client.
- **Agent‑as‑user:** Capability matrices and postmaster patterns.
- **Telemetry first:** Design for live dashboards from day one.

**Milestone log (keep appending):**
- 2025‑10‑12: v3 spec drafted; module manifests sketched; UI DSL contract stabilized (alpha).
- …

---

## 22) Open Decisions (Track via ADRs)
- **Client state model:** local store vs server state hydration strategy.
- **Schema registry location:** central vs per‑module (leaning per‑module + index).
- **Multi‑theme support:** future; shadcn is default.
- **Vector search:** opt‑in module for semantic lookup.

---

## 23) Next Steps (Actionable)
1. Implement `/core/Contracts` + skeleton services.
2. Build `module.json` loader + registrar.
3. Ship `Sprints` as the reference module end‑to‑end.
4. Implement the Fluent UI DSL → JSON compiler with snapshot tests.
5. Wire React renderer for Table/List/Detail + overlays with plane stack.
6. Stand up Command Router + policies + telemetry events.
7. Add scaffolders (`fe:make:*`) with tests and docs.
8. Publish Quickstart + ADR templates.

