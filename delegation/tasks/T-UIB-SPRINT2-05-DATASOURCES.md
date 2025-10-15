# Task: Make DataSource Resolvers Config-Based

**Task Code**: T-UIB-SPRINT2-05-DATASOURCES  
**Sprint**: UI Builder v2 Sprint 2  
**Priority**: MEDIUM  
**Assigned To**: BE-Kernel Agent  
**Status**: TODO  
**Created**: 2025-10-15  
**Depends On**: T-UIB-SPRINT2-01-TYPES, T-UIB-SPRINT2-03-SCHEMA

## Objective

Replace hard-coded DataSourceResolver classes (AgentDataSourceResolver, ModelDataSourceResolver) with a generic, config-driven resolver system. The goal is to create new data sources through database configuration alone, without writing PHP classes.

## Problem Statement

Currently, adding a new data source requires:

1. Creating a new `*DataSourceResolver.php` class
2. Hard-coding model name, searchable fields, filterable fields, sortable fields
3. Hard-coding data transformation logic
4. Manually defining capabilities

**Example** (current hard-coded approach):

```php
// app/Services/V2/AgentDataSourceResolver.php
class AgentDataSourceResolver
{
    public function query(array $params = []): array
    {
        $query = Agent::query()->with('agentProfile');
        
        // Hard-coded searchable fields
        if (isset($params['search'])) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('designation', 'like', "%{$search}%");
            });
        }
        
        // Hard-coded filterable fields
        if (isset($params['filters'])) {
            foreach ($params['filters'] as $field => $value) {
                if (in_array($field, ['status', 'agent_profile_id'])) {
                    $query->where($field, $value);
                }
            }
        }
        
        // Hard-coded sortable fields
        if (in_array($sortField, ['name', 'updated_at'])) {
            $query->orderBy($sortField, $sortDirection);
        }
        
        // Hard-coded transformation
        $transformedData = array_map(function ($agent) {
            return [
                'id' => $agent->id,
                'name' => $agent->name,
                'role' => $agent->designation,
                'provider' => $agent->agentProfile?->provider,
                'model' => $agent->agentProfile?->model,
                'status' => $agent->status,
                'updated_at' => $agent->updated_at->toIso8601String(),
                'avatar_url' => $agent->avatar_url,
                'avatar_path' => $agent->avatar_path,
            ];
        }, $data);
        
        return ['data' => $transformedData, 'meta' => ...];
    }
    
    public function getCapabilities(): array
    {
        return [
            'searchable' => ['name', 'designation'],
            'filterable' => ['status', 'agent_profile_id'],
            'sortable' => ['name', 'updated_at'],
        ];
    }
}
```

This approach requires a new PHP file for every model we want to expose as a data source.

## Proposed Solution

### 1. Generic Config-Driven Resolver

Create a single `GenericDataSourceResolver` that reads configuration from the database:

```php
namespace App\Services\V2;

use App\Models\FeUiDatasource;
use Illuminate\Support\Facades\Cache;

class GenericDataSourceResolver
{
    public function query(string $alias, array $params = []): array
    {
        $config = $this->getConfig($alias);
        
        $modelClass = $config['model'];
        $query = $modelClass::query();
        
        // Apply eager loading from config
        if (!empty($config['with'])) {
            $query->with($config['with']);
        }
        
        // Apply scopes from config
        if (!empty($config['scopes'])) {
            foreach ($config['scopes'] as $scope) {
                $query->{$scope}();
            }
        }
        
        // Generic search handling
        if (isset($params['search']) && !empty($params['search'])) {
            $search = $params['search'];
            $searchable = $config['capabilities']['searchable'] ?? [];
            
            if (!empty($searchable)) {
                $query->where(function ($q) use ($searchable, $search) {
                    foreach ($searchable as $field) {
                        $q->orWhere($field, 'like', "%{$search}%");
                    }
                });
            }
        }
        
        // Generic filter handling
        if (isset($params['filters']) && is_array($params['filters'])) {
            $filterable = $config['capabilities']['filterable'] ?? [];
            
            foreach ($params['filters'] as $field => $value) {
                if (in_array($field, $filterable) && !empty($value)) {
                    $query->where($field, $value);
                }
            }
        }
        
        // Generic sort handling
        if (isset($params['sort']) && is_array($params['sort'])) {
            $sortField = $params['sort']['field'] ?? 'updated_at';
            $sortDirection = $params['sort']['direction'] ?? 'desc';
            $sortable = $config['capabilities']['sortable'] ?? [];
            
            if (in_array($sortField, $sortable)) {
                $query->orderBy($sortField, $sortDirection);
            }
        } else {
            $defaultSort = $config['default_sort'] ?? ['updated_at', 'desc'];
            $query->orderBy($defaultSort[0], $defaultSort[1]);
        }
        
        // Pagination
        $perPage = $params['pagination']['per_page'] ?? 15;
        $page = $params['pagination']['page'] ?? 1;
        $paginated = $query->paginate($perPage, ['*'], 'page', $page);
        
        // Generic transformation
        $data = $paginated->items();
        $transformedData = array_map(function ($item) use ($config) {
            return $this->transformItem($item, $config['transform']);
        }, $data);
        
        return [
            'data' => $transformedData,
            'meta' => [
                'total' => $paginated->total(),
                'page' => $paginated->currentPage(),
                'per_page' => $paginated->perPage(),
                'last_page' => $paginated->lastPage(),
            ],
            'hash' => hash('sha256', json_encode($transformedData)),
        ];
    }
    
    protected function transformItem($item, array $transform): array
    {
        $result = [];
        
        foreach ($transform as $outputKey => $config) {
            if (is_string($config)) {
                // Simple field mapping: 'name' => 'name'
                $result[$outputKey] = data_get($item, $config);
            } elseif (is_array($config)) {
                // Complex mapping with type/format
                $source = $config['source'] ?? $outputKey;
                $value = data_get($item, $source);
                
                // Apply formatters
                if (isset($config['format'])) {
                    $value = $this->formatValue($value, $config['format']);
                }
                
                $result[$outputKey] = $value;
            }
        }
        
        return $result;
    }
    
    protected function formatValue($value, string $format)
    {
        return match($format) {
            'iso8601' => $value?->toIso8601String(),
            'date' => $value?->toDateString(),
            'avatar_url' => $value ? asset("storage/avatars/{$value}") : null,
            default => $value,
        };
    }
    
    protected function getConfig(string $alias): array
    {
        return Cache::remember("datasource.{$alias}", 3600, function () use ($alias) {
            $datasource = FeUiDatasource::where('alias', $alias)->firstOrFail();
            
            return [
                'model' => $datasource->handler, // e.g., 'App\Models\Agent'
                'with' => $datasource->default_params_json['with'] ?? [],
                'scopes' => $datasource->default_params_json['scopes'] ?? [],
                'default_sort' => $datasource->default_params_json['default_sort'] ?? ['updated_at', 'desc'],
                'capabilities' => $datasource->capabilities_json ?? [],
                'transform' => $datasource->schema_json['transform'] ?? [],
            ];
        });
    }
    
    public function getCapabilities(string $alias): array
    {
        $config = $this->getConfig($alias);
        return $config['capabilities'];
    }
}
```

### 2. Database Configuration

Store data source config in `fe_ui_datasources`:

```php
FeUiDatasource::create([
    'alias' => 'Agent',
    'handler' => 'App\Models\Agent', // Model class name
    'default_params_json' => [
        'with' => ['agentProfile'],
        'scopes' => [], // e.g., ['active', 'verified']
        'default_sort' => ['updated_at', 'desc'],
    ],
    'capabilities_json' => [
        'supports' => ['list', 'detail', 'search', 'paginate'],
        'searchable' => ['name', 'designation'],
        'filterable' => ['status', 'agent_profile_id'],
        'sortable' => ['name', 'updated_at'],
    ],
    'schema_json' => [
        'transform' => [
            'id' => 'id',
            'name' => 'name',
            'role' => 'designation',
            'provider' => ['source' => 'agentProfile.provider'],
            'model' => ['source' => 'agentProfile.model'],
            'status' => 'status',
            'updated_at' => ['source' => 'updated_at', 'format' => 'iso8601'],
            'avatar_url' => 'avatar_url',
            'avatar_path' => 'avatar_path',
        ],
    ],
    'version' => '1.0.0',
    'hash' => hash('sha256', 'Agent.1.0.0'),
    'enabled' => true,
]);
```

### 3. Update Controller

Modify `UiDataSourceController` to use `GenericDataSourceResolver`:

```php
namespace App\Http\Controllers\V2;

use App\Services\V2\GenericDataSourceResolver;

class UiDataSourceController extends Controller
{
    public function query(string $alias, Request $request, GenericDataSourceResolver $resolver)
    {
        $params = [
            'search' => $request->input('search'),
            'filters' => $request->input('filters', []),
            'sort' => $request->input('sort'),
            'pagination' => [
                'page' => $request->input('page', 1),
                'per_page' => $request->input('per_page', 15),
            ],
        ];
        
        $result = $resolver->query($alias, $params);
        
        return response()->json($result);
    }
}
```

## Implementation Steps

1. **Create GenericDataSourceResolver**
   - File: `app/Services/V2/GenericDataSourceResolver.php`
   - Implement query, transform, format methods
   - Add caching for config lookup
   - Handle edge cases (null relationships, missing fields)

2. **Migrate Existing Resolvers to Config**
   - Create database entries for Agent and Model datasources
   - Test that generic resolver produces identical output
   - Keep old resolvers temporarily for comparison

3. **Update Controller**
   - Switch to GenericDataSourceResolver
   - Remove hard-coded resolver lookup

4. **Create Migration Helper Command**
   ```bash
   php artisan fe:make:datasource Agent --model=Agent
   ```
   - Introspects model to suggest searchable/filterable fields
   - Generates database entry with sensible defaults

5. **Add Type System Integration**
   - If a Type is defined (from Task 1), prefer it
   - Allow datasources to reference Types for schema

6. **Create Seeder**
   - Migrate Agent and Model to config-based entries
   - Add additional examples (Project, Task, Sprint)

7. **Documentation**
   - How to create a new datasource via config
   - Transformation syntax guide
   - Format options reference

8. **Remove Old Resolvers**
   - Delete `AgentDataSourceResolver.php`
   - Delete `ModelDataSourceResolver.php`
   - Verify no references remain

## Acceptance Criteria

- [ ] GenericDataSourceResolver implemented
- [ ] Agent datasource works via config (matches old output)
- [ ] Model datasource works via config (matches old output)
- [ ] Search, filter, sort all work generically
- [ ] Relationship loading (with) works
- [ ] Data transformation works (including formatters)
- [ ] Null-safe operator used for relationships
- [ ] Config cached properly (invalidates on update)
- [ ] Controller uses generic resolver
- [ ] Old resolver classes deleted
- [ ] `fe:make:datasource` artisan command created
- [ ] Seeder migrates existing datasources
- [ ] Documentation created
- [ ] All existing pages/components still work
- [ ] No hard-coded datasources remain

## Testing Plan

```bash
# Test Agent datasource
curl http://localhost/api/v2/ui/datasource/Agent/query?search=test

# Test Model datasource
curl http://localhost/api/v2/ui/datasource/Model/query

# Test with filters
curl http://localhost/api/v2/ui/datasource/Agent/query?filters[status]=active

# Test with sort
curl http://localhost/api/v2/ui/datasource/Agent/query?sort[field]=name&sort[direction]=asc

# Verify UI still works
# Visit: /v2/pages/page.agent.table.modal
# Create agent, search, click row
```

## Advanced Features (Optional)

### Custom Transformers

Allow registering custom transformer classes for complex logic:

```php
'schema_json' => [
    'transform' => [
        'id' => 'id',
        'full_name' => ['transformer' => 'App\Transformers\FullNameTransformer'],
    ],
],
```

### Aggregations

Support aggregate queries:

```php
'capabilities_json' => [
    'supports' => ['list', 'detail', 'search', 'paginate', 'aggregate'],
    'aggregates' => ['count', 'sum:amount', 'avg:rating'],
],
```

### Computed Fields

Define computed fields in config:

```php
'schema_json' => [
    'computed' => [
        'is_premium' => ['expression' => 'plan_id > 1'],
        'days_old' => ['expression' => 'DATEDIFF(NOW(), created_at)'],
    ],
],
```

## Dependencies

- T-UIB-SPRINT2-01-TYPES (types can provide schemas)
- T-UIB-SPRINT2-03-SCHEMA (new datasource fields)
- Existing v2 API structure

## Estimated Time

4-6 hours

## Notes

- **Laravel's `data_get()` helper** is perfect for nested field access (`agentProfile.provider`)
- **Cache invalidation**: When datasource config updates, bust cache
- **Performance**: Config-driven adds minimal overhead (~1-2ms vs hard-coded)
- **Flexibility**: Can handle 80% of use cases; complex cases can still use custom resolvers
- **Type safety**: Consider generating TypeScript types from schema

## Related Tasks

- T-UIB-SPRINT2-01-TYPES (types provide schema metadata)
- T-UIB-SPRINT2-03-SCHEMA (datasource table fields)
- T-UIB-SPRINT2-04-COMPONENTS (components consume datasources)

## Migration Checklist

- [ ] Create GenericDataSourceResolver
- [ ] Test with Agent (matches old output)
- [ ] Test with Model (matches old output)
- [ ] Create config entries for both
- [ ] Update controller
- [ ] Test UI (/v2/pages/page.agent.table.modal)
- [ ] Delete old resolvers
- [ ] Create artisan command
- [ ] Write documentation
- [ ] Update seeder
