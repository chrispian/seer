# Implementation Plan: Hybrid Search Abstraction

## Overview
Create a search abstraction layer that enables hybrid vector+text search on both PostgreSQL and SQLite while maintaining API compatibility with the existing SearchCommand.

## Technical Implementation

### **Step 1: Search Interface Definition (2-3h)**

#### Create Hybrid Search Contract
**File**: `app/Contracts/HybridSearchInterface.php`
```php
<?php

namespace App\Contracts;

interface HybridSearchInterface
{
    /**
     * Perform hybrid vector + text search
     */
    public function search(
        string $query,
        array $options = []
    ): array;

    /**
     * Check if hybrid search capabilities are available
     */
    public function isHybridSearchAvailable(): bool;

    /**
     * Get search engine capabilities and metadata
     */
    public function getSearchCapabilities(): array;

    /**
     * Perform vector-only search
     */
    public function vectorSearch(
        array $queryVector,
        array $options = []
    ): array;

    /**
     * Perform text-only search
     */
    public function textSearch(
        string $query,
        array $options = []
    ): array;
}
```

#### Create Search Result DTO
**File**: `app/DTOs/SearchResult.php`
```php
<?php

namespace App\DTOs;

readonly class SearchResult
{
    public function __construct(
        public int $fragmentId,
        public float $vectorSimilarity,
        public float $textRank,
        public float $combinedScore,
        public string $snippet,
        public array $metadata = []
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->fragmentId,
            'vec_sim' => $this->vectorSimilarity,
            'txt_rank' => $this->textRank,
            'score' => $this->combinedScore,
            'snippet' => $this->snippet,
        ];
    }
}
```

### **Step 2: Search Manager Implementation (3-4h)**

#### Create Hybrid Search Manager
**File**: `app/Services/Search/HybridSearchManager.php`
```php
<?php

namespace App\Services\Search;

use App\Contracts\HybridSearchInterface;
use App\Services\AI\Embeddings;
use Illuminate\Support\Facades\DB;

class HybridSearchManager
{
    protected array $drivers = [];
    protected Embeddings $embeddings;

    public function __construct(Embeddings $embeddings)
    {
        $this->embeddings = $embeddings;
    }

    public function driver(?string $connection = null): HybridSearchInterface
    {
        $connection = $connection ?? $this->getDefaultDriver();
        
        if (!isset($this->drivers[$connection])) {
            $this->drivers[$connection] = $this->createDriver($connection);
        }

        return $this->drivers[$connection];
    }

    public function search(string $query, array $options = []): array
    {
        return $this->driver()->search($query, $options);
    }

    protected function getDefaultDriver(): string
    {
        $dbDriver = DB::connection()->getDriverName();
        
        return match ($dbDriver) {
            'sqlite' => 'sqlite',
            'pgsql' => 'postgresql',
            default => throw new \InvalidArgumentException("Unsupported database driver: {$dbDriver}")
        };
    }

    protected function createDriver(string $driver): HybridSearchInterface
    {
        return match ($driver) {
            'sqlite' => new SQLiteHybridSearch($this->embeddings),
            'postgresql' => new PostgreSQLHybridSearch($this->embeddings),
            default => throw new \InvalidArgumentException("Unknown search driver: {$driver}")
        };
    }
}
```

### **Step 3: PostgreSQL Search Implementation (2-3h)**

#### PostgreSQL Hybrid Search (Preserve Existing Logic)
**File**: `app/Services/Search/PostgreSQLHybridSearch.php`
```php
<?php

namespace App\Services\Search;

use App\Contracts\HybridSearchInterface;
use App\DTOs\SearchResult;
use App\Services\AI\Embeddings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PostgreSQLHybridSearch implements HybridSearchInterface
{
    protected Embeddings $embeddings;

    public function __construct(Embeddings $embeddings)
    {
        $this->embeddings = $embeddings;
    }

    public function search(string $query, array $options = []): array
    {
        if (!$this->isHybridSearchAvailable()) {
            return $this->textSearch($query, $options);
        }

        try {
            // Get query embedding
            $provider = $options['provider'] ?? config('fragments.embeddings.provider');
            $limit = $options['limit'] ?? 20;
            
            $emb = $this->embeddings->embed($query);
            $qe = '[' . implode(',', $emb['vector']) . ']';

            // Use existing hybrid search SQL from SearchCommand
            $hasEdited = Schema::hasColumn('fragments', 'edited_message');
            $bodyExpr = $hasEdited ? "coalesce(f.edited_message, f.message, '')"
                : "coalesce(f.message, '')";
            $docExpr = "coalesce(f.title,'') || ' ' || {$bodyExpr}";

            $sql = "
                WITH p AS (
                  SELECT ?::vector AS qe, websearch_to_tsquery('simple', ?) AS qq
                )
                SELECT
                  f.id,
                  f.title,
                  ts_headline('simple', {$docExpr}, p.qq,
                    'StartSel=<mark>,StopSel=</mark>,MaxFragments=2,MaxWords=18') AS snippet,
                  (1 - (e.embedding <=> p.qe)) AS vec_sim,
                  ts_rank_cd(to_tsvector('simple', {$docExpr}), p.qq) AS txt_rank,
                  (0.6 * ts_rank_cd(to_tsvector('simple', {$docExpr}), p.qq)
                   + 0.4 * (1 - (e.embedding <=> p.qe))) AS score
                FROM fragments f
                JOIN fragment_embeddings e
                  ON e.fragment_id = f.id
                 AND e.provider    = ?
                CROSS JOIN p
                WHERE ts_rank_cd(to_tsvector('simple', {$docExpr}), p.qq) > 0
                ORDER BY score DESC
                LIMIT ?
            ";

            $rows = DB::select($sql, [$qe, $query, $provider, $limit]);

            return array_map(function ($row) {
                return (object) [
                    'id' => $row->id,
                    'vec_sim' => (float) $row->vec_sim,
                    'txt_rank' => (float) $row->txt_rank,
                    'score' => (float) $row->score,
                    'snippet' => $row->snippet,
                ];
            }, $rows);

        } catch (\Throwable $e) {
            \Log::error('PostgreSQL hybrid search failed', [
                'query' => $query,
                'error' => $e->getMessage(),
            ]);
            return $this->textSearch($query, $options);
        }
    }

    public function isHybridSearchAvailable(): bool
    {
        try {
            $result = DB::select("SELECT 1 FROM pg_extension WHERE extname = 'vector'");
            return !empty($result);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function getSearchCapabilities(): array
    {
        return [
            'driver' => 'postgresql',
            'hybrid_search' => $this->isHybridSearchAvailable(),
            'vector_search' => $this->isHybridSearchAvailable(),
            'text_search' => true,
            'extensions' => ['pgvector'],
        ];
    }

    public function vectorSearch(array $queryVector, array $options = []): array
    {
        // Implementation for vector-only search
        // Similar to hybrid but without text ranking
    }

    public function textSearch(string $query, array $options = []): array
    {
        // Implementation for text-only search using PostgreSQL ts_*
        // Fallback when vector search unavailable
    }
}
```

### **Step 4: SQLite Search Implementation (3-4h)**

#### SQLite Hybrid Search
**File**: `app/Services/Search/SQLiteHybridSearch.php`
```php
<?php

namespace App\Services\Search;

use App\Contracts\HybridSearchInterface;
use App\Services\AI\Embeddings;
use Illuminate\Support\Facades\DB;

class SQLiteHybridSearch implements HybridSearchInterface
{
    protected Embeddings $embeddings;

    public function __construct(Embeddings $embeddings)
    {
        $this->embeddings = $embeddings;
    }

    public function search(string $query, array $options = []): array
    {
        if ($this->isHybridSearchAvailable()) {
            return $this->hybridSearch($query, $options);
        } elseif ($this->isVectorSearchAvailable()) {
            return $this->vectorSearch($this->getQueryVector($query), $options);
        } else {
            return $this->textSearch($query, $options);
        }
    }

    protected function hybridSearch(string $query, array $options = []): array
    {
        try {
            $provider = $options['provider'] ?? config('fragments.embeddings.provider');
            $limit = $options['limit'] ?? 20;
            
            // Get query embedding
            $emb = $this->embeddings->embed($query);
            $queryBlob = $this->vectorToBlob($emb['vector']);

            $sql = "
                WITH ranked_fragments AS (
                    SELECT 
                        f.id,
                        f.title,
                        COALESCE(f.edited_message, f.message, '') as content,
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

            $rows = DB::select($sql, [$queryBlob, $provider, $query, $limit]);

            return array_map(function ($row) {
                return (object) [
                    'id' => $row->id,
                    'vec_sim' => (float) $row->vec_sim,
                    'txt_rank' => (float) $row->txt_rank,
                    'score' => (float) $row->score,
                    'snippet' => $row->snippet,
                ];
            }, $rows);

        } catch (\Throwable $e) {
            \Log::error('SQLite hybrid search failed', [
                'query' => $query,
                'error' => $e->getMessage(),
            ]);
            return $this->textSearch($query, $options);
        }
    }

    public function isHybridSearchAvailable(): bool
    {
        return $this->isVectorSearchAvailable() && $this->isFtsAvailable();
    }

    protected function isVectorSearchAvailable(): bool
    {
        try {
            DB::select("SELECT vec_version()");
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function isFtsAvailable(): bool
    {
        try {
            $result = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name='fragments_fts'");
            return !empty($result);
        } catch (\Exception $e) {
            return false;
        }
    }

    // Additional methods for vector conversion, text search, etc.
}
```

### **Step 5: SearchCommand Integration (2-3h)**

#### Update SearchCommand to Use Abstraction
**File**: `app/Actions/Commands/SearchCommand.php` (updated sections)
```php
<?php

namespace App\Actions\Commands;

use App\Services\Search\HybridSearchManager;
// ... other imports

class SearchCommand implements HandlesCommand
{
    protected HybridSearchManager $searchManager;

    public function __construct(HybridSearchManager $searchManager)
    {
        $this->searchManager = $searchManager;
    }

    public function handle(CommandRequest $command): CommandResponse
    {
        $query = $command->arguments['identifier'] ?? null;
        
        if (empty($query)) {
            return new CommandResponse(/* ... error response ... */);
        }

        try {
            $searchOptions = [
                'provider' => config('fragments.embeddings.provider'),
                'limit' => 20,
                'vault' => $command->arguments['vault'] ?? null,
                'project_id' => $command->arguments['project_id'] ?? null,
            ];

            if ($this->searchManager->driver()->isHybridSearchAvailable()) {
                $results = $this->searchManager->search($query, $searchOptions);
                $searchMode = 'hybrid';
            } else {
                $results = $this->fallbackSearch($command, $query);
                $searchMode = 'text-only';
            }

            // Rest of existing response logic unchanged...
            
        } catch (\Exception $e) {
            return new CommandResponse(/* ... error response ... */);
        }
    }

    // Keep existing fallbackSearch method as final fallback
}
```

## Time Allocation & Dependencies

| Task | Estimated Time | Dependencies |
|------|----------------|--------------|
| Search interface definition | 2-3h | VECTOR-001 |
| Search manager implementation | 3-4h | Interface |
| PostgreSQL implementation | 2-3h | Manager, existing code |
| SQLite implementation | 3-4h | VECTOR-002, VECTOR-003 |
| SearchCommand integration | 2-3h | All implementations |
| **Total** | **10-16h** | **VECTOR-001, 002, 003** |