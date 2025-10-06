# TODO: SQLite Vector Store Implementation

## Phase 1: Extension Integration & Detection ⏱️ 3-4h

### Extension Loading Infrastructure
- [ ] **Implement** `initializeExtension()` method in `SqliteVectorStore`
  - [ ] Get PDO connection from Laravel database manager
  - [ ] Attempt sqlite-vec extension loading with multiple strategies
  - [ ] Handle extension_path configuration option
  - [ ] Try common extension naming variants (.so, .dll, .dylib)
  - [ ] Verify extension loaded with `vec_version()` test query

### Extension Detection & Diagnostics
- [ ] **Implement** `tryLoadExtension()` helper method
  - [ ] Try multiple extension file name variants
  - [ ] Handle platform-specific differences (Linux/macOS/Windows)
  - [ ] Log detailed failure information for troubleshooting
  - [ ] Graceful failure without breaking application

- [ ] **Implement** `getExtensionVersion()` method
  - [ ] Query `vec_version()` function safely
  - [ ] Handle cases where function doesn't exist
  - [ ] Return null on failure for consistent error handling

### Capability Reporting
- [ ] **Implement** `isVectorSupportAvailable()` method
  - [ ] Return boolean based on extension loading success
  - [ ] Cache result to avoid repeated checks
  - [ ] Used by manager for driver selection

- [ ] **Implement** `getDriverInfo()` method  
  - [ ] Return comprehensive driver metadata
  - [ ] Include extension name, version, availability status
  - [ ] Used for debugging and monitoring

### Error Handling
- [ ] **Add** comprehensive logging for extension loading
  - [ ] Success case: log extension name and version
  - [ ] Failure case: log specific error and attempted paths
  - [ ] Warning level for missing extension (not error)

- [ ] **Handle** PDOException gracefully
  - [ ] Catch extension loading failures
  - [ ] Set extensionLoaded flag appropriately
  - [ ] Don't break application when extension unavailable

## Phase 2: Vector Storage Implementation ⏱️ 4-6h

### Binary Vector Handling
- [ ] **Implement** `vectorToBlob()` method
  - [ ] Convert PHP float array to binary BLOB
  - [ ] Use `pack('f*', ...$vector)` for float32 little-endian
  - [ ] Handle empty arrays and invalid input
  - [ ] Document binary format for compatibility

- [ ] **Implement** `blobToVector()` method
  - [ ] Convert binary BLOB back to PHP float array
  - [ ] Use `unpack('f*', $blob)` with proper array conversion
  - [ ] Handle corrupted or invalid BLOB data
  - [ ] Return consistent array format

### Core Storage Operations
- [ ] **Implement** `store()` method
  - [ ] Check vector support availability first
  - [ ] Convert vector array to BLOB format
  - [ ] Use SQLite `INSERT OR REPLACE` for upsert semantics
  - [ ] Handle all required fields: fragment_id, provider, model, dims, embedding, content_hash
  - [ ] Set created_at and updated_at timestamps
  - [ ] Update vector index after successful storage

### Index Management
- [ ] **Implement** `updateVectorIndex()` method
  - [ ] Insert/update vector in virtual table index
  - [ ] Convert vector to sqlite-vec compatible format
  - [ ] Handle index update failures gracefully (log but don't fail storage)
  - [ ] Use fragment_id as rowid for efficient lookups

### Existence Checking
- [ ] **Implement** `exists()` method
  - [ ] Query fragment_embeddings table efficiently
  - [ ] Check fragment_id, provider, model, content_hash match
  - [ ] Return boolean result
  - [ ] Use database query builder for consistency

### Error Handling & Logging
- [ ] **Add** comprehensive error handling for storage operations
  - [ ] Log successful storage with metadata
  - [ ] Log and re-throw storage failures  
  - [ ] Handle database connection issues
  - [ ] Distinguish between extension and database errors

- [ ] **Add** graceful handling for missing extension
  - [ ] Log warning and return early when extension unavailable
  - [ ] Don't attempt database operations without vector support
  - [ ] Preserve existing fragment data integrity

## Phase 3: Vector Search Implementation ⏱️ 4-6h

### Basic Vector Similarity Search
- [ ] **Implement** `search()` method
  - [ ] Check vector support availability first
  - [ ] Convert query vector to BLOB format
  - [ ] Use `vec_distance_cosine()` for similarity calculation
  - [ ] Support similarity threshold filtering
  - [ ] Return results in consistent format matching PostgreSQL

### SQL Query Construction
- [ ] **Build** vector similarity search SQL
  - [ ] JOIN fragments and fragment_embeddings tables
  - [ ] Calculate `(1 - vec_distance_cosine(e.embedding, ?))` as vec_sim
  - [ ] Apply provider filtering
  - [ ] Apply similarity threshold filtering
  - [ ] Order by similarity descending
  - [ ] Apply LIMIT for result pagination

### Hybrid Search Implementation
- [ ] **Implement** `hybridSearch()` method
  - [ ] Combine vector similarity with FTS5 text search
  - [ ] Check for FTS5 table existence with `hasFtsSupport()`
  - [ ] Use `fragments_fts` virtual table for text ranking
  - [ ] Generate highlighted snippets with `highlight()` function
  - [ ] Combine scores: `(0.6 * txt_rank + 0.4 * vec_sim)`

### FTS5 Integration
- [ ] **Implement** `hasFtsSupport()` method
  - [ ] Check if `fragments_fts` table exists
  - [ ] Query `sqlite_master` table safely
  - [ ] Return boolean for FTS availability

- [ ] **Handle** FTS5 fallbacks
  - [ ] Fall back to vector-only search when FTS unavailable
  - [ ] Fall back to basic text search when vectors unavailable
  - [ ] Maintain consistent result format across fallback paths

### Text-Only Fallback
- [ ] **Implement** `fallbackTextSearch()` method
  - [ ] Simple LIKE-based text search when vectors unavailable
  - [ ] Search both title and message fields
  - [ ] Return results in same format as vector search
  - [ ] Set placeholder values for missing vector metrics

### Result Format Consistency
- [ ] **Ensure** search results match PostgreSQL format exactly
  - [ ] Include: id, title, snippet, vec_sim, txt_rank, score
  - [ ] Cast numeric values to float consistently
  - [ ] Handle null/missing values appropriately
  - [ ] Maintain backward compatibility with existing UI

## Phase 4: Index Optimization ⏱️ 2-3h

### Virtual Table Creation
- [ ] **Implement** `createVectorIndex()` method
  - [ ] Create vec0 virtual table for efficient similarity search
  - [ ] Use configurable vector dimensions from config
  - [ ] Handle virtual table creation errors gracefully
  - [ ] Populate index with existing embeddings

### Index Population
- [ ] **Implement** `rebuildVectorIndex()` method
  - [ ] Clear existing virtual table data
  - [ ] Iterate through all fragment_embeddings
  - [ ] Convert BLOB vectors back to array format
  - [ ] Insert into virtual table with proper rowid mapping
  - [ ] Log progress for large datasets

### Index Maintenance
- [ ] **Add** automatic index updates
  - [ ] Update virtual table when embeddings stored
  - [ ] Handle index update failures without breaking storage
  - [ ] Consider batch update strategies for performance
  - [ ] Log index maintenance operations

### Performance Optimization
- [ ] **Optimize** query patterns for SQLite
  - [ ] Use proper indexing strategies
  - [ ] Consider pagination for large result sets
  - [ ] Optimize JOIN operations
  - [ ] Benchmark against PostgreSQL performance

## Phase 5: Error Handling & Diagnostics ⏱️ 1-2h

### Comprehensive Error Handling
- [ ] **Implement** `handleVectorOperationError()` helper
  - [ ] Standardized error logging format
  - [ ] Include driver info in error context
  - [ ] Log operation type and parameters
  - [ ] Distinguish error types for debugging

### Connection Diagnostics
- [ ] **Implement** `diagnoseConnection()` method
  - [ ] Check SQLite version compatibility
  - [ ] Verify extension loading status
  - [ ] Test basic vector operations
  - [ ] Check table and index existence
  - [ ] Return comprehensive diagnostic report

### Graceful Degradation
- [ ] **Handle** all extension unavailable scenarios
  - [ ] Return empty results instead of throwing errors
  - [ ] Log warnings but don't break application flow
  - [ ] Provide clear feedback about capability limitations
  - [ ] Enable text-only search fallback

### Testing Infrastructure
- [ ] **Add** extension availability checks in tests
  - [ ] Skip vector tests when extension unavailable
  - [ ] Mark tests as skipped with clear reason
  - [ ] Provide alternative test paths for CI environments
  - [ ] Document extension setup for development

## Testing & Validation

### Unit Tests
- [ ] **Create** `tests/Unit/SqliteVectorStoreTest.php`
  - [ ] Test vector blob conversion accuracy
  - [ ] Test extension detection and reporting
  - [ ] Test storage and existence checking
  - [ ] Test search functionality with mock data
  - [ ] Test error handling for missing extension

### Integration Tests  
- [ ] **Create** `tests/Feature/SqliteVectorIntegrationTest.php`
  - [ ] Test full embedding workflow with SQLite
  - [ ] Test search results match expected format
  - [ ] Test fallback behavior when extension unavailable
  - [ ] Test performance benchmarks vs PostgreSQL
  - [ ] Test cross-platform compatibility

### Manual Testing
- [ ] **Test** extension loading in different environments
  - [ ] Development environment with bundled extension
  - [ ] Production environment with system-installed extension
  - [ ] NativePHP environment with bundled extension
  - [ ] Fallback behavior when extension missing

### Performance Benchmarks
- [ ] **Benchmark** search performance vs PostgreSQL
  - [ ] Measure query execution time
  - [ ] Compare result accuracy
  - [ ] Test with various dataset sizes
  - [ ] Document performance characteristics

## Quality Assurance

### Code Quality
- [ ] **Follow** Laravel coding standards
- [ ] **Add** comprehensive PHPDoc comments
- [ ] **Use** proper type hints throughout
- [ ] **Handle** all edge cases and error conditions

### Security
- [ ] **Sanitize** all database inputs
- [ ] **Use** prepared statements for all queries
- [ ] **Validate** vector data before storage
- [ ] **Handle** potential injection vectors

### Compatibility
- [ ] **Maintain** exact interface compatibility with PostgreSQL
- [ ] **Preserve** all existing method signatures
- [ ] **Return** identical result formats
- [ ] **Support** all configuration options

## Acceptance Criteria

### Functional Requirements
- [ ] SQLite vector store implements all EmbeddingStoreInterface methods
- [ ] Vector storage and retrieval works accurately
- [ ] Search results match PostgreSQL format and quality
- [ ] Extension loads reliably across platforms
- [ ] Graceful fallback when extension unavailable

### Performance Requirements  
- [ ] Search performance within 20% of PostgreSQL implementation
- [ ] Memory usage acceptable for typical datasets
- [ ] Index creation and maintenance efficient
- [ ] No significant application startup delay

### Reliability Requirements
- [ ] Handles missing extension gracefully
- [ ] Recovers from database errors appropriately
- [ ] Maintains data integrity under all conditions
- [ ] Provides useful error messages and diagnostics

## Handoff Checklist

### Deliverables
- [ ] **Complete** SQLite vector store implementation
- [ ] **Working** extension integration and detection
- [ ] **Functional** vector storage and search operations
- [ ] **Comprehensive** test suite with high coverage
- [ ] **Performance** benchmarks and optimization

### Documentation
- [ ] **API documentation** for all public methods
- [ ] **Configuration guide** for extension setup
- [ ] **Troubleshooting guide** for common issues
- [ ] **Performance tuning** recommendations

### Integration Requirements
- [ ] **Compatible** with manager class from VECTOR-001
- [ ] **Ready** for migration integration in VECTOR-003
- [ ] **Supports** search abstraction in VECTOR-004
- [ ] **Enables** feature detection in VECTOR-005

---

**Estimated Total**: 14-20 hours  
**Complexity**: High  
**Critical Path**: Core vector functionality for SQLite deployment  
**Success Metric**: Feature parity with PostgreSQL implementation at acceptable performance