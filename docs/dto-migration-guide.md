# DTO Migration Guide

## Why DTOs?

**Problem**: Provider data structure is inconsistent
- Sometimes it's an array
- Sometimes it's an object
- Properties accessed via array keys `['enabled']`
- Properties accessed via object `->enabled`
- Easy to break when changing structure

**Solution**: Data Transfer Objects (DTOs)
- Single source of truth for data structure
- Type-safe property access
- IDE autocomplete
- Changes only needed in one place

## ProviderDTO Structure

```php
class ProviderDTO
{
    public readonly string $name;
    public readonly string $displayName;
    public readonly bool $enabled;
    public readonly string $healthStatus;
    public readonly bool $isAvailable;
    public readonly array $capabilities;
    public readonly Collection $models;
    public readonly Collection $credentials;
    // ... more properties
}
```

## Migration Steps

### Step 1: Update Service to Return DTO

**Before:**
```php
public function getProvider(string $id): ?array
{
    $provider = AiProvider::find($id);
    return [
        'name' => $provider->name,
        'enabled' => $provider->enabled,
        // ... more fields
    ];
}
```

**After:**
```php
public function getProvider(string $id): ?\App\DTOs\ProviderDTO
{
    $provider = AiProvider::find($id);
    return ProviderDTO::fromModel($provider);
}
```

### Step 2: Update Controllers to Use DTO Properties

**Before:**
```php
$providerData = $this->service->getProvider($id);
$enabled = $providerData['enabled'];  // Array access
$name = $providerData->name;          // Object access (broken!)
```

**After:**
```php
$provider = $this->service->getProvider($id);
$enabled = $provider->enabled;        // Always object access
$name = $provider->displayName;       // Type-safe, autocomplete works
```

### Step 3: Update Resources

**Before:**
```php
return [
    'name' => $providerData['name'],
    'enabled' => $providerData['enabled'],
];
```

**After:**
```php
return $provider->toArray();  // Or customize as needed
```

## Benefits

### 1. Type Safety
```php
// Old way - no type checking
$enabled = $providerData['enabled'];  // Could be null, wrong type, etc.

// New way - fully typed
$enabled = $provider->enabled;  // Always bool, never null
```

### 2. Refactoring Safety
```php
// Change property name in ONE place (DTO)
// IDE finds all usages automatically
```

### 3. Documentation
```php
// DTO serves as documentation
// All properties visible in one place
// With types and descriptions
```

### 4. Testing
```php
// Easy to create test data
$provider = new ProviderDTO(
    name: 'test',
    displayName: 'Test Provider',
    enabled: true,
    // ... explicit all required fields
);
```

## Files to Update

Priority order:

1. ✅ `app/DTOs/ProviderDTO.php` - Created
2. ⏳ `app/Services/ProviderManagementService.php` - Return DTOs
3. ⏳ `app/Http/Controllers/Api/ProviderController.php` - Use DTO properties
4. ⏳ `app/Http/Controllers/Api/ModelController.php` - Use DTO properties
5. ⏳ `app/Http/Resources/ProviderResource.php` - Accept DTO
6. ⏳ Other files using `getProvider()` or `getAllProviders()`

## Example: Full Migration

### Service
```php
public function getAllProviders(): Collection
{
    return AiProvider::all()
        ->map(fn($model) => ProviderDTO::fromModel($model));
}
```

### Controller
```php
public function index()
{
    $providers = $this->service->getAllProviders();
    
    return response()->json([
        'data' => $providers->map(fn($p) => $p->toArray()),
    ]);
}
```

### Resource (if needed)
```php
public function toArray(Request $request): array
{
    /** @var ProviderDTO $provider */
    $provider = $this->resource;
    
    return [
        'id' => $provider->name,
        'name' => $provider->displayName,
        'enabled' => $provider->enabled,
        'status' => $provider->healthStatus,
        // ... only include what API needs
    ];
}
```

## Next Steps

1. Update `ProviderManagementService` methods to return DTOs
2. Update all controllers to use DTO properties
3. Run tests
4. Create DTOs for other models (AiModel, Agent, etc.)

## Creating More DTOs

Follow this pattern for other models:

```php
class ModelDTO
{
    public function __construct(
        public readonly string $modelId,
        public readonly string $name,
        // ... properties
    ) {}
    
    public static function fromModel(AiModel $model): self
    {
        return new self(
            modelId: $model->model_id,
            name: $model->name,
            // ...
        );
    }
    
    public function toArray(): array { /* ... */ }
}
```

Then: Database → Model → DTO → Controller → API Response

Clean, type-safe, maintainable! ✨
