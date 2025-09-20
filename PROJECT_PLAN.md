# Project Execution Plan

This document breaks the high-level initiatives into concrete, actionable todos. Tasks are grouped by the planning codes referenced in `CONTEXT.md`. Use the checkboxes to track progress and copy items into GitHub issues when team visibility or automation is helpful.

## Scope Update

- Splitting `seer` into separate engine/UI repositories and large-scale Flux Pro redesign work are paused for this codebase. Track those efforts in dedicated future projects (`fragments-engine-split`, `flux-ui-refactor`).
- This plan focuses on stabilising the current Laravel/Filament interface, hardening the fragment pipeline, and layering AI capabilities on top.

## Delegation Workflow

For every deliverable marked “Delegation packet”, create a folder under `delegation/<planning-code>-<slug>/` containing:
- `PLAN.md` – concrete tasks, acceptance criteria, sequencing.
- `CONTEXT.md` – architecture notes, domain nuances, links to prior art.
- `AGENT.md` – tailored operating profile for the agent (tone, tools, heuristics, risk factors).

Create or refresh these packets before handing work to another agent.

## Foundation (FND)

### FND-01 Rename and Rebrand
- [ ] Delegation packet: `delegation/FND-01-rebrand/` (PLAN.md, CONTEXT.md, AGENT.md) covering remaining branding strings + deployment script updates.
- [x] Confirm target organization/repo names with stakeholders (`fragments-engine` package + `project-mentat` NativePHP shell under hollis-labs).
- [x] Scaffold fresh Laravel 12 apps for both repos on PHP 8.3.
- [x] Set baseline SQLite configuration for local + single-user installs.
- [x] Update `composer.json`, `package.json`, and Filament configs to new vendor/name (Filament to be addressed when ported).
- [x] Rename PHP namespaces (`App\` → `HollisLabs\FragmentsEngine` / `HollisLabs\ProjectMentat`) and adjust autoloading.
- [ ] Replace hard-coded branding strings in Filament UI, Livewire components, and configs.
- [ ] Update deployment scripts, `.env.example`, and CI references to new repo slug.
- [x] Communicate rename plan (deprecation notice, README, changelog entry).
- [x] Document prerelease version shift to `0.2.0` and outline semantic versioning expectations pre-1.0.
- [x] Create `docs/` index with architecture overview and contribution guidelines.
- [x] Draft ADR template and store under `docs/adr/` (ADR-001 demo shell, ADR-002 AI fallback, ADR-003 UI stack).
- [ ] Write ADRs for pending decisions (mode strategy, AI provider fallback, UI framework).
- [ ] Update README to reference docs, package usage, and rename roadmap.

- [ ] Add Testbench coverage for package publishes, broadcasts, Horizon. (tracked via TODO in FragmentsEngineServiceProviderTest).

## Engineering Pipeline (ENG)

### ENG-01 Pipeline Audit
- [ ] Delegation packet: `delegation/ENG-01-pipeline-audit/` (process map templates, logging guidelines, open questions).
- [ ] Map each fragment pipeline action with success/error paths (sequence diagram or table).
- [ ] Add structured logging for each step with correlation IDs.
- [ ] Identify failure handling gaps and propose retries/fallbacks per action.

### ENG-02 Data-Driven Vault Routing
- [ ] Delegation packet: `delegation/ENG-02-routing/` (existing command specs, Livewire panel notes, outstanding backend wiring).
- [x] Instrument `app/Actions/RouteToVault.php` to log decision inputs/outputs.
- [x] Design data model for routing rules (per project/vault thresholds).
- [ ] Implement configuration persistence (DB table or JSONB) and management UI/command.
- [ ] Replace hard-coded `'debug'` vault with dynamic lookup + tests.
- [ ] Consume routing services from `hollis-labs/fragments-engine` package; ensure demo shell + Project Mentat share the same engine facade.

### ENG-03 Model Selection Step
- [ ] Delegation packet: `delegation/ENG-03-model-selection/` (provider inventory, weighting heuristics, UI expectations).
- [ ] Catalog available AI models/providers and required configuration keys.
- [ ] Add model selection metadata to fragments and session context.
- [ ] Implement selection strategy service (weighted rules, per-command overrides).
- [ ] Surface model choice in UI/toasts for transparency.

### ENG-04 Embeddings Toggle
- [ ] Delegation packet: `delegation/ENG-04-embeddings-toggle/` (config matrix, fallback behaviour, CLI outline).
- [ ] Add config flag/env guard to disable embeddings gracefully.
- [ ] Implement conditional logic in ingestion/search so pipeline degrades without embeddings.
- [ ] Provide admin command to backfill embeddings when re-enabled.

### ENG-05 Streaming Contract
- [ ] Delegation packet: `delegation/ENG-05-streaming-contract/` (event contract draft, transport trade-offs, UI integration plan).
- [ ] Define streaming response contract between backend and UI (payload shape, events).
- [ ] Prototype streaming transport (SSE or websockets) within Laravel stack.
- [ ] Update Chat UI component to handle streaming updates and completion states.
- [ ] Add feature flag to roll out streaming gradually.

## AI Integration (AI)

### AI-01 Provider Abstraction (BYOK/OAuth)
- [ ] Delegation packet: `delegation/AI-01-provider-abstraction/` (auth flow matrix, interface contract, security checklist).
- [ ] Inventory authentication flows needed (API key, OAuth device flow, local model).
- [ ] Create provider interface + adapters for OpenAI, Azure, Anthropic, local Ollama.
- [ ] Implement credential storage/service (encrypted config, vault, or NativePHP keychain).
- [ ] Expose admin UI/commands to manage provider credentials.

### AI-02 Local Ollama Fallback
- [ ] Delegation packet: `delegation/AI-02-ollama-fallback/` (health-check strategy, retry policy, ops notes).
- [ ] Detect availability of local Ollama at runtime with health check.
- [ ] Add retry/fallback logic when remote provider fails.
- [ ] Document local install steps and troubleshooting.

## Command System (CMD)

### CMD-01 Slash Command Expansion
- [ ] Delegation packet: `delegation/CMD-01-command-expansion/` (command matrix, UX copy, validation rules).
- [ ] Capture requirements/UX for `/vault`, `/project`, `/session`, `/context`, `/inbox`, `/compose`.
- [ ] Add command definitions to `app/Services/CommandRegistry.php` with validation + help text.
- [ ] Implement handlers and associated UI panels/toasts.
- [ ] Write command execution tests + docs.

### CMD-02 Keyboard Shortcuts
- [ ] Delegation packet: `delegation/CMD-02-shortcuts/` (current map audit, accessibility guidance, testing plan).
- [ ] Audit existing shortcuts and conflicts.
- [ ] Design shortcut map covering recall, capture, navigation.
- [ ] Implement JS layer (likely Filament/Livewire hooks) with discoverability overlay.
- [ ] Document shortcuts in UI and README.

### CMD-03 Natural Language Routing Stub
- [ ] Delegation packet: `delegation/CMD-03-nl-routing/` (intent taxonomy draft, evaluation metrics, logging requirements).
- [ ] Define intent taxonomy and sample utterances.
- [ ] Implement stub service that classifies NL input to slash commands (rule-based to start).
- [ ] Wire stub into chat input when no explicit slash prefix present.
- [ ] Log predictions for future training.

## Search & Recall (SRCH)

### SRCH-01 Search Tuning & Tests
- [ ] Delegation packet: `delegation/SRCH-01-search-tuning/` (fixture plan, ranking levers, regression metrics).
- [ ] Write integration tests covering SQL + hybrid search ranking.
- [ ] Add fixtures covering edge cases (partial recall, tags, projects).
- [ ] Tune ranking weights and document rationale.

### SRCH-02 Recall Palette Enhancements
- [ ] Delegation packet: `delegation/SRCH-02-recall-palette/` (UX findings, component inventory, telemetry schema).
- [ ] Gather UX feedback on palette interactions.
- [ ] Implement multi-select, quick filter, and recent search history.
- [ ] Add telemetry for palette usage to inform future tuning.

## Deferred Initiatives (Track in dedicated projects)

### FND-02 Split Engine from Project:Mentat Shell *(move to `fragments-engine-split`)*
- Work completed to date: service provider wrap, namespace cleanup, package consumption.
- Outstanding items (demo shell, `/engine` mount, seeding, docs) to be planned and executed in the new repository.

### UI Modernization Streams *(move to `flux-ui-refactor`)*
- Componentization plan, inline editing, debug toggles, infinite scroll, input focus recovery, and large-scale Flux adoption are all deferred.
- Re-spin requirements and design assets in the new UI/UX project before implementation.

### NativePHP Desktop (NATIVE) *(move to `mentat-desktop`)*
- Desktop shell scaffolding, credential sync, and streaming UI workstreams will relaunch alongside the UI refactor effort.

### Extensibility (EXT)
- Template/stub catalog and mode/plugin registry will be reconsidered once engine split stabilises; track in the new extensibility-focused plan.

## User Experience Polish (UX)

### UX-01 Toast Cleanup
- [ ] Delegation packet: `delegation/UX-01-toast-cleanup/` (toast inventory, severity guidelines, telemetry hooks).
- [ ] Audit existing toasts for redundancy/noise.
- [ ] Consolidate repeated success messages.
- [ ] Introduce iconography/severity levels.
- [ ] Add configuration for toast verbosity per user.

## Developer Experience (DEV)

### DEV-01 Testing Infrastructure
- [ ] Delegation packet: `delegation/DEV-01-testing-infra/` (target suites, tooling decisions, runbooks).
- [ ] Introduce Pest or improve PHPUnit config for speed and clarity.
- [ ] Add factories/seeders covering pipeline scenarios.
- [ ] Set up CI to run targeted test suites (unit, feature, integration).

### DEV-02 Seed Data
- [ ] Delegation packet: `delegation/DEV-02-seed-data/` (domain fixtures, seeder scripts, onboarding notes).
- [ ] Expand database seeders with realistic projects, vaults, and fragments.
- [ ] Document seed usage for onboarding.

### DEV-03 Tooling
- [ ] Delegation packet: `delegation/DEV-03-tooling/` (task runner expectations, static analysis policy, CI touchpoints).
- [ ] Add task runner scripts (`composer`, `npm`, or `just` file) for common workflows.
- [ ] Configure static analysis (Larastan/Psalm) and linting.

## Operations (OPS)

### OPS-01 Deployment Scripts
- [ ] Delegation packet: `delegation/OPS-01-deploy/` (environment matrix, automation stakes, smoke checklist template).
- [ ] Document current deployment targets/environments.
- [ ] Create automation scripts (GitHub Actions, Forge, etc.).
- [ ] Add smoke test checklist post-deploy.

### OPS-02 Logging & Telemetry
- [ ] Delegation packet: `delegation/OPS-02-telemetry/` (logging schema, tool options, dashboard wish-list).
- [ ] Standardize logging format and destinations.
- [ ] Integrate observability stack (e.g., Laravel Telescope, Sentry).
- [ ] Define metrics dashboard for pipeline health.

### OPS-03 Backup & Export Stubs
- [ ] Delegation packet: `delegation/OPS-03-backup-export/` (retention policy, export contract, restore drill).
- [ ] Design backup cadence and storage location.
- [ ] Implement export command for fragments/projects.
- [ ] Document restore procedure and test it.

## Global Next Steps
- [ ] Sequence upcoming work inside this repo: complete ENG-02 routing integration/tests, then ENG-03 model selection groundwork, followed by ENG-04 embeddings toggle.
- [ ] Spin up delegation packets for the next three workstreams queued for hand-off (ENG-02, ENG-03, UX-01) and file them under `delegation/`.
- [ ] Draft/refresh ADRs for mode strategy, AI fallback, UI stack to reflect the paused split/UI projects.
- [ ] Create GitHub issues or project board cards referencing active todos; assign owners and target milestones.
- [ ] Revisit plan monthly; log decisions in ADRs/CHANGELOG.
