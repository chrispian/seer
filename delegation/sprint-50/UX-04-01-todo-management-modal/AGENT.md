# UX-04-01 Todo Management Modal Agent Profile

## Mission
Create a comprehensive todo management modal using existing modal patterns, featuring search, filters, datatable display, state management, and advanced interactions like drag-drop sorting and context menu actions.

## Workflow
- Research existing modal patterns (CommandResultModal) and todo system architecture
- Install Shadcn Table component and implement datatable structure
- Create TodoManagementModal component with search and filter functionality
- Implement todo state cycling, drag-drop reordering, and context menu actions
- Add keyboard shortcuts and accessibility features
- Integrate with existing todo commands and backend APIs
- Use ReactMarkdown for consistent content display styling

## Quality Standards
- Modal follows existing CommandResultModal patterns for consistency
- Uses Shadcn components (Dialog, Table, Search, Select, DropdownMenu) throughout
- Implements proper React patterns with TypeScript types
- Maintains accessibility standards with keyboard navigation
- Integrates seamlessly with existing todo backend (TodoCommand, Fragment model)
- Performance optimized with proper memoization and virtual scrolling if needed
- Responsive design works across all breakpoints

## Deliverables
- TodoManagementModal component with full feature set
- Todo datatable with search, filters, sorting, and pagination
- Drag-drop functionality for custom todo ordering
- Context menu with Edit, Delete, Move, Reminder, Pin actions
- State cycling (done/not done) with date tracking
- Integration with existing /todo command system
- Keyboard shortcuts and accessibility support

## Key Features to Implement
- **Search Box**: Real-time filtering as user types
- **Filters**: Status (open/completed), Project, Tags, Date Range dropdown filters
- **Sort Options**: New/Old, Status, Priority toggles
- **Datatable**: Compact table with checkbox, title, metadata columns
- **State Cycling**: Click todo to toggle done/not done with completed_at tracking
- **Drag & Drop**: Custom sort order persistence
- **Context Menu**: Pin, Edit, Delete, Move, Reminder actions via ... menu
- **Responsive Design**: Works on mobile, tablet, desktop

## Technical Integration Points
- Uses existing Fragment model and Todo relationship
- Integrates with TodoCommand for backend operations
- Follows CommandResultModal styling and behavior patterns
- Uses existing API endpoints: `/api/commands/execute` for todo operations
- Implements proper error handling and loading states
- Uses ReactMarkdown for content display consistency

## Safety Notes
- Preserve existing todo functionality and data structure
- Ensure modal can be safely closed without data loss
- Implement proper error boundaries and fallback states
- Test with existing todo data and edge cases
- Maintain backwards compatibility with existing /todo commands

## Communication
- Report modal creation and integration progress
- Document any new API endpoints or modifications needed
- Provide testing results with various todo scenarios
- Confirm accessibility and responsive design compliance
- Deliver component ready for integration with chat command system