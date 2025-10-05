# Context

## Inputs
- Database tables populated by ORCH-01-03: `sprints`, `sprint_items`, `work_items`, `agent_profiles`.
- Services: `AgentProfileService` (filters + metadata), `DelegationMigrationService` (for any derived helpers if needed).
- CLI policy: reference `delegation/CLI-MCP-CONTEXT.md` — commands must return concise output and play nicely with MCP help tooling.

## Requirements Recap
- Provide read-only Artisan commands that summarise orchestration data and can later back MCP tools (`help.index` → CLI wrappers).
- Commands should support both table output (human-friendly) and JSON (for agents/automation).
- Filters: status/type/mode for agents, sprint selection for tasks, delegation status for work items.
- Include total counts and short hints so agents know next steps.

## Design Notes
- Reuse Laravel `Table` helper for pretty output; gate behind `--json` to switch to raw `json_encode`.
- Add `--limit` argument to prevent overwhelming output; default to ~20 results.
- Sprints command should show total tasks, completed counts, outstanding counts, and optionally expanded with `--details` (count only by default).
- Tasks command to show `task_code`, `delegation_status`, `agent_recommendation`, `estimate_text`, and `sprint_code`.
- Agents command to highlight `type`, `mode`, `status`, and capability summary (maybe top 3 capabilities).

## Dependencies & Registration
- Register commands in `routes/console.php`? (Not needed, Laravel auto-discovers console commands via namespace; ensure they sit under `App\Console\Commands`.)
- Ensure CLI docs mention new commands under quick actions.
