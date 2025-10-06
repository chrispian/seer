# ORCH-02-04 – Agent Management Tools

## What changed
- Introduced `AgentOrchestrationService` to build on `AgentProfileService`, adding helpers for resolving agents, upserting profiles, toggling status, and generating assignment-aware detail payloads.
- Added MCP tools in Tool Crate for agent operations:
  - `orchestration.agents.detail` for profile + stats + recent assignments.
  - `orchestration.agents.save` to create/update capabilities, tools, and status.
  - `orchestration.agents.status` to flip between active/inactive/archived.
- Updated Tool Crate server/config defaults, documentation, and CLI context so agents discover the new endpoints alongside the sprint/task tools.
- Expanded the orchestration feature test suite to cover the new agent flows end-to-end.

## Follow-ups / Next steps
- Wire up Artisan command mirrors during ORCH-02-05 so CLI parity matches MCP capabilities.
- Consider adding agent workload metrics (e.g., open assignment counts by status) once dashboard requirements are defined.
- Evaluate multi-agent bulk operations after initial UI integration feedback.
