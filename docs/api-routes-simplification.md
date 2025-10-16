# API Routes - Standard REST (Oct 2025)

## Current Pattern (Standard REST)

```
GET  /api/v2/ui/datasources/{alias}             - Query/list records
POST /api/v2/ui/datasources/{alias}             - Create new record
GET  /api/v2/ui/datasources/{alias}/capabilities - Get datasource capabilities
```

## Controller Methods

- `GET  /{alias}` → `DataSourceController::query()` - Query with URL params
- `POST /{alias}` → `DataSourceController::store()` - Create new record
- `GET  /{alias}/capabilities` → `DataSourceController::capabilities()`

## Rationale

1. **Standard REST** - GET for reading, POST for creating
2. **No ambiguity** - Clear separation between querying and creating
3. **URL parameters for queries** - Search, filters, sorting via query string
4. **Request body for creates** - Model data in POST body

## Query Parameters

GET requests accept these query parameters:

```
?search=term                    - Full-text search
&filters[field]=value           - Filter by field
&sort[field]=name              - Sort field
&sort[direction]=asc|desc      - Sort direction  
&page=1                        - Page number
&per_page=15                   - Results per page
```

Example:
```
GET /api/v2/ui/datasources/Agent?search=john&filters[status]=active&page=1&per_page=10
```

## Request/Response Examples

### Query (GET)
```bash
GET /api/v2/ui/datasources/Agent?search=john&filters[status]=active&page=1
```

Response:
```json
{
  "data": [...],
  "meta": {
    "total": 42,
    "page": 1,
    "per_page": 15,
    "last_page": 3
  },
  "hash": "..."
}
```

### Create (POST)
```bash
POST /api/v2/ui/datasources/Agent
Content-Type: application/json

{
  "name": "Test Agent",
  "designation": "T-123",
  "status": "active"
}
```

Response:
```json
{
  "id": "uuid",
  "name": "Test Agent",
  "designation": "T-123",
  "status": "active",
  "updated_at": "2025-10-16T..."
}
```
