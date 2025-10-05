# ORCH-01-04 Plan – Basic CLI Commands

## Goal
Expose orchestration data (agents, sprints, work items) via read-only Artisan commands following the help-first toolbox conventions. Outputs must be concise for MCP consumption and support JSON mode for automation.

## Milestones
1. **Command Design** – define command names, options, and reusable presenters for table/json output.
2. **Implementation** – build three Artisan commands (`orchestration:agents`, `orchestration:sprints`, `orchestration:tasks`) backed by query services.
3. **Integration** – register commands, ensure MCP server help docs reference them, and align naming with CLI-MCP context.
4. **Testing** – add Pest tests covering filtering/JSON output and verify artisan commands complete successfully.
5. **Documentation** – update delegation tracker and provide usage examples for agents.

## Sequencing
- Reuse `AgentProfileService` for agent filters first.
- Create dedicated query support class for WorkItems/Sprints to encapsulate filtering and summarisation.
- Implement commands sequentially (agents → sprints → tasks) since later commands depend on shared formatting helpers.
- Finish with tests and README snippet for quick reference.

## Open Questions
- How will commands eventually surface inside MCP help? (Follow-up: feed into tool crate docs.)
- Do we need pagination? For now, provide `--limit` and `--status`/`--type` filters to keep output small.
- Should tasks default to active sprint only? Start with optional `--sprint` filter and default to all.
