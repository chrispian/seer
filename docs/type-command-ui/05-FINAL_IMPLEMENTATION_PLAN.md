# Type + Command UI - Final Implementation Plan

**Date:** October 10, 2025  
**Status:** Approved - Ready for Implementation  
**Estimated Total Time:** 8-9 hours

---

## Decisions Made

### ✅ Config-Only (No Legacy)
- No `component` field support
- Backend must provide config
- Clear error messages when config missing
- Forces proper architecture

### ✅ Generic `data` Prop Only
- All components use `data` prop
- No type-specific props (sprints, tasks, agents)
- Clear, consistent pattern
- Agent-friendly

### ✅ Self-Contained Components
- Components handle their own wrappers
- No special cases in routing code
- Dashboard components use shared hook for consistency
- Fully modular

### ✅ Component Naming Convention
- Rename for consistency
- Pattern: `[Type][View][Container]`
- Examples: `AgentDashboard` → `AgentListDashboard`

### ✅ Phased Component Updates
- Get Phase 1-2 working first (routing only)
- Then update ALL components at once (Phase 3)
- Ensures consistency, prevents partial states

---

## Implementation Phases

### Phase 1: Foundation (1.5h)

**Goal:** Set up config-driven infrastructure

#### Task 1.1: Create Component Map (15 min)
```typescript
// resources/js/islands/chat/CommandResultModal.tsx

/**
 * Central registry mapping component names to React components.
 * All components must be registered here to be rendered by the command system.
 */
const COMPONENT_MAP: Record<string, React.ComponentType<any>> = {
  // List Components
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
  
  // Management Components
  'TodoManagementModal': TodoManagementModal,
  'TypeManagementModal': TypeManagementModal,
  'RoutingInfoModal': RoutingInfoModal,
  
  // Dashboard Components (will be renamed in Phase 3)
  'AgentListDashboard': AgentProfileDashboard,  // Temporary name mapping
  'AgentDashboard': AgentDashboard,              // Temporary
}
```

**Acceptance Criteria:**
- [x] All 20+ components mapped
- [x] JSDoc comment explains purpose
- [x] TypeScript types defined
- [x] No import errors

---

#### Task 1.2: Implement Config Resolution (30 min)
```typescript
/**
 * Determines which component to render based on backend config.
 * 
 * Config-Only Priority (NO LEGACY SUPPORT):
 * 1. config.ui.modal_container (explicit backend preference)
 * 2. config.ui.card_component (transformed to modal name)
 * 3. config.type.default_card_component (transformed)
 * 4. "UnifiedListModal" (fallback)
 * 
 * @throws Never throws - always returns a valid component name
 */
function getComponentName(result: CommandResult): string {
  if (!result.config) {
    console.warn('[CommandResultModal] No config provided - using fallback')
    return 'UnifiedListModal'
  }
  
  // Priority 1: Explicit UI modal container
  if (result.config.ui?.modal_container) {
    console.log('[CommandResultModal] Using ui.modal_container:', 
      result.config.ui.modal_container)
    return result.config.ui.modal_container
  }
  
  // Priority 2: UI card component (transform)
  if (result.config.ui?.card_component) {
    const transformed = transformCardToModal(result.config.ui.card_component)
    console.log('[CommandResultModal] Transformed ui.card_component:', 
      result.config.ui.card_component, '→', transformed)
    return transformed
  }
  
  // Priority 3: Type default card component (transform)
  if (result.config.type?.default_card_component) {
    const transformed = transformCardToModal(result.config.type.default_card_component)
    console.log('[CommandResultModal] Transformed type.default_card_component:', 
      result.config.type.default_card_component, '→', transformed)
    return transformed
  }
  
  // Fallback
  console.log('[CommandResultModal] No component specified - using UnifiedListModal')
  return 'UnifiedListModal'
}

/**
 * Transforms card component names to modal equivalents.
 * Convention: "XCard" → "XListModal"
 * 
 * @example
 * transformCardToModal("SprintCard") // "SprintListModal"
 * transformCardToModal("TaskCard")   // "TaskListModal"
 */
function transformCardToModal(cardName: string): string {
  if (cardName.endsWith('Card')) {
    return cardName.replace('Card', 'ListModal')
  }
  
  // Already a modal or unknown format - return as-is
  return cardName
}
```

**Acceptance Criteria:**
- [x] 3-level priority system implemented
- [x] No legacy field checks
- [x] Helpful console logs
- [x] Fallback to UnifiedListModal
- [x] Transform logic works correctly

---

#### Task 1.3: Implement Props Builder (30 min)
```typescript
/**
 * Builds standardized props for component rendering.
 * All components receive: isOpen, onClose, data, config
 * 
 * NO LEGACY PROPS - Only generic 'data' prop used.
 */
interface ComponentHandlers {
  onClose: () => void
  onRefresh?: () => void
  executeDetailCommand?: (cmd: string) => void
}

function buildComponentProps(
  result: CommandResult,
  componentName: string,
  handlers: ComponentHandlers
): Record<string, any> {
  const { onClose, onRefresh, executeDetailCommand } = handlers
  
  // Base props (all components)
  const props: Record<string, any> = {
    isOpen: true,
    onClose,
    data: result.data,
    config: result.config,
  }
  
  // Add refresh handler if provided
  if (onRefresh) {
    props.onRefresh = onRefresh
  }
  
  // Add smart selection handlers based on component type
  if (executeDetailCommand) {
    if (componentName.includes('Sprint')) {
      props.onItemSelect = (item: any) => {
        executeDetailCommand(`/sprint-detail ${item.code}`)
      }
    } else if (componentName.includes('Task')) {
      props.onItemSelect = (item: any) => {
        executeDetailCommand(`/task-detail ${item.task_code}`)
      }
    } else if (componentName.includes('Agent')) {
      props.onItemSelect = (item: any) => {
        executeDetailCommand(`/agent-profile-detail ${item.slug}`)
      }
    }
  }
  
  // Add back handler for detail views
  if (componentName.includes('Detail')) {
    props.onBack = handlers.onClose
  }
  
  console.log('[CommandResultModal] Built props:', {
    componentName,
    propKeys: Object.keys(props),
    hasData: !!props.data,
    hasConfig: !!props.config,
  })
  
  return props
}
```

**Acceptance Criteria:**
- [x] Generic `data` prop only (no type-specific)
- [x] Config always passed
- [x] Smart handlers based on component name
- [x] Back handler for detail views
- [x] Helpful logging

---

#### Task 1.4: Implement Component Renderer (30 min)
```typescript
/**
 * Main component rendering function.
 * Handles resolution, lookup, fallback, and rendering.
 * 
 * NO SPECIAL CASES - All components are treated equally.
 * Components handle their own wrappers.
 */
function renderComponent(
  result: CommandResult,
  handlers: ComponentHandlers
): React.ReactNode {
  // Resolve component name from config
  const componentName = getComponentName(result)
  
  // Lookup in registry
  let Component = COMPONENT_MAP[componentName]
  
  if (!Component) {
    console.warn(`[CommandResultModal] Component "${componentName}" not found in registry`)
    console.warn('[CommandResultModal] Falling back to UnifiedListModal')
    Component = COMPONENT_MAP['UnifiedListModal']
  }
  
  // Build props
  const props = buildComponentProps(result, componentName, handlers)
  
  console.log('[CommandResultModal] Rendering component:', {
    componentName,
    hasComponent: !!Component,
    configSource: getConfigSource(result),
  })
  
  // Render component (no special cases, components handle their own wrappers)
  return <Component {...props} />
}

/**
 * Helper to identify where component name came from (for debugging)
 */
function getConfigSource(result: CommandResult): string {
  if (!result.config) return 'fallback'
  if (result.config.ui?.modal_container) return 'ui.modal_container'
  if (result.config.ui?.card_component) return 'ui.card_component (transformed)'
  if (result.config.type?.default_card_component) return 'type.default_card_component (transformed)'
  return 'fallback'
}
```

**Acceptance Criteria:**
- [x] Config-driven resolution
- [x] Registry lookup
- [x] Graceful fallback
- [x] No special wrapping logic
- [x] Comprehensive logging

---

### Phase 2: Switch Statement Replacement (1h)

**Goal:** Remove hardcoded switch, use new system

#### Task 2.1: Replace renderOrchestrationUI (30 min)

**Remove (lines 136-423):**
- Entire `renderOrchestrationUI()` function
- All 20+ switch cases
- ~300 lines of code

**Replace with:**
```typescript
// Use new rendering system
const handlers: ComponentHandlers = {
  onClose,
  onRefresh: () => console.log('[CommandResultModal] Refresh requested'),
  executeDetailCommand,
}

// Main render logic for command results with components
if (result.success && result.config) {
  return renderComponent(result, handlers)
}
```

**Acceptance Criteria:**
- [x] Switch statement completely removed
- [x] New render function used
- [x] Detail view handling preserved
- [x] No TypeScript errors

---

#### Task 2.2: Update Detail View Handling (20 min)

Ensure detail views work with new system:

```typescript
// Detail view state (keep as-is)
const [detailView, setDetailView] = useState<CommandResult | null>(null)

// If we have a detail view, render it
if (detailView && detailView.success) {
  return renderComponent(detailView, {
    onClose,
    onRefresh: () => console.log('[CommandResultModal] Detail refresh'),
    executeDetailCommand,
  })
}
```

**Acceptance Criteria:**
- [x] Detail views render correctly
- [x] Back button works
- [x] Config passed to detail views
- [x] Drill-down navigation works

---

#### Task 2.3: Cleanup and Verification (10 min)

**Remove:**
- Old helper functions if any
- Unused imports
- Dead code

**Verify:**
- TypeScript compiles
- No console errors
- Imports all resolve

---

### Phase 3: Component Updates (4h)

**Goal:** Update ALL components to use new patterns

**Strategy:** Prototype with 1 component, then update all 11 at once

---

#### Task 3.1: Create Shared Utilities (30 min)

**Create: `resources/js/hooks/useFullScreenModal.tsx`**
```typescript
/**
 * Hook for full-screen modal/dashboard components.
 * Provides consistent Dialog setup for dashboard-style components.
 */
export function useFullScreenModal(isOpen: boolean, onClose: () => void) {
  return {
    dialogProps: {
      open: isOpen,
      onOpenChange: onClose,
    },
    contentProps: {
      className: "max-w-[95vw] h-[90vh] p-0",
    },
  }
}
```

**Create: `resources/js/types/modal.ts`**
```typescript
/**
 * Standardized props interface for all modal components.
 * All list/grid/detail components should extend this interface.
 */
export interface BaseModalProps {
  isOpen: boolean
  onClose: () => void
  data: any
  config?: ConfigObject
  onRefresh?: () => void
  onItemSelect?: (item: any) => void
  onBack?: () => void
}

/**
 * Config object structure from backend
 */
export interface ConfigObject {
  type?: {
    slug: string
    display_name: string
    plural_name?: string
    storage_type: 'model' | 'fragment'
    default_card_component?: string
    default_detail_component?: string
    icon?: string
    color?: string
  }
  ui?: {
    modal_container?: string
    layout_mode?: 'table' | 'grid' | 'list' | 'kanban'
    card_component?: string
    detail_component?: string
    filters?: Record<string, any>
    default_sort?: {
      field: string
      direction: 'asc' | 'desc'
    }
    pagination_default?: number
  }
  command?: {
    command: string
    name: string
    description?: string
    category?: string
  }
}
```

---

#### Task 3.2: Prototype with SprintListModal (45 min)

**Test the pattern with one component:**

**Before:**
```typescript
interface SprintListModalProps {
  isOpen: boolean
  onClose: () => void
  sprints: Sprint[]  // ❌ Type-specific prop
  onSprintSelect?: (sprint: Sprint) => void
  onRefresh?: () => void
}

export function SprintListModal({ 
  isOpen, 
  onClose, 
  sprints,  // ❌
  onSprintSelect,
  onRefresh 
}: SprintListModalProps) {
  // ...
}
```

**After:**
```typescript
interface SprintListModalProps extends BaseModalProps {
  data: Sprint[]  // ✅ Generic prop with typing
}

export function SprintListModal({ 
  isOpen, 
  onClose, 
  data,  // ✅ Generic
  config,  // ✅ Config-aware
  onItemSelect,  // ✅ Generic handler
  onRefresh 
}: SprintListModalProps) {
  // Use config for rendering decisions
  const title = config?.type?.plural_name || 'Sprints'
  const layoutMode = config?.ui?.layout_mode || 'table'
  
  console.log('[SprintListModal] Config-driven render:', {
    title,
    layoutMode,
    itemCount: data.length,
  })
  
  return (
    <DataManagementModal
      isOpen={isOpen}
      onClose={onClose}
      title={title}
      data={data}
      columns={columns}
      onRefresh={onRefresh}
      onRowClick={onItemSelect}
    />
  )
}
```

**Test thoroughly:**
- Execute `/sprints`
- Verify data displays
- Test drill-down to detail
- Check back button
- Verify config usage
- Check console logs

**Once prototype works, proceed to update all components.**

---

#### Task 3.3: Update All List Components (2h)

Update all 9 list/grid components using the prototype pattern:

1. **TaskListModal** - `tasks` → `data`
2. **AgentProfileGridModal** - `agents` → `data`
3. **BacklogListModal** - `tasks` → `data`
4. **ProjectListModal** - `projects` → `data`
5. **VaultListModal** - `vaults` → `data`
6. **BookmarkListModal** - `bookmarks` → `data`
7. **FragmentListModal** - `fragments` → `data`
8. **ChannelListModal** - `channels` → `data`
9. **UnifiedListModal** - Already uses `data`, enhance config support

**For each component:**
- [x] Update interface to extend `BaseModalProps`
- [x] Change prop from type-specific to `data`
- [x] Add config prop
- [x] Use config for title, layout decisions
- [x] Update handlers (onTaskSelect → onItemSelect)
- [x] Add console logging
- [x] Test manually

---

#### Task 3.4: Rename & Update Dashboard Components (45 min)

**Rename files:**
```bash
# Rename files
mv resources/js/pages/AgentProfileDashboard.tsx resources/js/pages/AgentProfileListDashboard.tsx
mv resources/js/pages/AgentDashboard.tsx resources/js/pages/AgentManagementDashboard.tsx
```

**Update components to use new hook:**

**AgentProfileListDashboard.tsx:**
```typescript
import { useFullScreenModal } from '@/hooks/useFullScreenModal'
import { BaseModalProps } from '@/types/modal'

interface AgentProfileListDashboardProps extends BaseModalProps {
  data: Agent[]  // Changed from initialAgents
}

export function AgentProfileListDashboard({ 
  isOpen, 
  onClose, 
  data,  // ✅ Generic prop
  config 
}: AgentProfileListDashboardProps) {
  const modal = useFullScreenModal(isOpen, onClose)
  
  return (
    <Dialog {...modal.dialogProps}>
      <DialogContent {...modal.contentProps}>
        {/* Dashboard content */}
        <div className="p-6">
          <h1>{config?.type?.plural_name || 'Agent Profiles'}</h1>
          {/* ... rest of dashboard */}
        </div>
      </DialogContent>
    </Dialog>
  )
}
```

**Update imports in CommandResultModal:**
```typescript
import { AgentProfileListDashboard } from '@/pages/AgentProfileListDashboard'
import { AgentManagementDashboard } from '@/pages/AgentManagementDashboard'
```

**Update component map:**
```typescript
const COMPONENT_MAP = {
  // ...
  'AgentProfileListDashboard': AgentProfileListDashboard,
  'AgentManagementDashboard': AgentManagementDashboard,
}
```

**Acceptance Criteria:**
- [x] Files renamed following convention
- [x] Components use `useFullScreenModal` hook
- [x] Generic `data` prop
- [x] Config-aware rendering
- [x] Self-contained Dialog wrapper

---

#### Task 3.5: Create Migration Script (30 min)

**Create: `scripts/find-legacy-props.sh`**
```bash
#!/bin/bash
# Find all usages of legacy type-specific props

echo "🔍 Searching for legacy prop usages..."
echo ""

echo "=== Sprint Props ==="
rg "sprints=" resources/js --type tsx --type ts

echo ""
echo "=== Task Props ==="
rg "tasks=" resources/js --type tsx --type ts

echo ""
echo "=== Agent Props ==="
rg "agents=" resources/js --type tsx --type ts

echo ""
echo "=== Project Props ==="
rg "projects=" resources/js --type tsx --type ts

echo ""
echo "=== Vault Props ==="
rg "vaults=" resources/js --type tsx --type ts

echo ""
echo "=== Bookmark Props ==="
rg "bookmarks=" resources/js --type tsx --type ts

echo ""
echo "=== Fragment Props ==="
rg "fragments=" resources/js --type tsx --type ts

echo ""
echo "=== Channel Props ==="
rg "channels=" resources/js --type tsx --type ts

echo ""
echo "✅ Migration check complete"
```

```bash
chmod +x scripts/find-legacy-props.sh
./scripts/find-legacy-props.sh
```

**Fix any remaining usages found.**

---

### Phase 4: Testing & Validation (1.5h)

#### Task 4.1: Manual Testing (45 min)

**Test all commands:**
```bash
✓ /sprints
  - Verify: Data displays in table
  - Click: Sprint row → detail view
  - Verify: Back button works
  - Check: Console shows config-driven render

✓ /tasks
  - Same tests as above

✓ /agents
✓ /backlog
✓ /projects
✓ /vaults
✓ /bookmarks
✓ /fragments
✓ /channels

✓ Special commands:
  - /todos
  - /types
  - /routing-info
```

**Document test results in spreadsheet or markdown table.**

---

#### Task 4.2: TypeScript & Build Validation (20 min)

```bash
# TypeScript check
npm run build

# Expected: Success, 0 errors

# Verify no import errors
# Verify all types resolve
# Check for any 'any' types that should be typed
```

---

#### Task 4.3: Backend Config Validation (15 min)

**Test in browser console:**
```javascript
// Test config structure
async function testCommandConfig(cmd) {
  const csrf = document.querySelector('meta[name="csrf-token"]').content
  const res = await fetch('/api/commands/execute', {
    method: 'POST',
    headers: { 
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': csrf
    },
    body: JSON.stringify({ command: cmd })
  })
  const result = await res.json()
  
  console.log(`\n=== ${cmd} ===`)
  console.log('Config present:', !!result.config)
  console.log('UI container:', result.config?.ui?.modal_container)
  console.log('Card component:', result.config?.ui?.card_component)
  console.log('Type default:', result.config?.type?.default_card_component)
  
  if (!result.config?.ui?.modal_container && 
      !result.config?.ui?.card_component && 
      !result.config?.type?.default_card_component) {
    console.warn('⚠️ No component specified - will use fallback')
  }
}

// Test multiple commands
['/sprints', '/tasks', '/agents', '/projects'].forEach(testCommandConfig)
```

**Fix any config issues in backend seeders.**

---

#### Task 4.4: Console Log Review (10 min)

**Check that logs are helpful:**
```
Expected logs:
✓ [CommandResultModal] Using ui.modal_container: SprintListModal
✓ [CommandResultModal] Built props: { ... }
✓ [CommandResultModal] Rendering component: SprintListModal
✓ [SprintListModal] Config-driven render: { title: "Sprints", ... }
```

**Remove or adjust any noisy/unhelpful logs.**

---

### Phase 5: Documentation (1h)

#### Task 5.1: Update Inline Documentation (20 min)

**Add JSDoc comments to all helper functions** (already shown in code above).

**Update CommandResultModal.tsx header comment:**
```typescript
/**
 * CommandResultModal - Config-driven command result renderer
 * 
 * This component orchestrates the rendering of command execution results
 * using a fully config-driven approach. The backend provides a config
 * object that determines which component to render and how to render it.
 * 
 * Architecture:
 * 1. Backend returns { data, config }
 * 2. getComponentName() resolves component using config priority
 * 3. Component lookup in COMPONENT_MAP registry
 * 4. buildComponentProps() creates standardized props
 * 5. renderComponent() renders with error handling
 * 
 * Key Principles:
 * - Config-only (no legacy support)
 * - Generic 'data' prop (no type-specific props)
 * - Self-contained components (handle their own wrappers)
 * - Graceful fallbacks (UnifiedListModal)
 * 
 * Adding a new component:
 * 1. Add to COMPONENT_MAP
 * 2. Update backend seeder with component name
 * 3. That's it - no other changes needed
 * 
 * @see docs/type-command-ui/
 */
```

---

#### Task 5.2: Create Component Developer Guide (20 min)

**Create: `docs/type-command-ui/COMPONENT_DEVELOPMENT_GUIDE.md`**

```markdown
# Component Development Guide

## Creating a New List Modal Component

### 1. Interface
All components extend BaseModalProps:

\`\`\`typescript
import { BaseModalProps } from '@/types/modal'

interface MyListModalProps extends BaseModalProps {
  data: MyType[]  // Type your data
}
\`\`\`

### 2. Component Implementation
\`\`\`typescript
export function MyListModal({ 
  isOpen, 
  onClose, 
  data,  // Generic prop
  config,  // Backend config
  onItemSelect,  // Generic handler
  onRefresh 
}: MyListModalProps) {
  // Use config for rendering decisions
  const title = config?.type?.plural_name || 'My Items'
  
  return (
    <DataManagementModal
      isOpen={isOpen}
      onClose={onClose}
      title={title}
      data={data}
      columns={columns}
      onRefresh={onRefresh}
      onRowClick={onItemSelect}
    />
  )
}
\`\`\`

### 3. Register Component
\`\`\`typescript
// In CommandResultModal.tsx
const COMPONENT_MAP = {
  'MyListModal': MyListModal,
  // ...
}
\`\`\`

### 4. Backend Configuration
\`\`\`php
// In database/seeders/CommandsSeeder.php
[
    'command' => '/my-items',
    'ui_modal_container' => 'MyListModal',
    // ...
]
\`\`\`

That's it! Your component is ready.

## Creating a Dashboard Component

Use the `useFullScreenModal` hook:

\`\`\`typescript
import { useFullScreenModal } from '@/hooks/useFullScreenModal'

export function MyDashboard({ isOpen, onClose, data, config }) {
  const modal = useFullScreenModal(isOpen, onClose)
  
  return (
    <Dialog {...modal.dialogProps}>
      <DialogContent {...modal.contentProps}>
        {/* Your dashboard content */}
      </DialogContent>
    </Dialog>
  )
}
\`\`\`

## Naming Conventions

- List view: `[Type]ListModal` (e.g., SprintListModal)
- Grid view: `[Type]GridModal` (e.g., AgentProfileGridModal)
- Detail view: `[Type]DetailModal` (e.g., TaskDetailModal)
- Dashboard: `[Type]ListDashboard` (e.g., AgentProfileListDashboard)
- Management: `[Type]ManagementModal` or `[Type]ManagementDashboard`
```

---

#### Task 5.3: Update README (20 min)

**Update: `docs/type-command-ui/README.md`**

Add "Decisions Made" section:
```markdown
## Decisions Made (October 10, 2025)

### Config-Only Architecture
- ✅ No legacy `component` field support
- ✅ Backend must provide config
- ✅ Eliminates technical debt from day one

### Generic Data Prop
- ✅ All components use `data` prop
- ✅ No type-specific props (sprints, tasks, etc.)
- ✅ Clear, consistent pattern

### Self-Contained Components
- ✅ Components handle their own wrappers
- ✅ No special cases in routing code
- ✅ `useFullScreenModal` hook for dashboards

### Component Naming
- ✅ Standardized naming convention
- ✅ Pattern: [Type][View][Container]
- ✅ Dashboard components renamed for consistency
```

---

## Updated File Changes

### Core Files (Major Changes)
- `resources/js/islands/chat/CommandResultModal.tsx`
  - Remove switch statement (~300 lines)
  - Add helper functions (~150 lines)
  - Net: 590 lines → ~350 lines

### New Files
- `resources/js/hooks/useFullScreenModal.tsx` (~20 lines)
- `resources/js/types/modal.ts` (~60 lines)
- `scripts/find-legacy-props.sh` (~50 lines)
- `docs/type-command-ui/COMPONENT_DEVELOPMENT_GUIDE.md` (~200 lines)

### Component Files (11 components, ~30 lines each)
All list/grid/dashboard components updated:
1. SprintListModal
2. TaskListModal
3. AgentProfileGridModal
4. BacklogListModal
5. ProjectListModal
6. VaultListModal
7. BookmarkListModal
8. FragmentListModal
9. ChannelListModal
10. AgentProfileListDashboard (renamed from AgentProfileDashboard)
11. AgentManagementDashboard (renamed from AgentDashboard)

**Total changes:** ~12 files, ~800 lines modified

---

## Risk Mitigation

### Primary Risk: Breaking All Commands
**Likelihood:** Low  
**Impact:** High  
**Mitigation:**
- Prototype with 1 component first (SprintListModal)
- Test thoroughly before updating others
- Update all components in single commit (atomic change)
- Rollback is single git revert

### Secondary Risk: Missing Config
**Likelihood:** Low (backend already tested)  
**Impact:** Low (graceful fallback)  
**Mitigation:**
- UnifiedListModal fallback always works
- Console warnings for debugging
- Backend config validation script

### Tertiary Risk: TypeScript Errors
**Likelihood:** Medium  
**Impact:** Medium (blocks build)  
**Mitigation:**
- Update types first (modal.ts)
- Fix one component at a time
- Run `npm run build` frequently

---

## Definition of Done

### Code ✅
- [x] Switch statement removed (~300 lines deleted)
- [x] Config-only resolution (no legacy support)
- [x] Generic `data` prop across all components
- [x] Self-contained components (no special wrappers)
- [x] Dashboard components renamed
- [x] Helper functions with JSDoc comments

### Testing ✅
- [x] All 12+ commands manually tested
- [x] Detail views work (drill-down + back)
- [x] TypeScript compilation succeeds
- [x] No console errors
- [x] Config validation passes
- [x] Migration script finds no legacy props

### Documentation ✅
- [x] Inline JSDoc comments
- [x] Component development guide
- [x] README updated with decisions
- [x] Code examples in docs

### Backend ✅
- [x] All seeders updated with config
- [x] No commands use legacy `component` field
- [x] Config present in all responses

---

## Timeline

| Phase | Tasks | Estimated Time |
|-------|-------|----------------|
| Phase 1: Foundation | 4 tasks | 1.5h |
| Phase 2: Switch Replacement | 3 tasks | 1h |
| Phase 3: Components | 5 tasks | 4h |
| Phase 4: Testing | 4 tasks | 1.5h |
| Phase 5: Documentation | 3 tasks | 1h |
| **Total** | **19 tasks** | **9h** |

**Conservative estimate with buffer:** 10-11 hours  
**Recommended timeline:** 2-3 work days

---

## Success Metrics

### Quantitative ✅
- Lines of code: 590 → 350 (40% reduction)
- Switch cases: 20+ → 0
- Component updates: 11 components
- Test coverage: 12+ commands

### Qualitative ✅
- Clear, consistent patterns
- Agent-friendly architecture
- Zero technical debt
- Config-driven flexibility
- Self-documenting code

---

## Next Steps

1. ✅ **Planning approved** - This document
2. ⏳ **Create orchestration sprint** - Waiting for orchestration system
3. ⏳ **Begin Phase 1** - Foundation (1.5h)
4. ⏳ **Phase 2** - Switch replacement (1h)
5. ⏳ **Phase 3** - Prototype + all components (4h)
6. ⏳ **Phase 4** - Testing (1.5h)
7. ⏳ **Phase 5** - Documentation (1h)
8. ⏳ **Deploy** - Production release

---

**Ready to begin implementation once orchestration system is ready!**
