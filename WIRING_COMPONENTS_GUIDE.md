# How to Wire Up Components in the V2 Config-Driven System

## Overview

This guide explains how to make Sprint 2 components work with the config-driven UI system. Components need to accept config from the database and work with dataSource, actions, and inter-component communication.

## Component Architecture

### 1. Component Config Interface

All components receive a `config` prop that extends `ComponentConfig`:

```typescript
interface ComponentConfig {
  id: string;              // Unique component identifier
  type: string;            // Component type (e.g., 'data-table', 'search.bar')
  props?: Record<string, any>;  // Component-specific properties
  actions?: Record<string, ActionConfig>;  // Action handlers
  children?: ComponentConfig[];  // Nested components
}
```

### 2. DataSource Integration

Components that fetch data should:

**Accept dataSource in props:**
```typescript
interface MyComponentConfig extends ComponentConfig {
  props: {
    dataSource?: string;  // Datasource alias (e.g., 'Agent', 'Model')
    // ... other props
  }
}
```

**Fetch from the API:**
```typescript
const [data, setData] = useState<any[]>([]);

useEffect(() => {
  if (props.dataSource) {
    fetch(`/api/v2/ui/datasource/${props.dataSource}/query`)
      .then(res => res.json())
      .then(result => setData(result.data || []))
  }
}, [props.dataSource]);
```

**Support search/filters:**
```typescript
const params = new URLSearchParams();
if (searchTerm) params.append('search', searchTerm);
if (filters) params.append('filters', JSON.stringify(filters));

fetch(`/api/v2/ui/datasource/${dataSource}/query?${params}`)
```

### 3. Action System

Components handle user interactions through actions:

**Action Config Type:**
```typescript
interface ActionConfig {
  type: 'command' | 'navigate' | 'emit' | 'http' | 'modal';
  command?: string;
  url?: string;
  event?: string;
  method?: 'GET' | 'POST' | 'PUT' | 'DELETE';
  payload?: Record<string, any>;
  modal?: 'form' | 'detail';  // For modal actions
  title?: string;
  fields?: any[];
  submitUrl?: string;
  refreshTarget?: string;
}
```

**Execute Actions:**
```typescript
const handleClick = () => {
  const action = config.actions?.click;
  if (action?.type === 'modal') {
    // Open modal
    setModalOpen(true);
  } else if (action?.type === 'http') {
    // Make HTTP request
    fetch(action.url, {
      method: action.method || 'POST',
      body: JSON.stringify(action.payload)
    });
  }
};
```

### 4. Inter-Component Communication

Components communicate via custom events:

**Emit Events:**
```typescript
// SearchBar emits search event
window.dispatchEvent(new CustomEvent('component:search', {
  detail: {
    target: 'component.table.agent',  // Target component ID
    search: searchValue
  }
}));
```

**Listen for Events:**
```typescript
// DataTable listens for search events
useEffect(() => {
  const handleSearch = (event: CustomEvent) => {
    if (event.detail.target === config.id) {
      setSearchTerm(event.detail.search);
    }
  };

  window.addEventListener('component:search', handleSearch as EventListener);
  return () => window.removeEventListener('component:search', handleSearch as EventListener);
}, [config.id]);
```

## Example: DataTableComponent

Here's how DataTableComponent was wired up:

```typescript
export function DataTableComponent({ config }: { config: DataTableConfig }) {
  const { props } = config;
  const { dataSource, columns, toolbar, rowAction } = props;

  // State
  const [data, setData] = useState<any[]>([]);
  const [loading, setLoading] = useState(false);
  const [searchTerm, setSearchTerm] = useState('');

  // Fetch data from dataSource
  useEffect(() => {
    if (dataSource) {
      setLoading(true);
      const params = new URLSearchParams();
      if (searchTerm) params.append('search', searchTerm);
      
      fetch(`/api/v2/ui/datasource/${dataSource}/query?${params}`)
        .then(res => res.json())
        .then(result => setData(result.data || []))
        .finally(() => setLoading(false));
    }
  }, [dataSource, searchTerm]);

  // Listen for search events
  useEffect(() => {
    const handleSearch = (event: CustomEvent) => {
      if (event.detail.target === config.id) {
        setSearchTerm(event.detail.search);
      }
    };
    window.addEventListener('component:search', handleSearch as EventListener);
    return () => window.removeEventListener('component:search', handleSearch as EventListener);
  }, [config.id]);

  // Render toolbar with action handlers
  const renderToolbar = () => (
    toolbar?.map(item => (
      <Button 
        key={item.id}
        onClick={() => {
          if (item.actions?.click?.type === 'modal') {
            // Open form modal
            setFormModalConfig(item.actions.click);
            setFormModalOpen(true);
          }
        }}
      >
        {item.props?.label}
      </Button>
    ))
  );

  // Handle row clicks
  const handleRowClick = async (row: any) => {
    if (rowAction?.type === 'modal') {
      const url = rowAction.url.replace('{{row.id}}', row.id);
      const response = await fetch(url);
      const data = await response.json();
      setDetailData(data);
      setDetailModalOpen(true);
    }
  };

  return (
    <>
      {renderToolbar()}
      <Table>
        {/* Table rows with onClick handler */}
      </Table>
      
      {/* Form Modal */}
      <Dialog open={formModalOpen}>
        {/* Render form fields from config */}
      </Dialog>
      
      {/* Detail Modal */}
      <Dialog open={detailModalOpen}>
        {/* Render detail fields from config */}
      </Dialog>
    </>
  );
}
```

## Registration

Register components in `ComponentRegistry.ts`:

```typescript
export function registerAdvancedComponents() {
  import('./advanced/DataTableComponent').then(({ DataTableComponent }) => {
    registry.register('data-table', DataTableComponent as ComponentRenderer);
  });
}
```

Call registration functions in `v2/main.tsx`:

```typescript
registerPrimitiveComponents();
registerLayoutComponents();
registerNavigationComponents();
registerCompositeComponents();
registerAdvancedComponents();
registerFormComponents();
```

## Database Configuration

Page configs live in `fe_ui_pages` table:

```json
{
  "overlay": "modal",
  "title": "Agents",
  "components": [
    {
      "id": "component.search.bar.agent",
      "type": "search.bar",
      "dataSource": "Agent",
      "result": {
        "target": "component.table.agent"
      },
      "props": {
        "placeholder": "Search agents..."
      }
    },
    {
      "id": "component.table.agent",
      "type": "data-table",
      "props": {
        "dataSource": "Agent",
        "columns": [...],
        "toolbar": [
          {
            "type": "button.icon",
            "props": { "label": "New Agent" },
            "actions": {
              "click": {
                "type": "modal",
                "modal": "form",
                "fields": [...],
                "submitUrl": "/api/v2/ui/datasource/Agent"
              }
            }
          }
        ],
        "rowAction": {
          "type": "modal",
          "url": "/api/v2/ui/types/Agent/{{row.id}}",
          "fields": [...]
        }
      }
    }
  ]
}
```

## Available Endpoints

### DataSource API
- `GET /api/v2/ui/datasource/{alias}/query` - Query data with filters/search
- `POST /api/v2/ui/datasource/{alias}` - Create new record
- `GET /api/v2/ui/datasource/{alias}/capabilities` - Get capabilities

### Types API
- `GET /api/v2/ui/types/{alias}/{id}` - Get single record details
- `GET /api/v2/ui/types/{alias}/query` - Query typed data

### Pages API
- `GET /api/v2/ui/pages/{key}` - Get page configuration

## Checklist for Wiring Components

When adding config-driven support to a component:

- [ ] Accept `dataSource` in props if component displays data
- [ ] Fetch from `/api/v2/ui/datasource/{alias}/query`
- [ ] Support search parameters in fetch
- [ ] Listen for relevant custom events (e.g., `component:search`)
- [ ] Handle actions from config (click, submit, etc.)
- [ ] Support toolbar/nested components if applicable
- [ ] Render modals based on action configs
- [ ] Register component in ComponentRegistry
- [ ] Test with database config

## Next Components to Wire

The following 56 Sprint 2 components need config-driven wiring:

**Primitives (21):** Most just need props mapping
**Layouts (10):** Need children component rendering
**Navigation (10):** Need navigation/routing support
**Composites (13):** Need action handlers
**Forms (7):** Need validation and submission
**Advanced (3):** Chart, Carousel, Sonner need specialized config

Start with components that will be commonly used in pages:
1. Form - For create/edit modals
2. Card - For layout structure
3. Tabs - For organizing content
4. Select, Input, Textarea - Already work with form modals
