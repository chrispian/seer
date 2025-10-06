# ORCH-02-02 – Task Management Tools

## What changed
- Added orchestration MCP tools for task level execution:
  - `orchestration.tasks.detail` returns a rich payload with delegation history, assignments, and metadata.
  - `orchestration.tasks.assign` creates `task_assignments` rows, updates work item delegation state, and records history notes.
  - `orchestration.tasks.status` updates delegation status and keeps the current assignment state in sync.
- Introduced `App\Services\TaskOrchestrationService` for resolving tasks/agents, managing assignments, and generating detail payloads.
- Extended Tool Crate config/server wiring plus docs to expose the new tools, and let integrators override the orchestration service or model bindings.
- Added feature coverage in `tests/Feature/ToolCrateOrchestrationToolsTest.php` validating assign/status/detail behaviour against imported delegation data.

## Follow-ups / Next steps
- Extend tooling for sprint/agent CRUD in ORCH-02-03/04 once task flows are stable.
- Mirror the new MCP tools with Artisan commands during ORCH-02-05.
- Consider soft-guards around repeated assignments (e.g. confirm cancellation vs. overwrite) during future UI integration.
