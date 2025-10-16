# ADR 005: Page Config JSON Format & Metadata

**Date:** October 16, 2025  
**Status:** Accepted  
**Related:** ADR 004 - Single Source of Truth

## Context

Page configurations are stored as JSON files in `storage/ui-builder/pages/`. We need a standard format that includes:
1. The actual page configuration
2. Metadata for tracking versions and detecting changes
3. Clear indication when configs drift from database

### Problem

Without metadata in the JSON files:
- Can't tell if JSON is in sync with database
- Can't detect when someone manually edited database
- No way to track version history in git
- Hard to debug "config reverted" issues

## Decision

**JSON files MUST include a `_meta` object with version tracking.**

### JSON Format Specification

```json
{
  "id": "page.{domain}.{feature}.{variant}",
  "title": "Page Title",
  "overlay": "modal" | "sheet" | "page",
  "layout": {
    "type": "rows" | "columns" | "grid",
    "id": "root-layout",
    "children": [...]
  },
  "_meta": {
    "version": 17,
    "hash": "ef4978ae02955cff6e2028863e9dff0a7bf470e142f323b3afe6b8e1d2a0ba39",
    "last_updated": "2025-10-16T05:55:07+00:00",
    "last_synced": "2025-10-16T06:48:22+00:00"
  }
}
```

### Metadata Fields

| Field | Type | Description | Source |
|-------|------|-------------|--------|
| `version` | integer | Auto-incremented version number | Database `version` column |
| `hash` | string | SHA-256 hash of config (without `_meta`) | Database `hash` column |
| `last_updated` | ISO8601 | When database record was last updated | Database `updated_at` column |
| `last_synced` | ISO8601 | When JSON was exported from database | Current timestamp |

### Hash Calculation

```php
// Hash is calculated from config WITHOUT _meta
$configWithoutMeta = $config;
unset($configWithoutMeta['_meta']);
$hash = hash('sha256', json_encode($configWithoutMeta));
```

This allows comparing JSON file hash with database hash to detect drift.

## Implementation

### Export Command

```bash
# Export current DB state to JSON files
php artisan ui-builder:export-pages

# Creates: storage/ui-builder/pages/{page-key}.json
# Includes: _meta with current version, hash, timestamps
```

**Implementation:**
```php
$config = $page->config;
$config['_meta'] = [
    'version' => $page->version,
    'hash' => $page->hash,
    'last_updated' => $page->updated_at->toIso8601String(),
    'last_synced' => now()->toIso8601String(),
];
```

### Seeder

```bash
# Load JSON files to database
php artisan db:seed --class=Modules\\UiBuilder\\database\\seeders\\V2UiBuilderSeeder
```

**Implementation:**
```php
$config = json_decode(File::get($configPath), true);

// Extract and remove metadata (not stored in config)
$meta = $config['_meta'] ?? null;
unset($config['_meta']);

$page = Page::updateOrCreate(['key' => $pageKey], ['config' => $config]);

// Warn if hash mismatch (config was modified)
if ($meta && $meta['hash'] !== $page->hash) {
    $this->warn("Hash mismatch - config was modified");
}
```

## Workflow Example

### Scenario: Update Model Page Form

```bash
# 1. Export current state (creates backup)
php artisan ui-builder:export-pages --cache
# → storage/ui-builder/cache/2025-10-16-063937/

# 2. Export to editable location
php artisan ui-builder:export-pages
# → storage/ui-builder/pages/page.model.table.modal.json
```

**Before editing, check metadata:**
```json
{
  "id": "page.model.table.modal",
  "layout": {...},
  "_meta": {
    "version": 9,
    "hash": "ec759bf2777f0ddc0a78f418ae9da434cccff45039eb8ffc6ac4fa71877323cf",
    "last_updated": "2025-10-16T06:25:41+00:00",
    "last_synced": "2025-10-16T06:48:22+00:00"
  }
}
```

**Edit the file:**
```bash
vim storage/ui-builder/pages/page.model.table.modal.json

# Remove 'role' field from toolbar form
# Save and exit
```

**After editing, metadata is now STALE:**
- `version`: Still shows 9 (will increment to 10 after seeding)
- `hash`: Still shows old hash (will change after seeding)
- You've modified config, so hash will mismatch

**Load changes:**
```bash
php artisan db:seed --class=Modules\\UiBuilder\\database\\seeders\\V2UiBuilderSeeder

# Output:
# ✓ Seeded page: page.model.table.modal
#   Version: 10  ← Incremented
#   Hash: a1b2c3d4e5f6...  ← Changed
#   ⚠ Hash mismatch - config was modified
```

**Re-export to sync metadata:**
```bash
php artisan ui-builder:export-pages

# Now JSON has:
# "version": 10,
# "hash": "a1b2c3d4e5f6...",  ← Matches database
# "last_synced": "2025-10-16T07:00:00+00:00"
```

## Detecting Drift

### Scenario: Someone Manually Edited Database

```bash
# You have JSON file with metadata
cat storage/ui-builder/pages/page.agent.table.modal.json | jq '._meta'
# {
#   "version": 17,
#   "hash": "ef4978ae...",
#   "last_synced": "2025-10-16T06:48:22+00:00"
# }

# Someone manually edits database via tinker
# (This is bad practice, but happens)

# Check for drift:
php artisan tinker --execute="
\$page = \Modules\UiBuilder\app\Models\Page::where('key', 'page.agent.table.modal')->first();
\$jsonMeta = json_decode(file_get_contents(storage_path('ui-builder/pages/page.agent.table.modal.json')), true)['_meta'];

echo 'JSON version: ' . \$jsonMeta['version'] . PHP_EOL;
echo 'DB version: ' . \$page->version . PHP_EOL;
echo 'Match: ' . (\$jsonMeta['version'] === \$page->version ? 'YES' : 'NO - DRIFT DETECTED!') . PHP_EOL;
"
```

**If drift detected:**
```bash
# Option 1: Trust database, export to JSON
php artisan ui-builder:export-pages

# Option 2: Trust JSON, reload to database
php artisan db:seed --class=Modules\\UiBuilder\\database\\seeders\\V2UiBuilderSeeder
```

## File Naming Convention

```
storage/ui-builder/pages/
├── page.{domain}.{feature}.{variant}.json
│
Examples:
├── page.agent.table.modal.json      ← Agent table in modal
├── page.model.table.modal.json      ← Model table in modal
├── page.task.form.sheet.json        ← Task form in sheet
└── page.dashboard.overview.page.json ← Dashboard as full page
```

**Format:** `page.{domain}.{feature}.{variant}.json`
- `domain`: agent, model, task, sprint, etc.
- `feature`: table, form, detail, etc.
- `variant`: modal, sheet, page, etc.

## Git Tracking

**What to commit:**
```gitignore
# Commit source files
/storage/ui-builder/pages/*.json

# Ignore backups
/storage/ui-builder/cache/
```

**In git diff, you'll see:**
```diff
{
  "id": "page.model.table.modal",
  "layout": {...},
  "_meta": {
-   "version": 9,
-   "hash": "ec759bf2...",
-   "last_updated": "2025-10-16T06:25:41+00:00",
-   "last_synced": "2025-10-16T06:48:22+00:00"
+   "version": 10,
+   "hash": "a1b2c3d4...",
+   "last_updated": "2025-10-16T07:00:00+00:00",
+   "last_synced": "2025-10-16T07:00:15+00:00"
  }
}
```

This makes it **obvious** when configs change.

## Validation

### Required Fields

Every JSON file MUST have:
- ✅ `id` (string) - Page key
- ✅ `title` (string) - Display title
- ✅ `overlay` (string) - Display mode
- ✅ `layout` (object) - Page layout
- ✅ `_meta` (object) - Metadata

### Metadata Requirements

The `_meta` object MUST have:
- ✅ `version` (integer) - Version number
- ✅ `hash` (string, 64 chars) - SHA-256 hash
- ✅ `last_updated` (ISO8601 string) - DB update timestamp
- ✅ `last_synced` (ISO8601 string) - Export timestamp

## Benefits

### 1. Drift Detection
```bash
# Quick check if JSON matches DB
cat storage/ui-builder/pages/page.agent.table.modal.json | jq '._meta.version'
# Compare with DB version
```

### 2. Git History
```bash
# See version changes in git
git log -p storage/ui-builder/pages/page.agent.table.modal.json

# See when config changed
git blame storage/ui-builder/pages/page.agent.table.modal.json
```

### 3. Rollback
```bash
# Rollback to previous version
git checkout HEAD~1 storage/ui-builder/pages/page.agent.table.modal.json
php artisan db:seed --class=Modules\\UiBuilder\\database\\seeders\\V2UiBuilderSeeder
```

### 4. Debugging
```
# Error: "Modal form has wrong fields"
# Check JSON metadata:
#   version: 10
#   last_synced: 2025-10-16T07:00:00
# 
# Check database:
#   version: 12  ← Someone edited database!
#   updated_at: 2025-10-16T08:30:00
#
# Someone manually edited DB 1.5 hours after JSON export
# Solution: Export fresh JSON or reload from JSON
```

## Commands Reference

```bash
# Export (with metadata)
php artisan ui-builder:export-pages

# Export to cache (timestamped backup)
php artisan ui-builder:export-pages --cache

# Load (strips metadata, compares hash)
php artisan db:seed --class=Modules\\UiBuilder\\database\\seeders\\V2UiBuilderSeeder

# Check metadata
cat storage/ui-builder/pages/{page-key}.json | jq '._meta'

# Compare JSON vs DB
php artisan tinker --execute="
\$page = \Modules\UiBuilder\app\Models\Page::where('key', '{page-key}')->first();
\$json = json_decode(file_get_contents(storage_path('ui-builder/pages/{page-key}.json')), true);
echo 'JSON version: ' . \$json['_meta']['version'] . PHP_EOL;
echo 'DB version: ' . \$page->version . PHP_EOL;
"
```

## Example: Full JSON File

```json
{
  "id": "page.agent.table.modal",
  "title": "Agents",
  "overlay": "modal",
  "layout": {
    "type": "rows",
    "id": "root-layout",
    "children": [
      {
        "id": "component.search.bar.agent",
        "type": "search.bar",
        "props": {
          "placeholder": "Search agents..."
        },
        "result": {
          "target": "component.table.agent",
          "open": "inline"
        }
      },
      {
        "id": "component.table.agent",
        "type": "data-table",
        "props": {
          "dataSource": "Agent",
          "columns": [
            { "key": "name", "label": "Name", "sortable": true },
            { "key": "role", "label": "Role", "filterable": true }
          ],
          "rowAction": {
            "type": "modal",
            "title": "Agent Details",
            "url": "/api/v2/ui/types/Agent/{{row.id}}",
            "fields": [
              { "key": "name", "label": "Name", "type": "text" }
            ]
          }
        }
      }
    ]
  },
  "_meta": {
    "version": 17,
    "hash": "ef4978ae02955cff6e2028863e9dff0a7bf470e142f323b3afe6b8e1d2a0ba39",
    "last_updated": "2025-10-16T05:55:07+00:00",
    "last_synced": "2025-10-16T06:48:22+00:00"
  }
}
```

---

**Summary:** Every JSON file has `_meta` for version tracking. Export adds it, seeder strips it, hash comparison detects drift.
