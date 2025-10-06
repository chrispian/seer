# ENG-06-01 Implementation Plan

## Phase 1: Core Models & Schema (2-3 hours)
1. Create TransclusionSpec model with proper relationships
2. Add Fragment model extensions for transclusion relationships  
3. Create database migration for transclusion_specs table
4. Implement JSON schema validation for transclusion specs

## Phase 2: UID Resolution System (2-3 hours)
5. Create UIDResolverService for fe:type/id parsing
6. Implement target lookup with proper error handling
7. Add workspace/project context resolution
8. Create UID validation and formatting utilities

## Phase 3: Transclusion Service Layer (2-3 hours)
9. Implement TransclusionService for spec management
10. Add spec creation, validation, and update methods
11. Implement relationship tracking and cleanup
12. Add conflict detection and resolution logic

## Phase 4: Command Integration (2-3 hours)
13. Create IncludeCommand implementing HandlesCommand
14. Add command parsing and argument validation
15. Integrate with existing command registration system
16. Implement command response formatting

## Dependencies
- Must complete before UX-05-01 (command interface)
- Requires existing Fragment model and command infrastructure
- Foundation for all other transclusion task packs

## Success Criteria
- TransclusionSpec model stores and validates all spec types
- UID resolution works for all fragment types
- IncludeCommand parses arguments and creates specs
- All tests pass and migrations run cleanly

## Total Estimated Time: 8-12 hours