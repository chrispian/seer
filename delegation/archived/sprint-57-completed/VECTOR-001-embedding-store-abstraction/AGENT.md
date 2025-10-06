# Agent Profile: Backend Engineer - EmbeddingStore Abstraction

## Role Requirements
**Primary**: Backend Engineer with Laravel expertise  
**Secondary**: Database architecture, interface design, dependency injection

## Skills Profile
- **Laravel Framework**: Expert level - service providers, contracts, dependency injection
- **Database Abstraction**: Advanced - multiple database backend support
- **PHP OOP**: Expert - interfaces, abstract classes, polymorphism  
- **Migration Patterns**: Advanced - safe schema changes, rollback strategies
- **Testing**: Intermediate - unit tests, mocking, database testing

## Domain Knowledge Required
- **Current Vector Implementation**: Understanding of existing pgvector integration
- **SQLite vs PostgreSQL**: Database feature differences and compatibility
- **Embedding Workflows**: Fragment processing pipeline and job queues
- **Laravel Service Container**: Binding patterns and resolution strategies

## Success Criteria
- Clean interface contracts with no database-specific dependencies
- Manager class properly routes to backend implementations
- Service provider bindings work with both SQLite and PostgreSQL
- Existing code seamlessly adopts new abstraction layer
- Zero breaking changes to public APIs

## Communication Style
- Technical precision in interface design decisions
- Clear documentation of backend switching logic
- Proactive identification of edge cases and compatibility issues
- Regular progress updates on abstraction completeness

## Task Complexity Assessment
**Estimated**: 12-18 hours  
**Complexity**: MEDIUM-HIGH  
**Risk Factors**: Interface design affects entire vector system  
**Dependencies**: None - foundation for all other vector tasks