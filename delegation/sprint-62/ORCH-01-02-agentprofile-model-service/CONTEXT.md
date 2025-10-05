# Context

## Upstream Inputs
- `database/migrations/2025_10_05_180528_create_agent_profiles_table.php` defines the schema with JSON metadata, mode/type/status columns, and UUID PKs.
- Agent template specs live under `delegation/agents/templates/` and inform enum values + labels in `App\Enums\AgentType`.
- Work items now carry `assignee_type`/`assignee_id`; TaskAssignment relations need the AgentProfile model to resolve active agents.

## Downstream Consumers
- ORCH-01-03 migration script will instantiate AgentProfile records from delegation files; it will rely on `AgentProfileService::create()` for validation + slug management.
- ORCH-01-04 CLI commands need list/find helpers plus catalog metadata for interactive prompts.
- Future UI work (Sprint 66) reads the same service to populate dropdowns and Kanban assignments.

## Design Notes
- Enums expose helper metadata (label, description, default mode) to centralize copy and prevent divergence between CLI/UI flows.
- Model boot hooks ensure slugs remain unique and default modes follow type conventions without requiring callers to pass everything explicitly.
- Service layer normalizes flexible inputs (strings, enums, arrays) so both migration scripts and interactive commands can share logic.
- JSON columns cast to arrays by default; service trims strings and removes empty entries before validation.
