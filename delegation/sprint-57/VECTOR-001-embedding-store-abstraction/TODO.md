# TODO: EmbeddingStore Abstraction Layer

## Phase 1: Core Interface Definition ⏱️ 3-4h

### Interface Contract Creation
- [ ] **Create** `app/Contracts/EmbeddingStoreInterface.php`
  - [ ] Define `store()` method signature for embedding persistence
  - [ ] Define `exists()` method for duplicate detection
  - [ ] Define `search()` method for vector similarity queries
  - [ ] Define `isVectorSupportAvailable()` for capability detection
  - [ ] Define `getDriverInfo()` for debugging and monitoring
  - [ ] Add comprehensive PHPDoc with parameter descriptions

### Supporting Data Structures
- [ ] **Create** `app/DTOs/VectorSearchResult.php`
  - [ ] Define readonly class with similarity, textRank, combinedScore
  - [ ] Add `toArray()` method for backward compatibility
  - [ ] Include snippet field for search result display
  - [ ] Ensure consistent structure with existing search results

### Documentation
- [ ] **Document** interface design decisions in docblocks
- [ ] **Document** method contracts and expected behaviors
- [ ] **Document** driver-specific considerations

## Phase 2: Manager Class Implementation ⏱️ 4-6h

### Manager Core Logic
- [ ] **Create** `app/Services/Embeddings/EmbeddingStoreManager.php`
  - [ ] Implement `driver()` method with connection parameter
  - [ ] Implement driver caching to avoid recreation
  - [ ] Add `getDefaultDriver()` with configuration-based selection
  - [ ] Add `detectOptimalDriver()` for auto-detection logic

### Driver Resolution
- [ ] **Implement** `createDriver()` factory method
  - [ ] Support 'sqlite' driver creation
  - [ ] Support 'postgresql' driver creation  
  - [ ] Throw meaningful exceptions for unknown drivers
  - [ ] Add driver validation before instantiation

### Helper Methods
- [ ] **Add** `getSupportedDrivers()` for introspection
- [ ] **Add** driver capability checking methods
- [ ] **Handle** edge cases for missing configuration

### Error Handling
- [ ] **Add** comprehensive exception handling
- [ ] **Add** logging for driver selection decisions
- [ ] **Add** fallback strategies for unsupported configurations

## Phase 3: Configuration Integration ⏱️ 2-3h

### Configuration Structure
- [ ] **Update** `config/fragments.php` embeddings section
  - [ ] Add `driver` key with 'auto' default
  - [ ] Add `drivers.sqlite` configuration block
  - [ ] Add `drivers.postgresql` configuration block
  - [ ] Preserve existing configuration keys for compatibility

### Service Provider
- [ ] **Create** `app/Providers/EmbeddingStoreServiceProvider.php`
  - [ ] Register `EmbeddingStoreManager` as singleton
  - [ ] Bind `EmbeddingStoreInterface` to manager's default driver
  - [ ] Add proper dependency injection resolution
  - [ ] Include any necessary boot logic

### Provider Registration
- [ ] **Update** `bootstrap/providers.php` or `config/app.php`
  - [ ] Add new service provider to providers array
  - [ ] Ensure proper loading order

### Environment Variables
- [ ] **Document** new environment variables
  - [ ] `EMBEDDINGS_DRIVER` - driver selection
  - [ ] `SQLITE_VECTOR_EXTENSION` - SQLite extension name
  - [ ] `SQLITE_VECTOR_EXTENSION_PATH` - extension file path
  - [ ] `PGVECTOR_EXTENSION_CHECK` - PostgreSQL extension validation

## Phase 4: Driver Skeleton Implementation ⏱️ 2-3h

### PostgreSQL Driver
- [ ] **Create** `app/Services/Embeddings/PgVectorStore.php`
  - [ ] Implement `EmbeddingStoreInterface`
  - [ ] **Move** existing logic from `EmbedFragment.php:69-74`
  - [ ] **Move** existing logic from `SearchCommand.php:158-180`
  - [ ] **Preserve** exact functionality for backward compatibility
  - [ ] **Add** proper error handling and logging

### PostgreSQL Driver Methods
- [ ] **Implement** `store()` method
  - [ ] Use existing `?::vector` casting logic
  - [ ] Use existing `ON CONFLICT` upsert logic
  - [ ] Maintain transaction safety
- [ ] **Implement** `exists()` method
  - [ ] Query `fragment_embeddings` table efficiently
  - [ ] Check all required fields: fragment_id, provider, model, content_hash
- [ ] **Implement** `search()` method
  - [ ] Use existing hybrid search SQL
  - [ ] Return `VectorSearchResult[]` array
  - [ ] Preserve similarity scoring logic
- [ ] **Implement** `isVectorSupportAvailable()`
  - [ ] Check for pgvector extension
  - [ ] Handle database connection errors gracefully
- [ ] **Implement** `getDriverInfo()`
  - [ ] Return driver metadata for debugging

### SQLite Driver Skeleton
- [ ] **Create** `app/Services/Embeddings/SqliteVectorStore.php`
  - [ ] Implement `EmbeddingStoreInterface`
  - [ ] **Throw** `RuntimeException` with "pending VECTOR-002" message
  - [ ] **Add** placeholder methods for all interface methods
  - [ ] **Include** basic driver info structure

## Phase 5: Integration & Testing ⏱️ 1-2h

### Unit Tests
- [ ] **Create** `tests/Unit/EmbeddingStoreManagerTest.php`
  - [ ] Test driver resolution for different database connections
  - [ ] Test configuration-based driver selection
  - [ ] Test auto-detection logic
  - [ ] Test error handling for unsupported drivers
  - [ ] Test singleton behavior and caching

### Integration Tests
- [ ] **Create** `tests/Feature/EmbeddingStoreIntegrationTest.php`
  - [ ] Test service container resolution
  - [ ] Test dependency injection with different drivers
  - [ ] Test configuration changes at runtime
  - [ ] Test PostgreSQL driver with existing functionality

### Manual Validation
- [ ] **Test** Laravel service container resolves `EmbeddingStoreInterface`
- [ ] **Test** Manager returns PostgreSQL driver for pgsql connections
- [ ] **Test** Configuration defaults don't break existing deployments
- [ ] **Test** Driver switching works via configuration changes

## Quality Assurance

### Code Quality
- [ ] **Follow** Laravel coding standards and conventions
- [ ] **Add** comprehensive docblocks for all public methods
- [ ] **Use** proper type hints throughout
- [ ] **Include** proper exception handling

### Performance
- [ ] **Ensure** abstraction layer adds <1ms overhead
- [ ] **Cache** driver instances to avoid recreation
- [ ] **Optimize** driver resolution logic

### Compatibility
- [ ] **Maintain** existing API signatures
- [ ] **Preserve** PostgreSQL functionality exactly
- [ ] **Support** existing configuration options
- [ ] **No breaking changes** to public interfaces

## Acceptance Criteria

### Functional Requirements
- [ ] `EmbeddingStoreInterface` provides complete abstraction
- [ ] `EmbeddingStoreManager` correctly resolves drivers
- [ ] PostgreSQL driver maintains 100% functionality
- [ ] Configuration supports both manual and auto driver selection
- [ ] Service provider integrates with Laravel container

### Technical Requirements
- [ ] Zero breaking changes to existing code
- [ ] All unit tests pass
- [ ] Integration tests verify end-to-end functionality
- [ ] Performance within acceptable limits (<1ms overhead)

### Documentation Requirements
- [ ] Interface contract fully documented
- [ ] Configuration options documented
- [ ] Driver selection logic explained
- [ ] Migration path from direct implementation documented

## Handoff Checklist

### Deliverables
- [ ] **Complete** interface contract with all method signatures
- [ ] **Working** PostgreSQL driver preserving existing functionality
- [ ] **Functional** manager class with driver resolution
- [ ] **Integrated** service provider with Laravel container
- [ ] **Comprehensive** unit and integration tests

### Documentation
- [ ] **API documentation** for all public interfaces
- [ ] **Configuration guide** for driver selection
- [ ] **Testing guide** for validating implementations
- [ ] **Troubleshooting guide** for common issues

### Next Task Dependencies
- [ ] **Interface contract** ready for SQLite implementation (VECTOR-002)
- [ ] **Manager class** ready for migration integration (VECTOR-003)
- [ ] **Driver pattern** established for search abstraction (VECTOR-004)
- [ ] **Configuration structure** ready for feature detection (VECTOR-005)

---

**Estimated Total**: 12-18 hours  
**Complexity**: Medium-High  
**Critical Path**: Foundation for all other vector tasks  
**Success Metric**: PostgreSQL driver works identically to current implementation