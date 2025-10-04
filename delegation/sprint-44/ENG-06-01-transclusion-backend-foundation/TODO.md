# ENG-06-01 Task Checklist

## Phase 1: Core Models & Schema ⏳
- [ ] Create `app/Models/TransclusionSpec.php` model
  - [ ] Add proper fillable fields and casts
  - [ ] Implement belongsTo relationship to Fragment
  - [ ] Add JSON validation for context and options fields
  - [ ] Create proper enum casts for kind and mode
- [ ] Add `transclusions()` relationship to Fragment model
  - [ ] Add hasMany TransclusionSpec relationship
  - [ ] Add transclusion-related scopes and methods
- [ ] Create migration `create_transclusion_specs_table`
  - [ ] Add all required fields with proper types
  - [ ] Set up foreign key constraints
  - [ ] Add indexes for performance
- [ ] Add JSON validation schema to `resources/schemas/transclusion_spec.schema.json`
- [ ] Create `TransclusionSpecFactory` for testing
- [ ] Add model tests for TransclusionSpec relationships

## Phase 2: UID Resolution System ⏳
- [ ] Create `app/Services/UIDResolverService.php`
  - [ ] Implement UID parsing for `fe:type/id` format
  - [ ] Add validation for UID format compliance
  - [ ] Create UID generation utilities
- [ ] Add target fragment lookup with error handling
  - [ ] Implement resolveUID method with Fragment lookup
  - [ ] Add proper error handling for missing fragments
  - [ ] Support type-specific resolution logic
- [ ] Implement context override resolution (@ws:, @proj:)
  - [ ] Parse context overrides from commands
  - [ ] Resolve workspace and project contexts
  - [ ] Apply context filtering to lookups
- [ ] Add UID validation and formatting utilities
  - [ ] Create isValidUID method
  - [ ] Add formatUID helper method
  - [ ] Implement UID collision detection
- [ ] Create comprehensive tests for UID resolution
  - [ ] Test valid and invalid UID formats
  - [ ] Test resolution with different fragment types
  - [ ] Test context override functionality

## Phase 3: Transclusion Service Layer ⏳
- [ ] Create `app/Services/TransclusionService.php`
  - [ ] Set up service class with proper injection
  - [ ] Add UIDResolverService dependency
- [ ] Implement `createSpec()` method with validation
  - [ ] Validate TransclusionSpec data
  - [ ] Create spec with proper relationships
  - [ ] Handle duplicate detection
- [ ] Add `updateSpec()` and `deleteSpec()` methods
  - [ ] Implement safe spec updates
  - [ ] Add proper cleanup for deleted specs
  - [ ] Handle cascade deletion scenarios
- [ ] Implement relationship tracking and cleanup
  - [ ] Track fragment_links for transclusions
  - [ ] Clean up orphaned relationships
  - [ ] Update link metadata
- [ ] Add conflict detection for circular references
  - [ ] Detect circular transclusion chains
  - [ ] Prevent infinite recursion
  - [ ] Provide helpful error messages
- [ ] Create service tests covering all edge cases
  - [ ] Test spec creation and validation
  - [ ] Test circular reference detection
  - [ ] Test cleanup and relationship management

## Phase 4: Command Integration ⏳
- [ ] Create `app/Actions/Commands/IncludeCommand.php`
  - [ ] Implement HandlesCommand interface
  - [ ] Follow established command patterns
- [ ] Implement command argument parsing
  - [ ] Parse uid, search, query arguments
  - [ ] Handle mode and layout options
  - [ ] Parse context overrides (@ws:, @proj:)
- [ ] Add command validation
  - [ ] Validate required arguments
  - [ ] Check argument combinations
  - [ ] Provide helpful error messages
- [ ] Register command in CommandRegistry
  - [ ] Add /include and /inc aliases
  - [ ] Update command help system
- [ ] Add command response formatting
  - [ ] Return proper CommandResponse
  - [ ] Include success/error messaging
  - [ ] Add relevant fragment data
- [ ] Create feature tests for IncludeCommand
  - [ ] Test basic UID inclusion
  - [ ] Test search-based inclusion
  - [ ] Test query-based list inclusion
  - [ ] Test error handling scenarios

## Quality Assurance ⏳
- [ ] Run `composer test` and ensure all tests pass
- [ ] Validate PSR-12 compliance with `./vendor/bin/pint`
- [ ] Check relationships work correctly in Tinker
  - [ ] Test TransclusionSpec creation
  - [ ] Verify Fragment relationships
  - [ ] Test UID resolution
- [ ] Verify migration runs cleanly on fresh database
- [ ] Test UID resolution with various formats
  - [ ] Test valid fe:type/id formats
  - [ ] Test invalid formats return proper errors
- [ ] Validate JSON schema enforcement
  - [ ] Test TransclusionSpec validation
  - [ ] Verify schema compliance
- [ ] Integration testing with existing systems
  - [ ] Test with Fragment model
  - [ ] Verify command registration
  - [ ] Check context resolution

## Documentation ⏳
- [ ] Update help system with /include command
- [ ] Add API documentation for new services
- [ ] Document UID format specification
- [ ] Create migration rollback procedures