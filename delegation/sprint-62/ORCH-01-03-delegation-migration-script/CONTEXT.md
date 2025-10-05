# Context

## Source Data Layout
- Global tracker: `delegation/SPRINT_STATUS.md` (priority, estimates, per-task status, optional agent column)
- Sprint packets: `delegation/sprint-*/` with `SPRINT_SUMMARY.md` and task folders (`TASK-ID-slug/` containing `AGENT.md`, `PLAN.md`, `TODO.md`, `CONTEXT.md`, `IMPLEMENTATION_SUMMARY.md`)
- Agent templates: `delegation/agents/templates/*.md`
- Active agents (optional historical artifacts): `delegation/agents/active/*.md`

## Target Schema
- `agent_profiles` (UUID, slug, type, mode, description, capabilities, constraints, metadata, status)
- `work_items` (UUID, type, status, priority, metadata JSON, delegation_* fields, estimated/actual hours)
- `sprints` and `sprint_items` (bridge linking work items to sprint ordering)
- `task_assignments` (future-proof for actual assignments; importer may populate recommended agent metadata without creating assignments yet)

## Mapping Strategy
- Sprint code derived from folder name (e.g. `sprint-62` → `SPRINT-62`), title from heading in `SPRINT_STATUS.md`, metadata stores priority, estimate text, and section impact copy.
- Work item metadata keeps task description, estimate text, sprint reference, source paths, and document presence flags. `delegation_status` mapped from markdown status (`done` → `completed`, `in-progress` → `in_progress`, default `unassigned`).
- `estimated_hours` averaged from numeric ranges when available (e.g. `14-20h` → `17`); non-numeric estimates fall back to null.
- Agent templates promoted to `agent_profiles` once using `AgentProfileService`; tasks record `agent_recommendation` (from table column or template inference) within `delegation_context`.
- Importer remains additive/idempotent: looks up records by sprint code + task slug with JSON path filters to avoid duplicates.

## Tooling Expectations
- Artisan command: `php artisan delegation:import {--sprint=} {--dry-run}` with verbose output summarising counts and warnings.
- Service handles dry-run by skipping writes and emitting structured summary array for CLI/tests.
- Logging via `info`/`warn` with aggregated warnings surfaced at command completion.

## Edge Cases
- Some sprints use tables without an Agent column; importer should tolerate missing fields.
- Task directories may be absent (e.g., backlog entries) – command should log and continue.
- Markdown formatting varies (backticks around status, extra whitespace); parser must normalise values.
- Future tasks may introduce new status tokens (`blocked`, `ready-for-review`); maintain extensible status mapping.
