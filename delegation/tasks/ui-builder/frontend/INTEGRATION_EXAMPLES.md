# Integration Examples

## Basic Usage

### Render a Page from Config

```tsx
import { V2ShellPage } from '@/components/v2'

export function AgentsPage() {
  return <V2ShellPage pageKey="page.agent.table.modal" />
}
```

### Inline Page (No Modal)

```tsx
import { PageRenderer } from '@/components/v2'

export function InlinePage() {
  return <PageRenderer pageKey="page.agent.list" />
}
```

### Controlled Modal

```tsx
import { useState } from 'react'
import { PageRenderer } from '@/components/v2'

export function ControlledModal() {
  const [open, setOpen] = useState(false)

  return (
    <>
      <button onClick={() => setOpen(true)}>Open Agents</button>
      <PageRenderer 
        pageKey="page.agent.table.modal" 
        open={open} 
        onOpenChange={setOpen} 
      />
    </>
  )
}
```

## Custom Components

### Create Custom Component

```tsx
import { useDataSource } from '@/components/v2'
import type { ComponentConfig } from '@/components/v2'

interface ChartComponentProps {
  config: ComponentConfig
}

export function ChartComponent({ config }: ChartComponentProps) {
  const { data, loading } = useDataSource({
    dataSource: config.dataSource || '',
  })

  if (loading) return <div>Loading chart...</div>

  return (
    <div className="chart-container">
      {/* Your chart implementation */}
    </div>
  )
}
```

### Register Custom Component

```tsx
import { componentRegistry } from '@/components/v2'
import { ChartComponent } from './ChartComponent'

componentRegistry.register('chart', ChartComponent)
```

### Use in Config

```json
{
  "id": "page.dashboard",
  "components": [
    {
      "id": "component.chart.usage",
      "type": "chart",
      "dataSource": "UsageMetrics"
    }
  ]
}
```

## Custom Actions

### Component with Custom Action

```tsx
import { useAction } from '@/components/v2'
import { Button } from '@/components/ui/button'
import type { ComponentConfig } from '@/components/v2'

export function ExportButtonComponent({ config }: { config: ComponentConfig }) {
  const { execute, loading } = useAction()

  const handleExport = async () => {
    const result = await execute({
      type: 'command',
      command: '/export-data',
      params: { format: 'csv' }
    })

    if (result.success && result.data?.downloadUrl) {
      window.open(result.data.downloadUrl, '_blank')
    }
  }

  return (
    <Button onClick={handleExport} disabled={loading}>
      {loading ? 'Exporting...' : 'Export CSV'}
    </Button>
  )
}
```

## Advanced DataSource Usage

### Paginated Table

```tsx
import { useState } from 'react'
import { useDataSource } from '@/components/v2'

export function PaginatedTable({ config }: { config: ComponentConfig }) {
  const [page, setPage] = useState(1)
  const { data, meta, loading } = useDataSource({
    dataSource: config.dataSource || '',
    pagination: { page, perPage: 20 },
  })

  return (
    <div>
      <Table data={data} />
      <Pagination 
        current={page}
        total={meta?.lastPage || 1}
        onChange={setPage}
      />
    </div>
  )
}
```

### Filtered and Sorted Table

```tsx
import { useState } from 'react'
import { useDataSource } from '@/components/v2'

export function AdvancedTable({ config }: { config: ComponentConfig }) {
  const [filters, setFilters] = useState<Record<string, any>>({})
  const [sort, setSort] = useState({ field: 'name', direction: 'asc' as const })

  const { data, loading, fetch } = useDataSource({
    dataSource: config.dataSource || '',
    filters,
    sort,
  })

  const handleFilterChange = (key: string, value: any) => {
    const newFilters = { ...filters, [key]: value }
    setFilters(newFilters)
    fetch({ filters: newFilters })
  }

  const handleSortChange = (field: string) => {
    const newSort = {
      field,
      direction: sort.field === field && sort.direction === 'asc' ? 'desc' : 'asc'
    } as const
    setSort(newSort)
    fetch({ sort: newSort })
  }

  return (
    <div>
      <FilterBar filters={filters} onChange={handleFilterChange} />
      <Table 
        data={data} 
        onSortChange={handleSortChange}
        currentSort={sort}
      />
    </div>
  )
}
```

## Slot Communication

### Parent-Child Communication

```tsx
import { useEffect } from 'react'
import { slotBinder, useDataSource } from '@/components/v2'

export function ParentComponent({ config }: { config: ComponentConfig }) {
  const { data } = useDataSource({
    dataSource: config.dataSource || '',
  })

  useEffect(() => {
    // Broadcast to child component
    if (config.result) {
      slotBinder.update(config.result, { data })
    }
  }, [data, config.result])

  return <div>Parent renders data, child listens</div>
}

export function ChildComponent({ config }: { config: ComponentConfig }) {
  const [receivedData, setReceivedData] = useState([])

  useEffect(() => {
    const unsubscribe = slotBinder.subscribe(config.id, result => {
      setReceivedData(result.data)
    })

    return unsubscribe
  }, [config.id])

  return <div>Child displays: {receivedData.length} items</div>
}
```

## Error Handling

### Custom Error Boundary

```tsx
import { Component, ErrorInfo, ReactNode } from 'react'

interface Props {
  children: ReactNode
  fallback?: ReactNode
}

interface State {
  hasError: boolean
  error?: Error
}

export class V2ErrorBoundary extends Component<Props, State> {
  constructor(props: Props) {
    super(props)
    this.state = { hasError: false }
  }

  static getDerivedStateFromError(error: Error): State {
    return { hasError: true, error }
  }

  componentDidCatch(error: Error, errorInfo: ErrorInfo) {
    console.error('V2 Component Error:', error, errorInfo)
  }

  render() {
    if (this.state.hasError) {
      return this.props.fallback || (
        <div className="p-4 border border-destructive rounded-lg">
          <h3 className="text-destructive font-semibold">Component Error</h3>
          <p className="text-sm">{this.state.error?.message}</p>
        </div>
      )
    }

    return this.props.children
  }
}
```

### Usage with Error Boundary

```tsx
import { V2ErrorBoundary } from './V2ErrorBoundary'
import { V2ShellPage } from '@/components/v2'

export function SafePage() {
  return (
    <V2ErrorBoundary>
      <V2ShellPage pageKey="page.agent.table.modal" />
    </V2ErrorBoundary>
  )
}
```

## Testing Examples

### Test Page Config Hook

```tsx
import { renderHook, waitFor } from '@testing-library/react'
import { usePageConfig } from '@/components/v2'

describe('usePageConfig', () => {
  it('fetches page config', async () => {
    global.fetch = jest.fn(() =>
      Promise.resolve({
        ok: true,
        json: () => Promise.resolve({ id: 'test', components: [] }),
      })
    ) as jest.Mock

    const { result } = renderHook(() => usePageConfig('test'))

    await waitFor(() => expect(result.current.loading).toBe(false))
    expect(result.current.config).toEqual({ id: 'test', components: [] })
  })
})
```

### Test Component

```tsx
import { render, screen } from '@testing-library/react'
import { TableComponent } from '@/components/v2'

jest.mock('@/components/v2/hooks/useDataSource', () => ({
  useDataSource: () => ({
    data: [{ id: 1, name: 'Test' }],
    loading: false,
    error: null,
    fetch: jest.fn(),
  }),
}))

describe('TableComponent', () => {
  it('renders data', () => {
    const config = {
      id: 'test',
      type: 'table',
      dataSource: 'Test',
      columns: [{ key: 'name', label: 'Name' }],
    }

    render(<TableComponent config={config} />)
    expect(screen.getByText('Test')).toBeInTheDocument()
  })
})
```

## Route Integration

### Add to React Router

```tsx
import { createBrowserRouter } from 'react-router-dom'
import { V2ShellPage } from '@/components/v2'

const router = createBrowserRouter([
  {
    path: '/v2/pages/:pageKey',
    element: <V2PageRoute />,
  },
])

function V2PageRoute() {
  const { pageKey } = useParams()
  return <V2ShellPage pageKey={pageKey || ''} />
}
```

### Add to Laravel Routes

```php
// routes/web.php
Route::get('/v2/pages/{key}', function ($key) {
    return view('v2-page', ['pageKey' => $key]);
});
```

```blade
<!-- resources/views/v2-page.blade.php -->
<div id="v2-root" data-page-key="{{ $pageKey }}"></div>

<script>
  const pageKey = document.getElementById('v2-root').dataset.pageKey
  ReactDOM.render(
    React.createElement(V2ShellPage, { pageKey }),
    document.getElementById('v2-root')
  )
</script>
```

## Backend API Examples

### Laravel Controller for Pages

```php
namespace App\Http\Controllers\V2;

class UIController extends Controller
{
    public function getPage(string $key)
    {
        $config = UIPageConfig::where('key', $key)->firstOrFail();
        return response()->json($config->config);
    }

    public function queryDataSource(string $alias, Request $request)
    {
        $resolver = DataSourceRegistry::get($alias);
        $query = $request->validate([
            'filters' => 'array',
            'search' => 'string',
            'sort' => 'array',
            'pagination' => 'array',
        ]);

        $result = $resolver->resolve($query);

        return response()->json([
            'data' => $result->data,
            'meta' => $result->meta,
        ]);
    }

    public function executeAction(Request $request)
    {
        $action = $request->validate([
            'type' => 'required|in:command,navigate',
            'command' => 'string',
            'route' => 'string',
            'params' => 'array',
        ]);

        if ($action['type'] === 'command') {
            $result = CommandRouter::execute($action['command'], $action['params']);
            return response()->json($result);
        }

        if ($action['type'] === 'navigate') {
            return response()->json([
                'success' => true,
                'redirect' => route($action['route'], $action['params']),
            ]);
        }
    }
}
```

### Register Routes

```php
Route::prefix('api/v2/ui')->group(function () {
    Route::get('pages/{key}', [UIController::class, 'getPage']);
    Route::post('datasource/{alias}/query', [UIController::class, 'queryDataSource']);
    Route::post('action', [UIController::class, 'executeAction']);
});
```

## Config Examples

### Complex Page with Multiple Components

```json
{
  "id": "page.agent.dashboard",
  "overlay": "page",
  "title": "Agent Dashboard",
  "components": [
    {
      "id": "layout.top",
      "type": "layout.columns",
      "components": [
        {
          "id": "search",
          "type": "search.bar",
          "dataSource": "Agent",
          "result": { "target": "table", "open": "inline" }
        },
        {
          "id": "toolbar",
          "type": "layout.rows",
          "components": [
            {
              "id": "btn.add",
              "type": "button.icon",
              "props": { "icon": "plus", "label": "New" },
              "actions": {
                "click": { "type": "command", "command": "/orch-agent-new" }
              }
            },
            {
              "id": "btn.export",
              "type": "button.icon",
              "props": { "icon": "download", "label": "Export" },
              "actions": {
                "click": { "type": "command", "command": "/export-agents" }
              }
            }
          ]
        }
      ]
    },
    {
      "id": "table",
      "type": "table",
      "dataSource": "Agent",
      "columns": [
        { "key": "name", "label": "Name", "sortable": true },
        { "key": "role", "label": "Role", "filterable": true },
        { "key": "status", "label": "Status", "filterable": true }
      ],
      "rowAction": {
        "type": "command",
        "command": "/orch-agent",
        "params": { "id": "{{row.id}}" }
      }
    }
  ]
}
```

---

These examples demonstrate the flexibility and extensibility of the UI Builder v2 system.
