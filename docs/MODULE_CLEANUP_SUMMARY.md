# Module Cleanup Summary - October 16, 2025

## What Was Done

### ✅ Deleted Duplicate Seeders

**From `database/seeders/` (Root Laravel):**
- ❌ `FeUiBuilderSeeder.php` 
- ❌ `V2UiBuilderSeeder.php`
- ❌ `V2ModelPageSeeder.php`
- ❌ `V2TypeSeeder.php`
- ❌ `UiRegistrySeeder.php`

**From `modules/UiBuilder/database/seeders/` (Module):**
- ❌ `FeUiBuilderSeeder.php` (old version with layout_tree_json)
- ❌ `V2ModelPageSeeder.php` (duplicate)

### ✅ Kept (Authoritative Source)

**In `modules/UiBuilder/database/seeders/`:**
- ✅ `V2UiBuilderSeeder.php` - Agent page seeder
- ✅ `UiRegistrySeeder.php` - Component registry seeder
- ✅ `V2TypeSeeder.php` - Type definitions seeder

---

## Files Still Using `layout_tree_json`

### Migration Files (Historical - Keep As-Is)
1. `modules/UiBuilder/database/migrations/2025_10_15_000001_create_ui_pages_table.php`
   - **Status:** Original table creation
   - **Action:** Keep (historical record)

2. `database/migrations/2025_10_15_162755_alter_fe_ui_pages_add_fields.php`
   - **Status:** Column rename: `config` → `layout_tree_json` → `config`
   - **Action:** Keep (historical record)

### Documentation Files (Reference Only)
- Multiple files in `docs/` and `delegation/` folders
- **Status:** Historical documentation
- **Action:** Keep for reference

### Test File
- `test-v2-components.html`
- **Status:** Test file still references old structure
- **Action:** Update or delete when testing infrastructure is formalized

---

## Current State

### ✅ Module Structure (Authoritative)

```
modules/UiBuilder/
├── app/
│   └── Models/
│       ├── Page.php           ← Uses 'config' column
│       ├── Datasource.php
│       ├── Component.php
│       └── ... (7 more models)
│
└── database/
    ├── seeders/
    │   ├── V2UiBuilderSeeder.php    ← Reads from JSON, uses 'config'
    │   ├── UiRegistrySeeder.php     ← Component registry
    │   └── V2TypeSeeder.php          ← Type definitions
    │
    └── migrations/
        └── 2025_10_15_000001_create_ui_pages_table.php
```

### ⚠️ Cross-Boundary Dependencies (Review Needed)

**Controllers:** `app/Http/Controllers/V2/`
- `UiPageController.php` → Uses `Modules\UiBuilder\app\Models\Page`
- `V2ShellController.php` → Uses `resources/views/v2/shell.blade.php`

**Views:** `resources/views/v2/`
- `shell.blade.php` → Loads `resources/js/v2/main.tsx`

**Frontend:** `resources/js/`
- `v2/` → Main entrypoint (4 files)
- `components/v2/` → Component library (50+ files)

**Routes:** `routes/`
- `api.php` → `/api/v2/ui/pages/{key}`
- `web.php` → `/v2/pages/{key}`

**See:** `docs/adr/003-cross-boundary-dependencies.md` for detailed review

---

## How to Run Seeders (Updated)

### ✅ Correct Way (Module)

```bash
# UI Builder pages
php artisan db:seed --class=Modules\\UiBuilder\\database\\seeders\\V2UiBuilderSeeder

# Component registry
php artisan db:seed --class=Modules\\UiBuilder\\database\\seeders\\UiRegistrySeeder

# Type definitions
php artisan db:seed --class=Modules\\UiBuilder\\database\\seeders\\V2TypeSeeder
```

### ❌ Old Way (DELETED - Won't Work)

```bash
# These files no longer exist
php artisan db:seed --class=Database\\Seeders\\V2UiBuilderSeeder
php artisan db:seed --class=Database\\Seeders\\FeUiBuilderSeeder
```

---

## Configuration Files Updated

### JSON Config (Source of Truth)
**File:** `delegation/tasks/ui-builder/frontend/page.agent.table.modal.json`

**Changes:**
- ✅ Added `layout: { type: 'rows', children: [...] }` for slot-based architecture
- ✅ Changed `rowAction.type` from `'command'` to `'modal'`
- ✅ Nested all component props under `props` object
- ✅ Added proper modal fields configuration

**Why:** This JSON file is loaded by `V2UiBuilderSeeder` to seed the database

---

## Key Learnings

### 1. Single Source of Truth
- **Before:** Same seeder in 2 locations with different content
- **After:** One seeder in `modules/UiBuilder/`
- **Benefit:** No configuration drift

### 2. Module-First Architecture
- **Rule:** UI Builder code lives in `modules/UiBuilder/`
- **Exception:** Controllers, views, frontend (integration points)
- **See:** `docs/adr/003-modules-first-architecture.md`

### 3. Column Naming Consistency
- **Database column:** `config` (not `layout_tree_json`)
- **Model accessor:** `$page->config`
- **Seeder:** Uses `'config' => $data`
- **Consistent:** Throughout module code

---

## Testing

### Verify Slot-Based Architecture Works

```bash
# Reseed the page
php artisan db:seed --class=Modules\\UiBuilder\\database\\seeders\\V2UiBuilderSeeder

# Visit in browser
http://localhost:8000/v2/pages/page.agent.table.modal
```

**Expected:**
- Modal opens with "Agents" title
- Search bar at top
- Table with agent data
- Click row → Opens detail modal (not alert)
- Add Agent button works

---

## Related Documents

1. **ADR 003: Modules-First Architecture**
   - `docs/adr/003-modules-first-architecture.md`
   - **Why:** Decision record for module structure

2. **Cross-Boundary Dependencies**
   - `docs/adr/003-cross-boundary-dependencies.md`
   - **Why:** Review needed for controllers/views/frontend

3. **Slot-Based Architecture**
   - `docs/ui-builder-slot-based-architecture.md`
   - **Why:** Technical implementation details

4. **POC Fixes (Reference)**
   - `docs/v2-audit-findings/poc-fixes.md`
   - **Why:** Shows working modal config from Oct 15

---

## Next Steps

### Immediate
- [x] Delete duplicate seeders
- [x] Update JSON config with working modal config
- [x] Document modules-first decision
- [x] Flag cross-boundary dependencies for review

### For Review (Decisions Needed)
- [ ] Controllers: Keep in root or move to module?
- [ ] Views: Keep in root or move to module?
- [ ] Frontend: Keep in root or extract to module?
- [ ] Routes: Keep in root or create module provider?

### Future
- [ ] Migrate views to module (if approved)
- [ ] Migrate controllers to module (if approved)
- [ ] Establish frontend build strategy (if extraction approved)
- [ ] Create module route registration pattern (if approved)

---

## Command Reference

```bash
# List module seeders
ls -la modules/UiBuilder/database/seeders/

# Run module seeder
php artisan db:seed --class=Modules\\UiBuilder\\database\\seeders\\V2UiBuilderSeeder

# Search for references
rg "layout_tree_json" --type php

# Check git status
git status database/seeders modules/UiBuilder
```

---

**Status:** ✅ Module cleanup complete. Cross-boundary dependencies flagged for review.
