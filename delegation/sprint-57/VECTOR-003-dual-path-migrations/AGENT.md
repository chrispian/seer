# Agent Profile: Backend Engineer - Dual-Path Database Migrations

## Role Requirements
**Primary**: Backend Engineer with Laravel migration expertise  
**Secondary**: Database schema design, cross-platform compatibility

## Skills Profile
- **Laravel Migrations**: Expert - schema definition, rollbacks, conditional logic
- **Multi-Database Support**: Advanced - driver detection, conditional DDL
- **SQLite vs PostgreSQL**: Expert - feature differences, compatibility strategies
- **Schema Design**: Advanced - indexing, constraints, performance optimization

## Domain Knowledge Required
- **Current Embedding Schema**: Understanding existing `fragment_embeddings` table
- **Vector Storage Patterns**: BLOB vs native vector types, indexing strategies
- **Migration Safety**: Zero-downtime deployments, rollback procedures
- **Extension Dependencies**: pgvector vs sqlite-vec requirements

## Success Criteria
- Migrations work correctly on both SQLite and PostgreSQL
- Zero breaking changes to existing deployments
- Proper driver detection and conditional schema creation
- Full rollback support for all migration steps
- Index optimization for both database backends

## Communication Style
- Clear documentation of schema differences between databases
- Detailed rollback procedures and safety considerations
- Performance implications of different schema approaches
- Migration testing strategies for both database types

## Task Complexity Assessment
**Estimated**: 8-12 hours  
**Complexity**: MEDIUM  
**Risk Factors**: Schema compatibility, migration safety, rollback complexity  
**Dependencies**: VECTOR-001 (driver detection), VECTOR-002 (SQLite schema requirements)