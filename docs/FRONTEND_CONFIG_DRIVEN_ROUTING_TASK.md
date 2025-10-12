# Frontend Task: Config-Driven Component Routing

**Task Code:** T-FE-UNIFY-01  
**Status:** Todo  
**Priority:** Medium  
**Estimated Time:** 2-3 hours  
**Dependencies:** Backend Type + Command Unification (✅ Complete)

---

## Overview

Refactor `CommandResultModal.tsx` to use backend-provided `config` object for component routing instead of hardcoded switch statement. This completes the Type + Command Unification project by making the frontend fully config-driven.

---

## Context

The backend unification project is complete. All slash commands (`/sprints`, `/tasks`, `/bookmarks`, etc.) now return a `config` object containing:
- Type metadata (slug, display name, storage type, default components)
- UI configuration (modal container, layout mode, card/detail components)
- Command metadata (name, description, category)

The frontend interface has been updated to accept this config, but the component routing logic still uses a hardcoded switch statement. This task updates the logic to be config-driven.

---

## Backend Response Structure

Every command now returns:

```typescript
{
  "success": true,
  "type": "sprint",
  "data": { "items": [...] },
  "config": {
    "type": {
      "slug": "sprint",
      "display_name": "Sprint",
      "plural_name": "Sprints",
      "storage_type": "model",  // or "fragment"
      "default_card_component": "SprintCard",
      "default_detail_component": "SprintDetailModal"
    },
    "ui": {
      "modal_container": "DataManagementModal",
      "layout_mode": "table",  // or "grid", "list"
      "card_component": "SprintCard",
      "detail_component": "SprintDetailModal",
      "filters": {...},
      "default_sort": {"field": "created_at", "direction": "desc"},
      "pagination_default": 50
    },
    "command": {
      "command": "/sprints",
      "name": "Sprint List",
      "description": "List sprints with progress stats",
      "category": "Orchestration"
    }
  }
}
```

**Config Priority:**
1. Command UI config (`config.ui.modal_container`)
2. Type defaults (`config.type.default_card_component`)
3. System fallback (`UnifiedListModal`)

---

## Current State

**File:** `resources/js/islands/chat/CommandResultModal.tsx`

**What's Done:**
- ✅ TypeScript interface updated to accept `config` object (lines 32-67)
- ✅ Backend sends config with every command

**What Needs Work:**
- ❌ Lines 117-394: Hardcoded `switch (currentResult.component)` statement
- ❌ Doesn't read from `config.ui.modal_container`
- ❌ Doesn't leverage config priority system
- ❌ Child components don't receive config

---

## Implementation Steps

### 1. Create Component Map (15 min)

Add constant at top of file (after imports):

```typescript
const COMPONENT_MAP: Record<string, React.ComponentType<any>> = {
  // Modal Containers
  'DataManagementModal': SprintListModal,
  
  // List Components
  'SprintListModal': SprintListModal,
  'TaskListModal': TaskListModal,
  'AgentProfileGridModal': AgentProfileGridModal,
  'BacklogListModal': BacklogListModal,
  'ProjectListModal': ProjectListModal,
  'VaultListModal': VaultListModal,
  'BookmarkListModal': BookmarkListModal,
  'UnifiedListModal': UnifiedListModal,
  'FragmentListModal': FragmentListModal,
  
  // Detail Components
  'SprintDetailModal': SprintDetailModal,
  'TaskDetailModal': TaskDetailModal,
  
  // Other
  'TodoManagementModal': TodoManagementModal,
  'TypeManagementModal': TypeManagementModal,
  'ChannelListModal': ChannelListModal,
  'RoutingInfoModal': RoutingInfoModal,
  'AgentProfileDashboard': AgentProfileDashboard,
  'AgentDashboard': AgentDashboard,
};
```

### 2. Create Helper Functions (30 min)

Replace `renderOrchestrationUI()` with helper-based approach:

```typescript
// Determine component name from result
const getComponentName = (result: CommandResult): string => {
  // Priority 1: Explicit component (legacy/override)
  if (result.component) return result.component;
  
  // Priority 2: UI modal container (preferred)
  if (result.config?.ui?.modal_container) {
    return result.config.ui.modal_container;
  }
  
  // Priority 3: UI card component
  if (result.config?.ui?.card_component) {
    // Convert card to modal (e.g., "SprintCard" -> "SprintListModal")
    return result.config.ui.card_component.replace('Card', 'ListModal');
  }
  
  // Priority 4: Type default
  if (result.config?.type?.default_card_component) {
    return result.config.type.default_card_component.replace('Card', 'ListModal');
  }
  
  // Fallback
  return 'UnifiedListModal';
};

// Get component from map
const getComponent = (componentName: string) => {
  const Component = COMPONENT_MAP[componentName];
  
  if (!Component) {
    console.warn(`Component not found: ${componentName}, falling back to UnifiedListModal`);
    return COMPONENT_MAP['UnifiedListModal'];
  }
  
  return Component;
};

// Determine data prop name for component
const getDataPropName = (componentName: string): string => {
  if (componentName.includes('Sprint')) return 'sprints';
  if (componentName.includes('Task')) return 'tasks';
  if (componentName.includes('Agent')) return 'agents';
  if (componentName.includes('Project')) return 'projects';
  if (componentName.includes('Vault')) return 'vaults';
  if (componentName.includes('Bookmark')) return 'bookmarks';
  if (componentName.includes('Fragment')) return 'fragments';
  if (componentName.includes('Channel')) return 'channels';
  
  return 'data'; // Generic fallback
};

// Build props for component
const getComponentProps = (result: CommandResult, componentName: string) => {
  const dataKey = getDataPropName(componentName);
  
  return {
    isOpen: true,
    onClose,
    [dataKey]: result.data,
    config: result.config,
    onRefresh: () => console.log('Refresh requested'),
    // Add detail-specific handlers if needed
    onSprintSelect: componentName.includes('Sprint') ? executeDetailCommand : undefined,
    onTaskSelect: componentName.includes('Task') ? executeDetailCommand : undefined,
  };
};
```

### 3. Replace Switch Statement (45 min)

Completely replace `renderOrchestrationUI()`:

```typescript
const renderOrchestrationUI = (currentResult: CommandResult = result, isDetail = false) => {
  // Check if we have component info
  if (!currentResult.component && !currentResult.config) {
    console.log('No component or config found, skipping UI');
    return null;
  }
  
  // Determine component
  const componentName = getComponentName(currentResult);
  const Component = getComponent(componentName);
  const props = getComponentProps(currentResult, componentName);
  
  console.log('Rendering:', {
    componentName,
    hasConfig: !!currentResult.config,
    isDetail,
    dataKeys: Object.keys(currentResult.data || {})
  });
  
  // Dashboard containers need full-screen dialog wrapper
  if (componentName.includes('Dashboard')) {
    return (
      <Dialog open={isOpen} onOpenChange={onClose}>
        <DialogContent className="max-w-[95vw] h-[90vh] p-0">
          <Component {...props} />
        </DialogContent>
      </Dialog>
    );
  }
  
  // Regular modal components
  return <Component {...props} />;
};
```

### 4. Update Detail Command Handling (30 min)

Ensure detail views use config too:

```typescript
const executeDetailCommand = async (detailCommand: string) => {
  console.log('Executing detail command:', detailCommand);
  setIsLoadingDetail(true);
  
  try {
    const csrf = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
    const response = await fetch('/api/commands/execute', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrf
      },
      body: JSON.stringify({ command: detailCommand })
    });

    const detailResult = await response.json();
    
    if (detailResult.success) {
      console.log('Detail result:', {
        hasConfig: !!detailResult.config,
        component: detailResult.component,
        type: detailResult.type
      });
      
      setDetailView(detailResult);
    } else {
      console.error('Detail command failed:', detailResult);
      alert(detailResult.error || 'Failed to load details');
    }
  } catch (error) {
    console.error('Detail command execution failed:', error);
    alert('Failed to load details');
  } finally {
    setIsLoadingDetail(false);
  }
};
```

### 5. Update Child Components (30 min - Optional)

Pass config to components that can use it:

**Example: UnifiedListModal.tsx**
```typescript
interface UnifiedListModalProps {
  isOpen: boolean;
  onClose: () => void;
  data: any;
  config?: {
    type?: { slug?: string; display_name?: string };
    ui?: { layout_mode?: string; card_component?: string };
  };
  onRefresh?: () => void;
}

export function UnifiedListModal({ 
  isOpen, 
  onClose, 
  data, 
  config,
  onRefresh 
}: UnifiedListModalProps) {
  // Use config for decisions
  const layoutMode = config?.ui?.layout_mode || 'table';
  const typeDisplay = config?.type?.display_name || 'Items';
  
  return (
    <Dialog open={isOpen} onOpenChange={onClose}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{typeDisplay}</DialogTitle>
        </DialogHeader>
        
        {/* Render based on layoutMode */}
        {layoutMode === 'table' && <TableView data={data} />}
        {layoutMode === 'grid' && <GridView data={data} />}
        {layoutMode === 'list' && <ListView data={data} />}
      </DialogContent>
    </Dialog>
  );
}
```

**Priority components to update:**
1. `UnifiedListModal` (handles all types)
2. `SprintListModal` (heavily used)
3. `TaskListModal` (heavily used)

## Testing Checklist

### Smoke Tests
```bash
# In browser console, test each command:
/sprints
/tasks
/bookmarks
/projects
/vaults
/agents
```

For each command, verify:
- [ ] Correct modal opens
- [ ] Data displays correctly
- [ ] Console shows: `Rendering: { componentName: "...", hasConfig: true }`
- [ ] No errors in console

### Detail View Tests
- [ ] Click sprint in `/sprints` → opens SprintDetailModal
- [ ] Click task in `/tasks` → opens TaskDetailModal
- [ ] Back button works
- [ ] Config present in detail view

### Fallback Tests
- [ ] Unknown component → UnifiedListModal + warning
- [ ] No config → uses `component` field (legacy)
- [ ] Missing modal container → tries card component
- [ ] All fallbacks fail gracefully

### Browser DevTools
```javascript
// Check response structure
fetch('/api/commands/execute', {
  method: 'POST',
  headers: { 
    'Content-Type': 'application/json',
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
  },
  body: JSON.stringify({ command: '/sprints' })
})
.then(r => r.json())
.then(result => {
  console.log('Config present:', !!result.config);
  console.log('UI config:', result.config?.ui);
  console.log('Type config:', result.config?.type);
});
```

## Success Criteria

✅ No hardcoded switch statement  
✅ Component name determined from config  
✅ All commands work (no regressions)  
✅ Config passed to child components (at least UnifiedListModal)  
✅ Helpful console logs for debugging  
✅ Fallbacks work when config missing  
✅ Detail views render correctly  
✅ No TypeScript errors

## Time Estimate

| Task | Time |
|------|------|
| Component map | 15 min |
| Helper functions | 30 min |
| Replace switch statement | 45 min |
| Detail view handling | 30 min |
| Update child components | 30 min |
| Testing & debugging | 30 min |
| **Total** | **~3 hours** |

## Files to Modify

**Required:**
- `resources/js/islands/chat/CommandResultModal.tsx` (main refactor)

**Optional (for config support):**
- `resources/js/components/unified/UnifiedListModal.tsx`
- `resources/js/components/orchestration/SprintListModal.tsx`
- `resources/js/components/orchestration/TaskListModal.tsx`
- Other list modals as needed

## Rollback Plan

**Low Risk:**
- Backend sends both `component` and `config`
- Frontend tries config first, falls back to component
- Can roll back frontend changes without backend changes
- Existing `component` field still works

**If Issues:**
1. Revert `CommandResultModal.tsx` changes
2. Old switch statement still present in git history
3. Backend continues working with legacy `component` field
4. No data loss or breaking changes

## Reference Documentation

**Backend Complete:**
- `docs/TYPE_COMMAND_UNIFICATION_COMPLETE.md` (678 lines)
- Backend task summaries: T-UNIFY-01 through T-UNIFY-20
- Sprint summaries: SPRINT-UNIFY-1 through SPRINT-UNIFY-5

**Database Seeders:**
- `database/seeders/TypesSeeder.php` (8 types)
- `database/seeders/CommandsSeeder.php` (12 commands)

**Example Config Response:**
See "Backend Response Structure" section above for full example.

---

## Questions?

**Q: Do we need to update all modals at once?**  
A: No. Start with CommandResultModal routing, then incrementally add config to child components.

**Q: What about the old `component` field?**  
A: Keep as fallback until complete? Priority: component > config ensures backward compatibility. Discuss with user.

**Q: How to test config locally?**  
A: Commands already return config. Check Network tab in DevTools or console.log the response.

**Q: What if a component isn't in the map?**  
A: Falls back to UnifiedListModal + console warning but let's consider what should happen here. 

---

## Ready to Start! 🚀

Backend is complete and tested. All commands return proper config. Frontend just needs to read and use it. 

**Happy coding!**
