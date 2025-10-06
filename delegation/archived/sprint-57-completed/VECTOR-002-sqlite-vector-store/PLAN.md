# Implementation Plan: SQLite Vector Store Implementation

## Overview
Implement a complete SQLite-based vector store using the sqlite-vec extension that provides feature parity with the existing PostgreSQL+pgvector implementation.

## Technical Implementation

### **Step 1: Extension Integration & Detection (3-4h)**

#### SQLite Extension Loading
**File**: `app/Services/Embeddings/SqliteVectorStore.php` (foundation)
```php
<?php

namespace App\Services\Embeddings;

use App\Contracts\EmbeddingStoreInterface;
use App\DTOs\VectorSearchResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PDO;
use PDOException;

class SqliteVectorStore implements EmbeddingStoreInterface
{
    protected ?PDO $pdo = null;
    protected bool $extensionLoaded = false;
    protected array $driverInfo = [];

    public function __construct()
    {
        $this->initializeExtension();
    }

    protected function initializeExtension(): void
    {
        try {
            $this->pdo = DB::connection()->getPdo();
            
            // Attempt to load sqlite-vec extension
            $extensionPath = config('fragments.embeddings.drivers.sqlite.extension_path');
            $extensionName = config('fragments.embeddings.drivers.sqlite.extension', 'sqlite-vec');
            
            if ($extensionPath) {
                $this->pdo->loadExtension($extensionPath);
            } else {
                // Try common extension names/paths
                $this->tryLoadExtension($extensionName);
            }
            
            // Verify extension loaded by testing a function
            $this->pdo->query("SELECT vec_version()")->fetchColumn();
            $this->extensionLoaded = true;
            
            Log::info('SQLite vector extension loaded successfully', [
                'extension' => $extensionName,
                'version' => $this->getExtensionVersion(),
            ]);
            
        } catch (PDOException $e) {
            Log::warning('SQLite vector extension failed to load', [
                'error' => $e->getMessage(),
                'extension' => $extensionName ?? 'unknown',
            ]);
            $this->extensionLoaded = false;
        }
        
        $this->driverInfo = [
            'driver' => 'sqlite',
            'extension' => $extensionName ?? 'sqlite-vec',
            'available' => $this->extensionLoaded,
            'version' => $this->extensionLoaded ? $this->getExtensionVersion() : null,
        ];
    }

    protected function tryLoadExtension(string $extensionName): void
    {
        $attempts = [
            $extensionName,
            "lib{$extensionName}",
            "{$extensionName}.so",
            "lib{$extensionName}.so",
            "{$extensionName}.dll",
            "{$extensionName}.dylib",
        ];
        
        foreach ($attempts as $attempt) {
            try {
                $this->pdo->loadExtension($attempt);
                return; // Success
            } catch (PDOException $e) {
                // Try next variant
                continue;
            }
        }
        
        throw new PDOException("Could not load extension with any attempted name");
    }

    protected function getExtensionVersion(): ?string
    {
        try {
            return $this->pdo->query("SELECT vec_version()")->fetchColumn();
        } catch (PDOException $e) {
            return null;
        }
    }

    public function isVectorSupportAvailable(): bool
    {
        return $this->extensionLoaded;
    }

    public function getDriverInfo(): array
    {
        return $this->driverInfo;
    }
}
```

### **Step 2: Vector Storage Implementation (4-6h)**

#### Vector Data Handling
```php
// Add to SqliteVectorStore class

protected function vectorToBlob(array $vector): string
{
    // Convert PHP array to binary format expected by sqlite-vec
    // Using float32 little-endian format
    return pack('f*', ...$vector);
}

protected function blobToVector(string $blob): array
{
    $unpacked = unpack('f*', $blob);
    return array_values($unpacked);
}

public function store(int $fragmentId, string $provider, string $model, int $dimensions, array $vector, string $contentHash): void
{
    if (!$this->isVectorSupportAvailable()) {
        Log::warning('SQLite vector store: extension not available, skipping storage', [
            'fragment_id' => $fragmentId,
            'provider' => $provider,
        ]);
        return;
    }

    try {
        $vectorBlob = $this->vectorToBlob($vector);
        
        // SQLite upsert using INSERT OR REPLACE
        $sql = "
            INSERT OR REPLACE INTO fragment_embeddings 
            (fragment_id, provider, model, dims, embedding, content_hash, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))
        ";
        
        DB::statement($sql, [
            $fragmentId,
            $provider, 
            $model,
            $dimensions,
            $vectorBlob,
            $contentHash
        ]);
        
        // Update vector index if using virtual tables
        $this->updateVectorIndex($fragmentId, $vector);
        
        Log::debug('SQLite vector store: embedding saved', [
            'fragment_id' => $fragmentId,
            'provider' => $provider,
            'model' => $model,
            'dimensions' => $dimensions,
        ]);
        
    } catch (\Exception $e) {
        Log::error('SQLite vector store: failed to store embedding', [
            'fragment_id' => $fragmentId,
            'provider' => $provider,
            'error' => $e->getMessage(),
        ]);
        throw $e;
    }
}

protected function updateVectorIndex(int $fragmentId, array $vector): void
{
    try {
        // Insert into virtual table for efficient similarity search
        $vectorString = implode(',', $vector);
        DB::statement(
            "INSERT OR REPLACE INTO fragment_embeddings_idx (rowid, embedding) VALUES (?, '[{$vectorString}]')",
            [$fragmentId]
        );
    } catch (\Exception $e) {
        // Index update failure shouldn't break storage
        Log::warning('SQLite vector store: index update failed', [
            'fragment_id' => $fragmentId,
            'error' => $e->getMessage(),
        ]);
    }
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
```

### **Step 3: Vector Search Implementation (4-6h)**

#### Basic Vector Similarity Search
```php
// Add to SqliteVectorStore class

public function search(array $queryVector, string $provider, int $limit = 20, float $threshold = 0.0): array
{
    if (!$this->isVectorSupportAvailable()) {
        Log::warning('SQLite vector store: extension not available, returning empty results');
        return [];
    }

    try {
        $queryBlob = $this->vectorToBlob($queryVector);
        
        // Basic vector similarity search
        $sql = "
            SELECT 
                f.id,
                f.title,
                f.message,
                (1 - vec_distance_cosine(e.embedding, ?)) AS vec_sim,
                0 AS txt_rank,
                (1 - vec_distance_cosine(e.embedding, ?)) AS score,
                SUBSTR(COALESCE(f.message, ''), 1, 200) AS snippet
            FROM fragments f
            JOIN fragment_embeddings e ON e.fragment_id = f.id
            WHERE e.provider = ?
              AND (1 - vec_distance_cosine(e.embedding, ?)) >= ?
            ORDER BY vec_sim DESC
            LIMIT ?
        ";
        
        $results = DB::select($sql, [
            $queryBlob, // For similarity calculation
            $queryBlob, // For score calculation  
            $provider,
            $queryBlob, // For threshold comparison
            $threshold,
            $limit
        ]);
        
        return array_map(function ($row) {
            return (object) [
                'id' => $row->id,
                'title' => $row->title,
                'snippet' => $row->snippet,
                'vec_sim' => (float) $row->vec_sim,
                'txt_rank' => (float) $row->txt_rank,
                'score' => (float) $row->score,
            ];
        }, $results);
        
    } catch (\Exception $e) {
        Log::error('SQLite vector store: search failed', [
            'provider' => $provider,
            'limit' => $limit,
            'error' => $e->getMessage(),
        ]);
        return [];
    }
}
```

#### Hybrid Search with FTS5
```php
// Add to SqliteVectorStore class

public function hybridSearch(array $queryVector, string $queryText, string $provider, int $limit = 20): array
{
    if (!$this->isVectorSupportAvailable()) {
        return $this->fallbackTextSearch($queryText, $limit);
    }

    try {
        $queryBlob = $this->vectorToBlob($queryVector);
        
        // Check if FTS5 table exists
        if (!$this->hasFtsSupport()) {
            return $this->search($queryVector, $provider, $limit);
        }
        
        $sql = "
            WITH ranked_fragments AS (
                SELECT 
                    f.id,
                    f.title,
                    f.message,
                    (1 - vec_distance_cosine(e.embedding, ?)) AS vec_sim,
                    fts.rank AS txt_rank,
                    highlight(fragments_fts, 1, '<mark>', '</mark>') AS snippet
                FROM fragments f
                JOIN fragment_embeddings e ON e.fragment_id = f.id
                JOIN fragments_fts fts ON fts.docid = f.id
                WHERE e.provider = ?
                  AND fts MATCH ?
            )
            SELECT 
                *,
                (0.6 * txt_rank + 0.4 * vec_sim) AS score
            FROM ranked_fragments
            ORDER BY score DESC
            LIMIT ?
        ";
        
        $results = DB::select($sql, [
            $queryBlob,
            $provider,
            $queryText,
            $limit
        ]);
        
        return array_map(function ($row) {
            return (object) [
                'id' => $row->id,
                'title' => $row->title,
                'snippet' => $row->snippet,
                'vec_sim' => (float) $row->vec_sim,
                'txt_rank' => (float) $row->txt_rank,  
                'score' => (float) $row->score,
            ];
        }, $results);
        
    } catch (\Exception $e) {
        Log::error('SQLite vector store: hybrid search failed', [
            'provider' => $provider,
            'query_text' => $queryText,
            'error' => $e->getMessage(),
        ]);
        
        // Fallback to vector-only search
        return $this->search($queryVector, $provider, $limit);
    }
}

protected function hasFtsSupport(): bool
{
    try {
        $result = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name='fragments_fts'");
        return !empty($result);
    } catch (\Exception $e) {
        return false;
    }
}

protected function fallbackTextSearch(string $queryText, int $limit): array
{
    // Fallback to basic text search when vectors unavailable
    $results = DB::table('fragments')
        ->where('message', 'LIKE', "%{$queryText}%")
        ->orWhere('title', 'LIKE', "%{$queryText}%")
        ->limit($limit)
        ->get();
        
    return $results->map(function ($row) use ($queryText) {
        return (object) [
            'id' => $row->id,
            'title' => $row->title,
            'snippet' => substr($row->message ?? '', 0, 200),
            'vec_sim' => 0.0,
            'txt_rank' => 1.0,
            'score' => 1.0,
        ];
    })->toArray();
}
```

### **Step 4: Index Optimization (2-3h)**

#### Virtual Table Setup
```php
// Add to SqliteVectorStore class

public function createVectorIndex(): void
{
    if (!$this->isVectorSupportAvailable()) {
        Log::warning('SQLite vector store: cannot create index, extension not available');
        return;
    }

    try {
        // Create virtual table for vector indexing
        $dimensions = config('fragments.embeddings.dimensions', 1536);
        DB::statement("
            CREATE VIRTUAL TABLE IF NOT EXISTS fragment_embeddings_idx 
            USING vec0(
                embedding FLOAT[{$dimensions}]
            )
        ");
        
        // Populate index with existing embeddings
        $this->rebuildVectorIndex();
        
        Log::info('SQLite vector store: vector index created successfully');
        
    } catch (\Exception $e) {
        Log::error('SQLite vector store: failed to create vector index', [
            'error' => $e->getMessage(),
        ]);
        throw $e;
    }
}

protected function rebuildVectorIndex(): void
{
    try {
        // Clear existing index
        DB::statement("DELETE FROM fragment_embeddings_idx");
        
        // Rebuild from fragment_embeddings table
        $embeddings = DB::table('fragment_embeddings')->get();
        
        foreach ($embeddings as $embedding) {
            $vector = $this->blobToVector($embedding->embedding);
            $vectorString = implode(',', $vector);
            
            DB::statement(
                "INSERT INTO fragment_embeddings_idx (rowid, embedding) VALUES (?, '[{$vectorString}]')",
                [$embedding->fragment_id]
            );
        }
        
        Log::info('SQLite vector store: index rebuilt', [
            'embeddings_count' => count($embeddings),
        ]);
        
    } catch (\Exception $e) {
        Log::error('SQLite vector store: index rebuild failed', [
            'error' => $e->getMessage(),
        ]);
        // Don't throw - index is optional optimization
    }
}
```

### **Step 5: Error Handling & Fallbacks (1-2h)**

#### Graceful Degradation
```php
// Add to SqliteVectorStore class

protected function handleVectorOperationError(\Exception $e, string $operation, array $context = []): void
{
    Log::error("SQLite vector store: {$operation} failed", array_merge([
        'error' => $e->getMessage(),
        'driver_info' => $this->getDriverInfo(),
    ], $context));
}

public function diagnoseConnection(): array
{
    $diagnosis = [
        'sqlite_version' => null,
        'extension_loaded' => $this->extensionLoaded,
        'extension_version' => null,
        'tables_exist' => false,
        'index_exists' => false,
        'sample_query_works' => false,
    ];
    
    try {
        $diagnosis['sqlite_version'] = $this->pdo->query("SELECT sqlite_version()")->fetchColumn();
        
        if ($this->extensionLoaded) {
            $diagnosis['extension_version'] = $this->getExtensionVersion();
            
            // Test basic vector operation
            $testVector = array_fill(0, 10, 0.1);
            $testBlob = $this->vectorToBlob($testVector);
            $this->pdo->query("SELECT vec_distance_cosine(?, ?)")
                     ->execute([$testBlob, $testBlob]);
            $diagnosis['sample_query_works'] = true;
        }
        
        // Check table existence
        $tables = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name='fragment_embeddings'");
        $diagnosis['tables_exist'] = !empty($tables);
        
        $index = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name='fragment_embeddings_idx'");
        $diagnosis['index_exists'] = !empty($index);
        
    } catch (\Exception $e) {
        $diagnosis['error'] = $e->getMessage();
    }
    
    return $diagnosis;
}
```

## Validation & Testing

### **Unit Tests**
**File**: `tests/Unit/SqliteVectorStoreTest.php`
```php
<?php

namespace Tests\Unit;

use App\Services\Embeddings\SqliteVectorStore;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SqliteVectorStoreTest extends TestCase
{
    use RefreshDatabase;

    protected SqliteVectorStore $store;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite']);
        $this->store = new SqliteVectorStore();
    }

    public function test_vector_blob_conversion()
    {
        $originalVector = [0.1, 0.2, 0.3, 0.4, 0.5];
        
        $blob = $this->invokeMethod($this->store, 'vectorToBlob', [$originalVector]);
        $converted = $this->invokeMethod($this->store, 'blobToVector', [$blob]);
        
        $this->assertEquals($originalVector, $converted, '', 0.0001);
    }

    public function test_extension_detection()
    {
        $available = $this->store->isVectorSupportAvailable();
        $info = $this->store->getDriverInfo();
        
        $this->assertIsBool($available);
        $this->assertArrayHasKey('driver', $info);
        $this->assertEquals('sqlite', $info['driver']);
    }

    public function test_store_and_exists()
    {
        if (!$this->store->isVectorSupportAvailable()) {
            $this->markTestSkipped('SQLite vector extension not available');
        }

        $vector = array_fill(0, 1536, 0.1);
        
        $this->store->store(1, 'openai', 'text-embedding-3-small', 1536, $vector, 'test-hash');
        
        $exists = $this->store->exists(1, 'openai', 'text-embedding-3-small', 'test-hash');
        $this->assertTrue($exists);
    }

    private function invokeMethod($object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $parameters);
    }
}
```

## Time Allocation

| Task | Estimated Time | Complexity |
|------|----------------|------------|
| Extension integration & detection | 3-4h | High |
| Vector storage implementation | 4-6h | Medium |
| Vector search implementation | 4-6h | High |
| Index optimization | 2-3h | Medium |
| Error handling & fallbacks | 1-2h | Low |
| **Total** | **14-20h** | **High** |

## Dependencies & Next Steps

### **Required from VECTOR-001**
- Complete `EmbeddingStoreInterface` contract
- Working manager class for driver resolution
- Service provider integration patterns

### **Enables Following Tasks**
- VECTOR-003: Database migrations need SQLite schema
- VECTOR-004: Search abstraction needs working SQLite implementation
- VECTOR-005: Feature detection needs capability reporting

### **External Dependencies**
- sqlite-vec extension available in deployment environment
- SQLite version 3.38+ for proper extension support
- Sufficient disk space for vector index storage