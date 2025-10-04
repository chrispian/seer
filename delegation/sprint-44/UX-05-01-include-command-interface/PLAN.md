# UX-05-01 Implementation Plan

## Phase 1: SlashCommand Extension (3-4 hours)
1. Extend SlashCommand with /include entries
2. Add /include and /inc alias support
3. Create IncludeCommand extension class
4. Implement basic command detection and parsing

## Phase 2: Target Selection Interface (4-5 hours)
5. Create TargetPicker component for UID/search selection
6. Implement search-based fragment selection
7. Add UID input with validation
8. Create fragment preview and selection UI

## Phase 3: Configuration Interface (3-4 hours)
9. Build ModeSelector for ref/copy selection
10. Create LayoutSelector for checklist/table/cards
11. Add context override inputs (@ws:, @proj:)
12. Implement configuration validation and preview

## Phase 4: Integration & Polish (2-3 hours)
13. Integrate with TipTap node insertion system
14. Add autocomplete and help system updates
15. Implement error handling and validation feedback
16. Add accessibility features and keyboard navigation

## Dependencies
- Requires ENG-06-01 completion (backend foundation)
- Depends on existing TipTap SlashCommand patterns
- Needs Fragment search API functionality

## Success Criteria
- /include command appears in slash menu with autocomplete
- Target picker allows both UID and search-based selection
- Mode and layout configuration works correctly
- Command generates proper TransclusionSpec for insertion
- Integration with existing TipTap system is seamless

## Total Estimated Time: 12-16 hours