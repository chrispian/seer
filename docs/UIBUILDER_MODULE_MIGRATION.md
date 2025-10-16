# UI Builder Module Migration - Complete

**Date:** October 16, 2025  
**Status:** ✅ Complete - Self-Contained Module  
**Next:** Package as vendor package

---

## Migration Summary

### ✅ What Was Moved to Module

**Controllers:** `modules/UiBuilder/app/Http/Controllers/V2/`
- ✅ `UiPageController.php` - API endpoint for page configs
- ✅ `V2ShellController.php` - Web endpoint for shell view

**Views:** `modules/UiBuilder/resources/views/v2/`
- ✅ `shell.blade.php` - Main shell view

**Routes:** `modules/UiBuilder/routes/`
- ✅ `api.php` - API routes (datasources + pages)
- ✅ `web.php` - Web routes (shell view)

**Seeders:** `modules/UiBuilder/database/seeders/`
- ✅ `V2UiBuilderSeeder.php` - Page seeder
- ✅ `UiRegistrySeeder.php` - Component registry
- ✅ `V2TypeSeeder.php` - Type definitions

**Models:** `modules/UiBuilder/app/Models/`
- ✅ Page, Datasource, Component, Registry, etc. (already there)

---

### ❌ What Was Deleted from Laravel Root

**Controllers:**
- ❌ `app/Http/Controllers/V2/UiPageController.php`
- ❌ `app/Http/Controllers/V2/V2ShellController.php`
- ❌ `app/Http/Controllers/V2/AgentController.php` (orphaned)
- ❌ `app/Http/Controllers/V2/UiActionController.php` (orphaned)

**Views:**
- ❌ `resources/views/v2/shell.blade.php`

**Seeders:**
- ❌ `database/seeders/FeUiBuilderSeeder.php`
- ❌ `database/seeders/V2UiBuilderSeeder.php`
- ❌ `database/seeders/V2ModelPageSeeder.php`
- ❌ `database/seeders/V2TypeSeeder.php`
- ❌ `database/seeders/UiRegistrySeeder.php`

**Routes:**
- ❌ `routes/api.php` - Line 304 (UI Builder page route)
- ❌ `routes/web.php` - Lines 43-46 (v2 pages route)

---

### ⚠️ What Remains in Laravel Root (Frontend Assets)

**JavaScript/TypeScript:** `resources/js/`
- ✅ `v2/` - Main entrypoint (4 files)
- ✅ `components/v2/` - Component library (50+ files)

**Why:** 
- Frontend build managed by root Vite config
- Will remain here when package is extracted
- Host application imports from package's published assets

---

## Module Structure (Final)

```
modules/UiBuilder/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       ├── DataSourceController.php
│   │       └── V2/
│   │           ├── UiPageController.php      ← API endpoint
│   │           └── V2ShellController.php     ← Web endpoint
│   └── Models/
│       ├── Page.php
│       ├── Datasource.php
│       └── ... (8 total models)
│
├── database/
│   ├── migrations/
│   │   └── 2025_10_15_000001_create_ui_pages_table.php
│   └── seeders/
│       ├── V2UiBuilderSeeder.php            ← Page seeder
│       ├── UiRegistrySeeder.php              ← Component registry
│       └── V2TypeSeeder.php                  ← Type definitions
│
├── resources/
│   └── views/
│       └── v2/
│           └── shell.blade.php               ← Main view
│
├── routes/
│   ├── api.php                               ← API routes
│   └── web.php                               ← Web routes
│
├── config/
│   └── ui-builder.php
│
├── UiBuilderServiceProvider.php
└── README.md
```

---

## Service Provider Configuration

**File:** `modules/UiBuilder/UiBuilderServiceProvider.php`

```php
public function boot(): void
{
    // Load migrations
    $this->loadMigrationsFrom(__DIR__ . '/database/migrations');

    // Load routes
    $this->loadRoutesFrom(__DIR__ . '/routes/api.php');
    $this->loadRoutesFrom(__DIR__ . '/routes/web.php');
    
    // Load views with 'ui-builder' namespace
    $this->loadViewsFrom(__DIR__ . '/resources/views', 'ui-builder');
    
    // Publish config
    $this->publishes([
        __DIR__ . '/config/ui-builder.php' => config_path('ui-builder.php'),
    ], 'ui-builder-config');
    
    // Publish views (optional)
    $this->publishes([
        __DIR__ . '/resources/views' => resource_path('views/vendor/ui-builder'),
    ], 'ui-builder-views');
}
```

---

## Routes Now Loading from Module

### API Routes
**Module:** `modules/UiBuilder/routes/api.php`

```bash
GET|HEAD   api/v2/ui/datasources/{alias}
POST       api/v2/ui/datasources/{alias}
GET|HEAD   api/v2/ui/datasources/{alias}/capabilities
GET|HEAD   api/v2/ui/pages/{key}              ← Page config API
```

### Web Routes
**Module:** `modules/UiBuilder/routes/web.php`

```bash
GET|HEAD   v2/pages/{key}                     ← Shell view
Named route: ui-builder.pages.show
```

**Verified with:**
```bash
php artisan route:list --path=v2
```

---

## View Resolution

**Controller uses module view namespace:**
```php
// modules/UiBuilder/app/Http/Controllers/V2/V2ShellController.php
return view('ui-builder::v2.shell', [...]);
```

**View namespace registered in service provider:**
```php
$this->loadViewsFrom(__DIR__ . '/resources/views', 'ui-builder');
```

**Result:**
- `ui-builder::v2.shell` → `modules/UiBuilder/resources/views/v2/shell.blade.php`

---

## Running Seeders

### ✅ Correct (Module)

```bash
php artisan db:seed --class=Modules\\UiBuilder\\database\\seeders\\V2UiBuilderSeeder
php artisan db:seed --class=Modules\\UiBuilder\\database\\seeders\\UiRegistrySeeder
php artisan db:seed --class=Modules\\UiBuilder\\database\\seeders\\V2TypeSeeder
```

### ❌ Old (Deleted)

```bash
# These files no longer exist
php artisan db:seed --class=Database\\Seeders\\V2UiBuilderSeeder
php artisan db:seed --class=Database\\Seeders\\FeUiBuilderSeeder
```

---

## Testing the Migration

### 1. Verify Routes Load
```bash
php artisan route:list --path=v2
```

**Expected:** 7 routes, all showing `Modules\UiBuilder\app\Http\Controllers...`

### 2. Test API Endpoint
```bash
curl http://localhost:8000/api/v2/ui/pages/page.agent.table.modal
```

**Expected:** JSON config with slot-based architecture

### 3. Test Web View
```bash
# Visit in browser
http://localhost:8000/v2/pages/page.agent.table.modal
```

**Expected:** 
- Modal opens with "Agents" title
- Search bar and data table visible
- Click row → Opens detail modal

### 4. Verify Module Routes
```bash
grep -r "V2ShellController\|UiPageController" routes/
```

**Expected:** Only comments (actual routes removed)

---

## Git Status Summary

**Deleted:**
- 4 controllers from `app/Http/Controllers/V2/`
- 1 view from `resources/views/v2/`
- 5 seeders from `database/seeders/`
- 2 old seeders from `modules/UiBuilder/database/seeders/`

**Added:**
- 2 controllers in `modules/UiBuilder/app/Http/Controllers/V2/`
- 1 view in `modules/UiBuilder/resources/views/v2/`
- 1 route file: `modules/UiBuilder/routes/web.php`

**Modified:**
- `modules/UiBuilder/UiBuilderServiceProvider.php` - Added view + route loading
- `modules/UiBuilder/routes/api.php` - Added page API route
- `routes/api.php` - Removed page route (replaced with comment)
- `routes/web.php` - Removed v2 routes (replaced with comment)

---

## Frontend Assets Strategy

**Decision:** Keep in Laravel root for now

**Reason:**
1. Vite build configuration in root
2. Will become vendor package soon
3. Host app will compile and bundle
4. Package provides source, host builds it

**When packaging:**
- Frontend stays in `resources/js/v2` and `resources/js/components/v2`
- Host app's `vite.config.ts` imports from `vendor/yourorg/ui-builder/resources/js`
- Or package publishes to host's `resources/js/vendor/ui-builder`

---

## Next Steps: Package Extraction

### 1. Create Composer Package

```bash
cd modules/UiBuilder
composer init

# package name: yourorg/ui-builder
# type: library
# require: laravel/framework ^11.0
```

### 2. Update composer.json (Root)

```json
{
  "require": {
    "yourorg/ui-builder": "dev-main"
  },
  "repositories": [
    {
      "type": "path",
      "url": "./modules/UiBuilder"
    }
  ]
}
```

### 3. Register Service Provider

```php
// config/app.php (or auto-discovery)
'providers' => [
    Modules\UiBuilder\UiBuilderServiceProvider::class,
]
```

### 4. Publish Assets

```bash
php artisan vendor:publish --tag=ui-builder-config
php artisan vendor:publish --tag=ui-builder-views
```

### 5. Test as Package

```bash
composer require yourorg/ui-builder
php artisan migrate
php artisan db:seed --class="Modules\UiBuilder\database\seeders\V2UiBuilderSeeder"
```

---

## Benefits Achieved

### ✅ Self-Contained
- All UI Builder code in one place
- No cross-contamination with Laravel root
- Clear module boundary

### ✅ Independently Versioned
- Can version the module separately
- Breaking changes isolated
- Easier to maintain

### ✅ Reusable
- Drop into any Laravel app
- Package extraction straightforward
- Frontend assets published to host

### ✅ Testable
- Module can be tested independently
- Mock external dependencies
- CI/CD for module alone

---

## References

- **ADR 003:** Modules-First Architecture
- **Cross-Boundary Dependencies:** All resolved
- **Module Cleanup:** Duplicates removed
- **Slot-Based Architecture:** Implemented and working

---

**Status:** ✅ Module is now self-contained and ready for package extraction.

**Test Command:**
```bash
php artisan route:list --path=v2
php artisan db:seed --class=Modules\\UiBuilder\\database\\seeders\\V2UiBuilderSeeder
```

**Test URL:**
```
http://localhost:8000/v2/pages/page.agent.table.modal
```
