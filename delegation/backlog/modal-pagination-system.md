# Modal Pagination System

**Status:** Backlog  
**Priority:** Medium  
**Estimated Effort:** 2-3 hours  
**Created:** 2025-10-06  
**Tags:** #ux #modals #pagination #tech-debt

## Problem

All modals that display list data (search results, tasks, sprints, etc.) have hardcoded `limit(50)` queries and display only 50 items with no pagination, sorting controls, or ability to load more records. This creates a poor user experience when dealing with large datasets.

**Affected Commands (12 total):**
- `SearchCommand` - Fragments (showing 50 periods)
- `SessionListCommand` - Sessions
- `VaultListCommand` - Vaults
- `InboxCommand` - Inbox items
- `ProjectListCommand` - Projects
- `TodoCommand` - Todos
- `AgentListCommand` - Agents
- `BacklogListCommand` - Backlog tasks
- `TaskListCommand` - Tasks
- `RecallCommand` - Recall results
- `BookmarkListCommand` - Bookmarks
- `AgentProfileListCommand` - Agent profiles

## Current Behavior

1. Commands query database with hardcoded `->limit(50)`
2. Modal renders first 50 items only
3. No pagination controls
4. No "load more" option
5. No server-side sorting (only client-side filtering)
6. User cannot access items beyond the first 50

## Desired Behavior

### Backend (Command Layer)

1. **Accept pagination parameters:**
   ```php
   $page = $request->get('page', 1);
   $perPage = $request->get('per_page', 50);
   $sortBy = $request->get('sort_by', 'created_at');
   $sortDir = $request->get('sort_dir', 'desc');
   ```

2. **Return pagination metadata:**
   ```php
   return [
       'data' => $items,
       'pagination' => [
           'current_page' => $page,
           'per_page' => $perPage,
           'total' => $total,
           'last_page' => $lastPage,
           'has_more' => $hasMore
       ],
       'sorting' => [
           'field' => $sortBy,
           'direction' => $sortDir,
           'available_fields' => ['created_at', 'updated_at', 'title', ...]
       ]
   ];
   ```

3. **Support configurable limits:**
   - Default: 50
   - Options: 25, 50, 100, 200
   - Max: 500 (prevent performance issues)

### Frontend (Modal Components)

1. **Add pagination controls:**
   - Previous/Next buttons
   - Page number indicator
   - Jump to page input
   - "Load More" button (infinite scroll alternative)

2. **Add sort controls:**
   - Dropdown for sort field selection
   - Direction toggle (asc/desc)
   - Remember user preferences per modal type

3. **Add per-page selector:**
   - Dropdown: 25, 50, 100, 200
   - Persist preference in localStorage

4. **Loading states:**
   - Show spinner when fetching new page
   - Disable controls during load
   - Optimistic UI updates

### Type System Integration

Create TypeScript types for standardized modal responses:

```typescript
interface PaginatedResponse<T> {
  data: T[]
  pagination: {
    current_page: number
    per_page: number
    total: number
    last_page: number
    has_more: boolean
  }
  sorting: {
    field: string
    direction: 'asc' | 'desc'
    available_fields: string[]
  }
  filters?: FilterDefinition[]
}

interface ModalConfig {
  defaultSort: string
  defaultSortDirection: 'asc' | 'desc'
  defaultPerPage: number
  availableSortFields: Array<{
    value: string
    label: string
  }>
  searchable: boolean
  filterable: boolean
}
```

## Implementation Plan

### Phase 1: Backend Foundation (1 hour)
1. Create `PaginationTrait` for Commands
2. Update `BaseCommand` to support pagination params
3. Add pagination helper methods
4. Create migration for user preferences table (optional)

### Phase 2: Update DataManagementModal (1 hour)
1. Add pagination state management
2. Implement pagination controls component
3. Add server-side fetch on page change
4. Add loading states
5. Update existing modals to use new API

### Phase 3: Command Updates (30 min)
1. Update all 12 commands to use pagination trait
2. Define sensible sort defaults per command:
   - Search: relevance, created_at
   - Tasks: status, created_at, updated_at
   - Bookmarks: created_at, title
   - etc.

### Phase 4: Polish (30 min)
1. Add keyboard shortcuts (Page Up/Down, Home/End)
2. Remember pagination state when closing/reopening modal
3. Add accessibility labels
4. Test with large datasets (1000+ items)

## Technical Notes

- Use Laravel's `paginate()` instead of `limit()`
- Consider using cursor pagination for better performance on large datasets
- Cache pagination results for 30s to reduce DB load
- Add query indexes if needed for sort performance

## Success Criteria

- [ ] All 12 commands support pagination
- [ ] Users can access all records, not just first 50
- [ ] Pagination controls are intuitive and accessible
- [ ] Performance remains acceptable with 10,000+ records
- [ ] Sort options make sense for each data type
- [ ] State is preserved when modal is reopened
- [ ] Mobile-friendly pagination controls

## Related Tasks

- Fix SearchCommand query extraction (blocks search pagination)
- Add infinite scroll option (alternative to pagination)
- Implement virtual scrolling for huge lists (future optimization)

## Notes

The current `DataManagementModal.tsx` already has client-side filtering and search built-in, which is good. We just need to add server-side pagination + sorting to complement it.

Consider making pagination optional - some modals with guaranteed small datasets (< 20 items) don't need it.
