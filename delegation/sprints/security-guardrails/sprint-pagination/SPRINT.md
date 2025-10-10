# Sprint: Modal Pagination System

**Sprint Code**: `SPRINT-PAGINATION`  
**Status**: `in_progress`  
**Duration**: 2-3 hours  
**Priority**: Medium  
**Created**: 2025-10-06

## Overview

Implement server-side pagination for all modal list commands to replace hardcoded `limit(50)` queries. This will enable users to access all records, not just the first 50, and provide better UX through pagination controls, sorting options, and per-page selection.

## Goals

1. **Backend Foundation**: Create reusable pagination trait for Commands
2. **Frontend Enhancement**: Add server-side pagination to DataManagementModal
3. **Command Updates**: Update all 11 affected commands to use pagination
4. **Polish**: Add keyboard shortcuts, state persistence, and accessibility

## Affected Commands (11 total)

Commands currently with `limit(50)`:
- `SessionListCommand` - Chat sessions
- `VaultListCommand` - Vault list
- `InboxCommand` - Inbox items
- `ProjectListCommand` - Projects
- `TodoCommand` - Todo items
- `AgentListCommand` - Agents
- `BacklogListCommand` - Backlog tasks
- `TaskListCommand` - Tasks
- `RecallCommand` - Recall results
- `BookmarkListCommand` - Bookmarks
- `AgentProfileListCommand` - Agent profiles

Note: `SearchCommand` was already updated to `limit(200)` with sort controls.

## Tasks

### Phase 1: Backend Foundation (30-45 min)
- T-PAGINATE-01: Create PaginationTrait for Commands (30m)
- T-PAGINATE-02: Update BaseCommand to support pagination params (15m)

### Phase 2: Frontend Updates (30-45 min)
- T-PAGINATE-03: Add server-side pagination to DataManagementModal (30m)
- T-PAGINATE-04: Add pagination controls component (15m)

### Phase 3: Command Updates (45-60 min)
- T-PAGINATE-05: Update SessionListCommand with pagination (5m)
- T-PAGINATE-06: Update VaultListCommand with pagination (5m)
- T-PAGINATE-07: Update InboxCommand with pagination (5m)
- T-PAGINATE-08: Update ProjectListCommand with pagination (5m)
- T-PAGINATE-09: Update TodoCommand with pagination (5m)
- T-PAGINATE-10: Update AgentListCommand with pagination (5m)
- T-PAGINATE-11: Update BacklogListCommand with pagination (5m)
- T-PAGINATE-12: Update TaskListCommand with pagination (5m)
- T-PAGINATE-13: Update RecallCommand with pagination (5m)
- T-PAGINATE-14: Update BookmarkListCommand with pagination (5m)
- T-PAGINATE-15: Update AgentProfileListCommand with pagination (5m)

### Phase 4: Polish & Testing (15-30 min)
- T-PAGINATE-16: Add keyboard shortcuts (Page Up/Down) (10m)
- T-PAGINATE-17: Test with large datasets (10m)
- T-PAGINATE-18: Update SearchCommand to use pagination system (10m)

## Success Criteria

- [ ] All 11 commands support server-side pagination
- [ ] Users can access all records beyond first 50
- [ ] Pagination controls are intuitive and accessible
- [ ] Performance remains acceptable with 10,000+ records
- [ ] Sort options work correctly for each modal
- [ ] State is preserved when modal is reopened
- [ ] Mobile-friendly pagination controls

## Dependencies

- Existing `DataManagementModal` component
- Existing `CommandController` request handling
- Laravel pagination support

## Technical Notes

**Pagination Parameters:**
```php
$page = $request->get('page', 1);
$perPage = $request->get('per_page', 50);
$sortBy = $request->get('sort_by', 'created_at');
$sortDir = $request->get('sort_dir', 'desc');
```

**Response Format:**
```php
[
    'data' => $items,
    'pagination' => [
        'current_page' => $page,
        'per_page' => $perPage,
        'total' => $total,
        'last_page' => $lastPage,
        'has_more' => $hasMore
    ]
]
```

## Notes

- Keep backward compatibility - commands without pagination params should still work
- Consider adding infinite scroll as alternative to pagination
- Add query indexes if needed for sort performance
- SearchCommand already has client-side sort - may need to align patterns
