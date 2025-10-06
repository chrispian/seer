# TODO: Enhanced Registry Schema & Metadata

## Database Schema Tasks

### Schema Migration
- [ ] Create migration file `enhance_command_registry_metadata.php`
- [ ] Add `name` column (VARCHAR 255, nullable)
- [ ] Add `category` column (VARCHAR 100, nullable) 
- [ ] Add `summary` column (TEXT, nullable)
- [ ] Add `usage` column (TEXT, nullable)
- [ ] Add `examples` column (JSON, nullable)
- [ ] Add `aliases` column (JSON, nullable)
- [ ] Add `keywords` column (JSON, nullable)
- [ ] Add index on `category` for filtering
- [ ] Add index on `name` for search
- [ ] Write rollback logic in `down()` method
- [ ] Test migration on fresh database
- [ ] Test migration on database with existing registry entries
- [ ] Verify rollback functionality

## Model Enhancement Tasks

### CommandRegistry Model Updates
- [ ] Add new fields to `$fillable` array
- [ ] Add `examples`, `aliases`, `keywords` to `$casts` as arrays
- [ ] Create `getDisplayNameAttribute()` accessor with fallback logic
- [ ] Create `getAliasesAttribute()` for safe JSON decoding
- [ ] Create `getExamplesAttribute()` for safe JSON decoding  
- [ ] Create `getKeywordsAttribute()` for safe JSON decoding
- [ ] Add validation rules for new fields
- [ ] Test model field access and JSON casting

## CommandPackLoader Enhancement Tasks

### Help Metadata Extraction
- [ ] Create `extractHelpMetadata()` method in CommandPackLoader
- [ ] Parse `help` block from command manifest
- [ ] Extract name with fallback to manifest name or slug
- [ ] Extract category with inference logic
- [ ] Extract summary (required for non-reserved commands)
- [ ] Extract usage pattern with default generation
- [ ] Extract examples array
- [ ] Extract aliases from triggers section
- [ ] Extract keywords array
- [ ] Test help metadata extraction with various manifest structures

### Validation Logic
- [ ] Create `validateHelpMetadata()` method
- [ ] Validate name length (1-255 characters)
- [ ] Validate category against allowed values
- [ ] Require summary for non-reserved commands
- [ ] Validate usage pattern format
- [ ] Validate examples array structure
- [ ] Validate keywords array
- [ ] Log validation errors without breaking loading
- [ ] Test validation with valid and invalid help blocks

### Helper Utilities
- [ ] Create `inferCategory()` method for pattern-based categorization
- [ ] Create `generateDefaultUsage()` method
- [ ] Create `analyzeUserInputSteps()` for usage pattern detection
- [ ] Test category inference for various command types
- [ ] Test default usage generation

### Registry Cache Update
- [ ] Modify `updateRegistryCache()` to include help metadata
- [ ] Integrate help metadata extraction
- [ ] Integrate validation with error logging
- [ ] Persist metadata to new registry fields
- [ ] Maintain backward compatibility with existing fields
- [ ] Test registry cache updates with enhanced metadata

## Testing Tasks

### Unit Tests
- [ ] Test help metadata extraction from complete manifest
- [ ] Test help metadata extraction with missing help block
- [ ] Test fallback logic for missing fields
- [ ] Test validation logic with valid data
- [ ] Test validation logic with invalid data
- [ ] Test category inference for various command patterns
- [ ] Test default usage generation
- [ ] Test model accessor methods
- [ ] Test JSON field casting

### Integration Tests
- [ ] Test full command pack loading with help metadata
- [ ] Test registry cache updates with new fields
- [ ] Test backward compatibility with existing packs
- [ ] Test loading packs without help blocks
- [ ] Test loading packs with invalid help data
- [ ] Test migration effects on pack loading

### Feature Tests
- [ ] Test command registry queries with new fields
- [ ] Test model relationships and queries
- [ ] Test performance with enhanced schema
- [ ] Test JSON field querying capabilities

## Documentation Tasks

### Code Documentation
- [ ] Document new model methods with PHPDoc
- [ ] Document CommandPackLoader enhancements
- [ ] Document validation rules and error handling
- [ ] Document help block specification
- [ ] Document migration considerations

### Schema Documentation
- [ ] Document new database columns and their purpose
- [ ] Document index strategy and performance considerations
- [ ] Document JSON field structure and usage
- [ ] Document backward compatibility approach

## Deployment Tasks

### Pre-Deployment
- [ ] Verify all tests pass
- [ ] Review code for security considerations
- [ ] Validate migration performance on realistic data
- [ ] Document rollback procedures

### Post-Deployment Validation
- [ ] Verify migration completed successfully
- [ ] Check registry entries have appropriate metadata
- [ ] Monitor application performance
- [ ] Validate help metadata appears in subsequent features

## Success Criteria Checklist

### Functional Requirements
- [ ] All existing command packs continue to load without errors
- [ ] Help metadata is extracted and persisted when available
- [ ] Graceful handling of packs without help blocks
- [ ] Validation errors are logged but non-blocking
- [ ] Model provides clean access to metadata fields

### Performance Requirements
- [ ] Migration completes in under 30 seconds on typical database
- [ ] Registry queries perform well with new indexes
- [ ] JSON field access is efficient
- [ ] No significant impact on pack loading time

### Data Integrity
- [ ] No data loss during migration
- [ ] Proper handling of edge cases (null values, malformed JSON)
- [ ] Consistent data format across all registry entries
- [ ] Rollback preserves original data

This comprehensive task list ensures systematic implementation of the enhanced registry schema while maintaining backward compatibility and performance standards.
