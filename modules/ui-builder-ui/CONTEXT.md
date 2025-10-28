# UI Builder UI - Context & Discoveries

## Overview
This module creates a UI for managing the UI Builder itself - a "meta UI" that uses the UI Builder package to create interfaces for building more UIs. The goal is to provide a cohesive, form-based system for creating and managing pages and components without requiring users to edit JSON directly.

## Architecture Discoveries

### UI Builder Package Structure
The `@hollis-labs/ui-builder` package is a 100% configuration-based system:
- **Database-Driven**: All UI definitions stored in `fe_ui_*` tables
- **JSON Configs**: Component and page configurations stored as JSON in database
- **Auto-Caching**: Generates JSON cache files in `storage/ui-builder/cache/`
- **Version Control**: Automatic hashing and versioning on save
- **No Code Changes**: New UIs created entirely through database inserts

### Database Tables

#### Core Tables
1. **fe_ui_pages**: Page configurations
   - `key` (unique identifier, e.g., "page.agent.table.modal")
   - `layout_tree_json` (renamed from `config`, contains full page layout)
   - `route` (optional, null for modal-only pages)
   - `module_key` (FK to fe_ui_modules)
   - `guards_json` (auth/role requirements)
   - `enabled` (boolean)
   - `hash`, `version` (auto-managed)

2. **fe_ui_components**: Reusable component definitions
   - `key` (unique identifier)
   - `type` (component type from registry)
   - `kind` (primitive, composite, layout, advanced)
   - `config` (component-specific configuration JSON)
   - `variant` (optional style variant)
   - `schema_json`, `defaults_json`, `capabilities_json`
   - `hash`, `version`

3. **fe_ui_registry**: Component type registry (metadata)
   - `type` (component, page, datasource, action, module)
   - `name`, `slug`, `description`
   - `version`
   - `reference_type`, `reference_id` (polymorphic)
   - `metadata` (JSON with kind, category, etc.)
   - `is_active`, `published_at`

4. **fe_ui_datasources**: Model-to-API mappings
   - Maps backend models to frontend data sources
   - Used by components like data-table

5. **fe_ui_modules**: Module grouping
   - Groups pages, components, and datasources
   - Defines navigation structure

6. **fe_ui_actions**: Action handlers
   - Defines available actions (modal, navigate, command, etc.)

7. **fe_ui_themes**: Theme configurations
   - Design tokens, Tailwind overrides
   - Variants (light, dark)

### Component Type System

The UI Builder supports multiple component categories:

#### Primitives
- **Actions**: button, button.icon, button.text
- **Inputs**: input, input.text, input.email, input.password, textarea, select
- **Display**: label, badge, avatar, skeleton, spinner, separator, kbd, typography.*
- **Forms**: checkbox, radio-group, switch, slider, field
- **Feedback**: alert, progress, toast, empty

#### Layouts
- **Containers**: card, scroll-area, aspect-ratio
- **Organization**: rows, columns, collapsible, accordion, tabs, resizable

#### Navigation
- **Core**: breadcrumb, pagination, sidebar

#### Composites
- **Overlays**: dialog, popover, tooltip, sheet, drawer
- **Menus**: navigation-menu, command, combobox, dropdown-menu, context-menu, menubar, hover-card
- **Search**: search.bar

#### Advanced
- **Data**: data-table (with sorting, filtering, pagination)
- **Visualization**: chart (bar, line, pie, area, donut)
- **Media**: carousel
- **Notifications**: sonner

#### Forms
- **Groups**: form, input-group, button-group, toggle-group
- **Specialized**: input-otp, date-picker, calendar, toggle, item

### Existing Examples

Two working examples demonstrate the pattern:
1. **page.agent.table.modal**: Agent management with search + data table
2. **page.model.table.modal**: AI Model management with search + data table

Both follow this structure:
```
Page (overlay: modal)
  └── Layout (type: rows)
      ├── SearchBar component
      └── DataTable component
          ├── columns configuration
          ├── rowAction (modal for details)
          └── toolbar (with add button)
```

## Design Philosophy

### User Experience Goals
1. **No JSON Editing**: Users interact with forms, dropdowns, checkboxes
2. **Progressive Disclosure**: Start simple, reveal complexity as needed
3. **Inline Actions**: Minimize page loads, use modals/sheets
4. **Visual Feedback**: Show previews where possible
5. **Guided Flow**: Wizard-style component/page creation

### Component Creation Flow
```
1. Select Page Type (modal, sheet, full-page)
2. Set Basic Info (key, title, module)
3. Choose Layout (rows, columns, card, etc.)
4. Add Components
   └── For each component:
       a. Select Component Type (from registry)
       b. Configure Properties (form based on schema)
       c. Add Actions (click, submit, etc.)
       d. Set Data Sources (if applicable)
5. Preview & Save
```

### Technical Constraints
- **No Code Modifications**: Only database inserts/updates
- **No Hardcoding**: All configuration comes from database
- **Additive Only**: Seeders only add new data, never modify existing
- **Schema Validation**: Validate against component schemas (MVP: required fields only)
- **Module Scoping**: All pages belong to `core.ui-builder` module

## Implementation Strategy

### MVP Scope
Focus on **Pages Management** first:
1. List all pages (data-table)
2. Create new page (multi-step form)
3. Edit page configuration (form-based)
4. Toggle page enabled/disabled
5. Delete page (with confirmation)

### Future Phases
- Component Library Management
- Registry Browser & Component Discovery
- Module Management
- Theme Configuration
- DataSource Mapping UI
- Visual Page Builder (drag-drop)

## Key Technical Decisions

### DataSource Strategy
Two-layer approach for data access:

1. **FeType Layer** (Schema Definition):
   - Create FeType entries for fe_ui_* tables
   - Defines field metadata (searchable, sortable, filterable)
   - Used by forms and generic CRUD operations

2. **Datasource Layer** (Data Resolution):
   - Create Datasource entries in fe_ui_datasources table
   - Maps alias (e.g., "UiPage") to model class and resolver
   - Defines capabilities, default params, and schema transformations
   - Required for data-table components to fetch data

Mappings:
- `UiPage` → HollisLabs\UiBuilder\Models\Page
- `UiComponent` → HollisLabs\UiBuilder\Models\Component
- `UiRegistry` → HollisLabs\UiBuilder\Models\Registry
- `UiModule` → HollisLabs\UiBuilder\Models\Module

All use `DataSourceResolver` class for generic query resolution.

### Seeder Architecture
- **UiBuilderUiSeeder**: Main orchestrator that calls all sub-seeders
- **UiBuilderTypesSeeder**: Create FeType entries for fe_ui_* tables
- **UiBuilderDatasourcesSeeder**: Create Datasource entries in fe_ui_datasources
- **UiBuilderPagesSeeder**: Seeds page definitions (page.ui-builder.pages.list, etc.)
- **UiBuilderModuleSeeder**: Seeds core.ui-builder module

Order matters: Types → Datasources → Pages → Module

### File Organization
```
modules/ui-builder-ui/
├── AGENT.md          # Original task description
├── CONTEXT.md        # This file - discoveries & decisions
├── TASKS.md          # Task tracking
├── seeders/          # All seeder files
│   ├── UiBuilderUiSeeder.php
│   ├── TypesSeeder.php
│   ├── PagesSeeder.php
│   └── ModuleSeeder.php
└── docs/             # Additional documentation
    └── component-schemas.md  # Component property schemas
```

## Implementation Notes

### Critical Discovery: Dual Layer Requirement
Initial implementation only created FeTypes, which defined the schema but not the data resolution layer. The UI showed "No data available" because:

1. **FeTypes** define the schema (fields, types, capabilities) but don't resolve queries
2. **Datasources** (fe_ui_datasources table) tell the UI Builder how to fetch data
3. Data-table components use `/api/ui/datasources/{alias}` endpoints
4. Without Datasource entries, the API has no resolver for the queries

**Solution**: Created `UiBuilderDatasourcesSeeder` to populate fe_ui_datasources with:
- Model class mappings
- Resolver class (DataSourceResolver)
- Capabilities (searchable, filterable, sortable fields)
- Schema transformations
- Default parameters (sorting, relationships)

This two-layer approach is essential:
- FeTypes = Schema definition for forms/validation
- Datasources = Query resolution for data-tables/APIs

## Next Steps
1. ✅ Create FeType definitions for fe_ui_* tables
2. ✅ Create Datasource entries for fe_ui_* tables
3. ✅ Build page.ui-builder.pages.list (pages management modal)
4. Test data loading in browser (verify "No data available" is fixed)
5. Test create → list flow
6. Document component schemas for future form generation
