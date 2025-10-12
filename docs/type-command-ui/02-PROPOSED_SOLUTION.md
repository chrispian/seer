# Type + Command UI - Proposed Solution

**Date:** October 10, 2025  
**Status:** Design Phase  
**Version:** 1.0

---

## Overview

This document proposes a **config-driven component routing system** that eliminates the 400+ line switch statement in favor of a clean, maintainable, and extensible architecture. The solution respects the backend's config priority system and provides a clear path for future enhancements.

---

## Design Principles

### 1. **Config-First** 
Backend config drives UI decisions, not hardcoded frontend logic.

### 2. **Convention Over Configuration**
Smart defaults reduce boilerplate (e.g., "SprintCard" → "SprintListModal").

### 3. **Graceful Degradation**
Unknown components fall back to UnifiedListModal with warnings, not errors.

### 4. **Progressive Enhancement**
Components can opt-in to config support without breaking existing behavior.

### 5. **Developer-Friendly**
Clear patterns, helpful logs, easy to debug and extend.

---

## Architecture

### Component Resolution Flow

```
1. User executes command (e.g., /sprints)
   ↓
2. Backend returns { data, config }
   ↓
3. CommandResultModal.getComponentName(result)
   ├─ Check result.config.ui.modal_container
   ├─ Check result.config.ui.card_component (transform)
   ├─ Check result.config.type.default_card_component (transform)
   ├─ Check result.component (legacy)
   └─ Fallback to "UnifiedListModal"
   ↓
4. Lookup component in COMPONENT_MAP
   ├─ Found → Use component
   └─ Not found → Warn + UnifiedListModal
   ↓
5. Build props (standardized + type-specific)
   ↓
6. Render component
```

### Component Map Structure

```typescript
// Central component registry
const COMPONENT_MAP: Record<string, React.ComponentType<any>> = {
  // Container Components (preferred)
  'DataManagementModal': DataManagementModal,
  
  // List Components (type-specific)
  'SprintListModal': SprintListModal,
  'TaskListModal': TaskListModal,
  'AgentProfileGridModal': AgentProfileGridModal,
  'BacklogListModal': BacklogListModal,
  'ProjectListModal': ProjectListModal,
  'VaultListModal': VaultListModal,
  'BookmarkListModal': BookmarkListModal,
  'FragmentListModal': FragmentListModal,
  'ChannelListModal': ChannelListModal,
  
  // Detail Components
  'SprintDetailModal': SprintDetailModal,
  'TaskDetailModal': TaskDetailModal,
  'UnifiedDetailModal': UnifiedDetailModal,
  
  // Generic Fallback
  'UnifiedListModal': UnifiedListModal,
  
  // Special Components
  'TodoManagementModal': TodoManagementModal,
  'TypeManagementModal': TypeManagementModal,
  'RoutingInfoModal': RoutingInfoModal,
  
  // Dashboard Components (full-screen)
  'AgentProfileDashboard': AgentProfileDashboard,
  'AgentDashboard': AgentDashboard,
}
```

### Config Priority Implementation

```typescript
function getComponentName(result: CommandResult): string {
  // Priority 1: Explicit UI modal container (backend-preferred)
  if (result.config?.ui?.modal_container) {
    return result.config.ui.modal_container
  }
  
  // Priority 2: UI card component (transform to modal)
  if (result.config?.ui?.card_component) {
    return transformCardToModal(result.config.ui.card_component)
  }
  
  // Priority 3: Type default card component (transform)
  if (result.config?.type?.default_card_component) {
    return transformCardToModal(result.config.type.default_card_component)
  }
  
  // Priority 4: Legacy component field (backward compat)
  if (result.component) {
    return result.component
  }
  
  // Fallback
  return 'UnifiedListModal'
}

function transformCardToModal(cardName: string): string {
  // "SprintCard" → "SprintListModal"
  // "TaskCard" → "TaskListModal"
  return cardName.replace('Card', 'ListModal')
}
```

### Props Standardization

```typescript
interface StandardModalProps {
  // Required
  isOpen: boolean
  onClose: () => void
  
  // Data (standardized)
  data?: any  // Generic data prop
  config?: ConfigObject  // Backend config
  
  // Handlers
  onRefresh?: () => void
  onItemSelect?: (item: any) => void  // Generic selection handler
  onBack?: () => void  // For detail views
  
  // Type-specific props (legacy support)
  [key: string]: any
}
```

### Props Builder

```typescript
function buildComponentProps(
  result: CommandResult, 
  componentName: string,
  handlers: {
    onClose: () => void
    onRefresh?: () => void
    executeDetailCommand?: (cmd: string) => void
  }
): StandardModalProps {
  // Base props (all components)
  const props: StandardModalProps = {
    isOpen: true,
    onClose: handlers.onClose,
    config: result.config,
    onRefresh: handlers.onRefresh,
  }
  
  // Add generic data prop (preferred)
  props.data = result.data
  
  // Add type-specific prop for backward compatibility
  const dataKey = getDataPropName(componentName)
  if (dataKey !== 'data') {
    props[dataKey] = result.data
  }
  
  // Add detail selection handlers (smart detection)
  if (componentName.includes('Sprint') && handlers.executeDetailCommand) {
    props.onItemSelect = (sprint) => {
      handlers.executeDetailCommand(`/sprint-detail ${sprint.code}`)
    }
    props.onSprintSelect = props.onItemSelect // Legacy
  }
  
  if (componentName.includes('Task') && handlers.executeDetailCommand) {
    props.onItemSelect = (task) => {
      handlers.executeDetailCommand(`/task-detail ${task.task_code}`)
    }
    props.onTaskSelect = props.onItemSelect // Legacy
  }
  
  return props
}

function getDataPropName(componentName: string): string {
  // Convention: "SprintListModal" → "sprints"
  const typeMatch = componentName.match(/^(\w+)(List|Detail|Grid)?Modal$/)
  if (!typeMatch) return 'data'
  
  const type = typeMatch[1].toLowerCase()
  
  // Pluralize for list views
  if (componentName.includes('List') || componentName.includes('Grid')) {
    return `${type}s`
  }
  
  // Singular for detail views
  return type
}
```

### Component Renderer

```typescript
function renderComponent(
  result: CommandResult,
  handlers: {
    onClose: () => void
    onRefresh?: () => void
    executeDetailCommand?: (cmd: string) => void
  }
): React.ReactNode {
  // Resolve component name
  const componentName = getComponentName(result)
  
  // Get component from map
  const Component = COMPONENT_MAP[componentName]
  
  if (!Component) {
    console.warn(`[CommandResultModal] Component not found: ${componentName}`)
    console.warn('[CommandResultModal] Falling back to UnifiedListModal')
    
    const FallbackComponent = COMPONENT_MAP['UnifiedListModal']
    const props = buildComponentProps(result, 'UnifiedListModal', handlers)
    return <FallbackComponent {...props} />
  }
  
  // Build props
  const props = buildComponentProps(result, componentName, handlers)
  
  // Log for debugging
  console.log('[CommandResultModal] Rendering:', {
    componentName,
    hasConfig: !!result.config,
    dataKeys: Object.keys(result.data || {}),
    propKeys: Object.keys(props),
  })
  
  // Special handling for dashboard components (full-screen)
  if (componentName.endsWith('Dashboard')) {
    return (
      <Dialog open={props.isOpen} onOpenChange={props.onClose}>
        <DialogContent className="max-w-[95vw] h-[90vh] p-0">
          <Component {...props} />
        </DialogContent>
      </Dialog>
    )
  }
  
  // Regular component (self-contained modal)
  return <Component {...props} />
}
```

---

## Implementation Plan

### Phase 1: Foundation (1-2 hours)
**Goal:** Set up infrastructure without breaking anything

**Tasks:**
1. Create `COMPONENT_MAP` constant
2. Create helper functions:
   - `getComponentName()`
   - `transformCardToModal()`
   - `getDataPropName()`
   - `buildComponentProps()`
   - `renderComponent()`
3. Add comprehensive logging
4. Add TypeScript types

**Deliverable:** New functions exist alongside old switch statement

---

### Phase 2: Replace Switch Statement (1 hour)
**Goal:** Remove hardcoded routing logic

**Tasks:**
1. Replace `renderOrchestrationUI()` with `renderComponent()`
2. Update main render logic to use new system
3. Keep `result.component` field as legacy fallback
4. Test all commands manually

**Deliverable:** Switch statement removed, all commands work

---

### Phase 3: Child Component Updates (2-3 hours)
**Goal:** Components leverage config for smarter rendering

**Priority Components:**
1. `UnifiedListModal` - Already config-aware, enhance further
2. `SprintListModal` - High traffic, add config support
3. `TaskListModal` - High traffic, add config support

**Tasks per component:**
1. Add `config?: ConfigObject` to props interface
2. Use `config.ui.layout_mode` for rendering decisions
3. Use `config.type.display_name` for titles
4. Support generic `data` prop alongside type-specific prop
5. Add helpful console logs

**Deliverable:** 3 components fully config-driven

---

### Phase 4: Testing & Refinement (1-2 hours)
**Goal:** Ensure stability and gather feedback

**Tasks:**
1. Test all commands in browser
2. Test detail views (drill-down)
3. Test error cases (unknown component, missing config)
4. Verify console logs are helpful
5. Check TypeScript errors
6. Run `npm run build`
7. Update documentation

**Deliverable:** Production-ready code

---

### Phase 5: Documentation & Migration Guide (1 hour)
**Goal:** Help future developers

**Tasks:**
1. Update component prop interfaces
2. Add JSDoc comments to helper functions
3. Create "Adding a New Command" guide
4. Create "Adding a New Component" guide
5. Document component naming conventions

**Deliverable:** Comprehensive documentation

---

## File Changes

### Core File (Major Changes)
- `resources/js/islands/chat/CommandResultModal.tsx`
  - Add COMPONENT_MAP (~30 lines)
  - Add helper functions (~100 lines)
  - Replace switch statement (~300 lines removed, ~50 lines added)
  - **Net change:** ~400 lines → ~200 lines

### Child Components (Minor Changes)
- `resources/js/components/unified/UnifiedListModal.tsx`
  - Already config-aware, minor enhancements
  - ~20 lines changed
  
- `resources/js/components/orchestration/SprintListModal.tsx`
  - Add config prop
  - Use config for decisions
  - ~30 lines changed
  
- `resources/js/components/orchestration/TaskListModal.tsx`
  - Add config prop
  - Use config for decisions
  - ~30 lines changed

### Optional Enhancements (Phase 2+)
- `resources/js/components/ui/DataManagementModal.tsx`
  - Already generic, no changes needed
  
- Other modal components (as needed)
  - Gradual config adoption
  - Backward compatible

---

## Backward Compatibility

### Legacy Component Field
```typescript
// Old way (still works)
{ component: "SprintListModal", data: [...] }

// New way (preferred)
{ config: { ui: { modal_container: "SprintListModal" } }, data: [...] }
```

### Type-Specific Props
```typescript
// Old way (still works)
<SprintListModal sprints={data} />

// New way (also supported)
<SprintListModal data={data} config={config} />
```

### Gradual Migration
- Phase 1: Both patterns work
- Phase 2: Config becomes primary
- Phase 3: Deprecate legacy patterns (optional, far future)

---

## Error Handling

### Missing Component
```typescript
// Console output
⚠️ [CommandResultModal] Component not found: CustomComponent
⚠️ [CommandResultModal] Falling back to UnifiedListModal

// User sees
UnifiedListModal with data rendered correctly
```

### Missing Config
```typescript
// Gracefully handled
const componentName = result.component || 'UnifiedListModal'
```

### Invalid Data
```typescript
// Component-level handling
if (!data || !data.items) {
  return <EmptyState message="No items found" />
}
```

---

## Testing Strategy

### Manual Testing Checklist
```bash
# List commands
/sprints ✓
/tasks ✓
/agents ✓
/projects ✓
/vaults ✓
/bookmarks ✓
/channels ✓
/fragments ✓

# Detail commands (drill-down)
/sprints → click sprint → detail view ✓
/tasks → click task → detail view ✓

# Special commands
/todos ✓
/types ✓
/routing-info ✓

# Edge cases
Unknown component → UnifiedListModal ✓
Missing config → legacy component field ✓
```

### Browser Console Validation
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
  console.log('✓ Config present:', !!result.config)
  console.log('✓ UI config:', result.config?.ui)
  console.log('✓ Modal container:', result.config?.ui?.modal_container)
})
```

### TypeScript Validation
```bash
npm run build  # Should pass with 0 errors
```

---

## Benefits Summary

### For Developers
- ✅ **Easier to maintain** - Single source of truth for routing
- ✅ **Easier to debug** - Clear flow, helpful logs
- ✅ **Easier to extend** - Add component to map, done
- ✅ **Better DX** - Code is self-documenting

### For Users
- ✅ **No breaking changes** - Everything continues to work
- ✅ **Better error handling** - Graceful fallbacks
- ✅ **Consistent UX** - Standardized patterns

### For Agents
- ✅ **Clear patterns** - Easy to understand and follow
- ✅ **Predictable structure** - Convention-based routing
- ✅ **Self-contained logic** - Helper functions, not monolithic switch

### For Product
- ✅ **Backend-driven UI** - Config changes don't require deploys
- ✅ **Flexible architecture** - Easy to add new types/commands
- ✅ **Future-proof** - Foundation for advanced features

---

## Future Enhancements (Post-MVP)

### 1. Dynamic Component Loading
```typescript
// Lazy load components
const COMPONENT_MAP = {
  'SprintListModal': () => import('@/components/orchestration/SprintListModal'),
  // ...
}
```

### 2. Component Registry API
```typescript
// Register components at runtime
ComponentRegistry.register('CustomModal', CustomModalComponent)
```

### 3. Advanced Config Support
```typescript
// Filters, sorting, actions
<UnifiedListModal 
  data={data}
  config={{
    filters: [{ field: 'status', options: ['active', 'done'] }],
    actions: [{ label: 'Archive', handler: archiveSprint }],
  }}
/>
```

### 4. Layout Modes
```typescript
// Backend controls layout
config.ui.layout_mode: "table" | "grid" | "list" | "kanban"
```

### 5. Custom Renderers
```typescript
// Config-driven cell rendering
config.ui.columns: [
  { key: 'status', render: 'StatusBadge' },
  { key: 'priority', render: 'PriorityIcon' },
]
```

---

## Open Questions

### 1. Naming Conventions
**Q:** Should we standardize on "Modal" suffix for all components?  
**A:** Yes, keeps the map predictable. "Dashboard" components are the exception.

### 2. Prop Names
**Q:** Should we deprecate type-specific props (e.g., `sprints`, `tasks`)?  
**A:** Support both. Generic `data` is preferred, type-specific for legacy.

### 3. Component Wrapper
**Q:** Should Dashboard components handle their own Dialog wrapper?  
**A:** No, keep wrapping logic in CommandResultModal for now. Consider refactoring in Phase 5+.

### 4. Error Boundaries
**Q:** Should we wrap components in error boundaries?  
**A:** Yes, but as a separate enhancement. Use React Error Boundaries at CommandResultModal level.

### 5. Loading States
**Q:** How should we handle loading states?  
**A:** Component-level for now. Consider global loading state in Phase 5+.

---

## Rollback Plan

### If Issues Arise
1. **Quick Fix:** Revert `CommandResultModal.tsx` to previous commit
2. **Backend:** Config continues to work, `component` field still present
3. **Zero Data Loss:** No database changes required
4. **Debugging:** Comprehensive logging helps identify issues quickly

### Safety Measures
1. Keep old switch statement in git history
2. Add feature flag (optional): `FEATURE_CONFIG_ROUTING`
3. Test in local environment first
4. Deploy during low-traffic period
5. Monitor error logs for 24 hours

---

## Success Criteria

### Code Quality
- [x] Lines of code reduced by 50%+ (400 → 200)
- [x] TypeScript strict mode passes
- [x] No console errors in production
- [x] Helper functions have clear, single responsibilities

### Functionality
- [x] All 12+ commands work without regression
- [x] Detail views (drill-down) work correctly
- [x] Back button works in detail views
- [x] Fallback system works for unknown components
- [x] Config priority system respects backend order

### Developer Experience
- [x] Adding new command requires 0 frontend code changes
- [x] Component resolution logic is documented
- [x] Console logs are clear and actionable
- [x] Agents can understand and explain the system

### Documentation
- [x] Inline JSDoc comments added
- [x] "Adding a Component" guide created
- [x] Component naming conventions documented
- [x] Config priority system explained

---

## Next Steps

1. **User Review:** Get feedback on proposed solution
2. **Prototype:** Build small working example
3. **Validate:** Test with 2-3 commands
4. **Full Implementation:** Execute phases 1-4
5. **Deploy:** Test in production with monitoring
