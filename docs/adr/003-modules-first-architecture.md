# ADR 003: Modules-First Architecture for UI Builder

**Date:** October 16, 2025  
**Status:** Accepted  
**Deciders:** Project Lead

## Context

During the slot-based architecture implementation, we discovered significant confusion caused by duplicate files existing in both the Laravel root structure (`database/seeders`, `app/Models`) and the modular structure (`modules/UiBuilder/`).

### The Problem

1. **Duplicate Seeders**: Same seeder existed in both locations with only namespace differences
   - `database/seeders/V2UiBuilderSeeder.php` 
   - `modules/UiBuilder/database/seeders/V2UiBuilderSeeder.php`

2. **Inconsistent Updates**: When updating one location, the other became stale, causing:
   - Configuration drift (working modal config vs outdated command config)
   - Column name mismatches (`config` vs `layout_tree_json`)
   - Wasted debugging time

3. **Mental Overhead**: Developers had to remember which version was authoritative

## Decision

**Adopt a Modules-First Architecture for UI Builder functionality:**

### Primary Rule
**All UI Builder code lives in `modules/UiBuilder/`**

This includes:
- ✅ Models: `modules/UiBuilder/app/Models/`
- ✅ Seeders: `modules/UiBuilder/database/seeders/`
- ✅ Migrations: `modules/UiBuilder/database/migrations/`
- ✅ Controllers: `modules/UiBuilder/app/Http/Controllers/` (when created)
- ✅ Services: `modules/UiBuilder/app/Services/` (when created)

### Exceptions (Laravel Root)
Code that remains in Laravel root structure:

1. **Controllers**: `app/Http/Controllers/V2/*`
   - Reason: Routes are in root `routes/` files
   - Future: May move to module with route registration

2. **Views**: `resources/views/v2/*`
   - Reason: Frontend compilation pipeline
   - Future: Planned migration to module views

3. **Frontend Assets**: `resources/js/v2/*`, `resources/js/components/v2/*`
   - Reason: Vite build configuration
   - Future: May extract to module with separate build

4. **Routes**: `routes/api.php`, `routes/web.php`
   - Reason: Laravel convention for root routes
   - Future: Module route registration system

## Files Deleted (Dead Experiments)

The following files were removed from `database/seeders/` as duplicates:

- ✅ `FeUiBuilderSeeder.php` → Kept in modules
- ✅ `V2UiBuilderSeeder.php` → Kept in modules
- ✅ `V2ModelPageSeeder.php` → Kept in modules
- ✅ `V2TypeSeeder.php` → Kept in modules
- ✅ `UiRegistrySeeder.php` → Kept in modules

These were **not** part of the module system and represented dead experimentation paths.

## Cross-Module Boundaries (Current State)

### ✅ Clean Integrations

**Controller → Module Model:**
```php
// app/Http/Controllers/V2/UiPageController.php
use Modules\UiBuilder\app\Models\Page;

class UiPageController extends Controller {
    public function show(string $key): JsonResponse {
        $page = Page::where('key', $key)->firstOrFail();
        return response()->json($page->config);
    }
}
```
**Status:** This is acceptable. Controllers can depend on module models.

### ⚠️ To Review Later

**Views in Laravel Root:**
- `resources/views/v2/shell.blade.php` → Used by `V2ShellController`
- **Future Task**: Migrate to module views or establish view publishing

**Frontend Assets:**
- `resources/js/v2/*` → Component renderers, shell page
- `resources/js/components/v2/*` → UI components
- **Future Task**: Module-based frontend build or keep as integration layer

## Consequences

### Positive

1. **Single Source of Truth**: One location per component eliminates drift
2. **Clear Ownership**: Module boundary makes it obvious what belongs together
3. **Easier Refactoring**: All related code moves together
4. **Better Testing**: Module can be tested in isolation
5. **Reduced Confusion**: No more "which seeder do I run?"

### Negative

1. **Namespace Verbosity**: `Modules\UiBuilder\app\Models\Page` vs `App\Models\Page`
2. **Learning Curve**: Team must understand module structure
3. **Migration Effort**: Existing references need updating

### Neutral

1. **Controllers Still in Root**: Acceptable compromise for now
2. **Views Still in Root**: Planned for future migration

## Implementation Checklist

### Completed ✅

- [x] Delete duplicate seeders from `database/seeders/`
- [x] Verify module seeders use `config` column (not `layout_tree_json`)
- [x] Document modules-first decision
- [x] Update working config in module seeder

### Future Tasks 📋

- [ ] Move V2 controllers to module: `modules/UiBuilder/app/Http/Controllers/V2/`
- [ ] Move V2 views to module: `modules/UiBuilder/resources/views/`
- [ ] Establish view publishing pattern or module view registration
- [ ] Consider frontend build strategy (keep in root vs module-based)
- [ ] Add module loading/registration to `composer.json` if needed

## Guidelines Going Forward

### For Developers

**When working with UI Builder:**

1. ✅ **DO**: Look in `modules/UiBuilder/` first
2. ✅ **DO**: Create new files in module structure
3. ✅ **DO**: Use module namespace: `Modules\UiBuilder\...\`
4. ❌ **DON'T**: Create duplicates in `app/` or `database/` root
5. ❌ **DON'T**: Assume root Laravel structure is authoritative

**When renaming/refactoring:**

1. ✅ **DO**: Search entire codebase with `rg` or `ag`
2. ✅ **DO**: Flag cross-boundary dependencies for review
3. ✅ **DO**: Involve project lead for architectural changes
4. ❌ **DON'T**: Rename without comprehensive search
5. ❌ **DON'T**: Make assumptions about unused code

### Running Seeders

**Correct Way:**
```bash
# Module seeder (authoritative)
php artisan db:seed --class=Modules\\UiBuilder\\database\\seeders\\V2UiBuilderSeeder
```

**Incorrect Way:**
```bash
# Root seeder (DELETED - doesn't exist anymore)
php artisan db:seed --class=Database\\Seeders\\V2UiBuilderSeeder
```

## References

- **Trigger Issue**: Slot-based architecture implementation revealed config drift
- **Root Cause**: `layout_tree_json` renamed to `config` in some places but not others
- **Discovery**: Two seeders updating same page with different configs
- **Resolution**: Deleted root duplicates, adopted modules-first

## Related Documents

- `docs/ui-builder-slot-based-architecture.md` - Slot architecture implementation
- `docs/v2-audit-findings/poc-fixes.md` - POC fixes showing working modal config
- `delegation/tasks/ui-builder/fe-ui-v2-sprint-2.md` - Sprint 2 schema work

---

**Lesson Learned:** Duplicate code with subtle differences is worse than no abstraction. Pick one location and stick with it.
