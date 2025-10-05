# Shadcn Blocks Integration Context

## Current State
- Well-established shadcn setup with 24+ components
- Widget-based architecture in `resources/js/widgets/`
- AppShell → RightRail → Widgets layout pattern
- Fixed-width RightRail (w-80) with manual spacing
- React/TypeScript frontend with Laravel backend

## Target Benefits
- Pre-built responsive layout patterns from shadcn blocks
- Enhanced widget integration with block containers
- Consistent spacing and breakpoint handling
- Foundation for user customization features
- Improved mobile adaptation
- Professional dashboard-like interface

## Technical Constraints
- Must preserve existing widget exports and functionality
- Cannot break current AppShell/RightRail architecture
- Performance must not degrade
- Tailwind v4 compatibility required
- Maintain existing chat functionality and routing

## Key Architecture Files
- `resources/js/components/AppShell.tsx` - Main layout shell
- `resources/js/components/AppSidebar.tsx` - Left navigation
- `resources/js/islands/shell/RightRail.tsx` - Widget container
- `resources/js/widgets/` - Modular widget system
- `components.json` - Shadcn configuration

## Dependencies
- Existing shadcn components (sidebar, card, etc.)
- Current widget system architecture
- AppShell/RightRail layout structure
- React Query for data management
- Zustand for state management

## Success Metrics
- All widgets responsive across breakpoints
- Sidebar collapse/expand functionality
- Grid-based widget arrangement options
- Consistent spacing throughout application
- Foundation ready for drag-and-drop customization
- Mobile experience significantly improved