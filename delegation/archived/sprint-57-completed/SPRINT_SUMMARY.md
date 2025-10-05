# Sprint 57: SQLite-First Vector Store Rollout

## Overview
Transform Fragments Engine from PostgreSQL+pgvector dependency to a dual-database architecture supporting both SQLite+sqlite-vec (default for NativePHP) and PostgreSQL+pgvector (optional). This enables embedded desktop builds with vector search while preserving existing functionality.

## Business Context
**Problem**: Current vector search requires PostgreSQL+pgvector, blocking NativePHP desktop distribution. SQLite ships with NativePHP but has no vector capabilities.

**Solution**: Create abstraction layer supporting both SQLite+sqlite-vec (bundled) and PostgreSQL+pgvector (optional), defaulting to SQLite for single-user desktop deployment.

**Value**: Enables native desktop builds with embedded vector search, 40% simpler deployment, maintains compatibility.

## Sprint Goals
1. **SQLite-First Architecture**: Default to SQLite+sqlite-vec for NativePHP builds
2. **Dual Backend Support**: Preserve PostgreSQL+pgvector as optional deployment target
3. **Zero Breaking Changes**: Maintain API compatibility for existing deployments
4. **Packaging Ready**: Bundle sqlite-vec extension with NativePHP builds
5. **Graceful Degradation**: Fall back to text-only search when vectors unavailable

## Technical Architecture

### Current State Analysis
- ✅ Laravel defaults to SQLite (`config/database.php:19`)
- ❌ Vector operations require PostgreSQL+pgvector
- ❌ 3 critical blockers: migrations, embedding jobs, search commands
- ❌ No fallback when vector extensions unavailable

### Target State
- ✅ SQLite+sqlite-vec as primary vector store
- ✅ PostgreSQL+pgvector as optional alternative  
- ✅ `EmbeddingStore` abstraction layer handles both backends
- ✅ NativePHP bundles sqlite-vec extension
- ✅ Graceful fallback to text-only search

### Key Components
```php
// Abstraction layer
app/Contracts/EmbeddingStoreInterface.php
app/Services/Embeddings/SqliteVectorStore.php
app/Services/Embeddings/PgVectorStore.php
app/Services/Embeddings/EmbeddingStoreManager.php

// Migration support
database/migrations/*_sqlite_vector_support.php
database/migrations/*_dual_path_fragment_embeddings.php

// Search abstraction
app/Services/Search/VectorSearchInterface.php
app/Services/Search/SqliteVectorSearch.php
app/Services/Search/PgVectorSearch.php
```

## Sprint Metrics
- **Timeline**: 6.5-9.8 days (52-78 hours)
- **Task Packs**: 6
- **Priority**: HIGH
- **Dependencies**: None (independent execution)
- **Risk Level**: MEDIUM (database abstraction complexity)

## Task Packs

| Pack ID | Description | Estimated | Dependencies | Agent Profile |
|---------|-------------|-----------|--------------|---------------|
| **VECTOR-001** | EmbeddingStore Abstraction Layer | 12-18h | None | Backend Engineer |
| **VECTOR-002** | SQLite Vector Store Implementation | 14-20h | VECTOR-001 | Backend Engineer |
| **VECTOR-003** | Dual-Path Database Migrations | 8-12h | VECTOR-001 | Backend Engineer |
| **VECTOR-004** | Hybrid Search Abstraction | 10-16h | VECTOR-002 | Backend Engineer |
| **VECTOR-005** | Configuration & Feature Detection | 4-6h | VECTOR-004 | Backend Engineer |
| **VECTOR-006** | NativePHP Packaging & Testing | 4-6h | VECTOR-005 | DevOps Engineer |

## Success Criteria
- [ ] SQLite+sqlite-vec fully functional for embeddings and search
- [ ] PostgreSQL+pgvector compatibility preserved
- [ ] Zero API breaking changes
- [ ] NativePHP build includes sqlite-vec extension
- [ ] All existing tests pass on both database backends
- [ ] Performance within 10% of PostgreSQL implementation

## Risk Mitigation
- **Database Abstraction Complexity**: Incremental implementation with extensive testing
- **sqlite-vec Integration**: Validate extension compatibility early in sprint
- **Performance Concerns**: Benchmark both backends to ensure acceptable performance
- **Migration Safety**: Dual-path migrations with rollback support

## Acceptance Testing
1. Fresh SQLite installation can embed and search fragments
2. Existing PostgreSQL deployments continue working unchanged  
3. Configuration switches between backends seamlessly
4. NativePHP build boots with vector search enabled
5. Fallback to text search works when vectors unavailable

---
*Sprint prepared for immediate execution. All task packs include comprehensive implementation plans, context analysis, and detailed TODO checklists.*