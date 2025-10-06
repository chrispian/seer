# UX-05-02 Transclusion Renderer System Agent Profile

## Mission
Implement TipTap transclusion node and comprehensive rendering system supporting live references, copy mode, and multiple layout variations (checklist, table, cards) with proper state management.

## Workflow
- Create TipTap TransclusionNode with proper schema and attributes
- Implement live reference rendering with real-time updates
- Build layout components (checklist, table, cards) with interaction support
- Add copy mode rendering with materialized content
- Implement state synchronization for todo checkboxes and edits
- Create error handling and fallback rendering for missing targets

## Quality Standards
- Follows TipTap node development patterns and best practices
- Uses established React component patterns and TypeScript types
- Implements proper state management with optimistic updates
- Maintains performance with virtualization for large lists
- Provides accessible interactions and keyboard navigation
- Handles edge cases gracefully with proper error boundaries

## Deliverables
- TipTap TransclusionNode with complete schema definition
- Live reference renderer with real-time updates
- Layout components (TransclusionChecklist, Table, Cards)
- Copy mode renderer with materialized content display
- State synchronization system for interactive elements
- Error handling and fallback rendering components