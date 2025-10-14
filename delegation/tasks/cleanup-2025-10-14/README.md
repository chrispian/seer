# Project Cleanup Task - 2025-10-14

## Objective
Clean up the Seer codebase by removing unused code, organizing systems, and improving maintainability.

## Backup Strategy
- All removed files backed up to `backup/` directory with original structure
- Database already backed up
- Git history preserves everything

## Phase 1: Remove Unused Models & Migrations

### Unused Models (0 references)
1. ✅ AgentVector - Vector storage system (never implemented)
2. ✅ ArticleFragment - Empty stub
3. ✅ CalendarEvent - Empty stub
4. ✅ FileText - Empty stub
5. ✅ FragmentTag - Pivot table (never used)
6. ✅ ObjectType - Empty stub
7. ✅ PromptEntry - Prompt registry (never implemented)
8. ✅ Thumbnail - Empty stub
9. ✅ WorkItemEvent - Event tracking (never used)

### Actions
- [x] Move models to `backup/models/`
- [x] Find and move corresponding migrations to `backup/migrations/`
- [ ] Document what each was intended for
- [ ] Test application after removal

## Phase 2: Review Rarely Used Models

Models with 1-5 references - need review to determine if:
- They're work-in-progress features
- They should be completed and integrated
- They should be removed

See: `rarely-used-models.md`

## Phase 3: System Inventory

Create comprehensive documentation of all systems:
- Purpose
- Files/directories
- Models/tables
- Services
- Commands
- Tests
- Dependencies

See: `systems-inventory.md`

## Phase 4: Cleanup Opportunities

After analysis, identify:
- Dead code in services
- Unused routes
- Orphaned views
- Unused configuration
- Duplicate functionality
- Consolidation opportunities

See: `cleanup-opportunities.md`

## Phase 5: Organization Recommendations

Group related code into:
- Core features (always loaded)
- Optional modules (can be disabled)
- Development tools
- Administrative features

See: `organization-plan.md`
