# UI Builder - Quick Reference Card

## 🚀 Most Common Workflow

```bash
# 1. Backup
php artisan ui-builder:export-pages --cache

# 2. Edit JSON (DO NOT touch _meta)
vim storage/ui-builder/pages/page.model.table.modal.json

# 3. Import
php artisan db:seed --class=Modules\\UiBuilder\\database\\seeders\\V2UiBuilderSeeder

# 4. Sync metadata
php artisan ui-builder:export-pages

# 5. Verify
php artisan ui-builder:sync --check
```

## 📋 Commands

| Command | Use Case |
|---------|----------|
| `ui-builder:export-pages` | Save DB to JSON |
| `ui-builder:export-pages --cache` | Timestamped backup |
| `ui-builder:sync` | Check drift + fix |
| `ui-builder:sync --check` | Check drift only |
| `db:seed --class=...V2UiBuilderSeeder` | Load JSON to DB |

## 🎯 Metadata Rules

### ✅ DO:
- Edit page config
- Leave `_meta` alone
- Import after editing
- Export to sync metadata

### ❌ DON'T:
- Edit `version`
- Edit `hash`
- Edit timestamps
- Skip export after import

## 📁 File Structure

```
storage/ui-builder/
├── pages/              ← Edit these
│   ├── page.agent.table.modal.json
│   └── page.model.table.modal.json
│
└── cache/              ← Backups (don't edit)
    └── 2025-10-16-065009/
```

## 🔍 Check Sync Status

```bash
php artisan ui-builder:sync --check
```

**Output:**
- `✓ In Sync` - All good
- `✗ Drift Detected` - Need to sync
- `⚠ Missing` - Need to export or import

## 🛠 Fix Drift

```bash
# Interactive (prompts you)
php artisan ui-builder:sync

# Database wins (export DB to JSON)
php artisan ui-builder:sync --force-export

# JSON wins (import JSON to DB)
php artisan ui-builder:sync --force-import
```

## 📝 Metadata Format

```json
{
  "id": "page.agent.table.modal",
  "layout": {...},
  "_meta": {
    "version": 17,
    "hash": "ef4978ae...",
    "last_updated": "2025-10-16T05:55:07+00:00",
    "last_synced": "2025-10-16T06:50:08+00:00"
  }
}
```

**All fields auto-generated - DO NOT EDIT**

## 🚨 Troubleshooting

### "Hash mismatch" warning
**Meaning:** Config changed  
**Action:** Normal - version incremented

### "Drift detected"
**Meaning:** JSON and DB differ  
**Action:** Run `ui-builder:sync` to fix

### "Missing from database"
**Meaning:** JSON exists but no DB record  
**Action:** Run seeder to import

### "Missing from JSON"
**Meaning:** DB record but no JSON file  
**Action:** Run export to create

## 💡 Pro Tips

1. **Always backup before editing:**
   ```bash
   php artisan ui-builder:export-pages --cache
   ```

2. **Commit JSON with metadata:**
   ```bash
   git add storage/ui-builder/pages/
   git commit -m "fix: update page config"
   ```

3. **Check sync before deploying:**
   ```bash
   php artisan ui-builder:sync --check || exit 1
   ```

4. **Use sync tool when confused:**
   ```bash
   php artisan ui-builder:sync  # Shows status + options
   ```

## 🔗 More Info

- Full workflow: `storage/ui-builder/METADATA_WORKFLOW.md`
- JSON format spec: `docs/adr/005-page-config-json-format.md`
- ADR: `docs/adr/004-single-source-of-truth.md`
