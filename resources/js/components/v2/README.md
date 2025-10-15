# UI Builder v2 - Config-Driven Renderer

A React-based config-driven renderer that dynamically renders pages from backend JSON configurations using Shadcn UI components.

## Overview

The v2 renderer system allows you to build entire UIs from JSON config files without writing component code. It handles:
- Dynamic component rendering from config
- Data fetching via datasources
- Action execution (commands, navigation)
- Slot-based data binding between components

## Architecture

### Core Infrastructure

**ComponentRegistry** - Maps component types to React components
- Extensible registration system
- Type-safe lookups
- Supports custom components

**PageRenderer** - Main orchestrator
- Fetches page config from `/api/v2/ui/pages/{key}`
- Renders components based on registry
- Handles loading/error states

**ActionDispatcher** - Executes actions
- Command type: POST `/api/v2/ui/action` with command name
- Navigate type: Handles route changes
- Context interpolation for dynamic params

**SlotBinder** - Connects data flows
- Subscribe/publish pattern
- Binds datasource results to target components
- Supports inline and modal targets

### Hooks

**usePageConfig(key)** - Fetch page configuration
```tsx
const { config, loading, error, refetch } = usePageConfig('page.agent.table.modal')
```

**useDataSource(options)** - Query datasources
```tsx
const { data, meta, loading, error, refetch, fetch } = useDataSource({
  dataSource: 'Agent',
  search: searchTerm,
  filters: { status: ['active'] },
  sort: { field: 'name', direction: 'asc' },
  pagination: { page: 1, perPage: 20 }
})
```

**useAction()** - Execute actions
```tsx
const { execute, loading, error } = useAction()
await execute(action, { row: { id: '123' } })
```

### Components

#### Primitives

**TableComponent** - Data table with actions
- Datasource integration
- Sortable/filterable columns
- Row click actions
- Toolbar buttons

**SearchBarComponent** - Debounced search input
- Updates target components via SlotBinder
- 300ms debounce

**ButtonIconComponent** - Action button with icon
- Supports common icons (plus, edit, trash, eye, settings)
- Executes actions on click

#### Layouts

**ModalLayout** - Dialog overlay
- Shadcn Dialog wrapper
- Title and close button

**RowsLayout** - Vertical flex layout

**ColumnsLayout** - Horizontal flex layout

## Usage

### Basic Example

```tsx
import { V2ShellPage } from '@/components/v2'

export function AgentsPage() {
  return <V2ShellPage pageKey="page.agent.table.modal" />
}
```

### Registering Custom Components

```tsx
import { componentRegistry } from '@/components/v2'
import { MyCustomComponent } from './MyCustomComponent'

componentRegistry.register('my.custom', MyCustomComponent)
```

### Page Config Example

```json
{
  "id": "page.agent.table.modal",
  "overlay": "modal",
  "title": "Agents",
  "components": [
    {
      "id": "component.search.bar.agent",
      "type": "search.bar",
      "dataSource": "Agent",
      "result": { 
        "target": "component.table.agent", 
        "open": "inline" 
      }
    },
    {
      "id": "component.table.agent",
      "type": "table",
      "dataSource": "Agent",
      "columns": [
        { "key": "name", "label": "Name", "sortable": true },
        { "key": "status", "label": "Status", "filterable": true }
      ],
      "rowAction": {
        "type": "command",
        "command": "/orch-agent",
        "params": { "id": "{{row.id}}" }
      },
      "toolbar": [
        {
          "id": "btn.add",
          "type": "button.icon",
          "props": { "icon": "plus", "label": "New Agent" },
          "actions": {
            "click": { "type": "command", "command": "/orch-agent-new" }
          }
        }
      ]
    }
  ]
}
```

## API Endpoints

The renderer expects these backend endpoints:

### GET /api/v2/ui/pages/{key}
Returns page configuration JSON

### POST /api/v2/ui/datasource/{alias}/query
Request:
```json
{
  "dataSource": "Agent",
  "filters": { "status": ["active"] },
  "search": "john",
  "sort": { "field": "name", "direction": "asc" },
  "pagination": { "page": 1, "perPage": 20 }
}
```

Response:
```json
{
  "data": [...],
  "meta": {
    "total": 100,
    "page": 1,
    "perPage": 20,
    "lastPage": 5
  }
}
```

### POST /api/v2/ui/action
Request:
```json
{
  "type": "command",
  "command": "/orch-agent",
  "params": { "id": "123" }
}
```

Response:
```json
{
  "success": true,
  "message": "Agent details opened",
  "data": {...},
  "redirect": "/agents/123"
}
```

## Design Patterns

### Config-Driven
Everything is driven by JSON config from the backend. No hardcoded UI logic.

### Component Registry
Easy to extend with custom components. Just implement the `RendererProps` interface.

### Slot-Based Binding
Components communicate via SlotBinder. Search updates table, modals update parent views.

### Action Dispatcher
All actions go through a single router. Backend decides what happens.

## TypeScript Types

All types are exported from `@/components/v2/types`:
- `PageConfig`
- `ComponentConfig`
- `ActionConfig`
- `DataSourceQuery`
- `DataSourceResult`
- `ActionResult`

## Testing

Currently awaiting backend API implementation for integration testing.

Manual testing checklist:
- [ ] Modal renders with title
- [ ] Table displays data from datasource
- [ ] Search updates table results
- [ ] Row click triggers action
- [ ] Toolbar buttons execute actions
- [ ] Loading states display
- [ ] Error states display
- [ ] Toast notifications work
- [ ] Responsive on mobile
- [ ] Responsive on desktop

## Future Enhancements

- Pagination UI for tables
- Filter UI for filterable columns
- Sort UI for sortable columns
- Error boundaries per component
- Optimistic updates
- Form components
- Validation
- Field-level permissions
- Component animation/transitions
