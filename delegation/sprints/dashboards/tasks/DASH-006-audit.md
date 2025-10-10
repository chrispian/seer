# DASH-006: Audit Logs Dashboard UI

## Status: Backlog (Depends on DASH-002)

## Priority: High

## Problem
Backend audit logging is complete (TASK-0002), but no UI exists for administrators to view, search, and analyze audit data. This dashboard surfaces security-critical information.

## Objectives
Build React-based dashboard for viewing and analyzing audit logs from both `activity_log` (Spatie) and `command_audit_logs` tables.

## Features

### 1. Activity Log Viewer
- Display recent activities with pagination
- Show: timestamp, user, event type, model, description
- Expandable rows for change details (JSON diff)
- Real-time updates (polling)

### 2. Command Audit Log Viewer
- Display recent command executions
- Highlight destructive commands (visual indicator)
- Show: timestamp, command, user, status, exit code, execution time
- Failed commands prominently shown
- Click to view full error output

### 3. Search & Filtering
- Filter by date range (today, 7 days, 30 days, custom)
- Filter by user
- Filter by event type (created/updated/deleted/destructive_command)
- Filter by model type
- Filter by status (commands: completed/failed/running)
- Destructive commands only toggle
- Full-text search on descriptions

### 4. Statistics Cards
- Total activities (last 24h)
- Destructive commands (last 7 days)
- Failed commands (last 24h)
- Most active user
- Most changed model type

### 5. Detail Views
- Modal/drawer for full activity details
- Before/after diff viewer (JSON)
- Complete command signature with arguments
- Full error output for failed commands
- Export single entry as JSON

## Components Structure

```
resources/js/pages/Dashboard/Audit/
├── index.tsx                # Main audit dashboard page
├── components/
│   ├── ActivityLogTable.tsx    # Activity log data table
│   ├── CommandLogTable.tsx     # Command log data table
│   ├── LogDetailDialog.tsx     # Expandable detail modal
│   ├── LogFilters.tsx          # Shared filter controls
│   ├── StatsCards.tsx          # Summary statistics
│   ├── JsonDiffViewer.tsx      # Before/after comparison
│   └── CommandSyntax.tsx       # Syntax-highlighted command
└── hooks/
    ├── useActivityLogs.ts      # React Query for activities
    ├── useCommandLogs.ts       # React Query for commands
    └── useAuditStats.ts        # React Query for stats
```

## shadcn/ui Components Used
- `Tabs` - Switch between Activities/Commands/Stats
- `Table` - Data tables (with TanStack Table)
- `Card` - Stats cards
- `Badge` - Status/event type indicators
- `Button` - Actions (refresh, export, filter)
- `Dialog` - Detail view modal
- `Select` - Filter dropdowns
- `Input` - Search field
- `Calendar` - Date range picker
- `Separator` - Visual dividers
- `ScrollArea` - Long content
- `Skeleton` - Loading states

## UI Mockup

```
┌──────────────────────────────────────────────────────────────────┐
│  Audit Logs                          [Refresh] [Export] [Filter] │
├──────────────────────────────────────────────────────────────────┤
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐               │
│  │ Activities  │  │ Destructive │  │ Failed Cmds │               │
│  │   1,234     │  │     12      │  │      3      │               │
│  │ ↑ 12% 24h   │  │ ⚠️  7 days   │  │ ❌  24h      │               │
│  └─────────────┘  └─────────────┘  └─────────────┘               │
├──────────────────────────────────────────────────────────────────┤
│  [Activities] [Commands] [Statistics]                            │
├──────────────────────────────────────────────────────────────────┤
│  Filters: [Date Range ▼] [User ▼] [Event ▼] [Search...] [Apply] │
├──────────────────────────────────────────────────────────────────┤
│  Time       User      Event      Model       Description     [▼] │
│  ────────────────────────────────────────────────────────────────│
│  12:26 PM   System    deleted    Fragment    Deleted fragment... │
│  12:25 PM   Admin     updated    User        Updated user na...  │
│  12:20 PM   System    ⚠️ destru.. Command    cache:clear exe...  │
│  12:15 PM   Admin     created    Fragment    Created fragme...   │
│  ...                                                              │
├──────────────────────────────────────────────────────────────────┤
│  Showing 1-50 of 1,234          [< Prev] Page 1 of 25 [Next >]  │
└──────────────────────────────────────────────────────────────────┘

Detail Modal:
┌──────────────────────────────────────────────────────────────────┐
│  Activity Details                                           [✕]  │
├──────────────────────────────────────────────────────────────────┤
│  Event: Updated                                                  │
│  Model: User #1 (chrispian@example.com)                         │
│  Causer: Admin (ID: 1)                                           │
│  Timestamp: 2025-10-09 12:25:00                                  │
│  IP: 127.0.0.1                                                   │
│                                                                  │
│  Changes:                                                        │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │ Old Value               │ New Value                        │ │
│  │ ────────────────────────────────────────────────────────── │ │
│  │ - "Chrispian Burks"     │ + "Test Updated Name"            │ │
│  └────────────────────────────────────────────────────────────┘ │
│                                                                  │
│  [Export JSON] [Copy Link] [View Model]                         │
└──────────────────────────────────────────────────────────────────┘
```

## Implementation Checklist

### Backend (Reference TASK-0003 or use DASH-002 APIs)
- [x] Activity logs API endpoint (from TASK-0002)
- [x] Command logs API endpoint (from TASK-0002)
- [ ] Stats/summary endpoint (add to DASH-002)
- [ ] Export endpoint (add to DASH-002)

### Frontend Components
- [ ] Create page route `/dashboard/audit`
- [ ] Build `AuditDashboard` main page with tabs
- [ ] Implement `StatsCards` with real data
- [ ] Build `ActivityLogTable` with TanStack Table
  - [ ] Sortable columns
  - [ ] Pagination
  - [ ] Row expansion
- [ ] Build `CommandLogTable` with TanStack Table
  - [ ] Highlight destructive commands
  - [ ] Status badges (success/failed)
  - [ ] Execution time display
- [ ] Create `LogFilters` component
  - [ ] Date range picker
  - [ ] User dropdown (load from API)
  - [ ] Event type dropdown
  - [ ] Search input with debounce
- [ ] Build `LogDetailDialog`
  - [ ] Show full activity details
  - [ ] Show full command details
  - [ ] Export JSON button
- [ ] Create `JsonDiffViewer`
  - [ ] Side-by-side or unified diff
  - [ ] Syntax highlighting
  - [ ] Collapse/expand nested objects
- [ ] Create `CommandSyntax` component
  - [ ] Syntax highlight bash commands
  - [ ] Copy to clipboard button

### React Query Hooks
```typescript
// useActivityLogs.ts
export function useActivityLogs(filters: ActivityFilters) {
  return useQuery({
    queryKey: ['activityLogs', filters],
    queryFn: () => fetchActivityLogs(filters),
    refetchInterval: 30000, // 30s polling
  });
}

// useCommandLogs.ts
export function useCommandLogs(filters: CommandFilters) {
  return useQuery({
    queryKey: ['commandLogs', filters],
    queryFn: () => fetchCommandLogs(filters),
    refetchInterval: 30000,
  });
}

// useAuditStats.ts
export function useAuditStats() {
  return useQuery({
    queryKey: ['auditStats'],
    queryFn: fetchAuditStats,
    refetchInterval: 60000, // 1min
  });
}
```

### Styling & UX
- [ ] Destructive commands: red badge + warning icon
- [ ] Failed commands: red text + X icon
- [ ] Success commands: green checkmark
- [ ] Responsive table (stack on mobile)
- [ ] Loading skeletons during fetch
- [ ] Empty states (no data found)
- [ ] Error states (API errors)
- [ ] Smooth transitions on filter changes

### Testing
- [ ] Test filtering combinations
- [ ] Test pagination
- [ ] Test search functionality
- [ ] Test detail view modal
- [ ] Test export functionality
- [ ] Test responsive layout
- [ ] Test accessibility (keyboard nav)
- [ ] Test with 1000+ records (performance)

## Technical Notes

### JSON Diff Viewer
Use existing library or build custom:
- **react-diff-viewer-continued** (feature-rich)
- **diff2html** (convert diff to HTML)
- Custom with `diff` package + highlighting

### Syntax Highlighting
For command display:
- **prism-react-renderer** (lightweight)
- **react-syntax-highlighter** (full-featured)

### Date Range Picker
shadcn doesn't include one - use:
- **react-day-picker** with shadcn Popover
- **date-fns** for date manipulation

### Table Virtualization
For 1000+ rows, use TanStack Table's virtualization:
```typescript
import { useVirtualizer } from '@tanstack/react-virtual';
```

## Acceptance Criteria
- [ ] Activity logs displayed with all fields
- [ ] Command logs displayed with all fields
- [ ] All filters work correctly
- [ ] Search returns relevant results
- [ ] Detail modal shows complete information
- [ ] JSON diff viewer clearly shows changes
- [ ] Destructive commands visually highlighted
- [ ] Failed commands show error output
- [ ] Stats cards show accurate data
- [ ] Export functionality works
- [ ] Page responsive on mobile
- [ ] Loads 1000+ records without lag
- [ ] Accessible (keyboard + screen reader)

## Estimated Effort
- Components: 1 day
- Tables & filtering: 0.5 days
- Detail views & diff: 0.5 days
- Styling & polish: 0.5 days
- Testing: 0.5 days

**Total: 2-3 days**

## Dependencies
- DASH-002 complete (APIs available)
- TASK-0002 complete (audit logging backend) ✅
- React Router configured
- shadcn/ui installed
- TanStack Query configured
- TanStack Table installed

## Related Tasks
- TASK-0003: Original audit UI spec (more detailed)
- DASH-001: Architecture planning
- DASH-002: Backend APIs
- DASH-007: Integration & navigation

## References
- Audit logging docs: `docs/AUDIT_LOGGING.md`
- Backend task: `delegation/backlog/database-audit-logs/TASK-0002.md`
- Spatie Activity Log: https://spatie.be/docs/laravel-activitylog
