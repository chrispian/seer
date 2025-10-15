# Task: Create New Database Schema (Modules, Themes, Table Updates)

**Task Code**: T-UIB-SPRINT2-03-SCHEMA  
**Sprint**: UI Builder v2 Sprint 2  
**Priority**: HIGH  
**Assigned To**: BE-Kernel Agent  
**Status**: TODO  
**Created**: 2025-10-15  
**Depends On**: T-UIB-SPRINT2-01-TYPES, T-UIB-SPRINT2-02-REGISTRY

## Objective

Design and implement the expanded schema for UI Builder v2, including new `fe_ui_modules` and `fe_ui_themes` tables, plus updates to existing tables for better organization and capabilities.

## New Tables to Create

### 1. fe_ui_modules

Modules are collections of related pages, components, and functionality (e.g., CRM, TTRPG Characters).

```sql
CREATE TABLE fe_ui_modules (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    key VARCHAR(100) UNIQUE NOT NULL COMMENT 'e.g., crm, ttrpg.characters',
    title VARCHAR(255) NOT NULL,
    description TEXT,
    manifest_json JSON NOT NULL COMMENT 'declares pages, required datasources, default actions',
    version VARCHAR(20) NOT NULL COMMENT 'semver',
    hash VARCHAR(64) NOT NULL,
    enabled BOOLEAN DEFAULT true,
    `order` INT DEFAULT 0,
    capabilities JSON COMMENT '["search","filter","export"]',
    permissions JSON COMMENT 'role/permission requirements',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_key (key),
    INDEX idx_enabled (enabled),
    INDEX idx_order (`order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Manifest JSON Structure**:
```json
{
  "pages": ["page.agent.table.modal", "page.agent.detail"],
  "datasources": ["Agent", "Agent.detail"],
  "actions": ["action.agent.create", "action.agent.update"],
  "navigation": {
    "label": "Agents",
    "icon": "users",
    "route": "/agents"
  }
}
```

### 2. fe_ui_themes

Themes define visual styling with design tokens, Tailwind overrides, and variants.

```sql
CREATE TABLE fe_ui_themes (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    key VARCHAR(100) UNIQUE NOT NULL COMMENT 'e.g., theme.default, theme.halloween2025',
    title VARCHAR(255) NOT NULL,
    description TEXT,
    design_tokens_json JSON NOT NULL COMMENT 'radius, spacing, colors, typography',
    tailwind_overrides_json JSON COMMENT 'custom Tailwind config',
    variants_json JSON COMMENT 'light/dark/accessible variants',
    version VARCHAR(20) NOT NULL,
    hash VARCHAR(64) NOT NULL,
    enabled BOOLEAN DEFAULT true,
    is_default BOOLEAN DEFAULT false,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_key (key),
    INDEX idx_enabled (enabled),
    INDEX idx_default (is_default)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Design Tokens JSON Structure**:
```json
{
  "radius": {
    "sm": "0.25rem",
    "md": "0.5rem",
    "lg": "0.75rem"
  },
  "spacing": {
    "unit": "4px"
  },
  "colors": {
    "primary": "#3b82f6",
    "secondary": "#64748b"
  },
  "typography": {
    "fontFamily": {
      "sans": ["Inter", "system-ui"]
    },
    "fontSize": {
      "base": "1rem"
    }
  }
}
```

## Existing Tables to Update

### 3. fe_ui_pages (Add/Update Columns)

```sql
ALTER TABLE fe_ui_pages
    ADD COLUMN route VARCHAR(255) NULL COMMENT 'optional; null for modal-only pages',
    ADD COLUMN meta_json JSON COMMENT 'SEO, breadcrumbs, etc.',
    CHANGE COLUMN config layout_tree_json JSON NOT NULL COMMENT 'top-level component tree',
    ADD COLUMN module_key VARCHAR(100) NULL COMMENT 'FK to fe_ui_modules.key',
    ADD COLUMN guards_json JSON COMMENT 'auth/roles requirements',
    ADD COLUMN enabled BOOLEAN DEFAULT true,
    ADD INDEX idx_route (route),
    ADD INDEX idx_module_key (module_key),
    ADD INDEX idx_enabled (enabled);
```

### 4. fe_ui_components (Add Columns)

Note: The `kind` column is added by T-UIB-SPRINT2-02-REGISTRY task. This task adds additional fields:

```sql
ALTER TABLE fe_ui_components
    ADD COLUMN variant VARCHAR(50) COMMENT 'e.g., standard, dense, modal, drawer',
    ADD COLUMN schema_json JSON COMMENT 'props/slots contract',
    ADD COLUMN defaults_json JSON COMMENT 'sane default values',
    ADD COLUMN capabilities_json JSON COMMENT 'searchable/sortable/filterable flags';
```

### 5. fe_ui_datasources (Add Columns)

```sql
ALTER TABLE fe_ui_datasources
    ADD COLUMN default_params_json JSON COMMENT 'default query parameters',
    ADD COLUMN capabilities_json JSON COMMENT '{"supports": ["list","detail","search","paginate","aggregate"]}',
    ADD COLUMN schema_json JSON COMMENT 'shape of data, meta, filters, sorts';
```

### 6. fe_ui_actions (Add Columns)

```sql
ALTER TABLE fe_ui_actions
    ADD COLUMN payload_schema_json JSON COMMENT 'params shape/validation',
    ADD COLUMN policy_json JSON COMMENT 'who can trigger (roles/permissions)';
```

## Models to Create/Update

### New Models

**FeUiModule.php**
```php
namespace App\Models;

class FeUiModule extends Model
{
    protected $table = 'fe_ui_modules';
    
    protected $fillable = [
        'key', 'title', 'description', 'manifest_json',
        'version', 'hash', 'enabled', 'order',
        'capabilities', 'permissions'
    ];
    
    protected $casts = [
        'manifest_json' => 'array',
        'capabilities' => 'array',
        'permissions' => 'array',
        'enabled' => 'boolean',
    ];
    
    public function pages()
    {
        return $this->hasMany(FeUiPage::class, 'module_key', 'key');
    }
}
```

**FeUiTheme.php**
```php
namespace App\Models;

class FeUiTheme extends Model
{
    protected $table = 'fe_ui_themes';
    
    protected $fillable = [
        'key', 'title', 'description',
        'design_tokens_json', 'tailwind_overrides_json', 'variants_json',
        'version', 'hash', 'enabled', 'is_default'
    ];
    
    protected $casts = [
        'design_tokens_json' => 'array',
        'tailwind_overrides_json' => 'array',
        'variants_json' => 'array',
        'enabled' => 'boolean',
        'is_default' => 'boolean',
    ];
}
```

### Update Existing Models

Update fillable/casts arrays in:
- `FeUiPage` - add route, meta_json, module_key, guards_json, enabled
- `FeUiComponent` - add variant, schema_json, defaults_json, capabilities_json
- `FeUiDatasource` - add default_params_json, capabilities_json, schema_json
- `FeUiAction` - add payload_schema_json, policy_json

## Seeders to Create

**ModulesThemesSeeder.php**
```php
namespace Database\Seeders;

class ModulesThemesSeeder extends Seeder
{
    public function run()
    {
        // Create default module
        FeUiModule::create([
            'key' => 'core.agents',
            'title' => 'Agent Management',
            'description' => 'Manage AI agents, profiles, and configurations',
            'manifest_json' => [
                'pages' => ['page.agent.table.modal'],
                'datasources' => ['Agent'],
                'actions' => ['action.agent.create'],
            ],
            'version' => '1.0.0',
            'hash' => hash('sha256', 'core.agents.1.0.0'),
            'enabled' => true,
            'order' => 10,
            'capabilities' => ['search', 'filter', 'export'],
            'permissions' => ['view_agents'],
        ]);
        
        // Create default theme
        FeUiTheme::create([
            'key' => 'theme.default',
            'title' => 'Default Theme',
            'description' => 'Standard Fragments Engine theme',
            'design_tokens_json' => [
                'radius' => ['sm' => '0.25rem', 'md' => '0.5rem', 'lg' => '0.75rem'],
                'colors' => ['primary' => '#3b82f6', 'secondary' => '#64748b'],
            ],
            'variants_json' => ['light', 'dark'],
            'version' => '1.0.0',
            'hash' => hash('sha256', 'theme.default.1.0.0'),
            'enabled' => true,
            'is_default' => true,
        ]);
    }
}
```

## Implementation Steps

1. **Create migration files** (use proper timestamp sequence)
   - `xxxx_create_fe_ui_modules_table.php`
   - `xxxx_create_fe_ui_themes_table.php`
   - `xxxx_alter_fe_ui_pages_add_fields.php`
   - `xxxx_alter_fe_ui_components_add_fields.php`
   - `xxxx_alter_fe_ui_datasources_add_fields.php`
   - `xxxx_alter_fe_ui_actions_add_fields.php`

2. **Create/update models** with proper fillable, casts, and relationships

3. **Create seeder** with sample modules and themes

4. **Update existing page configs** to use new fields:
   - Add `module_key` to agent page
   - Add example `guards_json` for auth

5. **Test migrations**:
   ```bash
   php artisan migrate
   php artisan db:seed --class=ModulesThemesSeeder
   ```

6. **Verify data integrity**:
   - Check foreign key relationships work
   - Verify JSON columns parse correctly
   - Test queries on new indexes

## Acceptance Criteria

- [ ] `fe_ui_modules` table created with all columns and indexes
- [ ] `fe_ui_themes` table created with all columns and indexes
- [ ] `fe_ui_pages` updated with new columns (route, meta_json, module_key, guards_json, enabled)
- [ ] `fe_ui_components` updated with new columns (variant, schema_json, defaults_json, capabilities_json)
- [ ] `fe_ui_datasources` updated with new columns
- [ ] `fe_ui_actions` updated with new columns
- [ ] FeUiModule model created with relationships
- [ ] FeUiTheme model created
- [ ] Existing models updated with new fillable/casts
- [ ] ModulesThemesSeeder creates sample data
- [ ] All migrations run successfully
- [ ] No data loss on existing tables
- [ ] Indexes improve query performance
- [ ] Documentation created for new schema

## Dependencies

- Requires T-UIB-SPRINT2-02-REGISTRY completed (for `kind` column)
- Should run after Types and Registry systems are in place

## Estimated Time

3-4 hours

## Notes

- **module_key as string FK**: Using key (string) instead of id (int) for flexibility
- **Hash versioning**: Generate hash from key + version for deterministic change detection
- **Manifest structure**: Keep flexible; adjust as modules evolve
- **Design tokens**: Follow Tailwind conventions for easy integration
- **Guards**: JSON format allows complex auth rules (roles, permissions, custom logic)

## Related Tasks

- T-UIB-SPRINT2-01-TYPES (provides type system foundation)
- T-UIB-SPRINT2-02-REGISTRY (provides registry and kind field)
- T-UIB-SPRINT2-04-COMPONENTS (will use new component fields)
- T-UIB-SPRINT2-05-DATASOURCES (will leverage new datasource capabilities)
