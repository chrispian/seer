# Implementation Plan: EmbeddingStore Abstraction Layer

## Overview
Create a database-agnostic abstraction layer for embedding storage and retrieval, enabling seamless switching between SQLite+sqlite-vec and PostgreSQL+pgvector backends.

## Technical Implementation

### **Step 1: Core Interface Definition (3-4h)**

#### Create EmbeddingStore Contract
**File**: `app/Contracts/EmbeddingStoreInterface.php`
```php
<?php

namespace App\Contracts;

interface EmbeddingStoreInterface
{
    /**
     * Store or update an embedding for a fragment
     */
    public function store(
        int $fragmentId,
        string $provider,
        string $model,
        int $dimensions,
        array $vector,
        string $contentHash
    ): void;

    /**
     * Check if embedding exists and is current
     */
    public function exists(
        int $fragmentId,
        string $provider,
        string $model,
        string $contentHash
    ): bool;

    /**
     * Search for similar embeddings
     */
    public function search(
        array $queryVector,
        string $provider,
        int $limit = 20,
        float $threshold = 0.0
    ): array;

    /**
     * Check if vector operations are available
     */
    public function isVectorSupportAvailable(): bool;

    /**
     * Get driver-specific information
     */
    public function getDriverInfo(): array;
}
```

#### Create Search Result DTO
**File**: `app/DTOs/VectorSearchResult.php`
```php
<?php

namespace App\DTOs;

readonly class VectorSearchResult
{
    public function __construct(
        public int $fragmentId,
        public float $similarity,
        public float $textRank,
        public float $combinedScore,
        public string $snippet
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->fragmentId,
            'vec_sim' => $this->similarity,
            'txt_rank' => $this->textRank,
            'score' => $this->combinedScore,
            'snippet' => $this->snippet,
        ];
    }
}
```

### **Step 2: Manager Class Implementation (4-6h)**

#### Create EmbeddingStore Manager
**File**: `app/Services/Embeddings/EmbeddingStoreManager.php`
```php
<?php

namespace App\Services\Embeddings;

use App\Contracts\EmbeddingStoreInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class EmbeddingStoreManager
{
    protected array $drivers = [];

    public function driver(?string $connection = null): EmbeddingStoreInterface
    {
        $connection = $connection ?? $this->getDefaultDriver();
        
        if (!isset($this->drivers[$connection])) {
            $this->drivers[$connection] = $this->createDriver($connection);
        }

        return $this->drivers[$connection];
    }

    protected function getDefaultDriver(): string
    {
        $configured = config('fragments.embeddings.driver', 'auto');
        
        if ($configured === 'auto') {
            return $this->detectOptimalDriver();
        }

        return $configured;
    }

    protected function detectOptimalDriver(): string
    {
        $dbDriver = DB::connection()->getDriverName();
        
        return match ($dbDriver) {
            'sqlite' => 'sqlite',
            'pgsql' => 'postgresql',
            default => throw new InvalidArgumentException("Unsupported database driver: {$dbDriver}")
        };
    }

    protected function createDriver(string $driver): EmbeddingStoreInterface
    {
        return match ($driver) {
            'sqlite' => new SqliteVectorStore(),
            'postgresql' => new PgVectorStore(),
            default => throw new InvalidArgumentException("Unknown embedding driver: {$driver}")
        };
    }

    public function getSupportedDrivers(): array
    {
        return ['sqlite', 'postgresql'];
    }
}
```

### **Step 3: Configuration Integration (2-3h)**

#### Update Fragments Configuration
**File**: `config/fragments.php` (add to embeddings section)
```php
'embeddings' => [
    'enabled' => env('EMBEDDINGS_ENABLED', false),
    'driver' => env('EMBEDDINGS_DRIVER', 'auto'), // auto, sqlite, postgresql
    'provider' => env('EMBEDDINGS_PROVIDER', 'openai'),
    'model' => env('OPENAI_EMBEDDING_MODEL', 'text-embedding-3-small'),
    'version' => env('EMBEDDINGS_VERSION', '1'),
    
    // Driver-specific configuration
    'drivers' => [
        'sqlite' => [
            'extension' => env('SQLITE_VECTOR_EXTENSION', 'sqlite-vec'),
            'extension_path' => env('SQLITE_VECTOR_EXTENSION_PATH', null),
        ],
        'postgresql' => [
            'extension_check' => env('PGVECTOR_EXTENSION_CHECK', true),
        ],
    ],
],
```

#### Create Service Provider
**File**: `app/Providers/EmbeddingStoreServiceProvider.php`
```php
<?php

namespace App\Providers;

use App\Contracts\EmbeddingStoreInterface;
use App\Services\Embeddings\EmbeddingStoreManager;
use Illuminate\Support\ServiceProvider;

class EmbeddingStoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(EmbeddingStoreManager::class);

        $this->app->bind(EmbeddingStoreInterface::class, function ($app) {
            return $app->make(EmbeddingStoreManager::class)->driver();
        });
    }

    public function boot(): void
    {
        // Additional boot logic if needed
    }
}
```

### **Step 4: Driver Skeleton Implementation (2-3h)**

#### PostgreSQL Driver (Preserve Current Logic)
**File**: `app/Services/Embeddings/PgVectorStore.php`
```php
<?php

namespace App\Services\Embeddings;

use App\Contracts\EmbeddingStoreInterface;
use App\DTOs\VectorSearchResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PgVectorStore implements EmbeddingStoreInterface
{
    public function store(int $fragmentId, string $provider, string $model, int $dimensions, array $vector, string $contentHash): void
    {
        // Move existing logic from EmbedFragment job
        $vec = '[' . implode(',', $vector) . ']';
        
        DB::statement('
            INSERT INTO fragment_embeddings (fragment_id, provider, model, dims, embedding, content_hash, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?::vector, ?, now(), now())
            ON CONFLICT (fragment_id, provider, model, content_hash)
            DO UPDATE SET dims = EXCLUDED.dims, embedding = EXCLUDED.embedding, updated_at = now()
        ', [$fragmentId, $provider, $model, $dimensions, $vec, $contentHash]);
    }

    public function exists(int $fragmentId, string $provider, string $model, string $contentHash): bool
    {
        return DB::table('fragment_embeddings')
            ->where('fragment_id', $fragmentId)
            ->where('provider', $provider)
            ->where('model', $model)
            ->where('content_hash', $contentHash)
            ->exists();
    }

    public function search(array $queryVector, string $provider, int $limit = 20, float $threshold = 0.0): array
    {
        // Move existing logic from SearchCommand
        // Return array of VectorSearchResult DTOs
    }

    public function isVectorSupportAvailable(): bool
    {
        try {
            $result = DB::select("SELECT 1 FROM pg_extension WHERE extname = 'vector'");
            return !empty($result);
        } catch (\Throwable $e) {
            Log::warning('PgVectorStore: could not check pgvector availability', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function getDriverInfo(): array
    {
        return [
            'driver' => 'postgresql',
            'extension' => 'pgvector',
            'available' => $this->isVectorSupportAvailable(),
        ];
    }
}
```

#### SQLite Driver (Skeleton for Next Task)
**File**: `app/Services/Embeddings/SqliteVectorStore.php`
```php
<?php

namespace App\Services\Embeddings;

use App\Contracts\EmbeddingStoreInterface;
use RuntimeException;

class SqliteVectorStore implements EmbeddingStoreInterface
{
    public function store(int $fragmentId, string $provider, string $model, int $dimensions, array $vector, string $contentHash): void
    {
        throw new RuntimeException('SQLite vector store implementation pending - VECTOR-002');
    }

    public function exists(int $fragmentId, string $provider, string $model, string $contentHash): bool
    {
        throw new RuntimeException('SQLite vector store implementation pending - VECTOR-002');
    }

    public function search(array $queryVector, string $provider, int $limit = 20, float $threshold = 0.0): array
    {
        throw new RuntimeException('SQLite vector store implementation pending - VECTOR-002');
    }

    public function isVectorSupportAvailable(): bool
    {
        return false; // Will be implemented in VECTOR-002
    }

    public function getDriverInfo(): array
    {
        return [
            'driver' => 'sqlite',
            'extension' => 'sqlite-vec',
            'available' => $this->isVectorSupportAvailable(),
        ];
    }
}
```

### **Step 5: Integration & Testing (1-2h)**

#### Update Service Provider Registration
**File**: `bootstrap/providers.php` or `config/app.php`
```php
// Add to providers array
App\Providers\EmbeddingStoreServiceProvider::class,
```

#### Basic Integration Test
**File**: `tests/Unit/EmbeddingStoreManagerTest.php`
```php
<?php

namespace Tests\Unit;

use App\Contracts\EmbeddingStoreInterface;
use App\Services\Embeddings\EmbeddingStoreManager;
use App\Services\Embeddings\PgVectorStore;
use Tests\TestCase;

class EmbeddingStoreManagerTest extends TestCase
{
    public function test_resolves_postgresql_driver_for_pgsql_connection()
    {
        config(['database.default' => 'pgsql']);
        
        $manager = new EmbeddingStoreManager();
        $driver = $manager->driver();
        
        $this->assertInstanceOf(PgVectorStore::class, $driver);
        $this->assertInstanceOf(EmbeddingStoreInterface::class, $driver);
    }

    public function test_driver_implements_contract()
    {
        $manager = app(EmbeddingStoreManager::class);
        $driver = $manager->driver();
        
        $this->assertInstanceOf(EmbeddingStoreInterface::class, $driver);
    }
}
```

## Validation Checklist

### **Functionality**
- [ ] EmbeddingStoreInterface defines complete API
- [ ] EmbeddingStoreManager resolves drivers correctly
- [ ] Configuration supports both driver selection and auto-detection
- [ ] Service provider bindings work with dependency injection
- [ ] PostgreSQL driver preserves existing functionality

### **Integration**
- [ ] Laravel service container resolves EmbeddingStoreInterface
- [ ] Configuration changes don't break existing deployments
- [ ] Driver switching works without code changes
- [ ] Error handling for unsupported drivers

### **Quality**
- [ ] Unit tests cover manager and driver resolution
- [ ] Documentation explains driver selection logic
- [ ] Code follows Laravel conventions and PSR standards
- [ ] No performance regression from abstraction layer

## Time Allocation

| Task | Estimated Time | Complexity |
|------|----------------|------------|
| Interface & DTO definition | 3-4h | Medium |
| Manager implementation | 4-6h | High |
| Configuration integration | 2-3h | Low |
| Driver skeletons | 2-3h | Medium |
| Testing & validation | 1-2h | Low |
| **Total** | **12-18h** | **Medium-High** |

## Dependencies & Next Steps

### **Immediate Dependencies**
- None - this is the foundation task

### **Blocks Following Tasks**
- VECTOR-002: SQLite implementation needs interface contract
- VECTOR-003: Migrations need driver detection logic
- VECTOR-004: Search abstraction needs manager class

### **Handoff Requirements**
- Complete interface documentation
- Working PostgreSQL driver with current functionality
- Manager class ready for SQLite driver implementation
- Configuration structure defined and documented