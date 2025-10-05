# Delegation Migration TODO

## Discovery
- [x] Catalogue sprint sections and table column permutations in `SPRINT_STATUS.md`
- [x] Inventory sprint directories and ensure expected task folders exist
- [x] Identify agent template → AgentType mappings and gaps

## Implementation
- [x] Implement markdown parsing helpers with unit tests
- [x] Build `DelegationMigrationService` with dry-run + import workflows
- [x] Create `delegation:import` Artisan command (filters, verbose summaries)
- [x] Seed agent templates into `agent_profiles` via service layer
- [x] Map statuses and estimates into orchestration-ready fields
- [x] Attach work items to sprints via `sprint_items`

## Testing & Validation
- [x] Pest coverage for parser + importer summary logic
- [x] Dry-run command against repository delegation data for verification
- [x] Execute live import in test database, verify counts and sample records

## Documentation & Tracking
- [x] Update implementation summary with results and limitations
- [x] Mark ORCH-01-03 status in sprint tracker
- [x] Capture follow-up items for CLI/MCP integration handoff
