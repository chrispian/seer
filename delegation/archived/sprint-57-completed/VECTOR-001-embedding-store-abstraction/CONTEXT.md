# Context: EmbeddingStore Abstraction Layer

## Current State Analysis

### **Existing Implementation Gaps**
The current vector implementation is tightly coupled to PostgreSQL+pgvector:

```php
// app/Jobs/EmbedFragment.php:69-74 - Direct SQL with pgvector syntax
DB::statement('
    INSERT INTO fragment_embeddings (fragment_id, provider, model, dims, embedding, content_hash, created_at, updated_at)
    VALUES (?, ?, ?, ?, ?::vector, ?, now(), now())
    ON CONFLICT (fragment_id, provider, model, content_hash)
    DO UPDATE SET dims = EXCLUDED.dims, embedding = EXCLUDED.embedding, updated_at = now()
', [$fragment->id, $this->provider, $this->model, $res['dims'], $vec, $this->contentHash]);
```

```php
// app/Actions/Commands/SearchCommand.php:158-180 - PostgreSQL-specific search
$sql = "
    WITH p AS (
      SELECT ?::vector AS qe, websearch_to_tsquery('simple', ?) AS qq
    )
    SELECT
      (1 - (e.embedding <=> p.qe)) AS vec_sim,
      ts_rank_cd(to_tsvector('simple', {$docExpr}), p.qq) AS txt_rank
    FROM fragments f
    JOIN fragment_embeddings e ON e.fragment_id = f.id
";
```

### **Database Driver Detection**
Both critical files check for PostgreSQL but lack abstraction:

```php
// Repeated in both EmbedFragment.php and SearchCommand.php
private function hasPgVectorSupport(): bool
{
    $driver = DB::connection()->getDriverName();
    if ($driver !== 'pgsql') {
        return false;
    }
    // Check pgvector extension...
}
```

### **Architecture Problems**
1. **Direct SQL**: Embedding storage uses raw SQL with database-specific syntax
2. **Search Coupling**: Vector search hardcoded to PostgreSQL functions
3. **No Abstraction**: Business logic mixed with database implementation details
4. **Feature Detection**: Scattered driver checks without centralized management

## Target Architecture

### **Abstraction Layer Design**
```php
interface EmbeddingStoreInterface
{
    public function store(int $fragmentId, string $provider, string $model, int $dims, array $vector, string $contentHash): void;
    public function exists(int $fragmentId, string $provider, string $model, string $contentHash): bool;
    public function search(array $queryVector, string $provider, int $limit = 20): array;
    public function isVectorSupportAvailable(): bool;
}

class EmbeddingStoreManager
{
    public function driver(?string $connection = null): EmbeddingStoreInterface;
    public function createSqliteDriver(): SqliteVectorStore;
    public function createPgVectorDriver(): PgVectorStore;
}
```

### **Service Provider Integration**
```php
// config/app.php providers or dedicated service provider
public function register(): void
{
    $this->app->singleton(EmbeddingStoreManager::class);
    
    $this->app->bind(EmbeddingStoreInterface::class, function ($app) {
        return $app->make(EmbeddingStoreManager::class)->driver();
    });
}
```

### **Configuration-Driven Selection**
```php
// config/fragments.php
'embeddings' => [
    'driver' => env('EMBEDDINGS_DRIVER', 'auto'), // auto, sqlite, postgresql
    'sqlite' => [
        'extension' => env('SQLITE_VECTOR_EXTENSION', 'sqlite-vec'),
    ],
    'postgresql' => [
        'extension_check' => env('PGVECTOR_EXTENSION_CHECK', true),
    ],
],
```

## Implementation Strategy

### **Phase 1: Interface Definition**
- Define `EmbeddingStoreInterface` with all required operations
- Create `EmbeddingStoreManager` for driver resolution
- Add configuration structure to `config/fragments.php`

### **Phase 2: Service Provider Setup**
- Create dedicated service provider or extend existing
- Implement driver resolution logic based on configuration
- Add dependency injection bindings

### **Phase 3: Driver Detection Logic**
- Centralize database driver detection
- Add vector extension availability checks
- Implement fallback strategies

### **Phase 4: Integration Points**
- Update `EmbedFragment` job to use abstraction
- Update `SearchCommand` to use abstraction  
- Preserve existing method signatures for compatibility

## Dependencies & Integration

### **Files to Modify**
- `app/Jobs/EmbedFragment.php` - Replace direct SQL with abstraction
- `app/Actions/Commands/SearchCommand.php` - Replace direct SQL with abstraction
- `config/fragments.php` - Add driver configuration
- `app/Providers/AppServiceProvider.php` - Or create dedicated provider

### **Backward Compatibility**
- Existing PostgreSQL deployments must continue working unchanged
- No changes to public APIs or method signatures
- Configuration defaults to current behavior unless explicitly changed

### **Testing Strategy**
- Unit tests for interface implementations
- Integration tests with both SQLite and PostgreSQL
- Mocking strategies for driver switching logic

## Risk Assessment

### **High Risk**
- **Interface Design**: Getting abstraction wrong affects entire vector system
- **Driver Resolution**: Configuration-based switching must be bulletproof

### **Medium Risk**  
- **Performance Impact**: Abstraction layer overhead must be minimal
- **Feature Parity**: Both backends must support equivalent operations

### **Mitigation Strategies**
- Start with minimal viable interface, expand as needed
- Extensive testing with both database backends
- Performance benchmarking to ensure acceptable overhead
- Rollback plan to direct implementation if abstraction fails