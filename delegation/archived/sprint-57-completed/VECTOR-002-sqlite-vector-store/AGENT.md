# Agent Profile: Backend Engineer - SQLite Vector Store Implementation

## Role Requirements
**Primary**: Backend Engineer with SQLite and vector database expertise  
**Secondary**: Database optimization, extension integration, performance tuning

## Skills Profile
- **SQLite**: Expert level - extensions, custom functions, performance optimization
- **Vector Operations**: Advanced - similarity search, indexing strategies, distance functions
- **PHP PDO/SQLite**: Expert - raw SQL, prepared statements, error handling
- **Laravel Database**: Advanced - query builder, raw SQL integration, transaction management
- **Performance Optimization**: Intermediate - indexing, query optimization, benchmarking

## Domain Knowledge Required
- **sqlite-vec Extension**: Integration patterns, API usage, performance characteristics
- **Vector Mathematics**: Cosine similarity, distance functions, normalization
- **Embedding Workflows**: Understanding vector dimensions, storage formats, search patterns
- **SQLite vs PostgreSQL**: Feature mapping, SQL syntax differences, limitation workarounds

## Technical Challenges
- **Extension Loading**: Dynamic loading of sqlite-vec extension in Laravel context
- **SQL Syntax Translation**: Converting PostgreSQL vector operations to SQLite equivalents
- **Performance Parity**: Achieving comparable search performance to pgvector
- **Index Management**: Implementing efficient vector indexing for similarity search

## Success Criteria
- SQLite vector store achieves feature parity with PostgreSQL implementation
- Search performance within 20% of PostgreSQL+pgvector
- Extension loads reliably across different deployment environments
- Graceful fallback when extension unavailable
- Zero breaking changes to external APIs

## Communication Style
- Technical details about vector operation implementation
- Performance benchmarking results and optimization strategies
- Clear documentation of SQLite-specific limitations and workarounds
- Proactive identification of deployment and packaging considerations

## Task Complexity Assessment
**Estimated**: 14-20 hours  
**Complexity**: HIGH  
**Risk Factors**: Extension integration, performance requirements, SQL translation complexity  
**Dependencies**: VECTOR-001 (abstraction layer) must be completed first