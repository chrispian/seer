# ORCH-01-03 Plan – Delegation Migration Script

## Goal
Import the markdown-based delegation system into the new orchestration database so Sprint/Task dashboards, CLI commands, and MCP tooling can operate on structured records instead of files.

## Milestones
1. **Discovery & Parsing Strategy** – catalogue sprint/task formats, map markdown fields to database columns, and decide normalization rules (status mapping, estimates, agent hints).
2. **Agent Seeding** – promote delegation agent templates into persistent `agent_profiles`, using `AgentProfileService` for validation and idempotency.
3. **Sprint & Work Item Importer** – parse `SPRINT_STATUS.md` + sprint directories, create/update `sprints`, `work_items`, `sprint_items`, and attach delegation metadata.
4. **Command Surface** – ship an Artisan command (`delegation:import`) supporting dry-run, sprint filters, verbosity, and summary output.
5. **Testing & Documentation** – cover markdown parsing helpers and importer happy-path with Pest tests; capture summary + remaining follow-ups in task packet.

## Sequencing
- Build parsing utilities first so tests can focus on deterministic sample fixtures.
- Seed agent profiles before creating work items to enable assignment recommendations.
- Wrap database writes in transactions and guard with dry-run toggle.
- Finish with command wiring and documentation updates.

## Open Questions / Follow-ups
- Future enhancement: capture TODO checkbox completion percentages for richer progress metrics.
- Evaluate storing rendered markdown/HTML for quick previews in UI (defer to Sprint 66 dashboard).
- Determine if live git history snapshots should also populate `delegation_history` (potential Sprint 63 refinement).
