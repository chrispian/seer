# API Routes Simplification (Oct 2025)

## Decision: Remove `/query` Suffix

### Old Pattern (removed)
```
POST /api/v2/ui/datasources/{alias}/query
GET  /api/v2/ui/datasources/{alias}/query
```

### New Pattern (current)
```
POST /api/v2/ui/datasources/{alias}
GET  /api/v2/ui/datasources/{alias}
```

## Rationale

1. **Simpler URLs** - RESTful pattern where POST/GET to same resource performs the query
2. **Forward-only** - No backward compatibility burden
3. **Consistent** - Matches standard REST conventions

## The `/query` suffix was redundant

- Querying a datasource is the primary action
- No need for `/query` suffix when that's the main purpose
- POST to datasource = query with parameters
- GET to datasource = query with URL params

## Controller Method

Both GET and POST map to the same controller method:
```php
DataSourceController::query(Request $request, string $alias)
```

The method name is `query()` but the route doesn't need `/query` in the URL.

## If You Need to Restore `/query`

If for some reason the `/query` suffix needs to come back:

1. **Update routes** in `modules/UiBuilder/routes/api.php`:
   ```php
   Route::get('{alias}/query', [DataSourceController::class, 'query']);
   Route::post('{alias}/query', [DataSourceController::class, 'query']);
   ```

2. **Update frontend** in:
   - `resources/js/components/v2/hooks/useDataSource.ts`
   - `resources/js/components/v2/advanced/DataTableComponent.tsx`
   
   Change:
   ```ts
   `/api/v2/ui/datasources/${alias}`
   ```
   To:
   ```ts
   `/api/v2/ui/datasources/${alias}/query`
   ```

3. **Rebuild**:
   ```bash
   php artisan route:clear
   npm run build
   ```

## Current Routes

```
GET  /api/v2/ui/datasources/{alias}             - Query datasource
POST /api/v2/ui/datasources/{alias}             - Query datasource with filters
GET  /api/v2/ui/datasources/{alias}/capabilities - Get datasource capabilities
```

Clean, simple, RESTful. ✨
