# Metadata Workflow Guide

## Quick Answer: DO NOT Edit Metadata Manually

When you edit a page JSON file, **DO NOT** update `version`, `hash`, `last_updated`, or `last_synced` yourself.

**The system handles all metadata automatically:**
- Version increments on import
- Hash recalculates on import
- Timestamps update on import/export

## Workflow: Editing a Page Config

### Step 1: Export Current State
```bash
# Create timestamped backup
php artisan ui-builder:export-pages --cache

# Export to editable files (with current metadata)
php artisan ui-builder:export-pages
```

**Result:** JSON files now have `_meta` with **current** DB state:
```json
{
  "id": "page.model.table.modal",
  "layout": {...},
  "_meta": {
    "version": 9,                    ← Current DB version
    "hash": "ec759bf2...",           ← Current DB hash
    "last_updated": "2025-10-16...", ← When DB was last changed
    "last_synced": "2025-10-16..."   ← When you ran export
  }
}
```

### Step 2: Edit the Page Config

Edit ONLY the page configuration, NOT the metadata:

```bash
vim storage/ui-builder/pages/page.model.table.modal.json
```

**Example Edit:**
```json
{
  "id": "page.model.table.modal",
  "layout": {
    "children": [
      {
        "props": {
          "toolbar": [
            {
              "actions": {
                "click": {
                  "fields": [
                    {"name": "name", ...},
                    {"name": "model_id", ...},
                    {"name": "provider_id", ...}
                    // Removed: {"name": "role", ...}  ← Your change
                  ]
                }
              }
            }
          ]
        }
      }
    ]
  },
  "_meta": {
    "version": 9,          ← LEAVE THIS ALONE
    "hash": "ec759bf2...", ← LEAVE THIS ALONE
    "last_updated": "...", ← LEAVE THIS ALONE
    "last_synced": "..."   ← LEAVE THIS ALONE
  }
}
```

**DO:**
- ✅ Edit page config (layout, components, props, etc.)
- ✅ Leave `_meta` exactly as is
- ✅ Save the file

**DON'T:**
- ❌ Change `version` number
- ❌ Change `hash` value
- ❌ Change `last_updated` timestamp
- ❌ Change `last_synced` timestamp

### Step 3: Check for Drift (Optional)

```bash
php artisan ui-builder:sync --check
```

**Output:**
```
✗ page.model.table.modal - DRIFT DETECTED
  JSON:
    Version: 9
    Hash: ec759bf2...        ← Old hash (before your edit)
  Database:
    Version: 9
    Hash: ec759bf2...        ← Same (you haven't imported yet)
```

Wait, no drift yet? That's because you edited JSON but haven't imported!

**After you import, JSON metadata will be STALE.**

### Step 4: Import to Database

```bash
php artisan db:seed --class=Modules\\UiBuilder\\database\\seeders\\V2UiBuilderSeeder
```

**What happens:**
1. Seeder reads JSON
2. Seeder **STRIPS** `_meta` (ignores it completely)
3. Seeder saves config to database
4. Database **AUTO-CALCULATES**:
   - `version` → Increments to 10
   - `hash` → Recalculates based on new config
   - `updated_at` → Sets to now()

**Output:**
```
✓ Seeded page: page.model.table.modal
  Version: 10          ← Incremented!
  Hash: a1b2c3d4...    ← Changed!
  ⚠ Hash mismatch - config was modified
```

### Step 5: Re-Export to Sync Metadata

Your JSON now has STALE metadata. Fix it:

```bash
php artisan ui-builder:export-pages
```

**Result:** JSON `_meta` now matches database:
```json
{
  "id": "page.model.table.modal",
  "layout": {...your changes...},
  "_meta": {
    "version": 10,          ← Updated!
    "hash": "a1b2c3d4...",  ← Updated!
    "last_updated": "2025-10-16T07:15:00+00:00", ← Updated!
    "last_synced": "2025-10-16T07:15:30+00:00"   ← Updated!
  }
}
```

### Step 6: Verify Sync

```bash
php artisan ui-builder:sync --check
```

**Output:**
```
✓ page.model.table.modal
  Status: In Sync
  Version: 10
  Hash: a1b2c3d4...
```

Perfect! ✅

## Summary: The Metadata Lifecycle

```
1. Export:
   ┌─────────────┐
   │  Database   │ version=9, hash=abc
   └──────┬──────┘
          │ php artisan ui-builder:export-pages
          ↓
   ┌─────────────┐
   │  JSON File  │ _meta: {version:9, hash:abc}
   └─────────────┘

2. You Edit (metadata stays stale):
   ┌─────────────┐
   │  JSON File  │ config changed
   │             │ _meta: {version:9, hash:abc} ← STALE
   └─────────────┘

3. Import:
   ┌─────────────┐
   │  JSON File  │ _meta stripped
   └──────┬──────┘
          │ php artisan db:seed
          ↓
   ┌─────────────┐
   │  Database   │ version=10, hash=xyz ← AUTO-UPDATED
   └─────────────┘

4. Re-Export (sync metadata):
   ┌─────────────┐
   │  Database   │ version=10, hash=xyz
   └──────┬──────┘
          │ php artisan ui-builder:export-pages
          ↓
   ┌─────────────┐
   │  JSON File  │ _meta: {version:10, hash:xyz} ← SYNCED!
   └─────────────┘
```

## Why Not Edit Metadata?

### Reason 1: It's Ignored
The seeder strips `_meta` before saving:
```php
$config = json_decode(File::get($configPath), true);
unset($config['_meta']);  // ← Thrown away!
$page->updateOrCreate(['config' => $config]);
```

### Reason 2: Database Owns Truth
Version and hash are calculated by the database model:
```php
// In Page model:
protected static function booted()
{
    static::saving(function ($page) {
        $newHash = hash('sha256', json_encode($page->config));
        if ($page->hash !== $newHash) {
            $page->hash = $newHash;
            $page->version = ($page->version ?? 0) + 1;  // Auto-increment
        }
    });
}
```

### Reason 3: Metadata is For Tracking Only
The `_meta` object exists to help you detect drift:
- "JSON says version 9, DB says version 12" = Someone edited DB manually
- "JSON hash differs from DB hash" = Configs are out of sync

## Common Scenarios

### Scenario 1: Multiple Edits Before Import

```bash
# Export
php artisan ui-builder:export-pages

# Edit 1: Remove field
vim storage/ui-builder/pages/page.model.table.modal.json

# Edit 2: Change column
vim storage/ui-builder/pages/page.model.table.modal.json

# Edit 3: Update title
vim storage/ui-builder/pages/page.model.table.modal.json

# Import (version increments ONCE, from 9 to 10)
php artisan db:seed --class=Modules\\UiBuilder\\database\\seeders\\V2UiBuilderSeeder

# Re-export
php artisan ui-builder:export-pages
```

**Result:** Version goes 9 → 10 (not 9 → 12)

Each import increments version by 1, regardless of how many edits you made to JSON.

### Scenario 2: Someone Edited Database Manually

```bash
# Your JSON says version 10
cat storage/ui-builder/pages/page.agent.table.modal.json | jq '._meta.version'
# 10

# Someone edits DB via tinker (BAD!)
php artisan tinker --execute="..."

# Check drift
php artisan ui-builder:sync --check

# Output:
# ✗ page.agent.table.modal - DRIFT DETECTED
#   JSON: version 10
#   DB: version 12  ← Someone did 2 manual edits!
```

**Fix:**
```bash
# Option 1: Trust DB (their edits win)
php artisan ui-builder:sync --force-export

# Option 2: Trust JSON (rollback their edits)
php artisan ui-builder:sync --force-import
```

## Commands Reference

| Command | Purpose | When to Use |
|---------|---------|-------------|
| `php artisan ui-builder:export-pages` | Export DB → JSON with metadata | After import, to sync metadata |
| `php artisan ui-builder:export-pages --cache` | Timestamped backup | Before editing, as safety |
| `php artisan db:seed --class=...V2UiBuilderSeeder` | Import JSON → DB (strips metadata) | After editing JSON |
| `php artisan ui-builder:sync` | Analyze drift, interactive fix | When confused about state |
| `php artisan ui-builder:sync --check` | Check drift only (CI/CD) | In scripts, pre-commit hooks |
| `php artisan ui-builder:sync --force-export` | DB wins (overwrites JSON) | Trust database over JSON |
| `php artisan ui-builder:sync --force-import` | JSON wins (overwrites DB) | Trust JSON over database |

## Best Practice Workflow

```bash
# Before work: backup and export
php artisan ui-builder:export-pages --cache
php artisan ui-builder:export-pages

# Edit JSON (leave _meta alone)
vim storage/ui-builder/pages/page.model.table.modal.json

# Import changes
php artisan db:seed --class=Modules\\UiBuilder\\database\\seeders\\V2UiBuilderSeeder

# Sync metadata
php artisan ui-builder:export-pages

# Verify
php artisan ui-builder:sync --check

# Commit
git add storage/ui-builder/pages/page.model.table.modal.json
git commit -m "fix(ui): remove role field from model form"
```

## Git Workflow

When committing, you'll see metadata changes:
```diff
{
  "layout": {
    "fields": [
      {"name": "name"},
-     {"name": "role"},  ← Your actual change
    ]
  },
  "_meta": {
-   "version": 9,
+   "version": 10,     ← Auto-incremented
-   "hash": "ec759bf2...",
+   "hash": "a1b2c3d4...",  ← Auto-updated
  }
}
```

This is **good** - it shows both your config change AND the version bump.

## TL;DR

**Edit page config** → **Import** → **Export** → **Done**

Metadata updates automatically. Never touch it.
