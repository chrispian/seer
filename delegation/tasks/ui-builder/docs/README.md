# Agents Builder PoC (Fragments Engine v2)

## Overview
A prototype for config-driven UI builder rendering a Single Resource (Agents) modal using Shadcn components.

### Includes
- Backend Kernel (config persistence, resolver, actions)
- Frontend Renderer (Shadcn + JSON config)
- Integration (v2 route/shell)
- Seeds (scaffold commands + demo config)
- Profile Ingestion (future)

---

## Quickstart

### 1. Run Migrations and Seed Demo Data

```bash
php artisan migrate
php artisan db:seed --class=V2UiBuilderSeeder
```

### 2. Access the Demo Agents Modal

Visit in your browser:
```
http://your-app.test/v2/pages/page.agent.table.modal
```

The demo page includes:
- Search bar for filtering agents
- Table with sortable/filterable columns
- Row click action to view agent details (`/orch-agent`)
- Toolbar button to create new agent (`/orch-agent-new`)

### 3. Scaffold a New Page

Create a new UI Builder page for any model:

```bash
php artisan fe:make:ui-page ProjectsModal --datasource=Project --with=table,search
```

This generates:
- JSON config file at `resources/schemas/ui-builder/pages/projects-modal.json`
- Database entry in `fe_ui_pages` table with computed hash and version
- Route accessible at `/v2/pages/page.projects-modal`

---

## Creating Pages

### Command Signature

```bash
php artisan fe:make:ui-page {name} {--datasource=} {--with=} {--overlay=}
```

### Parameters

- **name** (required): Page name (e.g., `AgentsModal`, `ProjectsList`)
- **--datasource** (required): Model name (e.g., `Agent`, `Project`, `Task`)
- **--with** (optional): Comma-separated components (default: `table`)
  - `table`: Data table with columns
  - `search`: Search bar
  - `filters`: Enable filterable columns
- **--overlay** (optional): Display mode (default: `modal`)
  - `modal`: Modal overlay
  - `drawer`: Side drawer
  - `page`: Full page

### Examples

**Simple table-only page:**
```bash
php artisan fe:make:ui-page TasksList --datasource=Task --with=table
```

**Table with search and filters:**
```bash
php artisan fe:make:ui-page ProjectsModal --datasource=Project --with=table,search,filters
```

**Full page layout:**
```bash
php artisan fe:make:ui-page UsersPage --datasource=User --with=table,search --overlay=page
```

### What Gets Generated

The scaffold command creates a complete page configuration:

```json
{
  "id": "page.projects-modal",
  "overlay": "modal",
  "title": "Projects Modal",
  "components": [
    {
      "id": "component.search.bar.projects-modal",
      "type": "search.bar",
      "dataSource": "Project",
      "resolver": "DataSourceResolver::class",
      "submit": false,
      "result": {
        "target": "component.table.projects-modal",
        "open": "inline"
      }
    },
    {
      "id": "component.table.projects-modal",
      "type": "table",
      "dataSource": "Project",
      "columns": [...],
      "rowAction": {...},
      "toolbar": [...]
    }
  ]
}
```

---

## Config Structure

### Page Config

Top-level page configuration defines the container and layout:

```json
{
  "id": "page.unique-key",
  "overlay": "modal|drawer|page",
  "title": "Display Title",
  "components": [...]
}
```

### Component Types

**Search Bar (`search.bar`)**
```json
{
  "id": "component.search.bar.agents",
  "type": "search.bar",
  "dataSource": "Agent",
  "resolver": "DataSourceResolver::class",
  "submit": false,
  "result": {
    "target": "component.table.agents",
    "open": "inline"
  }
}
```

**Table (`table`)**
```json
{
  "id": "component.table.agents",
  "type": "table",
  "dataSource": "Agent",
  "columns": [
    { "key": "name", "label": "Name", "sortable": true },
    { "key": "status", "label": "Status", "filterable": true }
  ],
  "rowAction": {
    "type": "command",
    "command": "/agent",
    "params": { "id": "{{row.id}}" }
  },
  "toolbar": [...]
}
```

**Button (`button.icon`)**
```json
{
  "id": "component.button.icon.add",
  "type": "button.icon",
  "props": {
    "icon": "plus",
    "label": "New Item"
  },
  "actions": {
    "click": {
      "type": "command",
      "command": "/create-item"
    }
  }
}
```

### DataSource

DataSources are resolved by the backend. The `dataSource` field references an Eloquent model:

```json
"dataSource": "Agent"
```

Backend resolvers should:
- Load model data
- Apply search/filter/sort
- Return formatted JSON

### Action Handlers

Actions trigger navigation or commands:

**Command Action (opens modal/component):**
```json
{
  "type": "command",
  "command": "/orch-agent",
  "params": { "id": "{{row.id}}" }
}
```

**Navigate Action (route change):**
```json
{
  "type": "navigate",
  "path": "/agents/{{row.id}}"
}
```

---

## Adding Components

### 1. Define Component in Config

Add to the `components` array in your page JSON:

```json
{
  "id": "component.filters.agents",
  "type": "filters",
  "dataSource": "Agent",
  "fields": [
    { "key": "status", "type": "select", "options": ["active", "inactive"] },
    { "key": "role", "type": "select", "options": ["admin", "user"] }
  ]
}
```

### 2. Update Frontend Renderer

Implement the component type in your React renderer:

```tsx
// resources/js/islands/v2/components/FilterComponent.tsx
export function FilterComponent({ config }) {
  return (
    <div>
      {config.fields.map(field => (
        <Select key={field.key} {...field} />
      ))}
    </div>
  );
}
```

Register in the main renderer:

```tsx
// resources/js/islands/v2/PageRenderer.tsx
const componentMap = {
  'search.bar': SearchBarComponent,
  'table': TableComponent,
  'button.icon': ButtonComponent,
  'filters': FilterComponent, // Add here
};
```

### 3. Add Backend Resolver (if needed)

If your component needs data resolution, implement a resolver method:

```php
// app/Services/DataSourceResolver.php
public function resolveFilters(string $dataSource, array $config): array
{
    // Return filter options dynamically
}
```

---

## Troubleshooting

### Page Not Found

**Problem:** `/v2/pages/page.my-page` returns 404

**Solutions:**
- Check the page exists in database: `php artisan tinker` → `FeUiPage::where('key', 'page.my-page')->first()`
- Verify route is registered in `routes/web.php` or `routes/api.php`
- Clear route cache: `php artisan route:clear`

### Invalid JSON Config

**Problem:** Page loads but renders blank

**Solutions:**
- Validate JSON syntax: `jq . resources/schemas/ui-builder/pages/my-page.json`
- Check browser console for parser errors
- Verify component IDs are unique across the page
- Ensure `dataSource` references an existing model

### Command Not Found

**Problem:** Clicking button/row triggers error "Command not found"

**Solutions:**
- Ensure command is registered in `app/Services/CommandRegistry.php`
- Check command class exists in `app/Commands/`
- Verify command slug matches the config (e.g., `/orch-agent` → `'orch-agent' => OrchAgentCommand::class`)

### DataSource Not Resolving

**Problem:** Table shows "No data" despite database records

**Solutions:**
- Check DataSourceResolver implementation
- Verify model name matches exactly (case-sensitive)
- Test model query manually: `Agent::all()`
- Check API endpoint `/api/v2/ui/datasource/{model}` returns data

### Columns Not Displaying

**Problem:** Table renders but columns are empty

**Solutions:**
- Verify column `key` matches model attribute names
- Check model has accessor or database column for each key
- Ensure model casts are defined for JSON fields
- Test data shape: `Agent::first()->toArray()`

---

## Next Steps

- Implement Profile ingestion job
- Expand to multi-resource layouts
- Add more component types (forms, charts, tabs)
- Build visual config editor
