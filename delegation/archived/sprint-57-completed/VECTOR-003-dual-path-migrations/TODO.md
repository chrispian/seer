# TODO: Dual-Path Database Migrations

## Phase 1: Migration Foundation & Helpers ⏱️ 2-3h

### Migration Helper Class
- [ ] **Create** `app/Database/MigrationHelpers/VectorMigrationHelper.php`
  - [ ] Implement `getDriver()` method for database driver detection
  - [ ] Implement `isPostgreSQL()` and `isSQLite()` boolean helpers
  - [ ] Implement `hasPostgreSQLVectorSupport()` with pgvector extension check
  - [ ] Implement `hasSQLiteVectorSupport()` with sqlite-vec extension check
  - [ ] Add comprehensive error handling for connection issues
  - [ ] Add logging for driver detection and extension availability

### Helper Integration
- [ ] **Add** autoloading support for helper classes
  - [ ] Update composer.json if needed for namespace
  - [ ] Test helper class instantiation and method calls
  - [ ] Verify helper works in migration context

### Documentation
- [ ] **Document** helper class usage patterns
- [ ] **Document** driver detection logic
- [ ] **Document** extension checking strategies

## Phase 2: Update Existing Embeddings Migration ⏱️ 2-3h

### Base Migration Update
- [ ] **Modify** `database/migrations/2025_08_30_045548_create_fragment_embeddings.php`
  - [ ] Import VectorMigrationHelper class
  - [ ] Replace hardcoded PostgreSQL logic with driver detection
  - [ ] Create common table structure first (id, fragment_id, provider, etc.)
  - [ ] Add driver-specific schema creation methods

### PostgreSQL Schema Method
- [ ] **Implement** `createPostgreSQLVectorSupport()` method
  - [ ] Preserve existing `vector(1536)` column creation
  - [ ] Preserve existing unique constraint logic
  - [ ] Keep commented ANN index for optional optimization
  - [ ] Maintain 100% backward compatibility

### SQLite Schema Method
- [ ] **Implement** `createSQLiteVectorSupport()` method
  - [ ] Add BLOB column for vector storage
  - [ ] Create appropriate unique constraints for SQLite
  - [ ] Create virtual table index if sqlite-vec extension available
  - [ ] Handle extension unavailability gracefully (log warning, continue)

### Migration Safety
- [ ] **Add** comprehensive error handling
  - [ ] Throw meaningful exceptions for unsupported drivers
  - [ ] Log driver selection and schema creation steps
  - [ ] Handle rollback scenarios properly

### Testing
- [ ] **Test** migration on fresh SQLite database
- [ ] **Test** migration rollback works correctly
- [ ] **Verify** PostgreSQL functionality unchanged

## Phase 3: Update Model/Hash Migration ⏱️ 2-3h

### Column Addition Logic
- [ ] **Modify** `database/migrations/2025_08_30_053234_add_model_hash_to_fragment_embeddings.php`
  - [ ] Add driver detection using VectorMigrationHelper
  - [ ] Check for existing columns before adding (idempotency)
  - [ ] Handle nullable columns initially for data backfill

### PostgreSQL NOT NULL Constraints
- [ ] **Preserve** existing PostgreSQL logic
  - [ ] Use `ALTER COLUMN SET NOT NULL` statements
  - [ ] Maintain existing error handling

### SQLite Table Recreation
- [ ] **Implement** `recreateSQLiteTableWithNotNull()` method
  - [ ] Create new table with NOT NULL constraints
  - [ ] Copy existing data with COALESCE for missing values
  - [ ] Handle foreign key constraints properly
  - [ ] Replace old table atomically

### Index Creation
- [ ] **Implement** `createUniqueIndex()` method
  - [ ] Use `CREATE UNIQUE INDEX IF NOT EXISTS` for both drivers
  - [ ] Handle index creation failures gracefully
  - [ ] Log index creation success/failure

### Data Backfill
- [ ] **Update** `backfillExistingData()` method
  - [ ] Work correctly with both PostgreSQL and SQLite
  - [ ] Handle null content_hash values
  - [ ] Use appropriate fallback models per provider
  - [ ] Update content_hash calculation logic

### Rollback Support
- [ ] **Implement** proper rollback for both drivers
  - [ ] Drop indexes before dropping columns
  - [ ] Handle SQLite table recreation rollback
  - [ ] Test rollback functionality thoroughly

## Phase 4: FTS5 Support Migration ⏱️ 2-3h

### New Migration Creation
- [ ] **Create** `database/migrations/2025_01_04_000001_create_sqlite_fts5_support.php`
  - [ ] Use VectorMigrationHelper for driver detection
  - [ ] Only run FTS5 logic for SQLite databases
  - [ ] Return early for PostgreSQL (no action needed)

### FTS5 Table Creation
- [ ] **Implement** `createFtsTable()` method
  - [ ] Create `fragments_fts` virtual table using FTS5
  - [ ] Configure content table mapping to fragments
  - [ ] Set up title and content columns for indexing
  - [ ] Handle FTS5 unavailability gracefully

### Initial Data Population
- [ ] **Implement** `populateFtsTable()` method
  - [ ] Query existing fragments with non-empty content
  - [ ] Use COALESCE for title and content fields
  - [ ] Handle edited_message vs message priority
  - [ ] Batch inserts for performance on large datasets

### Trigger Creation
- [ ] **Implement** `createFtsTriggers()` method
  - [ ] Create INSERT trigger for new fragments
  - [ ] Create UPDATE trigger for fragment changes
  - [ ] Create DELETE trigger for fragment removal
  - [ ] Handle trigger creation errors gracefully

### Migration Rollback
- [ ] **Implement** comprehensive rollback in `down()` method
  - [ ] Drop all FTS triggers in correct order
  - [ ] Drop FTS virtual table
  - [ ] Handle rollback errors gracefully

## Phase 5: Testing & Validation ⏱️ 1-2h

### Migration Tests
- [ ] **Create** `tests/Feature/VectorMigrationTest.php`
  - [ ] Test SQLite migration creates correct schema
  - [ ] Test PostgreSQL migration preserves existing functionality
  - [ ] Test migration rollbacks work correctly
  - [ ] Test FTS5 support creation and population

### Schema Validation
- [ ] **Test** table existence after migration
  - [ ] Verify `fragment_embeddings` table structure
  - [ ] Verify appropriate indexes created
  - [ ] Verify FTS5 table exists for SQLite
  - [ ] Verify foreign key constraints work

### Data Integrity Tests
- [ ] **Test** backfill functionality works correctly
  - [ ] Verify content_hash generation
  - [ ] Verify model assignment logic
  - [ ] Test with various existing data scenarios

### Cross-Platform Testing
- [ ] **Test** migrations on SQLite
  - [ ] Fresh installation
  - [ ] Upgrade from existing installation
  - [ ] Rollback scenarios
- [ ] **Test** migrations on PostgreSQL (if available)
  - [ ] Verify no breaking changes
  - [ ] Test extension detection logic

### Performance Testing
- [ ] **Benchmark** migration performance
  - [ ] Large dataset migration times
  - [ ] FTS5 population performance
  - [ ] Index creation performance

## Quality Assurance

### Code Quality
- [ ] **Follow** Laravel migration best practices
- [ ] **Use** proper transaction handling where appropriate
- [ ] **Add** comprehensive logging for debugging
- [ ] **Handle** edge cases and error conditions

### Safety Measures
- [ ] **Validate** schema changes are reversible
- [ ] **Test** rollback procedures thoroughly
- [ ] **Ensure** data integrity maintained throughout
- [ ] **Verify** no data loss scenarios

### Documentation
- [ ] **Document** migration procedure
- [ ] **Document** rollback procedures
- [ ] **Document** troubleshooting for common issues
- [ ] **Document** driver-specific differences

## Acceptance Criteria

### Functional Requirements
- [ ] Migrations work correctly on both SQLite and PostgreSQL
- [ ] PostgreSQL functionality exactly preserved
- [ ] SQLite gets appropriate schema with BLOB vector storage
- [ ] FTS5 support properly configured for SQLite
- [ ] All migrations are reversible

### Data Integrity Requirements
- [ ] No data loss during migration or rollback
- [ ] Content hash backfill works correctly
- [ ] Foreign key constraints maintained
- [ ] Index constraints properly enforced

### Performance Requirements
- [ ] Migration time acceptable for large datasets
- [ ] FTS5 population efficient
- [ ] Index creation optimized

## Handoff Checklist

### Deliverables
- [ ] **Complete** dual-path migration system
- [ ] **Working** VectorMigrationHelper class
- [ ] **Updated** existing migrations with driver detection
- [ ] **New** FTS5 support migration for SQLite
- [ ] **Comprehensive** test suite

### Documentation
- [ ] **Migration guide** for upgrading existing installations
- [ ] **Rollback procedures** for emergency scenarios
- [ ] **Troubleshooting guide** for common migration issues
- [ ] **Performance tuning** recommendations

### Integration Requirements
- [ ] **Compatible** with abstraction layer from VECTOR-001
- [ ] **Supports** SQLite implementation from VECTOR-002
- [ ] **Enables** search abstraction in VECTOR-004
- [ ] **Ready** for feature detection in VECTOR-005

---

**Estimated Total**: 8-12 hours  
**Complexity**: Medium  
**Critical Path**: Database schema foundation for all vector operations  
**Success Metric**: Zero breaking changes to PostgreSQL, full SQLite compatibility