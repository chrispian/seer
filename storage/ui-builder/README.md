# UI Builder Storage

## Directory Structure

```
storage/ui-builder/
├── pages/          ← Edit these files
│   ├── page.agent.table.modal.json
│   ├── page.model.table.modal.json
│   └── ...
│
└── cache/          ← DO NOT EDIT (backups only)
    └── 2025-10-16-063937/
        ├── page.agent.table.modal.json
        └── ...
```

## Workflow

### 1. Export Current Pages from Database
```bash
# Export to storage/ui-builder/pages/ (editable)
php artisan ui-builder:export-pages

# Create timestamped backup in cache/
php artisan ui-builder:export-pages --cache
```

### 2. Edit Page Config
```bash
vim storage/ui-builder/pages/page.agent.table.modal.json
```

### 3. Load Changes to Database
```bash
php artisan db:seed --class=Modules\\UiBuilder\\database\\seeders\\V2UiBuilderSeeder
```

### 4. Test
```
http://localhost:8000/v2/pages/page.agent.table.modal
```

## Files

- **`pages/`** - Editable source files loaded by seeder
- **`cache/`** - Timestamped backups (gitignored, do not edit)

## Rules

✅ **DO:**
- Export pages before making changes
- Edit files in `pages/` directory
- Run seeder after editing
- Create backups with `--cache` flag

❌ **DON'T:**
- Edit files in `cache/` directory
- Reference `delegation/` or `docs/` folders
- Manually edit database via tinker
- Create duplicate seeders

## Git

- `storage/ui-builder/pages/` - **Tracked** (source of truth)
- `storage/ui-builder/cache/` - **Ignored** (local backups only)
