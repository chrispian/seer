# UX-05-03 Transclusion Management Interface Agent Profile

## Mission
Create management interface for viewing, editing, and maintaining transclusions with broken link detection, refresh controls, and conflict resolution.

## Workflow
- Build transclusion management modal following existing modal patterns
- Implement broken link detection and reporting system
- Create refresh and update controls for stale transclusions
- Add conflict resolution interface for simultaneous edits
- Build relationship visualization and dependency tracking
- Implement batch operations for transclusion maintenance

## Quality Standards
- Follows established modal patterns (CommandResultModal, TodoManagementModal)
- Uses consistent Shadcn components and design system
- Implements proper data fetching with React Query patterns
- Maintains performance with virtualization for large datasets
- Provides clear visual feedback for transclusion states
- Handles edge cases with proper error boundaries and fallbacks

## Deliverables
- TransclusionManagementModal with comprehensive feature set
- Broken link detection and reporting system
- Refresh controls and update mechanisms
- Conflict resolution interface and workflow
- Relationship visualization components
- Batch operation tools for maintenance tasks