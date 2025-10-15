# Task: Implement FE Types System

**Task Code**: T-UIB-SPRINT2-01-TYPES  
**Sprint**: UI Builder v2 Sprint 2  
**Priority**: HIGH  
**Assigned To**: BE-Kernel Agent  
**Status**: TODO  
**Created**: 2025-10-15

## Objective

Integrate the `fe_types` system from the context pack into the main Seer application. This system provides config-first, strongly-typed schema management for dynamic data sources.

## Context Pack Location

`/Users/chrispian/Projects/seer/delegation/tasks/ui-builder/fe_types_min_pack_20251015_152612`

## Files to Migrate

### Database
- `db/migrations/2025_10_15_000101_create_fe_types_table.php`
- `db/migrations/2025_10_15_000102_create_fe_type_fields_table.php`
- `db/migrations/2025_10_15_000103_create_fe_type_relations_table.php`

**Target**: `database/migrations/`

### Models
- `app/Models/FeType.php`
- `app/Models/FeTypeField.php`
- `app/Models/FeTypeRelation.php`

**Target**: `app/Models/`

### Services
- `app/Services/Types/TypeRegistry.php`
- `app/Services/Types/TypeResolver.php`

**Target**: `app/Services/Types/`

### DTOs
- `app/DTO/Types/TypeSchema.php`
- `app/DTO/Types/TypeField.php`
- `app/DTO/Types/TypeRelation.php`

**Target**: `app/DTOs/Types/` (note: main app uses DTOs not DTO)

### Controllers
- `app/Http/Controllers/TypesController.php`

**Target**: `app/Http/Controllers/V2/TypesController.php` (adjust namespace to V2)

### Seeders
- `database/seeders/TypesDemoSeeder.php`

**Target**: `database/seeders/`

### Routes
- `routes/types.php`

**Integrate into**: `routes/api.php` under the `v2/ui` prefix

## Implementation Requirements

1. **Namespace Adjustments**
   - Update controller to `App\Http\Controllers\V2` namespace
   - Verify all imports and namespace declarations are correct

2. **Route Integration**
   - Add types routes to `routes/api.php` under existing v2/ui group:
     ```php
     Route::prefix('v2/ui')->group(function () {
         // existing routes...
         Route::get('/types/{alias}/query', [TypesController::class, 'query']);
         Route::get('/types/{alias}/{id}', [TypesController::class, 'show']);
     });
     ```

3. **Migration Timestamps**
   - Review migration filenames - ensure they don't conflict with existing migrations
   - Consider renaming to follow sequential pattern after latest migration

4. **Testing After Migration**
   ```bash
   php artisan migrate
   php artisan db:seed --class=TypesDemoSeeder
   # Test endpoint:
   # GET /api/v2/ui/types/Invoice/query
   ```

5. **Documentation**
   - Copy `docs/README.md` to `delegation/tasks/ui-builder/docs/FE_TYPES_SYSTEM.md`
   - Update with any implementation changes or integration notes

## Acceptance Criteria

- [ ] All migrations run successfully without errors
- [ ] Models are created and relationships work
- [ ] TypeRegistry can load and cache type schemas
- [ ] TypeResolver can query types with search/filter/sort
- [ ] TypesController endpoints return correct data format
- [ ] Demo seeder runs and populates sample data
- [ ] Test endpoint `/api/v2/ui/types/Invoice/query` returns results
- [ ] No namespace conflicts or import errors
- [ ] Documentation updated in delegation folder

## Dependencies

- None (can run in parallel with Task 2)

## Estimated Time

2-3 hours

## Notes

- This is a foundation system that will be used by the UI Builder for dynamic type management
- The system supports both DB-backed and adapter-based (Sushi, API) data sources
- Focus on getting the core system integrated; codegen features are Phase 2
- Ensure proper error handling for missing types or invalid schemas

## Related Tasks

- T-UIB-SPRINT2-02-REGISTRY (can run in parallel)
- T-UIB-SPRINT2-03-SCHEMA (depends on this)
- T-UIB-SPRINT2-05-DATASOURCES (will use types system)
