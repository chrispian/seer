# UI Builder UI - Installation Guide

## Overview
This guide walks through installing the UI Builder UI management interface step by step.

## Prerequisites
- Laravel application with UI Builder package installed
- Database migrations run
- Composer and npm dependencies installed

## Installation Steps

### Step 1: Run the Seeder

The main seeder creates all necessary database entries:

```bash
php artisan db:seed --class=UiBuilderUiSeeder
```

This will create:
- ✅ 4 FeType definitions (UiPage, UiComponent, UiRegistry, UiModule)
- ✅ 4 Datasource entries (fe_ui_datasources table)
- ✅ 3 Page configurations (pages.list, components.list, registry.browser)
- ✅ 1 Module definition (core.ui-builder)
- ✅ TypeRegistry cache refresh

**Expected Output:**
```
===========================================
  UI Builder UI - Management Interface
===========================================

✓ UiPage type with 9 fields
✓ UiComponent type with 8 fields
✓ UiRegistry type with 9 fields
✓ UiModule type with 8 fields
✓ UI Builder datasources created successfully
✓ UI Builder pages created successfully
✓ core.ui-builder module created
✓ TypeRegistry cache refreshed

Available pages:
  - page.ui-builder.pages.list
  - page.ui-builder.components.list
  - page.ui-builder.registry.browser
```

### Step 2: Verify Package Migration (Done)

**Note:** As of October 28, 2025, the FeType system has been migrated to the UI Builder package. No code changes are required - everything is configuration-based.

**What changed:**
- FeType models, services, and controllers moved to `@hollis-labs/ui-builder` package
- Removed all hardcoded model maps
- Everything is now fully database-driven via `fe_types` table

**Verification:**
```bash
php artisan route:list --path=api/ui/types
# Should show routes pointing to HollisLabs\UiBuilder\TypesController
```

See `MIGRATION-SUMMARY.md` for complete details.

### Step 3: Verify Installation

Run these commands to verify everything is set up correctly:

```bash
php artisan tinker
```

```php
// Check FeTypes
App\Models\FeType::whereIn('alias', ['UiPage', 'UiComponent', 'UiRegistry', 'UiModule'])->count()
// Should return: 4

// Check Datasources
HollisLabs\UiBuilder\Models\Datasource::whereIn('alias', ['UiPage', 'UiComponent', 'UiRegistry', 'UiModule'])->count()
// Should return: 4

// Check Pages
HollisLabs\UiBuilder\Models\Page::where('module_key', 'core.ui-builder')->count()
// Should return: 3

// Check Module
HollisLabs\UiBuilder\Models\Module::where('key', 'core.ui-builder')->first()
// Should return the core.ui-builder module object

// Test TypeResolver (for detail modals)
$resolver = app(App\Services\Types\TypeResolver::class);
$result = $resolver->show('UiPage', 1);
// Should return page data array
```

### Step 4: Test in Browser

1. **Navigate to UI Builder module** (if navigation is set up)
2. **Open Pages Manager**: Should load `page.ui-builder.pages.list` modal
3. **Verify data loads**: Table should show existing pages
4. **Test search**: Type in search bar to filter
5. **Test detail modal**: Click any row to view details
6. **Test create**: Click "New Page" button to open create form

## What Gets Created

### Database Tables Populated

1. **fe_types** (4 entries)
   - UiPage, UiComponent, UiRegistry, UiModule
   - With fields, capabilities, metadata

2. **fe_type_fields** (~34 entries)
   - Field definitions for each type
   - Searchable, sortable, filterable flags

3. **fe_ui_datasources** (4 entries)
   - Datasource configurations
   - Model mappings, resolver classes, capabilities

4. **fe_ui_pages** (3 entries)
   - page.ui-builder.pages.list
   - page.ui-builder.components.list
   - page.ui-builder.registry.browser

5. **fe_ui_modules** (1 entry)
   - core.ui-builder module with navigation

### API Endpoints Available

After installation, these endpoints work:

```
GET  /api/ui/datasources/UiPage          - List pages
POST /api/ui/datasources/UiPage          - Create page
GET  /api/ui/types/UiPage/{id}           - Get page details

GET  /api/ui/datasources/UiComponent     - List components
POST /api/ui/datasources/UiComponent     - Create component
GET  /api/ui/types/UiComponent/{id}      - Get component details

GET  /api/ui/datasources/UiRegistry      - List registry entries
GET  /api/ui/types/UiRegistry/{id}       - Get registry entry details

GET  /api/ui/datasources/UiModule        - List modules
GET  /api/ui/types/UiModule/{id}         - Get module details
```

## Troubleshooting

### "No data available" in tables

**Cause:** Datasources not created or cache issue

**Fix:**
```bash
php artisan db:seed --class=UiBuilderDatasourcesSeeder
php artisan cache:clear
```

### Detail modals are blank

**Cause:** SimpleTypeResolver not updated with new model mappings

**Fix:** Add the UI Builder models to `$modelMap` in `SimpleTypeResolver.php` (see Step 2)

### "Type alias not found" errors

**Cause:** TypeRegistry cache issue

**Fix:**
```bash
php artisan tinker
$registry = app(App\Services\Types\TypeRegistry::class);
$registry->refreshAll();
```

Or re-run the main seeder:
```bash
php artisan db:seed --class=UiBuilderUiSeeder
```

### Pages don't appear in navigation

**Cause:** Module not enabled or navigation not configured

**Fix:**
```bash
php artisan tinker
$module = HollisLabs\UiBuilder\Models\Module::where('key', 'core.ui-builder')->first();
$module->update(['enabled' => true]);
```

## Uninstallation

To remove the UI Builder UI:

```bash
php artisan tinker
```

```php
// Delete pages
HollisLabs\UiBuilder\Models\Page::where('module_key', 'core.ui-builder')->delete();

// Delete module
HollisLabs\UiBuilder\Models\Module::where('key', 'core.ui-builder')->delete();

// Delete datasources
HollisLabs\UiBuilder\Models\Datasource::whereIn('alias', ['UiPage', 'UiComponent', 'UiRegistry', 'UiModule'])->delete();

// Delete FeTypes (optional - may be used elsewhere)
App\Models\FeType::whereIn('alias', ['UiPage', 'UiComponent', 'UiRegistry', 'UiModule'])->delete();

// Clear cache
php artisan cache:clear
```

Then revert the `SimpleTypeResolver.php` changes.

## Next Steps

After installation:
1. Explore the Pages Manager to see existing pages
2. Create a test page to understand the workflow
3. Browse the Registry to see available component types
4. Check the Components List to see reusable components
5. Review the documentation in README.md for usage examples

## Support

If issues persist:
1. Check TROUBLESHOOTING.md for detailed debugging steps
2. Verify all verification commands pass
3. Check Laravel logs: `storage/logs/laravel.log`
4. Check browser console for JavaScript errors
