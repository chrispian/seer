# Type + Command UI - Task Breakdown

**Date:** October 10, 2025  
**Status:** Planning Phase  
**Estimated Total Time:** 6-9 hours

---

## Sprint Overview

### Sprint Goal
Refactor CommandResultModal to use config-driven component routing, eliminating the hardcoded switch statement and fully leveraging the backend's unified Type + Command system.

### Success Criteria
- Switch statement removed (400+ lines → ~200 lines)
- All commands work without regression
- Config priority system implemented
- 3+ components updated with config support
- Comprehensive documentation

---

## Phase 1: Foundation Setup

**Duration:** 1-2 hours  
**Risk Level:** Low (non-breaking changes)  
**Dependencies:** None

### Task 1.1: Create Component Map
**Estimated Time:** 20 minutes

**Description:**  
Create a centralized `COMPONENT_MAP` constant that registers all available UI components.

**Acceptance Criteria:**
- [ ] `COMPONENT_MAP` object created with 20+ component mappings
- [ ] All imports verified (no missing components)
- [ ] TypeScript types defined
- [ ] JSDoc comment added explaining usage

**File Changes:**
- `resources/js/islands/chat/CommandResultModal.tsx` - Add constant after imports

**Code Example:**
```typescript
/**
 * Central registry of all UI components available for command rendering.
 * Maps component names (from backend config) to React components.
 */
const COMPONENT_MAP: Record<string, React.ComponentType<any>> = {
  'SprintListModal': SprintListModal,
  'TaskListModal': TaskListModal,
  // ... all components
}
```

**Testing:**
- Verify all imports resolve
- Check TypeScript compilation succeeds

---

### Task 1.2: Implement Component Resolution Helper
**Estimated Time:** 30 minutes

**Description:**  
Create `getComponentName()` function that implements config priority logic.

**Acceptance Criteria:**
- [ ] Function implements 4-level priority system
- [ ] Handles missing config gracefully
- [ ] Returns fallback component name
- [ ] Helper function `transformCardToModal()` implemented
- [ ] Comprehensive JSDoc comments

**File Changes:**
- `resources/js/islands/chat/CommandResultModal.tsx` - Add helper functions

**Code Example:**
```typescript
/**
 * Determines which component to render based on config priority.
 * Priority: ui.modal_container > ui.card_component > type.default_card_component > legacy component > fallback
 */
function getComponentName(result: CommandResult): string {
  // Implementation per design doc
}

/**
 * Transforms card component names to modal equivalents.
 * Example: "SprintCard" → "SprintListModal"
 */
function transformCardToModal(cardName: string): string {
  // Implementation
}
```

**Testing:**
- Unit test priority order
- Test transformations
- Test fallback behavior

---

### Task 1.3: Implement Props Builder
**Estimated Time:** 30 minutes

**Description:**  
Create `buildComponentProps()` function that constructs standardized props for components.

**Acceptance Criteria:**
- [ ] Builds base props (isOpen, onClose, config)
- [ ] Adds type-specific props for backward compatibility
- [ ] Infers data prop name from component name
- [ ] Adds appropriate selection handlers
- [ ] Comprehensive JSDoc comments

**File Changes:**
- `resources/js/islands/chat/CommandResultModal.tsx` - Add helper function
- Add `getDataPropName()` helper

**Code Example:**
```typescript
/**
 * Builds standardized props for component rendering.
 * Includes both generic props and type-specific props for backward compatibility.
 */
function buildComponentProps(
  result: CommandResult,
  componentName: string,
  handlers: ComponentHandlers
): StandardModalProps {
  // Implementation per design doc
}
```

**Testing:**
- Test prop generation for different component types
- Verify handler attachment
- Check backward compatibility

---

### Task 1.4: Implement Component Renderer
**Estimated Time:** 40 minutes

**Description:**  
Create `renderComponent()` function that orchestrates component resolution and rendering.

**Acceptance Criteria:**
- [ ] Resolves component name via `getComponentName()`
- [ ] Looks up component in `COMPONENT_MAP`
- [ ] Handles missing components with fallback
- [ ] Adds helpful console logging
- [ ] Wraps Dashboard components in Dialog
- [ ] Comprehensive JSDoc comments

**File Changes:**
- `resources/js/islands/chat/CommandResultModal.tsx` - Add main render function

**Code Example:**
```typescript
/**
 * Main component rendering function.
 * Handles component resolution, props building, and rendering with fallbacks.
 */
function renderComponent(
  result: CommandResult,
  handlers: ComponentHandlers
): React.ReactNode {
  // Implementation per design doc
}
```

**Testing:**
- Test successful component rendering
- Test fallback to UnifiedListModal
- Verify Dashboard wrapping logic
- Check console logs

---

## Phase 2: Switch Statement Replacement

**Duration:** 1 hour  
**Risk Level:** Medium (core functionality change)  
**Dependencies:** Phase 1 complete

### Task 2.1: Update Main Render Logic
**Estimated Time:** 30 minutes

**Description:**  
Replace `renderOrchestrationUI()` switch statement with new `renderComponent()` function.

**Acceptance Criteria:**
- [ ] Old switch statement removed
- [ ] New render function integrated
- [ ] Detail view handling updated
- [ ] Legacy `component` field still works
- [ ] No TypeScript errors

**File Changes:**
- `resources/js/islands/chat/CommandResultModal.tsx` - Replace switch statement (~300 lines removed)

**Code Changes:**
```typescript
// OLD (remove)
const renderOrchestrationUI = (currentResult: CommandResult = result) => {
  switch (currentResult.component) {
    case 'SprintListModal': return <SprintListModal ... />
    // ... 20+ cases
  }
}

// NEW (use)
const handlers = {
  onClose,
  onRefresh: () => console.log('Refresh requested'),
  executeDetailCommand
}

const ui = renderComponent(result, handlers)
if (ui) return ui
```

**Testing:**
- Test 5+ commands
- Verify no regressions
- Check TypeScript compilation

---

### Task 2.2: Update Detail View Handling
**Estimated Time:** 20 minutes

**Description:**  
Ensure detail views (drill-down) work with new rendering system.

**Acceptance Criteria:**
- [ ] Detail view state management unchanged
- [ ] Detail commands execute correctly
- [ ] Back button works
- [ ] Config passed to detail views
- [ ] Loading state handled

**File Changes:**
- `resources/js/islands/chat/CommandResultModal.tsx` - Update detail view logic

**Testing:**
- Test `/sprints` → click sprint → detail view
- Test `/tasks` → click task → detail view
- Test back button
- Verify config present in detail views

---

### Task 2.3: Add Comprehensive Logging
**Estimated Time:** 10 minutes

**Description:**  
Add helpful console logs for debugging and development.

**Acceptance Criteria:**
- [ ] Log component resolution decisions
- [ ] Log config presence/absence
- [ ] Log fallback triggers
- [ ] Logs only in development (not production)
- [ ] Clear, actionable log messages

**File Changes:**
- `resources/js/islands/chat/CommandResultModal.tsx` - Add console.log statements

**Code Example:**
```typescript
console.log('[CommandResultModal] Rendering:', {
  componentName,
  hasConfig: !!result.config,
  configSource: getConfigSource(result),
  dataKeys: Object.keys(result.data || {}),
})
```

**Testing:**
- Verify logs appear in browser console
- Check log clarity and usefulness

---

## Phase 3: Child Component Updates

**Duration:** 2-3 hours  
**Risk Level:** Low (optional enhancements)  
**Dependencies:** Phase 2 complete

### Task 3.1: Update UnifiedListModal
**Estimated Time:** 45 minutes

**Description:**  
Enhance UnifiedListModal to fully leverage config for rendering decisions.

**Acceptance Criteria:**
- [ ] Config prop interface enhanced
- [ ] Uses `config.ui.layout_mode` for rendering
- [ ] Uses `config.type.display_name` for titles
- [ ] Supports generic `data` prop
- [ ] Backward compatible with old props
- [ ] Console logs added

**File Changes:**
- `resources/js/components/unified/UnifiedListModal.tsx` - ~20 lines changed

**Code Changes:**
```typescript
interface UnifiedListModalProps {
  isOpen: boolean
  onClose: () => void
  data?: any  // New generic prop
  config?: ConfigObject  // Enhanced config
  onRefresh?: () => void
}

export function UnifiedListModal({ 
  isOpen, 
  onClose, 
  data,
  config,
  onRefresh 
}: UnifiedListModalProps) {
  // Use config for decisions
  const layoutMode = config?.ui?.layout_mode || 'table'
  const title = config?.type?.plural_name || 'Items'
  
  console.log('[UnifiedListModal] Config-driven render:', { layoutMode, title })
  
  // ... rest of implementation
}
```

**Testing:**
- Test with different layout modes
- Verify title rendering
- Check backward compatibility

---

### Task 3.2: Update SprintListModal
**Estimated Time:** 45 minutes

**Description:**  
Add config support to SprintListModal for smarter rendering.

**Acceptance Criteria:**
- [ ] Config prop added to interface
- [ ] Uses config for title, sorting, pagination
- [ ] Supports both `sprints` and `data` props
- [ ] Console logs added
- [ ] Backward compatible

**File Changes:**
- `resources/js/components/orchestration/SprintListModal.tsx` - ~30 lines changed

**Code Changes:**
```typescript
interface SprintListModalProps {
  isOpen: boolean
  onClose: () => void
  sprints?: Sprint[]  // Legacy
  data?: Sprint[]  // Generic
  config?: ConfigObject  // New
  // ... rest
}

export function SprintListModal({ 
  sprints,
  data,
  config,
  // ... rest
}: SprintListModalProps) {
  // Support both prop patterns
  const items = data || sprints || []
  const title = config?.type?.plural_name || 'Sprints'
  
  console.log('[SprintListModal] Rendering with config:', {
    itemCount: items.length,
    title,
    hasConfig: !!config
  })
  
  // ... rest
}
```

**Testing:**
- Test with old props
- Test with new props
- Verify config usage

---

### Task 3.3: Update TaskListModal
**Estimated Time:** 45 minutes

**Description:**  
Add config support to TaskListModal (same pattern as Sprint).

**Acceptance Criteria:**
- [ ] Config prop added
- [ ] Uses config for rendering decisions
- [ ] Supports both `tasks` and `data` props
- [ ] Console logs added
- [ ] Backward compatible

**File Changes:**
- `resources/js/components/orchestration/TaskListModal.tsx` - ~30 lines changed

**Implementation:**  
Same pattern as SprintListModal (Task 3.2)

**Testing:**
- Same tests as SprintListModal

---

### Task 3.4: Update DataManagementModal (Optional)
**Estimated Time:** 30 minutes

**Description:**  
Enhance DataManagementModal to use config for advanced features.

**Acceptance Criteria:**
- [ ] Config prop added
- [ ] Uses config for column definitions (optional)
- [ ] Uses config for default sort
- [ ] Uses config for pagination
- [ ] Backward compatible

**File Changes:**
- `resources/js/components/ui/DataManagementModal.tsx` - ~20 lines changed

**Testing:**
- Test config-driven columns
- Test sorting/pagination
- Verify backward compatibility

---

## Phase 4: Testing & Validation

**Duration:** 1-2 hours  
**Risk Level:** Low (verification)  
**Dependencies:** Phase 3 complete

### Task 4.1: Manual Testing
**Estimated Time:** 45 minutes

**Description:**  
Test all commands in browser to ensure no regressions.

**Acceptance Criteria:**
- [ ] All list commands work (`/sprints`, `/tasks`, etc.)
- [ ] All detail commands work (drill-down)
- [ ] Back button works in detail views
- [ ] Unknown component fallback works
- [ ] Console logs are helpful
- [ ] No errors in browser console

**Test Cases:**
```bash
# List Commands
✓ /sprints
✓ /tasks
✓ /agents
✓ /projects
✓ /vaults
✓ /bookmarks
✓ /channels
✓ /fragments

# Detail Commands
✓ /sprints → click → detail view
✓ /tasks → click → detail view
✓ Back button works

# Special Commands
✓ /todos
✓ /types
✓ /routing-info

# Edge Cases
✓ Unknown component → fallback
✓ Missing config → legacy mode
```

**Deliverable:**  
Test results document with screenshots

---

### Task 4.2: TypeScript Validation
**Estimated Time:** 15 minutes

**Description:**  
Ensure TypeScript compilation succeeds with no errors.

**Acceptance Criteria:**
- [ ] `npm run build` succeeds
- [ ] Zero TypeScript errors
- [ ] All imports resolve
- [ ] Type definitions correct

**Commands:**
```bash
npm run build
# Expected: Success, 0 errors
```

**Deliverable:**  
Build log confirmation

---

### Task 4.3: Browser Console Validation
**Estimated Time:** 20 minutes

**Description:**  
Verify backend responses contain proper config objects.

**Acceptance Criteria:**
- [ ] All commands return config object
- [ ] Config priority fields present
- [ ] UI config present
- [ ] Type config present

**Test Script:**
```javascript
// Run in browser console
async function testCommand(cmd) {
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
  console.log('✓ Config present:', !!result.config)
  console.log('✓ UI config:', result.config?.ui)
  console.log('✓ Type config:', result.config?.type)
  console.log('✓ Modal container:', result.config?.ui?.modal_container)
}

// Test multiple commands
await testCommand('/sprints')
await testCommand('/tasks')
await testCommand('/agents')
```

**Deliverable:**  
Console output screenshots

---

### Task 4.4: Code Quality Check
**Estimated Time:** 20 minutes

**Description:**  
Run linting and formatting checks.

**Acceptance Criteria:**
- [ ] Laravel Pint passes (PHP)
- [ ] ESLint passes (TypeScript)
- [ ] Code follows conventions
- [ ] No unused variables
- [ ] Proper indentation

**Commands:**
```bash
# PHP
vendor/bin/pint --dirty

# TypeScript (if configured)
npm run lint
```

**Deliverable:**  
Lint results confirmation

---

## Phase 5: Documentation

**Duration:** 1 hour  
**Risk Level:** Low  
**Dependencies:** Phase 4 complete

### Task 5.1: Update Inline Documentation
**Estimated Time:** 20 minutes

**Description:**  
Add/update JSDoc comments for all new functions.

**Acceptance Criteria:**
- [ ] All helper functions have JSDoc comments
- [ ] Parameter descriptions included
- [ ] Return type descriptions included
- [ ] Usage examples in comments

**File Changes:**
- `resources/js/islands/chat/CommandResultModal.tsx` - Add JSDoc comments

**Example:**
```typescript
/**
 * Determines which component to render based on backend config.
 * 
 * Priority order:
 * 1. config.ui.modal_container (explicit backend preference)
 * 2. config.ui.card_component (transformed to modal name)
 * 3. config.type.default_card_component (transformed)
 * 4. result.component (legacy field, backward compatibility)
 * 5. "UnifiedListModal" (fallback)
 * 
 * @param result - Command execution result with data and config
 * @returns Component name to render
 * 
 * @example
 * const name = getComponentName(result)
 * // Returns: "SprintListModal" or "UnifiedListModal"
 */
```

---

### Task 5.2: Create "Adding a New Command" Guide
**Estimated Time:** 20 minutes

**Description:**  
Document how to add new commands without code changes.

**Acceptance Criteria:**
- [ ] Step-by-step guide created
- [ ] Includes seeder examples
- [ ] Explains component naming conventions
- [ ] Shows config examples

**File:**  
`docs/type-command-ui/ADDING_NEW_COMMANDS.md`

**Content:**
- Database seeder updates
- Component naming requirements
- Config structure examples
- Testing checklist

---

### Task 5.3: Create "Adding a New Component" Guide
**Estimated Time:** 20 minutes

**Description:**  
Document how to create config-aware components.

**Acceptance Criteria:**
- [ ] Component interface template
- [ ] Config prop usage examples
- [ ] Best practices documented
- [ ] Testing guidelines included

**File:**  
`docs/type-command-ui/ADDING_NEW_COMPONENTS.md`

**Content:**
- Standard props interface
- Config usage patterns
- Backward compatibility tips
- Example component code

---

## Risk Mitigation

### Risk 1: Breaking Changes
**Likelihood:** Medium  
**Impact:** High  
**Mitigation:**
- Keep legacy `component` field support
- Extensive manual testing
- Deploy during low-traffic period
- Have rollback plan ready

### Risk 2: Performance Regression
**Likelihood:** Low  
**Impact:** Medium  
**Mitigation:**
- Profile render performance
- Optimize component map lookup
- Use React.memo for expensive components
- Monitor production metrics

### Risk 3: Missing Components
**Likelihood:** Low  
**Impact:** Low  
**Mitigation:**
- Fallback to UnifiedListModal
- Console warnings for debugging
- Comprehensive testing

---

## Definition of Done

### Code
- [ ] Switch statement removed
- [ ] Helper functions implemented
- [ ] Config priority system working
- [ ] 3+ components updated
- [ ] TypeScript compilation succeeds
- [ ] No console errors

### Testing
- [ ] All commands manually tested
- [ ] Detail views work correctly
- [ ] Edge cases handled
- [ ] Browser console validation complete
- [ ] TypeScript validation passes

### Documentation
- [ ] JSDoc comments added
- [ ] "Adding Commands" guide created
- [ ] "Adding Components" guide created
- [ ] README updated (if applicable)

### Deployment
- [ ] Code reviewed
- [ ] Tested in staging (if available)
- [ ] Rollback plan documented
- [ ] Deployment notes written

---

## Time Estimate Summary

| Phase | Tasks | Time |
|-------|-------|------|
| Phase 1: Foundation | 4 | 2h |
| Phase 2: Switch Replacement | 3 | 1h |
| Phase 3: Child Components | 4 | 3h |
| Phase 4: Testing | 4 | 2h |
| Phase 5: Documentation | 3 | 1h |
| **Total** | **18** | **9h** |

**Conservative Estimate:** 9-11 hours (with debugging/issues)  
**Optimistic Estimate:** 6-7 hours (smooth execution)  
**Recommended:** 2-3 work days (with breaks, reviews)

---

## Next Actions

1. **Review:** User approves task breakdown
2. **Prioritize:** Decide on must-have vs nice-to-have
3. **Sprint Planning:** Create orchestration sprint/tasks
4. **Assign:** Determine who does what
5. **Execute:** Start with Phase 1
