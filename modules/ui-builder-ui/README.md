# UI Builder UI - Management Interface

A self-managing UI for the UI Builder package - use the UI Builder to build UIs for the UI Builder.

## Overview

This module provides a complete management interface for the `@hollis-labs/ui-builder` package. It demonstrates the power of the configuration-based UI system by using it to manage itself - creating a "meta UI" where users can create, edit, and manage pages and components without writing code or editing JSON.

## Features

### ✅ MVP - Currently Implemented

1. **Pages Management** (`page.ui-builder.pages.list`)
   - Browse all UI pages in the system
   - View page details (key, route, module, version, status)
   - Create new pages with form-based inputs
   - Search and filter pages
   - Toggle enabled/disabled status

2. **Components Management** (`page.ui-builder.components.list`)
   - Browse all reusable UI components
   - View component details (key, type, kind, variant)
   - Create new components
   - Filter by component kind (primitive, composite, layout, advanced)

3. **Registry Browser** (`page.ui-builder.registry.browser`)
   - Explore the component type registry
   - View component metadata and schemas
   - Filter by type (component, page, datasource, etc.)
   - See active/published status

4. **Data Layer Integration**
   - FeType definitions for all fe_ui_* tables
   - Full CRUD support through generic datasources
   - Automatic API endpoint generation

## Installation

### 1. Run the Seeder

```bash
php artisan db:seed --class=UiBuilderUiSeeder
```

This will create:
- 4 FeType definitions (UiPage, UiComponent, UiRegistry, UiModule)
- 4 Datasource entries (fe_ui_datasources table)
- 3 page configurations
- 1 module definition (core.ui-builder)

### 2. Verify Installation

```bash
php artisan tinker

# Check FeTypes
App\Models\FeType::whereIn('alias', ['UiPage', 'UiComponent', 'UiRegistry', 'UiModule'])->count()
// Should return: 4

# Check Datasources
HollisLabs\UiBuilder\Models\Datasource::whereIn('alias', ['UiPage', 'UiComponent', 'UiRegistry', 'UiModule'])->count()
// Should return: 4

# Check Pages
HollisLabs\UiBuilder\Models\Page::where('module_key', 'core.ui-builder')->count()
// Should return: 3

# Check Module
HollisLabs\UiBuilder\Models\Module::where('key', 'core.ui-builder')->first()
// Should return the core.ui-builder module
```

### 3. Access the UI

The pages are modal-based and can be accessed through the UI Builder's navigation system:

- **Pages Manager**: Open `page.ui-builder.pages.list` modal
- **Components Manager**: Open `page.ui-builder.components.list` modal
- **Registry Browser**: Open `page.ui-builder.registry.browser` modal

## Architecture

### Design Principles

1. **No Code Required**: Everything is configuration-based
2. **Form-Based Input**: Users never see JSON, only forms and dropdowns
3. **Additive Only**: Seeders use `updateOrCreate()` - safe to re-run
4. **Self-Contained**: No modifications to existing code
5. **Progressive Disclosure**: Simple by default, complexity when needed

### Database Structure

```
fe_ui_pages          → UiPage FeType
fe_ui_components     → UiComponent FeType
fe_ui_registry       → UiRegistry FeType
fe_ui_modules        → UiModule FeType
```

Each table is exposed through the Type system, enabling:
- Generic API endpoints (`/api/ui/datasources/{TypeAlias}`)
- Search, filter, sort, pagination
- CRUD operations through the UI

### Page Configuration Pattern

All pages follow this structure:

```
Page (overlay: modal)
  └── Layout (type: rows)
      ├── SearchBar (for filtering)
      └── DataTable (with columns, actions, toolbar)
          ├── Column definitions
          ├── Row actions (view details modal)
          └── Toolbar actions (create new)
```

## File Structure

```
modules/ui-builder-ui/
├── README.md                           # This file
├── AGENT.md                            # Original task description
├── CONTEXT.md                          # Architecture discoveries & decisions
├── TASKS.md                            # Task tracking
├── MVP-SUMMARY.md                      # Executive summary
├── seeders/
│   ├── UiBuilderUiSeeder.php          # Main orchestrator seeder
│   ├── UiBuilderTypesSeeder.php       # Creates FeType definitions
│   ├── UiBuilderDatasourcesSeeder.php # Creates Datasource entries
│   ├── UiBuilderPagesSeeder.php       # Creates page configurations
│   └── UiBuilderModuleSeeder.php      # Creates module definition
└── docs/
    └── (future: component schemas, user guides)
```

## Usage Examples

### Creating a New Page

1. Open the Pages Manager modal
2. Click "New Page" button
3. Fill out the form:
   - **Page Key**: `page.mymodule.users.list`
   - **Title**: `User Management`
   - **Display Type**: `Modal`
   - **Route**: `/admin/users` (optional)
   - **Module**: Select from dropdown
   - **Enabled**: Check to activate
4. Click "Create Page"
5. Page is created and appears in the list

### Viewing Page Details

1. In the Pages Manager, click any row
2. Modal opens showing full page details:
   - ID, key, route, module
   - Version and hash
   - Created/updated timestamps

### Browsing Component Types

1. Open the Registry Browser
2. Filter by type (component, page, datasource)
3. Search by name or slug
4. Click any entry to see metadata and schema details

## Development Notes

### Adding New Fields

To add fields to the create/edit forms:

1. Update the corresponding page configuration in `UiBuilderPagesSeeder.php`
2. Add the field to the `fields` array in the toolbar action
3. Re-run the seeder: `php artisan db:seed --class=UiBuilderUiSeeder`

### Creating Additional Pages

1. Add a new method to `UiBuilderPagesSeeder.php`
2. Call it from the `run()` method
3. Follow the existing pattern (modal + rows layout + search + data-table)
4. Update the module manifest in `UiBuilderModuleSeeder.php`

### Extending the Type System

To add more UI Builder tables:

1. Create a new method in `UiBuilderTypesSeeder.php`
2. Define the FeType with model, table, capabilities
3. Add field definitions for each column
4. Call the method from `run()`

## Roadmap

### Phase 2: Enhanced Page Creation
- [ ] Multi-step wizard for page creation
- [ ] Visual layout selection
- [ ] Component drag-and-drop builder
- [ ] Live preview

### Phase 3: Component Library
- [ ] Pre-built component templates
- [ ] Component cloning
- [ ] Component composition tools
- [ ] Schema-based property editors

### Phase 4: Advanced Features
- [ ] Module management UI
- [ ] Theme editor
- [ ] DataSource mapping UI
- [ ] Action configuration UI
- [ ] Permission management

### Phase 5: Developer Tools
- [ ] Export page as JSON
- [ ] Import page from JSON
- [ ] Version history viewer
- [ ] Dependency graph
- [ ] Usage analytics

## Contributing

This module is designed to eventually ship with the `@hollis-labs/ui-builder` package. When contributing:

1. Follow the no-code principle - only configuration changes
2. Use `updateOrCreate()` in seeders to maintain idempotency
3. Add comprehensive field validation
4. Document all new features in this README
5. Update CONTEXT.md with architectural decisions

## Support

For issues or questions:
1. Check CONTEXT.md for architectural background
2. Review existing page configurations in seeders
3. Examine the UI Builder package documentation
4. Review the agent/model table examples for patterns

## License

Same license as the main project.
