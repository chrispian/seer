# Cross-Boundary Dependencies: Module ↔ Laravel Root

**Date:** October 16, 2025  
**Related:** ADR 003 - Modules-First Architecture

## Overview

This document lists all places where Laravel root structure interacts with the `modules/UiBuilder/` module. These are flagged for review to determine if they should be migrated or remain as integration points.

---

## 1. Controllers (Laravel Root → Module Models)

### Location: `app/Http/Controllers/V2/`

#### UiPageController.php
```php
namespace App\Http\Controllers\V2;

use Modules\UiBuilder\app\Models\Page;  // ← Module dependency

class UiPageController extends Controller {
    public function show(string $key): JsonResponse {
        $page = Page::where('key', $key)->firstOrFail();
        return response()->json($page->config);
    }
}
```

**Route:** `routes/api.php:304`
```php
Route::get('/pages/{key}', [\App\Http\Controllers\V2\UiPageController::class, 'show']);
```

**Status:** ⚠️ **NEEDS DECISION**  
**Options:**
1. Keep in root (controller as integration layer)
2. Move to `modules/UiBuilder/app/Http/Controllers/V2/UiPageController.php`
3. Create module route registration system

---

#### V2ShellController.php
```php
namespace App\Http\Controllers\V2;

class V2ShellController extends Controller {
    public function show(string $key): View {
        return view('v2.shell', [  // ← View in Laravel root
            'pageKey' => $key,
            'isAuthenticated' => auth()->check(),
            'hasUsers' => \App\Models\User::exists(),
            'user' => auth()->user()?->only(['id', 'name', 'email']),
        ]);
    }
}
```

**Route:** `routes/web.php` (assumed)
**View:** `resources/views/v2/shell.blade.php`

**Status:** ⚠️ **NEEDS DECISION**  
**Depends on:** Views location decision (see section 2)

---

## 2. Views (Laravel Root)

### Location: `resources/views/v2/`

#### shell.blade.php
```blade
{{-- resources/views/v2/shell.blade.php --}}
<div id="v2-root" 
     data-page-key="{{ $pageKey }}"
     data-is-authenticated="{{ $isAuthenticated }}"
     ...>
</div>
@vite(['resources/js/v2/main.tsx'])
```

**Used By:** `V2ShellController::show()`  
**Loads:** Frontend entrypoint `resources/js/v2/main.tsx`

**Status:** ⚠️ **NEEDS DECISION**  
**Options:**
1. Keep in Laravel root (views are part of presentation layer)
2. Move to `modules/UiBuilder/resources/views/`
3. Use view publishing pattern (module provides, Laravel publishes)

---

## 3. Frontend Assets (Laravel Root)

### Location: `resources/js/v2/`

#### Main Entrypoint
- `resources/js/v2/main.tsx` - App bootstrap
- `resources/js/v2/V2ShellPage.tsx` - Shell component
- `resources/js/v2/ComponentRenderer.tsx` - Component renderer
- `resources/js/v2/registerCoreComponents.ts` - Component registration

**Compiles To:** `public/build/assets/main-*.js` (via Vite)

**Status:** ⚠️ **NEEDS DECISION**  
**Options:**
1. Keep in Laravel root (integrated build pipeline)
2. Move to `modules/UiBuilder/resources/js/` with separate build
3. Create module build that publishes to Laravel public directory

---

### Location: `resources/js/components/v2/`

#### Component Library
- `primitives/` - 20+ primitive components
- `composites/` - 13+ composite components
- `layouts/` - 10+ layout components
- `advanced/` - DataTable, Charts, etc.
- `ComponentRegistry.ts` - Component registration
- `types.ts` - TypeScript type definitions

**Compiles To:** `public/build/assets/` (via Vite)

**Status:** ⚠️ **NEEDS DECISION**  
**This is the largest migration decision**

**Options:**
1. **Keep in root** (easiest, maintains current build)
2. **Move to module** (proper encapsulation, requires build changes)
3. **Hybrid** (module provides components, root imports them)

**Considerations:**
- 50+ component files
- Shared across multiple pages
- TypeScript compilation
- Vite build configuration changes
- Import path updates throughout codebase

---

## 4. Routes (Laravel Root)

### Location: `routes/api.php`

```php
// Line 304
Route::prefix('v2')->group(function () {
    Route::get('/pages/{key}', [\App\Http\Controllers\V2\UiPageController::class, 'show']);
});
```

### Location: `routes/web.php`

```php
// Assumed - needs verification
Route::get('/v2/pages/{key}', [\App\Http\Controllers\V2\V2ShellController::class, 'show']);
```

**Status:** ⚠️ **NEEDS DECISION**  
**Options:**
1. Keep in root routes (Laravel convention)
2. Create module route provider: `modules/UiBuilder/routes/`
3. Use Laravel package auto-discovery pattern

---

## 5. Frontend Build Configuration

### Location: `vite.config.ts` (root)

```typescript
export default defineConfig({
  // ...
  build: {
    rollupOptions: {
      input: {
        app: 'resources/js/app.tsx',
        v2: 'resources/js/v2/main.tsx',  // ← Module entrypoint
      },
    },
  },
});
```

**Status:** ⚠️ **NEEDS DECISION**  
**Options:**
1. Keep in root config (centralized build)
2. Module-specific vite config with composition
3. Dynamic module detection/registration

---

## Summary by Status

### ✅ Clean (No Action Needed)
- Module models used by controllers: **Acceptable pattern**
- Controller → Model dependency: **Standard Laravel**

### ⚠️ Needs Decision (5 Areas)

1. **Controllers** (`app/Http/Controllers/V2/`)
   - 2 controller files
   - Depends on: Route registration strategy

2. **Views** (`resources/views/v2/`)
   - 1 Blade template
   - Depends on: View publishing pattern

3. **Frontend Entrypoint** (`resources/js/v2/`)
   - 4 core files
   - Depends on: Build strategy

4. **Component Library** (`resources/js/components/v2/`)
   - 50+ component files
   - **Largest migration effort**
   - Depends on: Build strategy + import path updates

5. **Routes** (`routes/*.php`)
   - 2 route definitions
   - Depends on: Route registration strategy

---

## Recommendations for Discussion

### High Priority Decisions

1. **Frontend Assets Strategy**
   - Biggest impact: 50+ files
   - Affects: Build config, imports, deployment
   - **Recommendation:** Keep in root for now, plan future extraction

2. **Views Location**
   - Small impact: 1 file
   - **Recommendation:** Keep in root until frontend strategy decided

3. **Controllers Location**
   - Medium impact: 2 files
   - **Recommendation:** Move to module once route registration pattern established

### Low Priority (Can Defer)

4. **Routes Registration**
   - Can work as-is
   - Future: Module route provider pattern

5. **Build Configuration**
   - Tied to frontend assets decision
   - Future: Module-aware build system

---

## Migration Sequence (If Approved)

**Phase 1: Module Structure** ✅ COMPLETE
- Models, Seeders, Migrations in module

**Phase 2: Backend Integration** (Next)
1. Create module route provider pattern
2. Move controllers to module
3. Register module routes

**Phase 3: Frontend Preparation** (Future)
1. Design module build strategy
2. Update import paths to use aliases
3. Test build with module structure

**Phase 4: Frontend Migration** (Future)
1. Move component library to module
2. Move entrypoints to module  
3. Update Vite config
4. Move views to module or establish publishing

---

## Questions for Decision

1. **Controllers:** Keep in root as integration layer, or move to module?
2. **Views:** Keep in root, move to module, or use publishing pattern?
3. **Frontend:** Keep in root (integrated build) or extract to module?
4. **Routes:** Keep in root files or create module route provider?
5. **Build:** Centralized root build or module-specific builds?

**Please indicate preferences for each area.**
