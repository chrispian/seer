# Type System Implementation Plan

## Phase 1: Foundation Infrastructure
**Duration**: 6-8 hours
**Status**: `todo`

### Registry Cache System
- [ ] Create `fragment_type_registry` migration
  - `slug` (unique), `version`, `source_path`, `schema_hash`
  - `hot_fields` (json), `capabilities` (json), `created_at`, `updated_at`
- [ ] Create `FragmentTypeRegistry` model with proper relationships
- [ ] Implement Type Pack loader service with file precedence
- [ ] Add configuration section to `config/fragments.php`

### Schema Validation Foundation  
- [ ] Create `TypePackValidator` service
- [ ] Integrate with existing `JsonSchemaValidator`
- [ ] Design validation hook points in Fragment model
- [ ] Error handling for validation failures (422 responses)

## Phase 2: Type Pack Structure
**Duration**: 4-6 hours  
**Status**: `backlog`

### File System Organization
- [ ] Create `fragments/types/` directory structure
- [ ] Implement Type Pack manifest loader (`type.yaml`)
- [ ] Add JSON schema loading for `state.schema.json`
- [ ] Index configuration loader for `indexes.yaml`
- [ ] File precedence resolver (storage > fragments > modules)

### Todo Type Pack Sample
- [ ] Create complete todo Type Pack structure
- [ ] Define todo state schema (status, due_at, priority, etc.)
- [ ] Configure hot fields for performance optimization
- [ ] Add validation rules and requirements

## Phase 3: Fragment Integration
**Duration**: 4-6 hours
**Status**: `backlog`

### Model Integration
- [ ] Add schema validation to Fragment model boot methods
- [ ] Preserve backward compatibility for existing fragments
- [ ] Integration with existing type inference system
- [ ] Update Fragment scopes to leverage new structure

### Validation Workflow
- [ ] Validate state on Fragment create/update
- [ ] Clear error messages with JSON path details
- [ ] Optional validation flag for gradual rollout
- [ ] Integration testing with existing Fragment workflows

## Phase 4: Performance Optimization
**Duration**: 4-6 hours
**Status**: `backlog`

### Generated Columns
- [ ] Create migration for todo hot fields
  - `status` VARCHAR(32) generated from `state->>'status'`
  - `due_at` TIMESTAMP generated from `state->>'due_at'`
  - `priority` VARCHAR(16) generated from `state->>'priority'`
- [ ] Add partial indexes scoped by type
- [ ] Performance testing before/after implementation

### Query Optimization
- [ ] Update Fragment scopes to use generated columns
- [ ] Add indexes for common query patterns
- [ ] Performance benchmarking and optimization

## Phase 5: Management Commands
**Duration**: 3-4 hours
**Status**: `backlog`

### Artisan Commands
- [ ] `frag:type:make {slug}` - Scaffold new Type Pack
- [ ] `frag:type:cache` - Rebuild registry from files
- [ ] `frag:type:validate {slug} {sample.json}` - Test validation
- [ ] Follow existing command patterns and conventions

### Developer Experience
- [ ] Template scaffolding for new Type Packs
- [ ] Registry cache rebuilding and verification
- [ ] Local validation testing tools
- [ ] Documentation and usage examples

## Phase 6: Testing & Documentation
**Duration**: 2-3 hours
**Status**: `backlog`

### Test Coverage
- [ ] Unit tests for Type Pack loading and validation
- [ ] Feature tests for Fragment integration
- [ ] Performance tests for generated columns
- [ ] Command testing with sample data

### Documentation
- [ ] Type Pack structure documentation
- [ ] Schema definition guidelines
- [ ] Performance optimization guide
- [ ] Migration and upgrade notes

## Acceptance Criteria

### Functional Requirements
- [ ] Type Packs load correctly with file precedence
- [ ] Schema validation works on Fragment create/update
- [ ] Generated columns significantly improve query performance
- [ ] Management commands follow Laravel conventions
- [ ] Backward compatibility maintained for existing fragments

### Performance Requirements
- [ ] Registry cache provides sub-millisecond lookups
- [ ] Generated columns improve query performance by >50%
- [ ] Schema validation adds <10ms overhead per operation
- [ ] File loading is cached and optimized

### Quality Requirements
- [ ] All code follows PSR-12 and project conventions
- [ ] Comprehensive test coverage (>80%)
- [ ] Clear error messages for validation failures
- [ ] Integration with existing codebase patterns

## Risk Mitigation

### Backward Compatibility
- Schema validation is opt-in initially
- Existing fragments continue to work unchanged
- Gradual migration path for existing data
- Fallback to current behavior if validation fails

### Performance Impact
- Registry cache minimizes filesystem operations
- Generated columns are optional and targeted
- Benchmarking before production deployment
- Rollback plan for performance regressions

### Integration Complexity
- Incremental integration with existing Fragment model
- Preserve existing type relationships and scopes
- Careful testing of AI provider integration points
- Clear separation of concerns between components

## Dependencies

### Internal Dependencies
- Existing Fragment model and relationships
- JsonSchemaValidator service
- Current type inference system
- Fragment creation and update workflows

### External Dependencies
- Laravel migration system
- PostgreSQL JSON support
- Existing test infrastructure
- Current AI provider system

## Success Metrics

### Developer Experience
- Type Pack creation time reduced to <5 minutes
- Clear validation error messages with JSON paths
- Management commands provide helpful feedback
- Documentation enables easy adoption

### Performance Improvements
- Todo query performance improved by >50%
- Registry lookups under 1ms consistently
- Schema validation overhead under 10ms
- Database query optimization measurable