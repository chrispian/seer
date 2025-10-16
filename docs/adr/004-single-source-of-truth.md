# ADR 004: Single Source of Truth for UI Builder Pages

**Date:** October 16, 2025  
**Status:** Accepted  
**Supersedes:** Multiple config locations

## Context

We experienced repeated issues with page configurations reverting to broken states:
- Agent page row clicks stopped working (reverted from modal to command)
- Model page form failed (reverted from provider_id to provider)
- Agents/developers confused about which config is authoritative

### Root Cause
Multiple sources of truth existed:
1. JSON files in `delegation/tasks/ui-builder/frontend/`
2. PHP seeders with hardcoded configs
3. Database records (manually patched via tinker)

When one was updated, others became stale. Running any seeder would overwrite working configs with broken ones.

## Decision

**ONE SOURCE OF TRUTH: JSON files in `delegation/tasks/ui-builder/frontend/`**

### The Rule

1. **Page configs ONLY exist as JSON files**
   - Location: `delegation/tasks/ui-builder/frontend/{page-key}.json`
   - Format: Complete page config with `id`, `overlay`, `title`, `layout`
   - Naming: Use the page key as filename

2. **Seeders ONLY read from JSON files**
   - Never hardcode configs in PHP
   - Never manually patch database via tinker
   - Seeder loads JSON → saves to database
   - That's it.

3. **Database is NOT the source of truth**
   - Database stores runtime state only
   - Can be reset at any time by running seeder
   - Never edit database directly

4. **To fix a page config:**
   ```bash
   # 1. Edit the JSON file
   vim delegation/tasks/ui-builder/frontend/page.agent.table.modal.json
   
   # 2. Run the seeder
   php artisan db:seed --class=Modules\\UiBuilder\\database\\seeders\\V2UiBuilderSeeder
   
   # 3. Test
   # That's it. Done.
   ```

## Implementation

### File Structure
```
delegation/tasks/ui-builder/frontend/
├── page.agent.table.modal.json       ← Agent page config
├── page.model.table.modal.json       ← Model page config
└── page.{future}.json                 ← Future pages
```

### Seeder (One Seeder, All Pages)
```php
// modules/UiBuilder/database/seeders/V2UiBuilderSeeder.php

public function run(): void
{
    $pages = [
        'page.agent.table.modal',
        'page.model.table.modal',
    ];

    foreach ($pages as $pageKey) {
        $configPath = base_path("delegation/tasks/ui-builder/frontend/{$pageKey}.json");
        
        if (!File::exists($configPath)) {
            $this->command->warn("Config not found: {$pageKey}");
            continue;
        }

        $config = json_decode(File::get($configPath), true);
        
        Page::updateOrCreate(
            ['key' => $pageKey],
            ['config' => $config]
        );
    }
}
```

### What Was Deleted
- ❌ `database/seeders/FeUiBuilderSeeder.php` - Hardcoded PHP config
- ❌ `database/seeders/V2ModelPageSeeder.php` - Hardcoded PHP config
- ❌ `modules/UiBuilder/database/seeders/FeUiBuilderSeeder.php` - Duplicate
- ❌ `modules/UiBuilder/database/seeders/V2ModelPageSeeder.php` - Duplicate

### What Remains
- ✅ `modules/UiBuilder/database/seeders/V2UiBuilderSeeder.php` - ONE seeder, reads JSON
- ✅ `delegation/tasks/ui-builder/frontend/*.json` - Page configs (source of truth)

## Consequences

### Positive
1. **No confusion** - One file to edit, always
2. **No drift** - Seeder always loads from JSON
3. **Version control** - JSON files track in git
4. **Easy rollback** - `git checkout file.json && seed`
5. **No manual DB edits** - Never use tinker to patch configs

### Negative
1. **Must run seeder** - Changes require running seeder
2. **Two-step process** - Edit JSON → Run seeder (vs direct DB edit)

### Mitigated
- Running seeder is fast: `php artisan db:seed --class=Modules\\UiBuilder\\database\\seeders\\V2UiBuilderSeeder`
- Can add to deployment scripts
- Can add file watcher for auto-reload in dev

## Workflow

### Adding a New Page
```bash
# 1. Create JSON file
cat > delegation/tasks/ui-builder/frontend/page.new.feature.json << 'EOF'
{
  "id": "page.new.feature",
  "title": "New Feature",
  "overlay": "modal",
  "layout": {
    "type": "rows",
    "children": [...]
  }
}
EOF

# 2. Add to seeder
# Edit modules/UiBuilder/database/seeders/V2UiBuilderSeeder.php
# Add 'page.new.feature' to $pages array

# 3. Run seeder
php artisan db:seed --class=Modules\\UiBuilder\\database\\seeders\\V2UiBuilderSeeder
```

### Fixing a Broken Page
```bash
# 1. Edit JSON file
vim delegation/tasks/ui-builder/frontend/page.agent.table.modal.json

# 2. Run seeder
php artisan db:seed --class=Modules\\UiBuilder\\database\\seeders\\V2UiBuilderSeeder

# 3. Clear browser cache (Cmd+Shift+R)
```

### Exporting Current DB State (Emergency)
```bash
# If you need to save current DB state to JSON
php artisan tinker --execute="
\$page = \Modules\UiBuilder\app\Models\Page::where('key', 'page.agent.table.modal')->first();
echo json_encode(\$page->config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
" > delegation/tasks/ui-builder/frontend/page.agent.table.modal.json
```

## Rules for Developers

### ✅ DO
- Edit JSON files in `delegation/tasks/ui-builder/frontend/`
- Run seeder after editing JSON
- Commit JSON files to git
- Test after seeding

### ❌ DON'T
- Hardcode configs in PHP seeders
- Edit database directly via tinker
- Create duplicate seeder files
- Assume database is source of truth

## Migration Checklist

- [x] Delete all duplicate seeders
- [x] Create JSON file for agent page
- [x] Create JSON file for model page
- [x] Update V2UiBuilderSeeder to load from JSON
- [x] Update V2UiBuilderSeeder to handle multiple pages
- [x] Test both pages load correctly
- [x] Document workflow

## Current State

**Source of Truth:**
```
delegation/tasks/ui-builder/frontend/
├── page.agent.table.modal.json       ✅ Working
└── page.model.table.modal.json       ✅ Working
```

**Seeder:**
```
modules/UiBuilder/database/seeders/V2UiBuilderSeeder.php  ✅ Loads both
```

**Database:**
```
fe_ui_pages table:
├── page.agent.table.modal (version 17)  ✅ From JSON
└── page.model.table.modal (version 9)   ✅ From JSON
```

## Verification

```bash
# Run seeder
php artisan db:seed --class=Modules\\UiBuilder\\database\\seeders\\V2UiBuilderSeeder

# Expected output:
# ✓ Seeded page: page.agent.table.modal
#   Version: 17
# ✓ Seeded page: page.model.table.modal
#   Version: 9

# Test pages
curl http://localhost:8000/api/v2/ui/pages/page.agent.table.modal | jq '.id'
curl http://localhost:8000/api/v2/ui/pages/page.model.table.modal | jq '.id'

# Both should return their respective page IDs
```

---

**Bottom Line:** If it's not in the JSON file, it doesn't exist. If you change the JSON, run the seeder. That's it.
