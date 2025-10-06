# UX-05-01 Task Checklist

## Phase 1: SlashCommand Extension ⏳
- [ ] Extend SlashCommand with /include entries
  - [ ] Add /include entry to command list
  - [ ] Add /inc alias support
  - [ ] Update command descriptions and help text
- [ ] Create IncludeCommand extension class
  - [ ] Create new extension file following TipTap patterns
  - [ ] Implement proper extension configuration
  - [ ] Add command detection logic
- [ ] Implement basic command detection and parsing
  - [ ] Parse /include command arguments
  - [ ] Handle basic argument validation
  - [ ] Add error handling for malformed commands
- [ ] Update autocomplete system
  - [ ] Extend fetchCommands with /include support
  - [ ] Add command completion suggestions
  - [ ] Update AutocompleteResult types

## Phase 2: Target Selection Interface ⏳
- [ ] Create TargetPicker component
  - [ ] Design component interface and props
  - [ ] Implement search and UID input modes
  - [ ] Add proper TypeScript types
- [ ] Implement search-based fragment selection
  - [ ] Integrate with Fragment search API
  - [ ] Add debounced search functionality
  - [ ] Create search results display
  - [ ] Add fragment type filtering
- [ ] Add UID input with validation
  - [ ] Create UID input field with fe:type/id format
  - [ ] Add real-time validation feedback
  - [ ] Implement UID format autocomplete
- [ ] Create fragment preview and selection UI
  - [ ] Display fragment title and metadata
  - [ ] Show fragment type and status
  - [ ] Add selection confirmation interface
  - [ ] Implement proper loading states

## Phase 3: Configuration Interface ⏳
- [ ] Build ModeSelector component
  - [ ] Create ref/copy mode selection
  - [ ] Add mode descriptions and help text
  - [ ] Implement proper state management
- [ ] Create LayoutSelector for display options
  - [ ] Add checklist/table/cards options
  - [ ] Implement layout preview functionality
  - [ ] Add conditional options based on selection
- [ ] Add context override inputs
  - [ ] Create @ws: workspace override input
  - [ ] Add @proj: project override input
  - [ ] Implement context validation
- [ ] Implement configuration validation and preview
  - [ ] Validate argument combinations
  - [ ] Show configuration preview
  - [ ] Add helpful error messages
  - [ ] Implement real-time validation feedback

## Phase 4: Integration & Polish ⏳
- [ ] Integrate with TipTap node insertion system
  - [ ] Connect to TransclusionNode creation
  - [ ] Handle spec generation and insertion
  - [ ] Add proper cursor positioning
- [ ] Add autocomplete and help system updates
  - [ ] Update command help documentation
  - [ ] Add example usage in help system
  - [ ] Integrate with existing command palette
- [ ] Implement error handling and validation feedback
  - [ ] Add comprehensive error messages
  - [ ] Implement proper error boundaries
  - [ ] Add validation feedback UI
- [ ] Add accessibility features and keyboard navigation
  - [ ] Implement proper ARIA labels
  - [ ] Add keyboard shortcuts and navigation
  - [ ] Ensure screen reader compatibility
  - [ ] Add focus management

## Component Development ⏳
- [ ] Create shared types and interfaces
  - [ ] Define IncludeCommandArgs interface
  - [ ] Add TransclusionSpec TypeScript types
  - [ ] Create validation helper types
- [ ] Implement helper utilities
  - [ ] Create UID validation helpers
  - [ ] Add command parsing utilities
  - [ ] Implement configuration helpers
- [ ] Add comprehensive testing
  - [ ] Unit tests for components
  - [ ] Integration tests for command flow
  - [ ] E2E tests for user interactions

## Quality Assurance ⏳
- [ ] Test with existing TipTap system
  - [ ] Verify SlashCommand integration
  - [ ] Test command detection and parsing
  - [ ] Validate node insertion
- [ ] Cross-browser testing
  - [ ] Test in Chrome, Firefox, Safari
  - [ ] Verify mobile responsiveness
  - [ ] Check accessibility compliance
- [ ] Performance testing
  - [ ] Test search debouncing
  - [ ] Verify autocomplete performance
  - [ ] Check memory leaks
- [ ] User experience testing
  - [ ] Test command discoverability
  - [ ] Verify intuitive workflows
  - [ ] Check error message clarity

## Integration Testing ⏳
- [ ] Test with backend TransclusionSpec creation
- [ ] Verify Fragment search integration
- [ ] Test context resolution functionality
- [ ] Validate command execution flow
- [ ] Check error handling scenarios