# FE Types System Documentation

**Status**: ✅ Implemented  
**Date**: 2025-10-15  
**Task**: T-UIB-SPRINT2-01-TYPES  

## Overview

The FE Types System provides config-first, strongly-typed schema management for dynamic data sources in the UI Builder v2. It supports multiple data source types (Eloquent models, raw database tables, Sushi models, and external APIs) with a unified interface.

## Architecture

### Core Components

1. **Models** (`app/Models/`)
   - `FeType` - Main type definition with alias, source type, and configuration
   - `FeTypeField` - Field definitions with search/sort/filter capabilities
   - `FeTypeRelation` - Relationship definitions between types

2. **DTOs** (`app/DTOs/Types/`)
   - `TypeSchema` - Immutable type schema representation
   - `TypeField` - Field configuration
   - `TypeRelation` - Relation configuration

3. **Services** (`app/Services/Types/`)
   - `TypeRegistry` - Manages type schemas with caching
   - `TypeResolver` - Queries and retrieves data based on type schemas

4. **Controller** (`app/Http/Controllers/Api/`)
   - `TypesController` - API endpoints for querying types

## Database Schema

### fe_types
- `id` - Primary key
- `alias` - Unique type identifier (e.g., "Agent", "Invoice")
- `source_type` - Data source type: eloquent, database, sushi, api
- `config` - JSON configuration (model class, table name, etc.)
- `capabilities` - JSON array of supported operations
- `metadata` - Additional metadata (description, icon, etc.)
- `enabled` - Boolean flag
- Timestamps

### fe_type_fields
- `id` - Primary key
- `fe_type_id` - Foreign key to fe_types
- `name` - Field name
- `type` - Data type (string, integer, date, etc.)
- `label` - Display label
- `required` - Boolean flag
- `searchable` - Boolean flag
- `sortable` - Boolean flag
- `filterable` - Boolean flag
- `validation` - JSON validation rules
- `metadata` - Additional metadata
- `order` - Display order
- Timestamps

### fe_type_relations
- `id` - Primary key
- `fe_type_id` - Foreign key to fe_types
- `name` - Relation name
- `type` - Relation type (hasMany, belongsTo, etc.)
- `related_type` - Target type alias
- `foreign_key` - Foreign key column
- `local_key` - Local key column
- `metadata` - Additional metadata
- Timestamps

## API Endpoints

### Query Type
```
GET /api/v2/ui/types/{alias}/query
```

**Parameters:**
- `search` - Search term (searches across searchable fields)
- `sort` - Field to sort by
- `direction` - Sort direction (asc/desc)
- `filters` - Array of field filters
- `per_page` - Results per page (default: 15)

**Response:**
```json
{
  "data": [...],
  "meta": {
    "total": 100,
    "per_page": 15,
    "current_page": 1,
    "last_page": 7
  }
}
```

### Show Single Record
```
GET /api/v2/ui/types/{alias}/{id}
```

**Response:**
```json
{
  "data": {...}
}
```

## Usage Examples

### Registering a Type

```php
use App\DTOs\Types\TypeSchema;
use App\DTOs\Types\TypeField;
use App\Services\Types\TypeRegistry;

$registry = app(TypeRegistry::class);

$schema = new TypeSchema(
    alias: 'Agent',
    sourceType: 'eloquent',
    fields: [
        new TypeField(
            name: 'designation',
            type: 'string',
            label: 'Designation',
            searchable: true,
            sortable: true,
            filterable: true
        ),
        // ... more fields
    ],
    config: [
        'model' => \App\Models\Agent::class,
    ],
    enabled: true
);

$registry->register($schema);
```

### Querying a Type

```php
use App\Services\Types\TypeResolver;

$resolver = app(TypeResolver::class);

$result = $resolver->query('Agent', [
    'search' => 'John',
    'sort' => 'created_at',
    'direction' => 'desc',
    'per_page' => 20
]);

// $result contains:
// - data: array of records
// - meta: pagination info
```

### Getting a Single Record

```php
$resolver = app(TypeResolver::class);
$record = $resolver->show('Agent', $id);
```

## Source Types

### eloquent
Uses Laravel Eloquent models. Requires `model` in config.

```php
'config' => [
    'model' => \App\Models\Agent::class,
]
```

### database
Uses raw database queries. Requires `table` and optionally `primary_key` in config.

```php
'config' => [
    'table' => 'agents',
    'primary_key' => 'id',
]
```

### sushi
For in-memory data sources (implementation pending).

### api
For external API data sources (implementation pending).

## Caching

The TypeRegistry caches type schemas for 1 hour (3600 seconds). Cache can be refreshed:

```php
$registry->refresh('Agent');     // Refresh single type
$registry->refreshAll();         // Refresh all types
```

## Demo Seeder

The `TypesDemoSeeder` registers an Agent type for testing purposes. Run with:

```bash
php artisan db:seed --class=TypesDemoSeeder
```

## Testing

```bash
# Run migrations
php artisan migrate

# Seed demo data
php artisan db:seed --class=TypesDemoSeeder

# Test via tinker
php artisan tinker
> $resolver = app(\App\Services\Types\TypeResolver::class);
> $result = $resolver->query('Agent', ['per_page' => 5]);
> print_r($result);
```

## Integration Notes

- The system is designed to be extended with additional source types
- Field capabilities (searchable, sortable, filterable) drive UI component behavior
- The unified query interface allows the UI to treat all data sources consistently
- Type schemas can be defined in seeders or registered dynamically at runtime

## Future Enhancements

1. **Codegen Features** - Auto-generate TypeScript interfaces from schemas
2. **Sushi Support** - Complete implementation for in-memory data sources
3. **API Support** - Complete implementation for external APIs
4. **Schema Validation** - Validate data against field types and rules
5. **Computed Fields** - Support for derived/calculated fields
6. **Nested Relations** - Support for deeply nested relationship loading
