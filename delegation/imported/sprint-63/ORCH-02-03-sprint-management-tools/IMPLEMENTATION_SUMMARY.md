# ORCH-02-03 – Sprint Management Tools

## What changed
- Added `SprintOrchestrationService` to centralise sprint resolution, metadata updates, status changes, and task attachments (with WorkItem metadata + sprint_items alignment).
- Extended Tool Crate MCP surface with sprint management tools:
  - `orchestration.sprints.detail` for deep sprint snapshots (stats, tasks, assignments).
  - `orchestration.sprints.save` to create/update sprint metadata, dates, and notes.
  - `orchestration.sprints.status` for quick status/notes updates.
  - `orchestration.sprints.attach_tasks` to associate work items with sprint backlogs.
- Updated `SprintsListTool` to reuse the new service for consistent summaries.
- Configured Tool Crate defaults (`tool-crate.php`) to expose the sprint service binding and new tool toggles; refreshed docs/CLI context so agents know about the new commands.
- Expanded feature coverage in `tests/Feature/ToolCrateOrchestrationToolsTest.php` to exercise sprint detail/save/status/attach flows end-to-end.

## Follow-ups / Next steps
- Mirror the sprint MCP tools with Artisan commands during ORCH-02-05.
- Consider additional tools for removing tasks from sprints or bulk priority updates after UI requirements solidify.
- Revisit sprint ordering/position handling when Kanban UI work begins to ensure parity with dashboard expectations.
