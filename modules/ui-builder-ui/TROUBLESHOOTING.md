# UI Builder UI - Troubleshooting Guide

## Detail Modals Are Blank

### Symptoms
- Data tables load successfully with rows
- Clicking a row opens the detail modal
- Modal opens but all fields are blank/empty
- No errors in browser console

### Root Cause
The detail modals use `/api/ui/types/{alias}/{id}` endpoint which is handled by `SimpleTypeResolver`. This resolver uses a hardcoded `$modelMap` that doesn't include the UI Builder models by default.

### Solution
Add the UI Builder models to the `SimpleTypeResolver`:

**File:** `app/Services/Types/SimpleTypeResolver.php`

```php
protected $modelMap = [
    'Agent' => \App\Models\Agent::class,
    'Model' => \App\Models\AiModel::class,
    'Task' => \App\Models\OrchestrationTask::class,
    'Sprint' => \App\Models\OrchestrationSprint::class,
    // Add these lines:
    'UiPage' => \HollisLabs\UiBuilder\Models\Page::class,
    'UiComponent' => \HollisLabs\UiBuilder\Models\Component::class,
    'UiRegistry' => \HollisLabs\UiBuilder\Models\Registry::class,
    'UiModule' => \HollisLabs\UiBuilder\Models\Module::class,
];
```

### Verification

```bash
php artisan tinker
$resolver = app(App\Services\Types\TypeResolver::class);
$result = $resolver->show('UiPage', 1);
// Should return page data with all fields
```

## "No data available" in Tables

### Symptoms
- Pages/Components/Registry modals open successfully
- Search bar appears
- Data table renders but shows "No data available"
- No errors in browser console

### Root Cause
Missing **Datasource** entries in the `fe_ui_datasources` table. FeTypes define the schema, but Datasources tell the UI Builder how to fetch the actual data.

### Solution
Run the seeder (which includes the datasources seeder):

```bash
php artisan db:seed --class=UiBuilderUiSeeder
```

### Verification

```bash
php artisan tinker

# Check that datasources exist
HollisLabs\UiBuilder\Models\Datasource::whereIn('alias', ['UiPage', 'UiComponent', 'UiRegistry', 'UiModule'])->count()
// Should return: 4

# Check specific datasource
HollisLabs\UiBuilder\Models\Datasource::where('alias', 'UiPage')->first()
// Should return a datasource with model_class, resolver_class, etc.
```

### API Test
Test the datasource API directly:

```bash
# Test UiPage datasource
curl http://localhost:8000/api/ui/datasources/UiPage

# Should return JSON with data array
{
  "data": [
    {
      "id": 1,
      "key": "page.ui-builder.pages.list",
      ...
    }
  ],
  "meta": {...}
}
```

## Pages Don't Appear in List

### Symptoms
- Datasources are created
- API returns empty data array
- Tables show "No data available"

### Possible Causes

1. **Pages not seeded**: Check if pages exist
   ```bash
   php artisan tinker
   HollisLabs\UiBuilder\Models\Page::where('module_key', 'core.ui-builder')->count()
   // Should return: 3
   ```

2. **Pages disabled**: Check enabled status
   ```bash
   HollisLabs\UiBuilder\Models\Page::where('module_key', 'core.ui-builder')->where('enabled', true)->count()
   // Should return: 3
   ```

3. **Database migration issue**: Check table exists
   ```bash
   php artisan tinker
   Schema::hasTable('fe_ui_pages')
   // Should return: true
   ```

### Solution
Re-run the seeder:
```bash
php artisan db:seed --class=UiBuilderUiSeeder
```

## API Returns 404

### Symptoms
- Browser console shows 404 errors
- Network tab shows failed requests to `/api/ui/datasources/{alias}`

### Possible Causes

1. **Routes not registered**: Check if UI Builder routes are loaded
   ```bash
   php artisan route:list --path=api/ui/datasources
   # Should show 3 routes
   ```

2. **Service provider not loaded**: Check config
   ```bash
   php artisan config:clear
   php artisan route:clear
   php artisan cache:clear
   ```

## Modal Doesn't Open

### Symptoms
- Clicking navigation link does nothing
- No modal appears
- No errors in console

### Possible Causes

1. **Page key mismatch**: Check page exists
   ```bash
   php artisan tinker
   HollisLabs\UiBuilder\Models\Page::where('key', 'page.ui-builder.pages.list')->exists()
   // Should return: true
   ```

2. **JavaScript not compiled**: Rebuild assets
   ```bash
   npm run build
   # or for development
   npm run dev
   ```

3. **Module not enabled**: Check module
   ```bash
   php artisan tinker
   HollisLabs\UiBuilder\Models\Module::where('key', 'core.ui-builder')->where('enabled', true)->exists()
   // Should return: true
   ```

## Search/Filter Not Working

### Symptoms
- Data loads successfully
- Search bar appears
- Typing in search doesn't filter results

### Possible Causes

1. **Datasource capabilities missing**: Check datasource config
   ```bash
   php artisan tinker
   $ds = HollisLabs\UiBuilder\Models\Datasource::where('alias', 'UiPage')->first();
   dd($ds->capabilities);
   // Should show searchable, filterable, sortable arrays
   ```

2. **Re-seed datasources**:
   ```bash
   php artisan db:seed --class=UiBuilderDatasourcesSeeder
   ```

## Create Form Doesn't Submit

### Symptoms
- "New Page" button opens modal
- Form appears with fields
- Clicking "Create Page" does nothing or shows error

### Possible Causes

1. **CSRF token issue**: Check browser console for 419 errors

2. **Validation failure**: Check network tab for 422 errors

3. **API endpoint missing**: Check routes
   ```bash
   php artisan route:list --path=api/ui/datasources --method=POST
   ```

4. **Missing required fields**: Check form has all required fields filled

## General Debugging Steps

### 1. Check Seeder Execution
```bash
php artisan db:seed --class=UiBuilderUiSeeder
# Should complete without errors
```

### 2. Check Database Tables
```bash
php artisan tinker

# FeTypes
App\Models\FeType::whereIn('alias', ['UiPage', 'UiComponent', 'UiRegistry', 'UiModule'])->count()
// Returns: 4

# Datasources
HollisLabs\UiBuilder\Models\Datasource::whereIn('alias', ['UiPage', 'UiComponent', 'UiRegistry', 'UiModule'])->count()
// Returns: 4

# Pages
HollisLabs\UiBuilder\Models\Page::where('module_key', 'core.ui-builder')->count()
// Returns: 3

# Module
HollisLabs\UiBuilder\Models\Module::where('key', 'core.ui-builder')->exists()
// Returns: true
```

### 3. Check API Endpoints
```bash
# List datasource routes
php artisan route:list --path=api/ui/datasources

# Test API directly
curl http://localhost:8000/api/ui/datasources/UiPage
```

### 4. Check Browser Console
- Open browser DevTools (F12)
- Check Console tab for JavaScript errors
- Check Network tab for failed requests
- Look for 404, 419, 422, 500 errors

### 5. Clear Caches
```bash
php artisan config:clear
php artisan route:clear
php artisan cache:clear
php artisan view:clear
```

### 6. Check Logs
```bash
# Laravel logs
tail -f storage/logs/laravel.log

# Check for errors related to:
# - DataSourceResolver
# - Datasource model
# - UI Builder routes
```

## Still Having Issues?

### Collect Debug Information

1. **Seeder output**:
   ```bash
   php artisan db:seed --class=UiBuilderUiSeeder > seeder-output.log 2>&1
   ```

2. **Database state**:
   ```bash
   php artisan tinker
   # Run all verification commands and save output
   ```

3. **API response**:
   ```bash
   curl -v http://localhost:8000/api/ui/datasources/UiPage > api-test.log 2>&1
   ```

4. **Browser console**: Screenshot or copy all errors

5. **Network tab**: Export HAR file of failed requests

### Common Fixes Summary

| Issue | Quick Fix |
|-------|-----------|
| No data available | `php artisan db:seed --class=UiBuilderDatasourcesSeeder` |
| 404 on API | `php artisan route:clear && php artisan config:clear` |
| Modal won't open | Check page key matches navigation |
| Search not working | Re-seed datasources with capabilities |
| Create fails | Check CSRF token and validation errors |
| General issues | Clear all caches, re-run full seeder |

## Prevention

To avoid issues in future:
1. Always run the full `UiBuilderUiSeeder` (not individual seeders)
2. Check seeder output for errors
3. Verify with `php artisan tinker` commands
4. Test API endpoints before using UI
5. Keep browser console open during development
