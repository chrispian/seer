# FE-01 Type System Implementation TODO

## Phase 1: Foundation Infrastructure

### Registry Cache System
- [ ] Create migration: `create_fragment_type_registry_table`
  - [ ] `slug` VARCHAR(255) UNIQUE NOT NULL
  - [ ] `version` VARCHAR(32) NOT NULL
  - [ ] `source_path` TEXT NOT NULL
  - [ ] `schema_hash` VARCHAR(64) NOT NULL
  - [ ] `hot_fields` JSONB DEFAULT '{}'
  - [ ] `capabilities` JSONB DEFAULT '{}'
  - [ ] `min_requirements` JSONB DEFAULT '[]'
  - [ ] `created_at`, `updated_at` TIMESTAMP
- [ ] Create `FragmentTypeRegistry` model
  - [ ] Define fillable fields and casts
  - [ ] Add relationships if needed
  - [ ] Follow existing model patterns

### Configuration
- [ ] Extend `config/fragments.php` with type system settings
  - [ ] Type Pack search paths with precedence
  - [ ] Registry cache settings
  - [ ] Validation configuration options
- [ ] Add default type pack paths configuration

### Type Pack Loader Service
- [ ] Create `app/Services/TypePackLoader.php`
  - [ ] File discovery with precedence (storage > fragments > modules)
  - [ ] YAML manifest parsing for `type.yaml`
  - [ ] JSON schema loading for `state.schema.json`
  - [ ] Index configuration parsing for `indexes.yaml`
  - [ ] Hash calculation for cache invalidation
- [ ] Add error handling for malformed Type Packs
- [ ] Implement caching strategy for loaded packs

### Schema Validation Service
- [ ] Create `app/Services/TypePackValidator.php`
  - [ ] Integration with existing `JsonSchemaValidator`
  - [ ] Type Pack schema validation
  - [ ] Fragment state validation against schema
  - [ ] Clear error message formatting with JSON paths
- [ ] Add validation result value objects
- [ ] Implement validation caching for performance

## Phase 2: Type Pack Structure

### Directory Structure Creation
- [ ] Create `fragments/types/` base directory
- [ ] Design Type Pack directory structure template
- [ ] Add `.gitkeep` files for empty directories
- [ ] Document Type Pack structure requirements

### Todo Type Pack Sample
- [ ] Create `fragments/types/todo/` directory
- [ ] Create `type.yaml` manifest
  - [ ] Name, slug, version, extends
  - [ ] Min requirements, hot fields
  - [ ] UI configuration, prompts, policy
- [ ] Create `state.schema.json` for todo state
  - [ ] Status enum: open, in_progress, blocked, done, canceled
  - [ ] Due date with proper date-time format
  - [ ] Priority enum: low, med, high, urgent
  - [ ] Required fields validation
- [ ] Create `indexes.yaml` for generated columns
  - [ ] Status column configuration
  - [ ] Due date column configuration
  - [ ] Partial index definitions

### File Precedence System
- [ ] Implement Type Pack discovery across multiple paths
- [ ] User override support in `storage/fragments/types/`
- [ ] Module support in `modules/*/fragments/types/`
- [ ] Conflict resolution and precedence rules

## Phase 3: Fragment Model Integration

### Validation Hooks
- [ ] Add schema validation to Fragment model `creating` event
- [ ] Add schema validation to Fragment model `updating` event
- [ ] Preserve existing boot method functionality
- [ ] Add validation bypass flag for migration/seeding

### Backward Compatibility
- [ ] Ensure existing fragments continue to work
- [ ] Add graceful degradation for missing Type Packs
- [ ] Preserve existing type relationships
- [ ] Maintain current Fragment scope functionality

### Error Handling
- [ ] Return 422 responses for validation failures
- [ ] Format validation errors with clear JSON paths
- [ ] Add validation error logging
- [ ] Provide helpful error messages for developers

### Type Resolution
- [ ] Integrate with existing type inference system
- [ ] Add Type Pack lookup by fragment type
- [ ] Cache resolved Type Packs for performance
- [ ] Handle type transitions and updates

## Phase 4: Performance Optimization

### Generated Columns Migration
- [ ] Create migration for todo generated columns
  - [ ] `status` VARCHAR(32) GENERATED ALWAYS AS (state->>'status')
  - [ ] `due_at` TIMESTAMP GENERATED ALWAYS AS ((state->>'due_at')::timestamp)
  - [ ] `priority` VARCHAR(16) GENERATED ALWAYS AS (state->>'priority')
- [ ] Add proper column indexes
- [ ] Test column generation with existing data

### Partial Indexes
- [ ] Create partial index for todo status: `WHERE type = 'todo'`
- [ ] Create partial index for todo due dates: `WHERE type = 'todo' AND due_at IS NOT NULL`
- [ ] Add composite indexes for common query patterns
- [ ] Performance test index effectiveness

### Fragment Scope Updates
- [ ] Update `scopeTodos` to use generated columns
- [ ] Update `scopeOpenTodos` to use status column
- [ ] Update `scopeCompletedTodos` to use status column
- [ ] Add new scopes for due date queries

### Performance Testing
- [ ] Benchmark queries before generated columns
- [ ] Benchmark queries after generated columns
- [ ] Measure schema validation overhead
- [ ] Test registry cache performance

## Phase 5: Management Commands

### Command Infrastructure
- [ ] Create base command class if needed
- [ ] Follow existing command patterns in `app/Console/Commands/`
- [ ] Add proper error handling and output formatting
- [ ] Implement dry-run options where appropriate

### `frag:type:make` Command
- [ ] Create `app/Console/Commands/FragTypeMake.php`
- [ ] Scaffold Type Pack directory structure
- [ ] Generate template files with placeholders
- [ ] Add validation for slug format and conflicts
- [ ] Provide helpful success messages and next steps

### `frag:type:cache` Command
- [ ] Create `app/Console/Commands/FragTypeCache.php`
- [ ] Discover all Type Packs across search paths
- [ ] Validate Type Pack structure and schemas
- [ ] Update registry cache with current data
- [ ] Print summary of loaded packs and any warnings
- [ ] Add option to show suggested SQL for indexes

### `frag:type:validate` Command
- [ ] Create `app/Console/Commands/FragTypeValidate.php`
- [ ] Load Type Pack by slug
- [ ] Validate sample JSON against schema
- [ ] Show validation results with clear error paths
- [ ] Support validating multiple samples
- [ ] Add option for batch validation

## Phase 6: Testing & Documentation

### Unit Tests
- [ ] Test Type Pack loading and caching
- [ ] Test schema validation with various inputs
- [ ] Test registry cache operations
- [ ] Test command functionality with mocked data

### Feature Tests
- [ ] Test Fragment creation with schema validation
- [ ] Test Fragment updates with validation
- [ ] Test generated column functionality
- [ ] Test management commands end-to-end

### Integration Tests
- [ ] Test with existing Fragment workflows
- [ ] Test performance improvements
- [ ] Test backward compatibility
- [ ] Test error handling edge cases

### Performance Tests
- [ ] Benchmark registry cache vs file system
- [ ] Benchmark generated columns vs JSON queries
- [ ] Measure schema validation overhead
- [ ] Test cache invalidation performance

## Verification & Acceptance

### Functional Verification
- [ ] All Type Packs load correctly with precedence
- [ ] Schema validation works on Fragment operations
- [ ] Generated columns improve query performance
- [ ] Commands work as expected with clear output
- [ ] Existing fragments continue to work unchanged

### Performance Verification
- [ ] Registry lookups under 1ms consistently
- [ ] Generated columns show >50% query improvement
- [ ] Schema validation adds <10ms overhead
- [ ] No performance regression in existing workflows

### Quality Verification
- [ ] Code follows PSR-12 and project patterns
- [ ] Test coverage >80% for new functionality
- [ ] Error messages are clear and actionable
- [ ] Documentation is complete and accurate

### Integration Verification
- [ ] Preserves existing Fragment model functionality
- [ ] Integrates cleanly with AI provider system
- [ ] Works with existing type relationships
- [ ] Maintains backward compatibility

## Post-Implementation Tasks

### Documentation
- [ ] Update Type Pack structure documentation
- [ ] Add schema definition guidelines
- [ ] Document performance optimization strategies
- [ ] Create migration guide for existing types

### Cleanup
- [ ] Remove any temporary files or debug code
- [ ] Optimize imports and dependencies
- [ ] Clean up migration files
- [ ] Remove unused configuration options

### Handoff Preparation
- [ ] Create demo Type Pack examples
- [ ] Prepare performance benchmark results
- [ ] Document any known limitations
- [ ] Create upgrade/rollback procedures