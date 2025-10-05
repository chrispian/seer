# Widget Containers Context

## Current Widget System
- RightRail component in `resources/js/islands/shell/RightRail.tsx`
- Fixed-width container (w-80) with manual spacing
- Four main widgets: TodayActivityWidget, RecentBookmarksWidget, ToolCallsWidget, SessionInfoWidget
- Centralized exports in `resources/js/widgets/index.ts`
- Individual widget folders with components, hooks, and types

## Current Layout Structure
```
RightRail (w-80)
├── ScrollArea (flex-1)
│   ├── TodayActivityWidget
│   ├── RecentBookmarksWidget
│   └── ToolCallsWidget
└── SessionInfoWidget (pinned to bottom)
```

## Widget Architecture
- Each widget is self-contained with its own data management
- Uses React Query for data fetching and caching
- Shadcn Card components for consistent styling
- Individual loading, error, and empty states
- TypeScript with proper type definitions

## Target Enhancements
- Grid-based responsive container system
- Improved spacing and alignment consistency
- Better mobile widget experience
- Container queries for component-level responsiveness
- Flexible widget positioning and arrangement
- Enhanced visual hierarchy

## Technical Requirements
- Preserve all widget functionality and data flows
- Maintain widget exports and type definitions
- Keep existing React Query integration
- Ensure widget performance is maintained
- Support responsive behavior across breakpoints
- Maintain widget loading and error states

## Widget Dependencies
- React Query for data management
- Shadcn Card, Badge, and other UI components
- Custom hooks for data fetching
- Zustand store integration where applicable
- Existing API endpoints and data structures

## Mobile Considerations
- Current fixed width doesn't work well on mobile
- Widgets need better touch interactions
- Scrolling behavior should be optimized
- Widget content should adapt to smaller screens
- Consider widget stacking and priority on mobile

## Success Metrics
- All widgets function identically after refactor
- Responsive behavior across all breakpoints
- Improved mobile widget experience
- Consistent spacing and visual hierarchy
- Better performance on smaller devices