# UI Builder v2 Schema Implementation

**Task**: T-UIB-SPRINT2-03-SCHEMA  
**Status**: COMPLETED  
**Date**: 2025-10-15

## Overview

Successfully implemented the expanded database schema for UI Builder v2, including new `fe_ui_modules` and `fe_ui_themes` tables, plus updates to existing UI tables.

## Migrations Created

### 1. Create fe_ui_modules Table
**File**: `2025_10_15_162753_create_fe_ui_modules_table.php`  
**Status**: ✅ Ran

Columns:
- id (bigserial, PK)
- key (varchar 100, unique) - e.g., "core.agents"
- title (varchar 255)
- description (text, nullable)
- manifest_json (json) - declares pages, datasources, actions, navigation
- version (varchar 20) - semver
- hash (varchar 64)
- enabled (boolean, default true)
- order (integer, default 0)
- capabilities (json, nullable) - ["search", "filter", "export"]
- permissions (json, nullable) - role/permission requirements
- created_at, updated_at (timestamps)

Indexes: key, enabled, order

### 2. Create fe_ui_themes Table
**File**: `2025_10_15_162754_create_fe_ui_themes_table.php`  
**Status**: ✅ Ran

Columns:
- id (bigserial, PK)
- key (varchar 100, unique) - e.g., "theme.default"
- title (varchar 255)
- description (text, nullable)
- design_tokens_json (json) - radius, spacing, colors, typography
- tailwind_overrides_json (json, nullable)
- variants_json (json, nullable) - light/dark/accessible
- version (varchar 20)
- hash (varchar 64)
- enabled (boolean, default true)
- is_default (boolean, default false)
- created_at, updated_at (timestamps)

Indexes: key, enabled, is_default

### 3. Alter fe_ui_pages Table
**File**: `2025_10_15_162755_alter_fe_ui_pages_add_fields.php`  
**Status**: ✅ Ran

Added columns:
- route (varchar 255, nullable) - for non-modal pages
- meta_json (json, nullable) - SEO, breadcrumbs
- **Renamed**: config → layout_tree_json
- module_key (varchar 100, nullable) - FK to fe_ui_modules.key
- guards_json (json, nullable) - auth/roles requirements
- enabled (boolean, default true)

Indexes: route, module_key, enabled

### 4. Alter fe_ui_components Table
**File**: `2025_10_15_162755_alter_fe_ui_components_add_fields.php`  
**Status**: ✅ Ran

Added columns:
- variant (varchar 50, nullable) - e.g., standard, dense, modal
- schema_json (json, nullable) - props/slots contract
- defaults_json (json, nullable) - default values
- capabilities_json (json, nullable) - searchable/sortable flags

### 5. Alter fe_ui_datasources Table
**File**: `2025_10_15_162756_alter_fe_ui_datasources_add_fields.php`  
**Status**: ✅ Ran

Added columns:
- default_params_json (json, nullable) - default query params
- capabilities_json (json, nullable) - supports: list, detail, search, paginate
- schema_json (json, nullable) - data shape, meta, filters

### 6. Alter fe_ui_actions Table
**File**: `2025_10_15_162757_alter_fe_ui_actions_add_fields.php`  
**Status**: ✅ Ran

Added columns:
- payload_schema_json (json, nullable) - params validation
- policy_json (json, nullable) - who can trigger

## Models Created/Updated

### New Models

1. **FeUiModule** (`app/Models/FeUiModule.php`)
   - Fillable: key, title, description, manifest_json, version, hash, enabled, order, capabilities, permissions
   - Casts: manifest_json, capabilities, permissions (arrays), enabled (boolean)
   - Relationships: pages() hasMany
   - Scopes: enabled()
   - Auto-generates hash from key + version

2. **FeUiTheme** (`app/Models/FeUiTheme.php`)
   - Fillable: key, title, description, design_tokens_json, tailwind_overrides_json, variants_json, version, hash, enabled, is_default
   - Casts: design_tokens_json, tailwind_overrides_json, variants_json (arrays), enabled, is_default (booleans)
   - Scopes: enabled(), default()
   - Auto-generates hash from key + version

### Updated Models

3. **FeUiPage** (`app/Models/FeUiPage.php`)
   - Added fillable: route, meta_json, module_key, guards_json, enabled, layout_tree_json
   - Added casts: layout_tree_json, meta_json, guards_json (arrays), enabled (boolean)
   - Relationships: module() belongsTo FeUiModule
   - Scopes: enabled(), byRoute()

4. **FeUiComponent** (`app/Models/FeUiComponent.php`)
   - Added fillable: variant, schema_json, defaults_json, capabilities_json
   - Added casts: schema_json, defaults_json, capabilities_json (arrays)
   - Scopes: byKind(), byType()

5. **FeUiDatasource** (`app/Models/FeUiDatasource.php`)
   - Added fillable: default_params_json, capabilities_json, schema_json
   - Added casts: default_params_json, capabilities_json, schema_json (arrays)
   - Scopes: byAlias()

6. **FeUiAction** (`app/Models/FeUiAction.php`)
   - Added fillable: payload_schema_json, policy_json
   - Added casts: payload_schema_json, policy_json (arrays)
   - Scopes: byType()

## Seeder Created

**ModulesThemesSeeder** (`database/seeders/ModulesThemesSeeder.php`)

Creates:
1. **core.agents** module
   - Title: "Agent Management"
   - Pages: page.agent.table.modal
   - Datasources: Agent
   - Actions: action.agent.create
   - Capabilities: search, filter, export
   - Permissions: view_agents

2. **theme.default** theme
   - Title: "Default Theme"
   - Design tokens: radius (sm/md/lg), spacing, colors (primary/secondary), typography
   - Variants: light, dark
   - is_default: true

## Verification

### Tables Created
```sql
✅ fe_ui_modules (13 columns, 3 indexes)
✅ fe_ui_themes (12 columns, 3 indexes)
```

### Tables Altered
```sql
✅ fe_ui_pages (+6 columns, +3 indexes, 1 column renamed)
✅ fe_ui_components (+4 columns)
✅ fe_ui_datasources (+3 columns)
✅ fe_ui_actions (+2 columns)
```

### Seeded Data
```
✅ 1 module created (core.agents)
✅ 1 theme created (theme.default)
```

### Relationships Tested
```
✅ FeUiModule->pages() relationship
✅ FeUiPage->module() relationship
✅ FeUiModule::enabled() scope
✅ FeUiTheme::default() scope
```

## Key Design Decisions

1. **String Foreign Keys**: Using `module_key` (string) instead of integer ID for flexibility
2. **Hash Generation**: Auto-generated from key + version for deterministic change detection
3. **JSON Columns**: Extensive use of JSON for flexible schema evolution
4. **Column Rename**: `config` → `layout_tree_json` for clarity
5. **Nullable Columns**: Most new columns are nullable for backward compatibility

## Migration Notes

- Migration batch: 32
- No data loss on existing tables
- Column rename handled properly with migration rollback support
- All indexes created for query performance

## Next Steps

1. Update existing page configs to use new fields (module_key, guards_json)
2. Implement UI for managing modules and themes
3. Add validation for manifest_json and design_tokens_json structures
4. Create additional seeders for common modules
5. Implement theme switching functionality

## Related Tasks

- T-UIB-SPRINT2-01-TYPES (provides type system foundation)
- T-UIB-SPRINT2-02-REGISTRY (provides registry and kind field)
- T-UIB-SPRINT2-04-COMPONENTS (will use new component fields)
- T-UIB-SPRINT2-05-DATASOURCES (will leverage new datasource capabilities)
