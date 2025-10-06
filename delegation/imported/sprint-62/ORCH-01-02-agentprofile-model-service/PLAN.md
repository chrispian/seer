# ORCH-01-02 Implementation Plan

## Goal
Expose a first-class AgentProfile model backed by enums and a service layer so upcoming CLI/MCP work can query, create, and manage agents without touching delegation files directly.

## Milestones
1. **Enum Catalog** – encode agent templates, working modes, and lifecycle statuses with helper metadata for UI/CLI usage.
2. **Model Layer** – create the AgentProfile Eloquent model, relationships, casts, scopes, and lifecycle hooks for slug/mode defaults.
3. **Service Layer** – ship AgentProfileService with filtering APIs, validation logic, lifecycle helpers (activate/archive/delete), and catalog lookups.
4. **Testing & Factories** – add database factory plus Pest unit coverage for creation, updates, filtering, and catalog metadata.
5. **Documentation** – capture implementation summary, update TODO tracker, and note next dependencies (migration importer, CLI consumers).

## Sequencing
- Bootstrap enums before model/service so casts and defaults compile.
- Implement model + factory together to unblock tests.
- Finish service & tests before wiring into CLI tasks.
- Document outcomes immediately so migration script can rely on service API.

## Open Questions / Follow-ups
- Should AgentProfile track avatar URLs or other presentation metadata? (Defer to Sprint 66 UI work.)
- Do we need auditing/history tables? (Likely Sprint 63+ once assignments exist.)
- Any need for per-agent tool permission validation? (Revisit with CLI command implementation.)
