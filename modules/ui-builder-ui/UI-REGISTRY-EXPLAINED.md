# UI Registry Explained

## What is the UI Registry?

The `fe_ui_registry` table is a **component catalog** that tracks all UI components, pages, data sources, and layouts registered in the system. Think of it as an "inventory" or "manifest" of all UI elements.

## Table Structure

```sql
fe_ui_registry
├── type              # Type of item (component, page, datasource, layout)
├── name              # Human-friendly name
├── slug              # Unique identifier
├── description       # Optional description
├── version           # Version number
├── reference_type    # Polymorphic relationship type
├── reference_id      # Polymorphic relationship ID
├── metadata          # JSON metadata
├── hash              # Content hash for change detection
├── is_active         # Active status
├── published_at      # Publication timestamp
└── soft deletes
```

## What's in the Registry?

Currently stores 5 items:

| Type       | Slug                       | Name                |
|------------|----------------------------|---------------------|
| component  | component.table            | Table Component     |
| component  | component.button           | Button Component    |
| component  | layout.modal               | Modal Layout        |
| datasource | datasource.agent           | Agent Data Source   |
| page       | page.agent.table.modal     | Agent Table Page    |

## Use Cases

### 1. Component Discovery
Frontend code can query the registry to discover available components:

```php
// Get all active components
$components = Registry::active()
    ->byType('component')
    ->published()
    ->get();
```

### 2. Version Control
Track which version of a component is deployed:

```php
$component = Registry::where('slug', 'component.table')->first();
echo $component->version; // "1.0.0"
```

### 3. Change Detection
The hash field detects when a component's metadata changes:

```php
$registry->generateHash(); // SHA256 of slug + version + metadata + timestamp
```

### 4. Polymorphic References
Link registry items to actual database records:

```php
// A registry item can reference any model
$registry->reference_type = 'HollisLabs\UiBuilder\Models\Component';
$registry->reference_id = 42;

// Access the related model
$component = $registry->reference; // Uses morphTo relationship
```

### 5. Feature Gating
Control which components are available:

```php
// Disable a component
Registry::where('slug', 'component.table')
    ->update(['is_active' => false]);

// Or soft delete
$registry->delete();
```

## Integration with DataSource System

The Registry is **also a DataSource**, meaning you can query it via the DataSource API:

```bash
# Get all registry items
GET /api/ui/datasources/UiRegistry

# Get specific item
GET /api/ui/types/UiRegistry/1
```

**DataSource Configuration:**
```php
Datasource::where('alias', 'UiRegistry')->first();
// model_class: HollisLabs\UiBuilder\Models\Registry
// Searchable: type, name, slug
// Filterable: type, is_active
// Sortable: name, version, published_at
```

## Related DTOs

### RegistryItem DTO
Located at: `vendor/hollis-labs/ui-builder/src/DTOs/RegistryItem.php`

Used for transferring registry data between layers:

```php
use HollisLabs\UiBuilder\DTOs\RegistryItem;

$dto = RegistryItem::fromArray($dbRecord);
$array = $dto->toArray();
```

**Properties:**
- `type` - Item type (component, page, datasource, layout)
- `name` - Display name
- `slug` - Unique slug
- `description` - Optional description
- `version` - Version string
- `referenceType` - Polymorphic type
- `referenceId` - Polymorphic ID
- `metadata` - Additional data
- `isActive` - Active status
- `publishedAt` - Publish timestamp

## Typical Workflow

1. **Component Created** → Register in `fe_ui_registry`
2. **Frontend Loads** → Query registry for available components
3. **Component Updated** → Hash recalculated, version bumped
4. **Component Deployed** → `published_at` set, `is_active` enabled
5. **Component Deprecated** → `is_active` disabled or soft deleted

## Why Not Just Query Models Directly?

The registry provides:

- ✅ **Unified catalog** - All UI elements in one place
- ✅ **Version tracking** - Know what's deployed
- ✅ **Change detection** - Hash-based change tracking
- ✅ **Soft deletes** - Keep history of removed items
- ✅ **Polymorphic references** - Flexible relationships
- ✅ **Feature flags** - Control availability

## Related Tables

- `fe_ui_pages` - Actual page configurations
- `fe_ui_components` - Actual component definitions
- `fe_ui_datasources` - Actual data source mappings

The Registry is the **manifest** that points to these actual implementations.
