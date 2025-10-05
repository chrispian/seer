# Agent Profile: Backend Engineer - Hybrid Search Abstraction

## Role Requirements
**Primary**: Backend Engineer with search system expertise  
**Secondary**: Performance optimization, query architecture, API design

## Skills Profile
- **Search Systems**: Expert - vector similarity, full-text search, hybrid approaches
- **Laravel Integration**: Advanced - jobs, commands, service integration
- **Performance Optimization**: Advanced - query optimization, caching, indexing
- **API Design**: Expert - backward compatibility, graceful degradation

## Domain Knowledge Required
- **Current Search Implementation**: Understanding existing SearchCommand logic
- **Vector Mathematics**: Cosine similarity, distance functions, scoring algorithms
- **Full-Text Search**: PostgreSQL ts_*, SQLite FTS5, search ranking
- **Embedding Workflows**: Integration with existing fragment processing pipeline

## Success Criteria
- SearchCommand updated to use abstraction layer seamlessly
- Search performance maintains or improves current benchmarks
- Hybrid search works on both SQLite and PostgreSQL
- Graceful fallback when vector extensions unavailable
- Zero breaking changes to search API

## Communication Style
- Performance metrics and benchmark comparisons
- Clear explanation of search algorithm changes
- Documentation of fallback behavior and edge cases
- Progress updates on integration with existing codebase

## Task Complexity Assessment
**Estimated**: 10-16 hours  
**Complexity**: HIGH  
**Risk Factors**: Complex search logic, performance requirements, integration complexity  
**Dependencies**: VECTOR-001 (abstraction), VECTOR-002 (SQLite implementation), VECTOR-003 (migrations)