# Context: Hybrid Search Abstraction

## Current State Analysis

### **Existing Search Implementation Issues**
The current `SearchCommand` has hardcoded PostgreSQL dependencies:

```php
// app/Actions/Commands/SearchCommand.php:158-180
$sql = "
    WITH p AS (
      SELECT ?::vector AS qe, websearch_to_tsquery('simple', ?) AS qq
    )
    SELECT
      (1 - (e.embedding <=> p.qe)) AS vec_sim,
      ts_rank_cd(to_tsvector('simple', {$docExpr}), p.qq) AS txt_rank,
      (0.6 * ts_rank_cd(...) + 0.4 * (1 - (e.embedding <=> p.qe))) AS score
    FROM fragments f
    JOIN fragment_embeddings e ON e.fragment_id = f.id
";
```

### **Problems with Current Approach**
1. **Database-Specific SQL**: Uses PostgreSQL `?::vector`, `<=>`, `websearch_to_tsquery`
2. **Hardcoded Driver Check**: Duplicates `hasPgVectorSupport()` logic
3. **No Abstraction**: Search logic mixed with database implementation
4. **Limited Fallback**: Basic text search doesn't leverage full-text capabilities

## Target Architecture

### **Search Service Abstraction**
```php
interface HybridSearchInterface
{
    public function search(
        string $query,
        array $options = []
    ): array;
    
    public function isHybridSearchAvailable(): bool;
    public function getSearchCapabilities(): array;
}

class HybridSearchManager
{
    public function driver(?string $connection = null): HybridSearchInterface;
    public function search(string $query, array $options = []): array;
}
```

### **Driver-Specific Implementations**
```php
// PostgreSQL implementation preserving existing logic
class PostgreSQLHybridSearch implements HybridSearchInterface
{
    public function search(string $query, array $options = []): array
    {
        // Existing complex SQL with pgvector and ts_* functions
    }
}

// SQLite implementation with FTS5 + sqlite-vec
class SQLiteHybridSearch implements HybridSearchInterface  
{
    public function search(string $query, array $options = []): array
    {
        // sqlite-vec + FTS5 equivalent functionality
    }
}
```

### **Integrated SearchCommand**
```php
// Updated SearchCommand using abstraction
public function handle(CommandRequest $command): CommandResponse
{
    $hybridSearch = app(HybridSearchManager::class);
    
    if ($hybridSearch->isHybridSearchAvailable()) {
        $results = $hybridSearch->search($query, $options);
        $searchMode = 'hybrid';
    } else {
        $results = $this->fallbackSearch($command, $query);
        $searchMode = 'text-only';
    }
}
```

## Implementation Challenges

### **Score Normalization**
Different databases require different scoring approaches:
```php
// PostgreSQL: ts_rank_cd + cosine distance
$score = 0.6 * ts_rank_cd(...) + 0.4 * (1 - cosine_distance)

// SQLite: FTS5 rank + sqlite-vec cosine
$score = 0.6 * fts5_rank + 0.4 * (1 - vec_distance_cosine)
```

### **Query Translation**
```php
// PostgreSQL full-text query
websearch_to_tsquery('simple', 'search terms')

// SQLite FTS5 equivalent
FTS5 MATCH 'search terms'
```

### **Result Format Consistency**
Both implementations must return identical structure:
```php
[
    'id' => $fragmentId,
    'vec_sim' => 0.85,      // Vector similarity 0-1
    'txt_rank' => 0.12,     // Text relevance score
    'score' => 0.55,        // Combined weighted score
    'snippet' => '...<mark>highlighted</mark>...',
];
```

## Risk Assessment

### **High Risk Areas**
1. **Performance Parity**: SQLite implementation must match PostgreSQL speed
2. **Score Consistency**: Different scoring algorithms may produce different rankings
3. **Feature Gaps**: FTS5 vs PostgreSQL ts_* feature differences

### **Mitigation Strategies**
1. **Extensive Benchmarking**: Compare performance across both implementations
2. **Score Calibration**: Normalize scoring algorithms for consistent results
3. **Graceful Degradation**: Progressive fallback from hybrid → vector-only → text-only