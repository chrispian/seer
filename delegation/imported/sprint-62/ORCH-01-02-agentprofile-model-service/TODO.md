# AgentProfile Model & Service TODO

## Implementation Checklist
- [x] Add AgentType enum with labels, descriptions, and default mode helpers
- [x] Add AgentMode enum with labels and descriptions
- [x] Add AgentStatus enum with labels
- [x] Create AgentProfile model with UUIDs, JSON casts, scopes, and relationships
- [x] Implement boot hooks for slug generation and mode inference
- [x] Create TaskAssignment relationship hooks for future orchestration queries
- [x] Build AgentProfileService with list/find/create/update/archive/activate/delete helpers
- [x] Normalize inputs (slug, enums, list fields) and enforce validation
- [x] Create AgentProfileFactory for tests
- [x] Write Pest unit tests covering creation, updates, filtering, catalog metadata, slug collisions

## Testing & QA
- [x] Run `./vendor/bin/pest tests/Unit/AgentProfileServiceTest.php`
- [ ] Run broader orchestration test suite once ORCH-01-03/04 are in place (follow-up)

## Documentation
- [x] Capture agent brief, plan, and context in delegation tracker
- [x] Draft implementation summary (pending final write-up below)

## Follow-ups / Blockers
- [ ] Wire AgentProfileService into migration importer (ORCH-01-03)
- [ ] Expose service via CLI/MCP commands (ORCH-01-04)
