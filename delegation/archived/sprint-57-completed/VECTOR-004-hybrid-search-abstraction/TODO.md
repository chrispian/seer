# TODO: Hybrid Search Abstraction

## Phase 1: Search Interface Definition ⏱️ 2-3h

### Core Interface Creation
- [ ] **Create** `app/Contracts/HybridSearchInterface.php`
  - [ ] Define `search()` method for hybrid vector+text search
  - [ ] Define `isHybridSearchAvailable()` for capability detection
  - [ ] Define `getSearchCapabilities()` for driver metadata
  - [ ] Define `vectorSearch()` for vector-only queries
  - [ ] Define `textSearch()` for text-only fallback
  - [ ] Add comprehensive PHPDoc with parameter descriptions

### Search Result DTO
- [ ] **Create** `app/DTOs/SearchResult.php`
  - [ ] Define readonly class with fragmentId, similarities, scores
  - [ ] Add `toArray()` method for backward compatibility
  - [ ] Include snippet and metadata fields
  - [ ] Ensure format matches existing SearchCommand output

### Interface Documentation
- [ ] **Document** search scoring methodology
- [ ] **Document** options parameter structure
- [ ] **Document** fallback behavior strategies

## Phase 2: Search Manager Implementation ⏱️ 3-4h

### Manager Core Logic
- [ ] **Create** `app/Services/Search/HybridSearchManager.php`
  - [ ] Implement driver resolution based on database connection
  - [ ] Add driver caching to avoid recreation overhead
  - [ ] Implement `search()` delegation to appropriate driver
  - [ ] Add `getDefaultDriver()` with database detection

### Driver Factory
- [ ] **Implement** `createDriver()` factory method
  - [ ] Support 'sqlite' and 'postgresql' driver creation
  - [ ] Pass Embeddings service to driver constructors
  - [ ] Throw meaningful exceptions for unknown drivers
  - [ ] Add driver validation before instantiation

### Service Integration
- [ ] **Inject** Embeddings service dependency
  - [ ] Pass to driver constructors for vector generation
  - [ ] Handle embedding service failures gracefully
  - [ ] Add proper dependency injection configuration

### Error Handling
- [ ] **Add** comprehensive exception handling
- [ ] **Add** logging for driver selection decisions
- [ ] **Handle** database connection failures
- [ ] **Provide** useful error messages for debugging

## Phase 3: PostgreSQL Search Implementation ⏱️ 2-3h

### Core Implementation
- [ ] **Create** `app/Services/Search/PostgreSQLHybridSearch.php`
  - [ ] Implement `HybridSearchInterface`
  - [ ] **Move** existing logic from `SearchCommand.php:158-180`
  - [ ] **Preserve** exact functionality for backward compatibility
  - [ ] **Add** proper error handling and logging

### Hybrid Search Method
- [ ] **Implement** `search()` method
  - [ ] Use existing embedding generation logic
  - [ ] Use existing hybrid search SQL with pgvector
  - [ ] Preserve `websearch_to_tsquery` and `ts_headline` usage
  - [ ] Maintain existing score weighting (0.6 text + 0.4 vector)
  - [ ] Return results in consistent format

### Capability Detection
- [ ] **Implement** `isHybridSearchAvailable()`
  - [ ] Check for pgvector extension availability
  - [ ] Handle database connection errors gracefully
  - [ ] Cache result to avoid repeated checks

- [ ] **Implement** `getSearchCapabilities()`
  - [ ] Return PostgreSQL-specific metadata
  - [ ] Include extension status and version info
  - [ ] Used for debugging and monitoring

### Fallback Methods
- [ ] **Implement** `vectorSearch()` method
  - [ ] Vector-only search without text ranking
  - [ ] Use cosine similarity ordering
  - [ ] Handle vector extension unavailability

- [ ] **Implement** `textSearch()` method
  - [ ] Use PostgreSQL `to_tsvector` and `ts_rank`
  - [ ] Fallback when vector search unavailable
  - [ ] Maintain consistent result format

### Compatibility
- [ ] **Ensure** exact result format matches existing SearchCommand
- [ ] **Preserve** all SQL optimization strategies
- [ ] **Maintain** existing error handling patterns
- [ ] **Keep** performance characteristics identical

## Phase 4: SQLite Search Implementation ⏱️ 3-4h

### Core Implementation
- [ ] **Create** `app/Services/Search/SQLiteHybridSearch.php`
  - [ ] Implement `HybridSearchInterface`
  - [ ] Add constructor with Embeddings dependency
  - [ ] Implement progressive fallback strategy

### Hybrid Search Logic
- [ ] **Implement** `search()` method with fallback chain
  - [ ] Try hybrid search if both vector and FTS available
  - [ ] Fall back to vector-only if FTS unavailable
  - [ ] Fall back to text-only if vectors unavailable
  - [ ] Return consistent results across all modes

- [ ] **Implement** `hybridSearch()` private method
  - [ ] Generate query embedding using Embeddings service
  - [ ] Convert vector to BLOB format for sqlite-vec
  - [ ] JOIN fragments, fragment_embeddings, fragments_fts
  - [ ] Use `vec_distance_cosine()` for vector similarity
  - [ ] Use FTS5 rank for text relevance
  - [ ] Combine scores with same weighting as PostgreSQL

### Vector Operations
- [ ] **Implement** `vectorToBlob()` helper method
  - [ ] Convert PHP array to binary format for sqlite-vec
  - [ ] Use float32 little-endian packing
  - [ ] Handle empty or invalid vectors

- [ ] **Implement** `vectorSearch()` method
  - [ ] Vector-only search using sqlite-vec
  - [ ] Apply similarity threshold filtering
  - [ ] Order by cosine similarity descending
  - [ ] Return results in consistent format

### FTS5 Integration
- [ ] **Implement** `textSearch()` method
  - [ ] Use fragments_fts virtual table if available
  - [ ] Generate highlighted snippets with FTS5 highlight()
  - [ ] Fall back to LIKE search if FTS unavailable
  - [ ] Apply result ranking and ordering

### Capability Detection
- [ ] **Implement** `isHybridSearchAvailable()`
  - [ ] Check both vector and FTS availability
  - [ ] Return true only if both extensions work

- [ ] **Implement** `isVectorSearchAvailable()`
  - [ ] Test `vec_version()` function availability
  - [ ] Handle extension loading failures gracefully

- [ ] **Implement** `isFtsAvailable()`
  - [ ] Check for fragments_fts table existence
  - [ ] Verify FTS5 virtual table functionality

- [ ] **Implement** `getSearchCapabilities()`
  - [ ] Return SQLite-specific metadata
  - [ ] Include extension status for both sqlite-vec and FTS5
  - [ ] Used for debugging and feature detection

### Performance Optimization
- [ ] **Optimize** query patterns for SQLite
  - [ ] Use appropriate indexing strategies
  - [ ] Minimize data conversion overhead
  - [ ] Implement result pagination efficiently

## Phase 5: SearchCommand Integration ⏱️ 2-3h

### Dependency Injection
- [ ] **Update** `SearchCommand` constructor
  - [ ] Inject `HybridSearchManager` dependency
  - [ ] Remove hardcoded PostgreSQL logic
  - [ ] Keep existing fallback search method as final fallback

### Search Logic Update
- [ ] **Update** `handle()` method
  - [ ] Replace direct database queries with manager calls
  - [ ] Use `isHybridSearchAvailable()` for capability detection
  - [ ] Pass search options (provider, vault, project_id, limit)
  - [ ] Maintain existing response format exactly

### Options Mapping
- [ ] **Map** CommandRequest arguments to search options
  - [ ] Extract vault, project_id, provider from command arguments
  - [ ] Set appropriate limits and thresholds
  - [ ] Handle missing or invalid option values

### Response Integration
- [ ] **Preserve** existing response format
  - [ ] Keep panelData structure identical
  - [ ] Maintain search mode reporting (hybrid, vector-only, text-only)
  - [ ] Keep fragment data transformation logic
  - [ ] Preserve error handling and toast messages

### Backward Compatibility
- [ ] **Ensure** no breaking changes to public API
  - [ ] Keep existing method signatures
  - [ ] Preserve response format exactly
  - [ ] Maintain error message format
  - [ ] Keep performance characteristics

### Service Provider Integration
- [ ] **Register** HybridSearchManager in service container
  - [ ] Add to existing service provider or create new one
  - [ ] Configure dependency injection for Embeddings service
  - [ ] Ensure proper singleton/scoped lifecycle

## Testing & Validation

### Unit Tests
- [ ] **Create** `tests/Unit/HybridSearchManagerTest.php`
  - [ ] Test driver resolution for different database connections
  - [ ] Test search delegation to appropriate drivers
  - [ ] Test error handling for unsupported drivers

- [ ] **Create** `tests/Unit/PostgreSQLHybridSearchTest.php`
  - [ ] Test search functionality with mocked database
  - [ ] Test capability detection logic
  - [ ] Test fallback behavior

- [ ] **Create** `tests/Unit/SQLiteHybridSearchTest.php`
  - [ ] Test vector conversion helpers
  - [ ] Test search with various capability combinations
  - [ ] Test progressive fallback logic

### Integration Tests
- [ ] **Create** `tests/Feature/SearchAbstractionTest.php`
  - [ ] Test SearchCommand with both database backends
  - [ ] Test response format consistency
  - [ ] Test performance benchmarks
  - [ ] Test fallback behavior end-to-end

### Performance Testing
- [ ] **Benchmark** search performance vs current implementation
  - [ ] Measure query execution time for various dataset sizes
  - [ ] Compare result quality between PostgreSQL and SQLite
  - [ ] Test memory usage and resource consumption

### Manual Testing
- [ ] **Test** search functionality in actual UI
  - [ ] Verify result format and display
  - [ ] Test search modes and fallback indicators
  - [ ] Validate highlighted snippets and scoring

## Quality Assurance

### Code Quality
- [ ] **Follow** Laravel coding standards
- [ ] **Add** comprehensive PHPDoc comments
- [ ] **Use** proper type hints throughout
- [ ] **Handle** all edge cases and error conditions

### Performance
- [ ] **Ensure** abstraction layer adds minimal overhead (<2ms)
- [ ] **Optimize** database queries for both backends
- [ ] **Cache** capability detection results
- [ ] **Minimize** vector conversion overhead

### Compatibility
- [ ] **Maintain** exact interface compatibility with current SearchCommand
- [ ] **Preserve** all response formats and structures
- [ ] **Support** all existing search options and parameters
- [ ] **No breaking changes** to public APIs

## Acceptance Criteria

### Functional Requirements
- [ ] SearchCommand works identically with both PostgreSQL and SQLite
- [ ] Search results maintain consistent format and quality
- [ ] Hybrid search combines vector and text ranking appropriately
- [ ] Graceful fallback when extensions unavailable
- [ ] Performance within 10% of current implementation

### Technical Requirements
- [ ] Clean abstraction layer with proper separation of concerns
- [ ] Comprehensive error handling and logging
- [ ] Extensive test coverage for all search modes
- [ ] Documentation for search architecture and fallback behavior

### Integration Requirements
- [ ] Seamless integration with existing SearchCommand
- [ ] Proper dependency injection configuration
- [ ] Zero breaking changes to existing functionality
- [ ] Compatible with all Sprint 57 vector infrastructure

## Handoff Checklist

### Deliverables
- [ ] **Complete** hybrid search abstraction layer
- [ ] **Working** PostgreSQL and SQLite search implementations
- [ ] **Updated** SearchCommand using abstraction
- [ ] **Comprehensive** test suite with benchmarks
- [ ] **Performance** validation and optimization

### Documentation
- [ ] **API documentation** for search interfaces
- [ ] **Architecture guide** for search abstraction layer
- [ ] **Performance tuning** guide for both backends
- [ ] **Troubleshooting guide** for search issues

### Integration Requirements
- [ ] **Compatible** with embedding store from VECTOR-001 and VECTOR-002
- [ ] **Uses** database schema from VECTOR-003
- [ ] **Ready** for feature detection in VECTOR-005
- [ ] **Supports** NativePHP packaging in VECTOR-006

---

**Estimated Total**: 10-16 hours  
**Complexity**: High  
**Critical Path**: Search functionality integration across vector infrastructure  
**Success Metric**: Identical search behavior and performance across PostgreSQL and SQLite