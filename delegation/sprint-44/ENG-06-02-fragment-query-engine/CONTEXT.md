# ENG-06-02 Fragment Query Engine Context

## Technical Architecture

### Query Syntax Specification
```
# Basic syntax
type:todo where:done=false sort:due limit:20

# Advanced filtering
type:note,bookmark tag:#work where:importance>5 sort:created desc limit:10

# Context scoping
@ws:work @proj:fragments type:todo where:due<2025-10-15 sort:priority asc

# Field filtering
where:state.priority=high,urgent where:metadata.category=development
```

### Query Components
- **Type Filter**: `type:todo`, `type:note,bookmark`
- **Tag Filter**: `tag:#work`, `tag:urgent,important`
- **Where Clauses**: `where:field=value`, `where:field>value`, `where:field<date`
- **Sorting**: `sort:field`, `sort:field asc`, `sort:field desc`
- **Pagination**: `limit:20`, `offset:40`
- **Context**: `@ws:workspace`, `@proj:project`

### Integration Points
- **Fragment Model**: app/Models/Fragment.php - Core query target
- **Existing Scopes**: Fragment model scopes and relationships
- **Search System**: Integration with existing search functionality
- **Context Stack**: Workspace/project resolution
- **Caching**: Query result caching for performance

### Dependencies
- ENG-06-01 TransclusionSpec foundation
- Fragment model and relationship system
- Context resolution system
- Existing Fragment filtering and search

### Database Optimization
- Proper indexing for common query patterns
- Eager loading for related models
- Query result caching
- Pagination for large result sets