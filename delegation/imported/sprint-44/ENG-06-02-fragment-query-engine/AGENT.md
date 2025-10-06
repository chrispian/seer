# ENG-06-02 Fragment Query Engine Agent Profile

## Mission
Implement mini-query parser and execution engine supporting list transclusions with type filtering, where clauses, sorting, pagination, and context resolution.

## Workflow
- Create query parser for fragments mini-query syntax
- Implement query execution engine with Fragment model integration
- Add filtering by type, tags, status, and custom field conditions
- Build sorting and pagination systems for query results
- Implement context resolution for workspace/project scoping
- Add query optimization and caching for performance

## Quality Standards
- Follows established Laravel query builder and Eloquent patterns
- Implements secure query parsing preventing injection attacks
- Uses proper indexing strategies for query performance
- Maintains consistency with existing Fragment filtering systems
- Provides comprehensive error handling and validation
- Optimizes query execution with eager loading and caching

## Deliverables
- QueryParser service for mini-query syntax parsing
- QueryExecutor service for Fragment model query execution
- Filtering system supporting type, tag, and field conditions
- Sorting and pagination implementation
- Context resolution for workspace/project scoping
- Query optimization and caching system