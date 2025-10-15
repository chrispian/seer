# Task: Implement FE UI Registry + Feature Flags System

**Task Code**: T-UIB-SPRINT2-02-REGISTRY  
**Sprint**: UI Builder v2 Sprint 2  
**Priority**: HIGH  
**Assigned To**: BE-Kernel Agent  
**Status**: TODO  
**Created**: 2025-10-15

## Objective

Integrate the `fe_ui_registry` and `fe_ui_feature_flags` systems from the context pack into the main Seer application. This adds a unified registry for UI artifacts (pages, components, modules, themes) and feature flag capabilities.

## Context Pack Location

`/Users/chrispian/Projects/seer/delegation/tasks/ui-builder/fe_ui_registry_flags_pack_20251015_152026`

## Files to Migrate

### Database
- `db/migrations/2025_10_15_000001_create_fe_ui_registry_table.php`
- `db/migrations/2025_10_15_000002_create_fe_ui_feature_flags_table.php`
- `db/migrations/2025_10_15_000003_alter_fe_ui_components_add_kind.php`

**Target**: `database/migrations/`

### Models
- `app/Models/FeUiRegistry.php`
- `app/Models/FeUiFeatureFlag.php`

**Target**: `app/Models/`

### Services
- `app/Services/FeatureFlagService.php`

**Target**: `app/Services/V2/` (adjust namespace)

### DTOs
- `app/DTO/Ui/RegistryItem.php`
- `app/DTO/Ui/FeatureFlagDTO.php`

**Target**: `app/DTOs/Ui/`

### Config
- `config/fe_feature_flags.php`
- `config/fe_ui_registry.php`

**Target**: `config/`

### Seeders
- `database/seeders/UiRegistrySeeder.php`

**Target**: `database/seeders/`

## Implementation Requirements

1. **Migration Order**
   - The `alter_fe_ui_components_add_kind` migration modifies existing `fe_ui_components` table
   - Verify this table exists (should from v2 MVP)
   - Adds `kind` enum column: `primitive|composite|pattern|layout`

2. **Service Namespace**
   - Move FeatureFlagService to `App\Services\V2` namespace
   - Update config files to reference correct namespace

3. **Config Publishing**
   - Both config files define default feature flags and registry settings
   - Review and adjust defaults for production use
   - Consider adding environment-specific overrides

4. **Feature Flag Service**
   - Supports percentage-based rollouts
   - Condition-based targeting (user attributes, segments)
   - Cache-friendly design
   - Example usage:
     ```php
     if (app(FeatureFlagService::class)->isEnabled('ui.halloween_haunt', $user)) {
         // Show seasonal theme
     }
     ```

5. **Registry Integration**
   - Registry tracks publishable UI artifacts with versioning
   - Supports `type` field: `page|component|module|theme|datasource|action`
   - Links to actual tables via `reference_type` + `reference_id`
   - Hash-based change detection

6. **Testing After Migration**
   ```bash
   php artisan migrate
   php artisan db:seed --class=UiRegistrySeeder
   
   # Test in Tinker:
   php artisan tinker
   > use App\Services\V2\FeatureFlagService;
   > app(FeatureFlagService::class)->isEnabled('ui.modal_v2');
   ```

7. **Documentation**
   - Copy relevant docs to `delegation/tasks/ui-builder/docs/`:
     - `FE_UI_REGISTRY.md`
     - `FEATURE_FLAGS.md`
     - `ADR_v2_Layouts_as_Components.md`

## Acceptance Criteria

- [ ] All migrations run successfully
- [ ] Registry table created with proper indexes
- [ ] Feature flags table created with condition support
- [ ] `fe_ui_components.kind` column added (enum with 4 values)
- [ ] Models created with correct relationships
- [ ] FeatureFlagService can evaluate flags with conditions
- [ ] FeatureFlagService respects percentage rollouts
- [ ] Registry seeder populates sample data
- [ ] Config files loaded and accessible
- [ ] No conflicts with existing v2 tables
- [ ] Documentation copied and updated

## Dependencies

- None (can run in parallel with Task 1)
- Requires `fe_ui_components` table (exists from v2 MVP)

## Estimated Time

2-3 hours

## Notes

- The `kind` field on components enables better organization:
  - **primitive**: basic building blocks (button, input, badge)
  - **composite**: assembled from primitives (form, card, toolbar)
  - **pattern**: reusable patterns (resource list, detail view)
  - **layout**: structural components (grid, columns, sidebar)

- Feature flags support:
  - Percentage rollouts (e.g., 5% of users)
  - Condition targeting (attributes, segments, roles)
  - Environment overrides (staging vs production)

- Registry purpose:
  - Central catalog of all UI artifacts
  - Version tracking and change detection
  - Discovery and validation of available components
  - Foundation for marketplace/plugin system

## Related Tasks

- T-UIB-SPRINT2-01-TYPES (can run in parallel)
- T-UIB-SPRINT2-03-SCHEMA (depends on this for registry integration)
- T-UIB-SPRINT2-04-COMPONENTS (will use kind field)
