# Repo Context & Findings

- Chat orchestration lives in `app/Filament/Resources/FragmentResource/Pages/ChatInterface.php`; Flux components were introduced for routing/command panels but most business logic remains in Livewire/Filament.
- Fragment ingest pipeline flows through chaos parsing, enrichment, routing, and embeddings. Logging exists though retries/fallbacks are uneven; `RouteToVault` still defaults to the `'debug'` vault pending ENG-02 completion.
- Slash-command infrastructure (see `app/Services/CommandRegistry.php`) now includes a `/routing` command that surfaces the Flux-based management UI.
- Hybrid search combines PostgreSQL full-text and pgvector; embeddings can be disabled via config but graceful degradation is incomplete.
- Test coverage is improving (new feature test for routing rules) yet pipeline/command regressions remain lightly covered.

# Project Plan Snapshot

Active tracks mirror `PROJECT_PLAN.md`:
- **FND-01** – finish branding clean-up, docs, README, and add Testbench coverage.
- **ENG-01…ENG-05** – pipeline audit, routing rules, model selection, embeddings toggle, streaming contract.
- **AI-01/02** – provider abstraction and local Ollama fallback.
- **CMD-01…03** – expand slash commands, shortcuts, and NL routing stub.
- **SRCH-01/02** – expand search tests and recall palette UX.
- **UX-01, DEV-01…03, OPS-01…03** – toast hygiene, testing infra, seeding, tooling, deployment, telemetry, and backups.

Deferred for sibling projects:
- Engine/application split (FND-02) → `fragments-engine-split`.
- Major UI/UX modernization & NativePHP desktop → `flux-ui-refactor` and `mentat-desktop`.
- Extensibility registry/templates → follow-on extensibility roadmap.

# Delegation Workflow

When handing off a workstream, scaffold `delegation/<planning-code>-<slug>/` with:
1. `PLAN.md` detailing scope, acceptance criteria, sequencing.
2. `CONTEXT.md` pointing to relevant code, ADRs, data, and known risks.
3. `AGENT.md` describing the ideal operator (tooling, tone, heuristics, safe boundaries).

Keep these packets current so agents can pick up tasks asynchronously.

# Execution Status

- ENG-02 UI/service scaffolding is present but routing decisions still bypass the new rules; wiring/tests remain.
- Project plan updated to reflect narrowed scope and delegation workflow.
- Outstanding ADRs: mode strategy, AI fallback policy, UI stack (needs addendum to note new project split).

# Recommended Next Steps

1. Close the loop on ENG-02 (RouteToVault integration + tests) before delegating to an implementation agent.
2. Prepare delegation packets for ENG-02, ENG-03, and UX-01.
3. Refresh ADRs/README so the paused split/UI refactor context is explicit.
4. Stand up GitHub issues or a Linear project aligned to the updated plan.
