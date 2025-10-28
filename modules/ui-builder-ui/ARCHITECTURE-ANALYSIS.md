# UI Builder Architecture Analysis & Recommendations

## Current State: Two Parallel Type Systems

### System 1: UI Builder's DataSource System ✅
**Location:** `@hollis-labs/ui-builder` package

**Components:**
- `HollisLabs\UiBuilder\Models\Datasource` (model)
- `HollisLabs\UiBuilder\Services\DataSourceResolver` (resolver)
- `HollisLabs\UiBuilder\Http\Controllers\DataSourceController` (API)
- `fe_ui_datasources` table

**Routes:**
- `GET /api/ui/datasources/{alias}` - List/query
- `POST /api/ui/datasources/{alias}` - Create
- `GET /api/ui/datasources/{alias}/capabilities` - Get capabilities

**How it works:**
1. Datasource entries define model mappings in `fe_ui_datasources` table
2. DataSourceResolver reads config from database
3. Fully configuration-driven (no hardcoding)
4. Used by data-table components

**Status:** ✅ **Works perfectly, fully config-driven, no hardcoding**

---

### System 2: Main App's FeType System ⚠️
**Location:** Main app (`app/` directory)

**Components:**
- `App\Models\FeType` (model)
- `App\Models\FeTypeField` (field definitions)
- `App\Models\FeTypeRelation` (relationship definitions)
- `App\Services\Types\TypeRegistry` (registry service)
- `App\Services\Types\TypeResolver` (full-featured resolver) ✅
- `App\Services\Types\SimpleTypeResolver` (hardcoded override) ❌
- `App\Http\Controllers\Api\TypesController` (API)
- `fe_types`, `fe_type_fields`, `fe_type_relations` tables

**Routes:**
- `GET /api/ui/types/{alias}/query` - List/query
- `GET /api/ui/types/{alias}/{id}` - Show single record

**How it works:**
1. FeType entries define schemas in `fe_types` table
2. TypeRegistry reads from database with caching
3. **TypeResolver** (parent) - Fully DB-driven, uses TypeRegistry ✅
4. **SimpleTypeResolver** (child) - Overrides with hardcoded map ❌

**Status:** ⚠️ **Mix of config-driven (TypeResolver) and hardcoded (SimpleTypeResolver)**

---

## The Problem: Hardcoding in SimpleTypeResolver

### SimpleTypeResolver.php (Currently)
```php
class SimpleTypeResolver extends TypeResolver
{
    protected $modelMap = [
        'Agent' => \App\Models\Agent::class,
        'Model' => \App\Models\AiModel::class,
        'Task' => \App\Models\OrchestrationTask::class,
        'Sprint' => \App\Models\OrchestrationSprint::class,
        // Recently added:
        'UiPage' => \HollisLabs\UiBuilder\Models\Page::class,
        'UiComponent' => \HollisLabs\UiBuilder\Models\Component::class,
        'UiRegistry' => \HollisLabs\UiBuilder\Models\Registry::class,
        'UiModule' => \HollisLabs\UiBuilder\Models\Module::class,
    ];

    public function show(string $alias, mixed $id): ?array
    {
        if (!isset($this->modelMap[$alias])) {
            throw new \Exception("Type alias '{$alias}' not found");
        }
        // ... hardcoded logic
    }
}
```

### Why This Exists
Looking at the code, `SimpleTypeResolver` was likely created as a **temporary/simplified version** before the full TypeRegistry system was implemented. The parent `TypeResolver` already has all the DB-driven logic.

### AppServiceProvider.php
```php
$this->app->bind(
    \App\Services\Types\TypeResolver::class,
    \App\Services\Types\SimpleTypeResolver::class
);
```

This binding makes ALL TypeResolver requests use SimpleTypeResolver instead.

---

## Files That Need to Move to UI Builder Package

### Core Type System Files (Should be in UI Builder)

1. **Models:**
   - `app/Models/FeType.php`
   - `app/Models/FeTypeField.php`
   - `app/Models/FeTypeRelation.php`

2. **Services:**
   - `app/Services/Types/TypeRegistry.php` ✅ Keep (config-driven)
   - `app/Services/Types/TypeResolver.php` ✅ Keep (config-driven)
   - `app/Services/Types/SimpleTypeResolver.php` ❌ **DELETE** (hardcoded)

3. **Controllers:**
   - `app/Http/Controllers/Api/TypesController.php` - Needs refactoring (has hardcoded map)

4. **DTOs/Schemas:**
   - `app/DTOs/Types/TypeSchema.php`
   - `app/DTOs/Types/TypeField.php`
   - `app/DTOs/Types/TypeRelation.php`

5. **Migrations:**
   - `database/migrations/*_create_fe_types_table.php`
   - `database/migrations/*_create_fe_type_fields_table.php`
   - `database/migrations/*_create_fe_type_relations_table.php`

---

## Recommendations

### Recommendation 1: Remove SimpleTypeResolver ✅ **RECOMMENDED**

**Change:** Remove the hardcoded SimpleTypeResolver and use the parent TypeResolver directly.

**In `app/Providers/AppServiceProvider.php`:**
```php
// REMOVE this binding:
$this->app->bind(
    \App\Services\Types\TypeResolver::class,
    \App\Services\Types\SimpleTypeResolver::class
);

// TypeResolver will be used directly (it's already fully DB-driven)
```

**Delete file:** `app/Services/Types/SimpleTypeResolver.php`

**Result:** 
- ✅ Zero hardcoding
- ✅ All types defined in database
- ✅ TypeResolver already has full search/filter/transform logic
- ✅ Uses TypeRegistry which reads from `fe_types` table

---

### Recommendation 2: Refactor TypesController ✅ **RECOMMENDED**

**Current problem:**
```php
// TypesController has hardcoded map too!
$modelMap = [
    'Agent' => \App\Models\Agent::class,
    'Model' => \App\Models\AiModel::class,
];
```

**Change to:**
```php
public function show(string $alias, mixed $id): JsonResponse
{
    try {
        // Just use the resolver - no hardcoding
        $result = $this->resolver->show($alias, $id);

        if (!$result) {
            return response()->json([
                'error' => 'Record not found',
            ], 404);
        }

        return response()->json([
            'data' => $result,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
        ], 404);
    }
}
```

**Result:**
- ✅ No hardcoded map
- ✅ Uses TypeResolver which reads from database
- ✅ Works for any type defined in `fe_types` table

---

### Recommendation 3: Move FeType System to UI Builder Package 🎯 **FUTURE**

This is a larger refactor but makes architectural sense:

**Rationale:**
1. `fe_types` and `fe_ui_datasources` do the same thing (map aliases to models)
2. Both are used by UI Builder components
3. FeType system is more sophisticated (fields, relations, capabilities)
4. Could replace or merge with Datasource system

**Migration Path:**
1. Move FeType models to `HollisLabs\UiBuilder\Models\`
2. Move TypeRegistry/TypeResolver to `HollisLabs\UiBuilder\Services\`
3. Move TypesController to `HollisLabs\UiBuilder\Http\Controllers\`
4. Consider merging with or replacing DataSource system

**Benefit:**
- Single source of truth for type definitions
- All UI Builder functionality self-contained
- Easier to version and distribute

---

## Summary of Current UI Builder Files in Main App

### Files That Make UI Builder Work (in main app)

1. **Type System (should move to package):**
   - ✅ `app/Models/FeType.php` - Core model
   - ✅ `app/Models/FeTypeField.php` - Field definitions
   - ✅ `app/Models/FeTypeRelation.php` - Relations
   - ✅ `app/Services/Types/TypeRegistry.php` - Registry service
   - ✅ `app/Services/Types/TypeResolver.php` - Resolver service
   - ❌ `app/Services/Types/SimpleTypeResolver.php` - **DELETE** (hardcoded)
   - ⚠️ `app/Http/Controllers/Api/TypesController.php` - **REFACTOR** (remove hardcoding)

2. **Utilities:**
   - `app/Console/Commands/ExportUiPages.php` - Export pages command
   - `app/Console/Commands/DevRefreshCommand.php` - Dev utility

3. **Seeders (project-specific):**
   - `database/seeders/UiBuilderTypesSeeder.php` - Our seeder
   - `database/seeders/TypesDemoSeeder.php` - Demo data
   - Vendor: `vendor/hollis-labs/ui-builder/database/seeders/TypeSeeder.php`

---

## Action Plan

### Phase 1: Remove Hardcoding (Immediate) ⚡

1. **Delete `SimpleTypeResolver.php`**
   ```bash
   rm app/Services/Types/SimpleTypeResolver.php
   ```

2. **Remove binding in `AppServiceProvider.php`**
   ```php
   // Remove these lines:
   $this->app->bind(
       \App\Services\Types\TypeResolver::class,
       \App\Services\Types\SimpleTypeResolver::class
   );
   ```

3. **Refactor `TypesController::show()`**
   - Remove hardcoded `$modelMap`
   - Use `$this->resolver->show()` directly

4. **Test:**
   ```bash
   php artisan tinker
   $resolver = app(App\Services\Types\TypeResolver::class);
   $result = $resolver->show('UiPage', 1);
   // Should work without hardcoding
   ```

### Phase 2: Documentation Update

1. Update INSTALLATION.md - Remove SimpleTypeResolver section
2. Update README.md - Document that it's zero-hardcoding
3. Update TROUBLESHOOTING.md - Remove references to hardcoded maps

### Phase 3: Move to UI Builder Package (Future)

1. Create migration plan for moving FeType system
2. Evaluate merging with Datasource system
3. Move models, services, controllers to package
4. Update namespace imports in seeders
5. Publish migrations from package

---

## Verification Checklist

After implementing Phase 1, verify:

- [ ] `SimpleTypeResolver.php` deleted
- [ ] `AppServiceProvider.php` binding removed
- [ ] `TypesController.php` refactored (no hardcoded map)
- [ ] Detail modals still work (uses TypeResolver)
- [ ] Data tables still work (uses DataSourceResolver)
- [ ] Create forms work (POST to datasources)
- [ ] All types resolved from database
- [ ] No hardcoding anywhere

---

## Conclusion

**Current State:**
- UI Builder's DataSource system: ✅ Fully config-driven
- Main app's FeType system: ⚠️ Mix of config + hardcoding

**Recommended State:**
- Remove SimpleTypeResolver ❌ Delete
- Use TypeResolver (parent) ✅ Already config-driven
- Refactor TypesController ✅ Remove hardcoding
- Future: Move entire FeType system to UI Builder package

**Result:**
- 🎯 Zero hardcoding
- 🎯 100% database-driven
- 🎯 Easy to extend (just add to `fe_types` table)
- 🎯 Self-contained UI Builder package
