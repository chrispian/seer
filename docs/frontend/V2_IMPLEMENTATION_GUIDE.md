# Fragments Engine v2 UI System - Implementation Guide

## Quick Start

### Prerequisites
- Laravel 12 with PostgreSQL
- Node.js 18+ with npm
- React 18 with TypeScript
- Tailwind CSS & shadcn/ui

### Initial Setup

```bash
# Run migrations
php artisan migrate

# Seed demo data
php artisan db:seed --class=V2UiBuilderSeeder

# Install frontend dependencies
npm install

# Build assets
npm run build

# Start development server
composer run dev
```

### Access Demo Page
```
http://your-app.test/v2/pages/page.agent.table.modal
```

## Core Implementation Tasks

### 1. Fix Critical API Implementation Gap

**Problem:** Missing API controllers for documented endpoints

**Solution:** Create V2UiController

```php
// app/Http/Controllers/Api/V2/V2UiController.php
<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\FeUiPage;
use App\Services\V2\DataSourceManager;
use App\Services\V2\ActionProcessor;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class V2UiController extends Controller
{
    public function __construct(
        private DataSourceManager $dataSourceManager,
        private ActionProcessor $actionProcessor
    ) {}

    /**
     * GET /api/v2/ui/pages/{key}
     */
    public function getPage(string $key): JsonResponse
    {
        $page = FeUiPage::where('key', $key)->firstOrFail();
        
        return response()->json([
            'id' => $page->id,
            'key' => $page->key,
            'config' => $page->config,
            'hash' => $page->hash,
            'version' => $page->version,
            'timestamp' => $page->updated_at->toIso8601String(),
        ]);
    }

    /**
     * POST /api/v2/ui/datasource/{alias}/query
     */
    public function queryDataSource(string $alias, Request $request): JsonResponse
    {
        $params = $request->validate([
            'search' => 'nullable|string|max:255',
            'filters' => 'nullable|array',
            'sort' => 'nullable|array',
            'sort.field' => 'nullable|string',
            'sort.direction' => 'nullable|in:asc,desc',
            'pagination' => 'nullable|array',
            'pagination.page' => 'nullable|integer|min:1',
            'pagination.per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $result = $this->dataSourceManager->query($alias, $params);
        
        return response()->json($result);
    }

    /**
     * GET /api/v2/ui/datasource/{alias}/capabilities
     */
    public function getDataSourceCapabilities(string $alias): JsonResponse
    {
        $capabilities = $this->dataSourceManager->getCapabilities($alias);
        
        return response()->json($capabilities);
    }

    /**
     * POST /api/v2/ui/action
     */
    public function executeAction(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:command,navigate,emit,http,modal',
            'command' => 'required_if:type,command|string',
            'url' => 'required_if:type,navigate,http|string',
            'event' => 'required_if:type,emit|string',
            'method' => 'nullable|in:GET,POST,PUT,DELETE',
            'payload' => 'nullable|array',
        ]);

        $result = $this->actionProcessor->execute($validated);
        
        return response()->json([
            'success' => $result['success'] ?? false,
            'result' => $result['data'] ?? null,
            'hash' => hash('sha256', json_encode($result)),
        ]);
    }

    /**
     * GET /api/v2/ui/types/{alias}/{id}
     */
    public function getTypeRecord(string $alias, string $id): JsonResponse
    {
        $record = $this->dataSourceManager->find($alias, $id);
        
        return response()->json($record);
    }
}
```

**Register Routes:**

```php
// routes/api.php
use App\Http\Controllers\Api\V2\V2UiController;

Route::prefix('v2/ui')->group(function () {
    Route::get('pages/{key}', [V2UiController::class, 'getPage']);
    Route::post('datasource/{alias}/query', [V2UiController::class, 'queryDataSource']);
    Route::get('datasource/{alias}/capabilities', [V2UiController::class, 'getDataSourceCapabilities']);
    Route::post('action', [V2UiController::class, 'executeAction']);
    Route::get('types/{alias}/{id}', [V2UiController::class, 'getTypeRecord']);
});
```

### 2. Fix Model/Migration Mismatch

**Problem:** FeUiPage model doesn't match migration

**Solution:** Update model

```php
// app/Models/FeUiPage.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeUiPage extends Model
{
    protected $table = 'fe_ui_pages';

    protected $fillable = [
        'key',
        'config',  // Changed from layout_tree_json
        'hash',
        'version',
    ];

    protected $casts = [
        'config' => 'array',  // Proper JSON casting
        'version' => 'integer',
    ];

    protected static function booted()
    {
        static::saving(function ($page) {
            $newHash = hash('sha256', json_encode($page->config));
            if ($page->hash !== $newHash) {
                $page->hash = $newHash;
                $page->version = ($page->version ?? 0) + 1;
            }
        });
    }
}
```

### 3. Implement DataSource Manager

```php
// app/Services/V2/DataSourceManager.php
<?php

namespace App\Services\V2;

use App\Models\FeUiDatasource;
use Illuminate\Support\Facades\Cache;
use Exception;

class DataSourceManager
{
    private array $resolvers = [];

    public function query(string $alias, array $params = []): array
    {
        $resolver = $this->getResolver($alias);
        
        // Check cache
        $cacheKey = "datasource:{$alias}:" . md5(json_encode($params));
        
        return Cache::remember($cacheKey, 300, function () use ($resolver, $params) {
            return $resolver->query($params);
        });
    }

    public function find(string $alias, string $id): array
    {
        $resolver = $this->getResolver($alias);
        return $resolver->find($id);
    }

    public function getCapabilities(string $alias): array
    {
        $resolver = $this->getResolver($alias);
        return $resolver->getCapabilities();
    }

    private function getResolver(string $alias)
    {
        if (!isset($this->resolvers[$alias])) {
            $datasource = FeUiDatasource::where('alias', $alias)->firstOrFail();
            $resolverClass = $datasource->resolver_class;
            
            if (!class_exists($resolverClass)) {
                throw new Exception("Resolver class {$resolverClass} not found");
            }
            
            $this->resolvers[$alias] = new $resolverClass($datasource->model_class);
        }
        
        return $this->resolvers[$alias];
    }
}
```

### 4. Implement Action Processor

```php
// app/Services/V2/ActionProcessor.php
<?php

namespace App\Services\V2;

use App\Services\CommandRegistry;
use Illuminate\Support\Facades\Http;

class ActionProcessor
{
    public function __construct(
        private CommandRegistry $commandRegistry
    ) {}

    public function execute(array $action): array
    {
        return match ($action['type']) {
            'command' => $this->executeCommand($action),
            'navigate' => $this->executeNavigate($action),
            'emit' => $this->executeEmit($action),
            'http' => $this->executeHttp($action),
            'modal' => $this->executeModal($action),
            default => ['success' => false, 'error' => 'Unknown action type'],
        };
    }

    private function executeCommand(array $action): array
    {
        try {
            $result = $this->commandRegistry->execute(
                $action['command'],
                $action['payload'] ?? []
            );
            
            return ['success' => true, 'data' => $result];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function executeNavigate(array $action): array
    {
        return [
            'success' => true,
            'data' => ['url' => $action['url']],
        ];
    }

    private function executeEmit(array $action): array
    {
        // Events are handled client-side
        return [
            'success' => true,
            'data' => [
                'event' => $action['event'],
                'payload' => $action['payload'] ?? [],
            ],
        ];
    }

    private function executeHttp(array $action): array
    {
        try {
            $response = Http::request(
                $action['method'] ?? 'POST',
                $action['url'],
                $action['payload'] ?? []
            );
            
            return [
                'success' => $response->successful(),
                'data' => $response->json(),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function executeModal(array $action): array
    {
        // Modal actions are handled client-side
        return [
            'success' => true,
            'data' => $action['modal'] ?? [],
        ];
    }
}
```

### 5. Create Generic DataSource Resolver

```php
// app/Services/V2/Resolvers/GenericResolver.php
<?php

namespace App\Services\V2\Resolvers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class GenericResolver
{
    private string $modelClass;
    private Model $model;

    public function __construct(string $modelClass)
    {
        $this->modelClass = $modelClass;
        $this->model = new $modelClass;
    }

    public function query(array $params = []): array
    {
        $query = $this->model->query();
        
        // Apply search
        if (!empty($params['search'])) {
            $this->applySearch($query, $params['search']);
        }
        
        // Apply filters
        if (!empty($params['filters'])) {
            $this->applyFilters($query, $params['filters']);
        }
        
        // Apply sorting
        if (!empty($params['sort'])) {
            $this->applySorting($query, $params['sort']);
        }
        
        // Apply pagination
        $perPage = $params['pagination']['per_page'] ?? 15;
        $page = $params['pagination']['page'] ?? 1;
        
        $paginated = $query->paginate($perPage, ['*'], 'page', $page);
        
        return [
            'data' => $paginated->items(),
            'meta' => [
                'total' => $paginated->total(),
                'page' => $paginated->currentPage(),
                'per_page' => $paginated->perPage(),
                'last_page' => $paginated->lastPage(),
            ],
            'hash' => hash('sha256', json_encode($paginated->items())),
        ];
    }

    public function find(string $id): array
    {
        $record = $this->model->findOrFail($id);
        return $record->toArray();
    }

    public function getCapabilities(): array
    {
        return [
            'searchable' => $this->getSearchableFields(),
            'filterable' => $this->getFilterableFields(),
            'sortable' => $this->getSortableFields(),
        ];
    }

    protected function applySearch(Builder $query, string $search): void
    {
        $searchable = $this->getSearchableFields();
        
        $query->where(function (Builder $q) use ($search, $searchable) {
            foreach ($searchable as $field) {
                $q->orWhere($field, 'like', "%{$search}%");
            }
        });
    }

    protected function applyFilters(Builder $query, array $filters): void
    {
        $filterable = $this->getFilterableFields();
        
        foreach ($filters as $field => $value) {
            if (in_array($field, $filterable) && !empty($value)) {
                $query->where($field, $value);
            }
        }
    }

    protected function applySorting(Builder $query, array $sort): void
    {
        $field = $sort['field'] ?? 'created_at';
        $direction = $sort['direction'] ?? 'desc';
        
        if (in_array($field, $this->getSortableFields())) {
            $query->orderBy($field, $direction);
        }
    }

    protected function getSearchableFields(): array
    {
        // Override in specific resolvers
        return ['name', 'title', 'description'];
    }

    protected function getFilterableFields(): array
    {
        // Override in specific resolvers
        return ['status', 'type', 'category'];
    }

    protected function getSortableFields(): array
    {
        // Override in specific resolvers
        return ['name', 'created_at', 'updated_at'];
    }
}
```

## Frontend Implementation

### 1. Fix Component Registry Loading

```typescript
// resources/js/components/v2/ComponentRegistry.ts

// Add loading state management
class ComponentRegistry {
  private components: Map<string, ComponentRenderer> = new Map();
  private loading: Set<string> = new Set();
  private loadPromises: Map<string, Promise<void>> = new Map();

  async register(type: string, loader: () => Promise<{ default: ComponentRenderer }>): Promise<void> {
    if (this.components.has(type)) return;
    if (this.loading.has(type)) {
      return this.loadPromises.get(type);
    }

    this.loading.add(type);
    const promise = loader().then(module => {
      this.components.set(type, module.default);
      this.loading.delete(type);
      this.loadPromises.delete(type);
    });

    this.loadPromises.set(type, promise);
    return promise;
  }

  async waitForComponent(type: string): Promise<ComponentRenderer | undefined> {
    if (this.components.has(type)) {
      return this.components.get(type);
    }
    
    if (this.loading.has(type)) {
      await this.loadPromises.get(type);
      return this.components.get(type);
    }
    
    return undefined;
  }
}
```

### 2. Add Error Boundaries

```typescript
// resources/js/components/v2/ErrorBoundary.tsx
import React from 'react';
import { Alert, AlertDescription } from '@/components/ui/alert';

interface ErrorBoundaryState {
  hasError: boolean;
  error?: Error;
}

export class ComponentErrorBoundary extends React.Component<
  { children: React.ReactNode; componentId?: string },
  ErrorBoundaryState
> {
  constructor(props: any) {
    super(props);
    this.state = { hasError: false };
  }

  static getDerivedStateFromError(error: Error): ErrorBoundaryState {
    return { hasError: true, error };
  }

  componentDidCatch(error: Error, errorInfo: React.ErrorInfo) {
    console.error('Component error:', {
      componentId: this.props.componentId,
      error,
      errorInfo,
    });
    
    // Send telemetry
    window.dispatchEvent(new CustomEvent('error:component', {
      detail: {
        componentId: this.props.componentId,
        error: error.toString(),
        stack: error.stack,
      }
    }));
  }

  render() {
    if (this.state.hasError) {
      return (
        <Alert variant="destructive">
          <AlertDescription>
            Component failed to load: {this.state.error?.message}
          </AlertDescription>
        </Alert>
      );
    }

    return this.props.children;
  }
}

// Wrap components during rendering
export function renderComponent(config: ComponentConfig): React.ReactElement {
  const Component = registry.get(config.type);
  
  if (!Component) {
    return <Alert>Component type "{config.type}" not found</Alert>;
  }
  
  return (
    <ComponentErrorBoundary componentId={config.id}>
      <Component config={config} />
    </ComponentErrorBoundary>
  );
}
```

### 3. Fix Search Debounce Memory Leak

```typescript
// resources/js/components/v2/composites/SearchBarComponent.tsx
import { useState, useEffect, useRef } from 'react';

export function SearchBarComponent({ config }: { config: SearchBarConfig }) {
  const [value, setValue] = useState('');
  const timeoutRef = useRef<NodeJS.Timeout>();

  useEffect(() => {
    // Clear existing timeout
    if (timeoutRef.current) {
      clearTimeout(timeoutRef.current);
    }

    // Set new timeout
    timeoutRef.current = setTimeout(() => {
      if (config.result?.target) {
        window.dispatchEvent(new CustomEvent('component:search', {
          detail: {
            target: config.result.target,
            search: value
          }
        }));
      }
    }, 300);

    // Cleanup on unmount or value change
    return () => {
      if (timeoutRef.current) {
        clearTimeout(timeoutRef.current);
      }
    };
  }, [value, config.result?.target]);

  // ... rest of component
}
```

### 4. Implement Template String Parser

```typescript
// resources/js/lib/templateParser.ts
export function parseTemplate(template: string, context: Record<string, any>): string {
  return template.replace(/\{\{([^}]+)\}\}/g, (match, path) => {
    const keys = path.trim().split('.');
    let value = context;
    
    for (const key of keys) {
      if (value && typeof value === 'object' && key in value) {
        value = value[key];
      } else {
        console.warn(`Template path not found: ${path}`);
        return match; // Return original if not found
      }
    }
    
    return String(value);
  });
}

// Usage in DataTableComponent
const handleRowClick = async (row: any) => {
  if (rowAction?.type === 'modal' && rowAction.url) {
    const url = parseTemplate(rowAction.url, { row });
    const response = await fetch(url);
    // ...
  }
};
```

## Database Seeders

### Create DataSource Seeder

```php
// database/seeders/V2DataSourceSeeder.php
<?php

namespace Database\Seeders;

use App\Models\FeUiDatasource;
use Illuminate\Database\Seeder;

class V2DataSourceSeeder extends Seeder
{
    public function run(): void
    {
        $dataSources = [
            [
                'alias' => 'Agent',
                'model_class' => 'App\\Models\\OrchestrationAgent',
                'resolver_class' => 'App\\Services\\V2\\Resolvers\\AgentResolver',
                'capabilities' => [
                    'searchable' => ['name', 'description'],
                    'filterable' => ['status', 'role', 'provider'],
                    'sortable' => ['name', 'created_at', 'updated_at'],
                ],
            ],
            [
                'alias' => 'Task',
                'model_class' => 'App\\Models\\OrchestrationTask',
                'resolver_class' => 'App\\Services\\V2\\Resolvers\\GenericResolver',
                'capabilities' => [
                    'searchable' => ['title', 'description'],
                    'filterable' => ['status', 'priority'],
                    'sortable' => ['title', 'priority', 'created_at'],
                ],
            ],
            // Add more data sources
        ];

        foreach ($dataSources as $data) {
            FeUiDatasource::updateOrCreate(
                ['alias' => $data['alias']],
                $data
            );
        }
    }
}
```

## Security Implementation

### 1. Add CSRF Protection

```typescript
// resources/js/lib/api.ts
export async function apiRequest(url: string, options: RequestInit = {}) {
  const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  
  return fetch(url, {
    ...options,
    headers: {
      ...options.headers,
      'X-CSRF-TOKEN': token || '',
      'Content-Type': 'application/json',
    },
  });
}
```

### 2. Add Authorization Middleware

```php
// app/Http/Middleware/AuthorizeDataSource.php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AuthorizeDataSource
{
    public function handle(Request $request, Closure $next)
    {
        $alias = $request->route('alias');
        $user = $request->user();
        
        // Check permissions
        if (!$user->can("datasource.{$alias}.query")) {
            abort(403, 'Unauthorized to query this data source');
        }
        
        return $next($request);
    }
}
```

## Testing Implementation

### 1. Component Tests

```typescript
// tests/components/SearchBarComponent.test.tsx
import { render, fireEvent, waitFor } from '@testing-library/react';
import { SearchBarComponent } from '@/components/v2/composites/SearchBarComponent';

describe('SearchBarComponent', () => {
  it('emits search event after debounce', async () => {
    const mockEventListener = jest.fn();
    window.addEventListener('component:search', mockEventListener);
    
    const config = {
      id: 'search-1',
      type: 'search.bar',
      result: { target: 'table-1' },
    };
    
    const { getByRole } = render(<SearchBarComponent config={config} />);
    const input = getByRole('searchbox');
    
    fireEvent.change(input, { target: { value: 'test' } });
    
    await waitFor(() => {
      expect(mockEventListener).toHaveBeenCalledWith(
        expect.objectContaining({
          detail: { target: 'table-1', search: 'test' }
        })
      );
    }, { timeout: 400 });
  });
});
```

### 2. API Tests

```php
// tests/Feature/V2UiApiTest.php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\FeUiPage;
use Illuminate\Foundation\Testing\RefreshDatabase;

class V2UiApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_fetch_page_configuration()
    {
        $page = FeUiPage::create([
            'key' => 'test.page',
            'config' => ['test' => 'config'],
        ]);

        $response = $this->getJson('/api/v2/ui/pages/test.page');

        $response->assertOk()
            ->assertJson([
                'key' => 'test.page',
                'config' => ['test' => 'config'],
                'version' => 1,
            ]);
    }

    public function test_datasource_query_with_pagination()
    {
        // Seed test data
        // ...

        $response = $this->postJson('/api/v2/ui/datasource/Agent/query', [
            'pagination' => ['page' => 1, 'per_page' => 10],
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data',
                'meta' => ['total', 'page', 'per_page', 'last_page'],
                'hash',
            ]);
    }
}
```

## Performance Optimization

### 1. Implement Request Caching

```typescript
// resources/js/lib/cache.ts
class RequestCache {
  private cache = new Map<string, { data: any; timestamp: number }>();
  private ttl = 5 * 60 * 1000; // 5 minutes

  set(key: string, data: any): void {
    this.cache.set(key, { data, timestamp: Date.now() });
  }

  get(key: string): any | null {
    const cached = this.cache.get(key);
    if (!cached) return null;
    
    if (Date.now() - cached.timestamp > this.ttl) {
      this.cache.delete(key);
      return null;
    }
    
    return cached.data;
  }

  clear(): void {
    this.cache.clear();
  }
}

export const requestCache = new RequestCache();
```

### 2. Add Virtual Scrolling

```typescript
// resources/js/components/v2/advanced/VirtualTable.tsx
import { useVirtual } from '@tanstack/react-virtual';

export function VirtualTable({ data, rowHeight = 50 }) {
  const parentRef = useRef<HTMLDivElement>(null);
  
  const rowVirtualizer = useVirtual({
    size: data.length,
    parentRef,
    estimateSize: useCallback(() => rowHeight, [rowHeight]),
  });

  return (
    <div ref={parentRef} style={{ height: '400px', overflow: 'auto' }}>
      <div style={{ height: `${rowVirtualizer.totalSize}px` }}>
        {rowVirtualizer.virtualItems.map((virtualRow) => (
          <div
            key={virtualRow.index}
            style={{
              position: 'absolute',
              top: 0,
              left: 0,
              width: '100%',
              height: `${virtualRow.size}px`,
              transform: `translateY(${virtualRow.start}px)`,
            }}
          >
            {/* Render row content */}
          </div>
        ))}
      </div>
    </div>
  );
}
```

## Deployment Checklist

- [ ] Run migrations on production
- [ ] Seed initial data sources
- [ ] Build frontend assets
- [ ] Clear caches
- [ ] Test all endpoints
- [ ] Verify CSRF protection
- [ ] Check error logging
- [ ] Monitor performance metrics
- [ ] Document API endpoints
- [ ] Train team on system

## Common Issues & Solutions

### Issue: Components not rendering
**Solution:** Check ComponentRegistry loading, verify component type matches registration

### Issue: DataSource queries failing
**Solution:** Verify resolver class exists, check model permissions, review query logs

### Issue: Actions not executing
**Solution:** Check CommandRegistry registration, verify action payload format

### Issue: Memory leaks in components
**Solution:** Clean up event listeners, clear timeouts, use useRef for mutable values

### Issue: Configuration not updating
**Solution:** Clear cache, check hash computation, verify version increment

## Next Steps

1. Implement visual config editor
2. Add GraphQL support
3. Create component marketplace
4. Build automated testing suite
5. Add real-time collaboration