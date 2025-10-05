# Context: SQLite Vector Store Implementation

## Problem Statement

### **Current SQLite Limitations**
SQLite has no native vector support. The existing vector implementation is PostgreSQL-only:

```sql
-- PostgreSQL-specific operations that need SQLite equivalents
ALTER TABLE fragment_embeddings ADD COLUMN embedding vector(1536);
INSERT INTO ... VALUES (?::vector, ...)
SELECT (1 - (e.embedding <=> p.qe)) AS vec_sim
```

### **sqlite-vec Extension Solution**
[sqlite-vec](https://github.com/asg017/sqlite-vec) provides vector operations for SQLite:
- **Vector storage**: `BLOB` columns with vector data
- **Similarity functions**: `vec_distance_cosine()`, `vec_distance_L2()`  
- **Indexing**: Virtual tables for efficient similarity search
- **Compatibility**: MIT licensed, can be bundled with NativePHP

## Target Implementation Architecture

### **Vector Storage Strategy**
```sql
-- SQLite approach: JSON metadata + BLOB vector data
CREATE TABLE fragment_embeddings (
    id INTEGER PRIMARY KEY,
    fragment_id INTEGER NOT NULL,
    provider TEXT NOT NULL,
    model TEXT NOT NULL,
    dims INTEGER NOT NULL,
    embedding BLOB,  -- Vector data as BLOB
    content_hash TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (fragment_id) REFERENCES fragments(id) ON DELETE CASCADE
);

-- Vector index for efficient similarity search
CREATE VIRTUAL TABLE fragment_embeddings_idx USING vec0(
    embedding FLOAT[1536]
);
```

### **Search Operation Translation**
```php
// PostgreSQL approach
$sql = "SELECT (1 - (e.embedding <=> ?::vector)) AS similarity";

// SQLite equivalent  
$sql = "SELECT (1 - vec_distance_cosine(e.embedding, ?)) AS similarity";
```

### **Hybrid Search Implementation**
```sql
-- PostgreSQL hybrid search with full-text
WITH p AS (
  SELECT ?::vector AS qe, websearch_to_tsquery('simple', ?) AS qq
)
SELECT 
  (1 - (e.embedding <=> p.qe)) AS vec_sim,
  ts_rank_cd(to_tsvector('simple', doc), p.qq) AS txt_rank
FROM fragments f
JOIN fragment_embeddings e ON e.fragment_id = f.id;

-- SQLite equivalent with FTS5
WITH query_vector AS (SELECT ? AS qe)
SELECT 
  f.id,
  (1 - vec_distance_cosine(e.embedding, qe)) AS vec_sim,
  fts.rank AS txt_rank,
  highlight(fts, 1, '<mark>', '</mark>') AS snippet
FROM fragments f
JOIN fragment_embeddings e ON e.fragment_id = f.id
JOIN fragments_fts fts ON fts.docid = f.id
CROSS JOIN query_vector
WHERE fts MATCH ?
ORDER BY (0.6 * fts.rank + 0.4 * vec_sim) DESC;
```

## Implementation Challenges

### **Extension Loading**
```php
// Challenge: Load sqlite-vec extension in Laravel context
try {
    $pdo = DB::connection()->getPdo();
    $pdo->loadExtension('sqlite-vec'); // May fail in some environments
} catch (PDOException $e) {
    // Graceful fallback to text-only search
}
```

### **Vector Data Handling**
```php
// Challenge: Convert PHP array to SQLite BLOB format
public function arrayToBlob(array $vector): string
{
    // sqlite-vec expects specific binary format
    return pack('f*', ...$vector); // Float32 little-endian
}

public function blobToArray(string $blob): array
{
    return unpack('f*', $blob); // Convert back to PHP array
}
```

### **Performance Considerations**
- **Index Strategy**: Virtual tables vs. manual indexing
- **Batch Operations**: Efficient bulk embedding insertion
- **Memory Usage**: Large vector datasets in SQLite
- **Query Optimization**: Similarity search performance

## Feature Parity Requirements

### **Core Operations**
1. **Store Embeddings**: Insert/update with upsert semantics
2. **Existence Check**: Efficient duplicate detection
3. **Vector Search**: Cosine similarity with configurable limits
4. **Hybrid Search**: Combined vector + full-text search
5. **Extension Detection**: Runtime capability checking

### **PostgreSQL Compatibility**
```php
// Must support identical interface signatures
interface EmbeddingStoreInterface
{
    public function store(int $fragmentId, string $provider, string $model, int $dimensions, array $vector, string $contentHash): void;
    public function exists(int $fragmentId, string $provider, string $model, string $contentHash): bool;
    public function search(array $queryVector, string $provider, int $limit = 20, float $threshold = 0.0): array;
    public function isVectorSupportAvailable(): bool;
    public function getDriverInfo(): array;
}
```

### **Search Result Format**
```php
// Must return identical structure to PostgreSQL implementation
return [
    'id' => $fragment->id,
    'vec_sim' => 0.85,
    'txt_rank' => 0.12,
    'score' => 0.55, // Combined score
    'snippet' => 'highlighted text with <mark>query</mark> terms',
];
```

## Risk Assessment & Mitigation

### **High Risk Areas**
1. **Extension Loading**: sqlite-vec may not load in all environments
   - **Mitigation**: Graceful fallback to text-only search
   - **Detection**: Runtime capability checking

2. **Performance Gap**: SQLite may be slower than PostgreSQL
   - **Mitigation**: Optimize indexing and query strategies
   - **Benchmark**: Target within 20% of PostgreSQL performance

3. **Binary Compatibility**: Vector blob format across platforms
   - **Mitigation**: Use standardized float32 little-endian format
   - **Testing**: Cross-platform validation

### **Medium Risk Areas**
1. **SQL Translation**: Complex queries may not translate directly
   - **Mitigation**: Implement equivalent functionality step-by-step
   - **Testing**: Comprehensive test suite comparing results

2. **Memory Usage**: Large vector datasets may stress SQLite
   - **Mitigation**: Implement pagination and chunking
   - **Monitoring**: Add memory usage tracking

## Development Strategy

### **Phase 1: Basic Vector Operations**
- Implement vector storage with BLOB format
- Add extension loading and detection
- Create basic similarity search

### **Phase 2: Advanced Search**
- Implement hybrid search with FTS5
- Add index optimization
- Performance tuning and benchmarking

### **Phase 3: Production Readiness**
- Error handling and graceful fallbacks
- Comprehensive testing
- Documentation and deployment guides

## Testing Strategy

### **Unit Tests**
- Vector storage and retrieval accuracy
- Extension loading edge cases
- Error handling for missing extension

### **Integration Tests**
- Compare search results with PostgreSQL implementation
- Performance benchmarking
- Cross-platform compatibility

### **Feature Tests**
- End-to-end embedding workflow
- Search functionality in actual UI
- Fallback behavior when extension unavailable

## Dependencies & Integration Points

### **Required from VECTOR-001**
- `EmbeddingStoreInterface` contract
- `VectorSearchResult` DTO structure
- Manager class integration patterns

### **Configuration Integration**
```php
// config/fragments.php additions needed
'drivers' => [
    'sqlite' => [
        'extension' => 'sqlite-vec',
        'extension_path' => null, // Auto-detect or specify path
        'index_type' => 'virtual_table', // or 'manual'
        'chunk_size' => 1000, // For bulk operations
    ],
],
```

### **Migration Requirements**
- SQLite-compatible schema creation
- Data migration from PostgreSQL if needed
- Index creation for optimal performance