# Type + Command UI - Architecture Diagrams

**Visual guides to understand the system design**

---

## Current Architecture (Before)

```
┌─────────────────────────────────────────────────────────────┐
│                        User Interface                        │
│                     (Chat / Command Input)                   │
└───────────────────────────┬─────────────────────────────────┘
                            │
                            │ /sprints
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                    Backend (Laravel)                         │
│  ┌──────────────────────────────────────────────────────┐   │
│  │ CommandController                                    │   │
│  │  - Executes command                                  │   │
│  │  - Returns: { success, data, component, config }    │   │
│  └──────────────────────────────────────────────────────┘   │
└───────────────────────────┬─────────────────────────────────┘
                            │
                            │ Response JSON
                            ▼
┌─────────────────────────────────────────────────────────────┐
│               Frontend (CommandResultModal)                  │
│                                                              │
│  ❌ PROBLEM: 400+ LINE SWITCH STATEMENT                     │
│                                                              │
│  switch (result.component) {                                │
│    case 'SprintListModal':                                  │
│      return <SprintListModal sprints={data} />              │
│    case 'TaskListModal':                                    │
│      return <TaskListModal tasks={data} />                  │
│    case 'AgentProfileGridModal':                            │
│      return <AgentProfileGridModal agents={data} />         │
│    // ... 20+ more cases                                    │
│  }                                                           │
│                                                              │
│  Issues:                                                     │
│  - Hardcoded component mapping                              │
│  - Type-specific props (sprints, tasks, agents)            │
│  - Config object ignored                                    │
│  - Adding command = code change                             │
└─────────────────────────────────────────────────────────────┘
```

---

## New Architecture (After)

```
┌─────────────────────────────────────────────────────────────┐
│                        User Interface                        │
│                     (Chat / Command Input)                   │
└───────────────────────────┬─────────────────────────────────┘
                            │
                            │ /sprints
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                    Backend (Laravel)                         │
│  ┌──────────────────────────────────────────────────────┐   │
│  │ CommandController                                    │   │
│  │  - Looks up command in database                      │   │
│  │  - Executes handler class                            │   │
│  │  - Composes config (Type + Command)                  │   │
│  │  - Returns: { success, data, config }               │   │
│  └──────────────────────────────────────────────────────┘   │
│                                                              │
│  ┌──────────────────────────────────────────────────────┐   │
│  │ Config Object (Composed)                             │   │
│  │  {                                                   │   │
│  │    type: {                                           │   │
│  │      slug: "sprint",                                 │   │
│  │      default_card_component: "SprintCard"            │   │
│  │    },                                                │   │
│  │    ui: {                                             │   │
│  │      modal_container: "DataManagementModal",         │   │
│  │      layout_mode: "table"                            │   │
│  │    },                                                │   │
│  │    command: {                                        │   │
│  │      command: "/sprints",                            │   │
│  │      category: "Orchestration"                       │   │
│  │    }                                                 │   │
│  │  }                                                   │   │
│  └──────────────────────────────────────────────────────┘   │
└───────────────────────────┬─────────────────────────────────┘
                            │
                            │ Response JSON with Config
                            ▼
┌─────────────────────────────────────────────────────────────┐
│               Frontend (CommandResultModal)                  │
│                                                              │
│  ✅ SOLUTION: CONFIG-DRIVEN ROUTING                         │
│                                                              │
│  ┌────────────────────────────────────────────────────┐     │
│  │ 1. Component Registry (One-time setup)             │     │
│  │                                                     │     │
│  │ const COMPONENT_MAP = {                            │     │
│  │   'SprintListModal': SprintListModal,              │     │
│  │   'TaskListModal': TaskListModal,                  │     │
│  │   'UnifiedListModal': UnifiedListModal,            │     │
│  │   // ... all components                            │     │
│  │ }                                                   │     │
│  └────────────────────────────────────────────────────┘     │
│                                                              │
│  ┌────────────────────────────────────────────────────┐     │
│  │ 2. Smart Resolution (Config Priority)              │     │
│  │                                                     │     │
│  │ const componentName = getComponentName(result)     │     │
│  │   // Priority:                                     │     │
│  │   // 1. config.ui.modal_container                  │     │
│  │   // 2. config.ui.card_component (transformed)     │     │
│  │   // 3. config.type.default_card_component         │     │
│  │   // 4. result.component (legacy fallback)         │     │
│  │   // 5. "UnifiedListModal" (default)               │     │
│  └────────────────────────────────────────────────────┘     │
│                                                              │
│  ┌────────────────────────────────────────────────────┐     │
│  │ 3. Component Lookup                                │     │
│  │                                                     │     │
│  │ const Component = COMPONENT_MAP[componentName]     │     │
│  │   || COMPONENT_MAP['UnifiedListModal']             │     │
│  └────────────────────────────────────────────────────┘     │
│                                                              │
│  ┌────────────────────────────────────────────────────┐     │
│  │ 4. Props Builder (Standardized)                    │     │
│  │                                                     │     │
│  │ const props = {                                    │     │
│  │   isOpen: true,                                    │     │
│  │   onClose,                                         │     │
│  │   data: result.data,        // Generic            │     │
│  │   config: result.config,    // Backend config     │     │
│  │   sprints: result.data,     // Legacy compat      │     │
│  │   onRefresh,                                       │     │
│  │ }                                                  │     │
│  └────────────────────────────────────────────────────┘     │
│                                                              │
│  ┌────────────────────────────────────────────────────┐     │
│  │ 5. Render                                          │     │
│  │                                                     │     │
│  │ return <Component {...props} />                    │     │
│  └────────────────────────────────────────────────────┘     │
│                                                              │
│  Benefits:                                                   │
│  ✓ Config-driven (backend controls UI)                      │
│  ✓ Generic props (data, config)                             │
│  ✓ Backward compatible (legacy props still work)            │
│  ✓ Adding command = 0 code changes                          │
│  ✓ 400 lines → 200 lines                                    │
└─────────────────────────────────────────────────────────────┘
```

---

## Component Resolution Flow

```
┌──────────────────────────┐
│  User Executes Command   │
│       /sprints           │
└────────────┬─────────────┘
             │
             ▼
┌────────────────────────────────────────────────────┐
│           Backend Returns Result                   │
│  {                                                 │
│    success: true,                                  │
│    data: [...],                                    │
│    config: {                                       │
│      ui: { modal_container: "SprintListModal" },   │
│      type: { default_card_component: "SprintCard" }│
│    }                                               │
│  }                                                 │
└────────────┬───────────────────────────────────────┘
             │
             ▼
┌────────────────────────────────────────────────────┐
│     getComponentName(result)                       │
│                                                    │
│  ┌────────────────────────────────────────────┐   │
│  │ Check config.ui.modal_container?           │   │
│  │   ✓ "SprintListModal" → Return it          │   │
│  └────────────────────────────────────────────┘   │
│              │ Not found?                          │
│              ▼                                      │
│  ┌────────────────────────────────────────────┐   │
│  │ Check config.ui.card_component?            │   │
│  │   ✓ Transform: "SprintCard" → "SprintList  │   │
│  │     Modal"                                  │   │
│  └────────────────────────────────────────────┘   │
│              │ Not found?                          │
│              ▼                                      │
│  ┌────────────────────────────────────────────┐   │
│  │ Check config.type.default_card_component?  │   │
│  │   ✓ Transform: "SprintCard" → "SprintList  │   │
│  │     Modal"                                  │   │
│  └────────────────────────────────────────────┘   │
│              │ Not found?                          │
│              ▼                                      │
│  ┌────────────────────────────────────────────┐   │
│  │ Check result.component? (legacy)           │   │
│  │   ✓ Use it if present                      │   │
│  └────────────────────────────────────────────┘   │
│              │ Still not found?                    │
│              ▼                                      │
│  ┌────────────────────────────────────────────┐   │
│  │ Fallback to "UnifiedListModal"             │   │
│  └────────────────────────────────────────────┘   │
│                                                    │
│  Returns: "SprintListModal"                        │
└────────────┬───────────────────────────────────────┘
             │
             ▼
┌────────────────────────────────────────────────────┐
│     Lookup in COMPONENT_MAP                        │
│                                                    │
│  COMPONENT_MAP["SprintListModal"]                  │
│    → SprintListModal component                     │
│                                                    │
│  If not found:                                     │
│    → COMPONENT_MAP["UnifiedListModal"]             │
│    → Log warning                                   │
└────────────┬───────────────────────────────────────┘
             │
             ▼
┌────────────────────────────────────────────────────┐
│     buildComponentProps(result, componentName)     │
│                                                    │
│  Returns:                                          │
│  {                                                 │
│    isOpen: true,                                   │
│    onClose: fn,                                    │
│    data: result.data,          // Generic         │
│    sprints: result.data,       // Legacy compat   │
│    config: result.config,      // Backend config  │
│    onRefresh: fn,                                  │
│    onSprintSelect: fn,         // Smart handler   │
│  }                                                 │
└────────────┬───────────────────────────────────────┘
             │
             ▼
┌────────────────────────────────────────────────────┐
│     Render Component                               │
│                                                    │
│  <SprintListModal                                  │
│    isOpen={true}                                   │
│    onClose={fn}                                    │
│    data={[...]}                                    │
│    config={{...}}                                  │
│    sprints={[...]}  // Legacy                      │
│    onSprintSelect={fn}                             │
│  />                                                │
└────────────────────────────────────────────────────┘
```

---

## Data Flow: Before vs After

### Before (Hardcoded)

```
Backend                         Frontend
───────                         ────────

Command DB      ──────┐
                      ├──→ { component: "SprintListModal",
Type DB         ──────┘        data: [...] }
                                      │
                                      ▼
                          ┌───────────────────────────┐
                          │ CommandResultModal        │
                          │                           │
                          │ switch(component) {       │
                          │   case "SprintListModal": │
                          │     return <Sprint... />  │
                          │   case "TaskListModal":   │
                          │     return <Task... />    │
                          │   // ... 20+ more         │
                          │ }                         │
                          └───────────────────────────┘
                                      │
                                      ▼
                          ┌───────────────────────────┐
                          │ SprintListModal           │
                          │   Props: { sprints }      │
                          └───────────────────────────┘
```

### After (Config-Driven)

```
Backend                         Frontend
───────                         ────────

Command DB ──┐
             ├─→ Compose Config
Type DB   ───┘        │
                      ▼
          { 
            config: {
              ui: { modal_container: "..." },
              type: { ... },
              command: { ... }
            },
            data: [...]
          }
              │
              ▼
  ┌───────────────────────────────────────┐
  │ CommandResultModal                    │
  │                                       │
  │ const name = getComponentName(result) │
  │   // Uses config priority             │
  │                                       │
  │ const Component = COMPONENT_MAP[name] │
  │   // Simple lookup                    │
  │                                       │
  │ const props = buildComponentProps()   │
  │   // Standardized props               │
  │                                       │
  │ return <Component {...props} />       │
  └───────────────────────────────────────┘
              │
              ▼
  ┌───────────────────────────────────────┐
  │ SprintListModal                       │
  │   Props: {                            │
  │     data,          // Generic         │
  │     config,        // Config-aware    │
  │     sprints,       // Legacy compat   │
  │   }                                   │
  │                                       │
  │ Uses config.ui.layout_mode for render│
  └───────────────────────────────────────┘
```

---

## Config Priority Waterfall

```
┌──────────────────────────────────────────────────┐
│         Config Priority Waterfall                │
└──────────────────────────────────────────────────┘

Priority 1: config.ui.modal_container
───────────────────────────────────────
Explicit backend preference
Most specific, highest priority

Example:
  config.ui.modal_container = "DataManagementModal"
  → Use DataManagementModal ✓

         │
         │ Not set?
         ▼

Priority 2: config.ui.card_component
───────────────────────────────────────
UI-level component (transformed)

Example:
  config.ui.card_component = "SprintCard"
  → Transform to "SprintListModal" ✓

         │
         │ Not set?
         ▼

Priority 3: config.type.default_card_component
───────────────────────────────────────
Type-level default (transformed)

Example:
  config.type.default_card_component = "SprintCard"
  → Transform to "SprintListModal" ✓

         │
         │ Not set?
         ▼

Priority 4: result.component (legacy)
───────────────────────────────────────
Backward compatibility field

Example:
  result.component = "SprintListModal"
  → Use SprintListModal ✓

         │
         │ Not set?
         ▼

Priority 5: UnifiedListModal (fallback)
───────────────────────────────────────
Generic fallback component
Always works with any data

Example:
  Nothing set
  → Use UnifiedListModal ✓
  → Log warning for debugging
```

---

## Component Transformation

```
Card Component Name      Transform      Modal Component Name
───────────────────      ─────────      ────────────────────

"SprintCard"         ─────────────→    "SprintListModal"
                     replace('Card',    
                     'ListModal')       

"TaskCard"           ─────────────→    "TaskListModal"

"AgentProfileCard"   ─────────────→    "AgentProfileListModal"

"ProjectCard"        ─────────────→    "ProjectListModal"


Special Cases:
──────────────

"DataManagementModal"   No transform   "DataManagementModal"
(Already a modal)                      (Use as-is)

"AgentDashboard"        No transform   "AgentDashboard"
(Dashboard component)                  (Use as-is, wrap in Dialog)

"UnifiedListModal"      No transform   "UnifiedListModal"
(Fallback)                             (Use as-is)
```

---

## Comparison Table

| Aspect | Before (Hardcoded) | After (Config-Driven) |
|--------|-------------------|----------------------|
| **Lines of Code** | 400+ lines | ~200 lines |
| **Component Resolution** | Hardcoded switch | Config priority system |
| **Adding New Command** | Code change required | Zero code changes |
| **Props** | Type-specific (sprints, tasks) | Generic (data, config) |
| **Config Usage** | Ignored | Fully leveraged |
| **Fallback** | Error | UnifiedListModal |
| **Debugging** | Difficult | Helpful logs |
| **Testing** | Hard to test switch | Easy to test helpers |
| **Maintainability** | Low | High |
| **Flexibility** | Low | High |
| **Developer Experience** | Confusing | Clear patterns |

---

## Rollback Scenario

```
Production Issue Detected
         │
         ▼
┌────────────────────────┐
│ Revert Single File     │
│                        │
│ git revert <commit>    │
│                        │
│ File:                  │
│ CommandResultModal.tsx │
└────────┬───────────────┘
         │
         ▼
┌────────────────────────┐
│ Old Switch Returns     │
│                        │
│ Backend still sends    │
│ both component field   │
│ and config (compat)    │
└────────┬───────────────┘
         │
         ▼
┌────────────────────────┐
│ System Working Again   │
│                        │
│ Zero data loss         │
│ Zero breaking changes  │
└────────────────────────┘
```

---

## Future Enhancement: Lazy Loading

```
Current (All Loaded Upfront)
────────────────────────────

import { SprintListModal } from '...'
import { TaskListModal } from '...'
// ... 20+ imports

const COMPONENT_MAP = {
  'SprintListModal': SprintListModal,
  'TaskListModal': TaskListModal,
  // ...
}

Bundle Size: ~500KB
Load Time: 2s


Future (Lazy Loaded)
────────────────────

const COMPONENT_MAP = {
  'SprintListModal': 
    React.lazy(() => import('@/components/orchestration/SprintListModal')),
  'TaskListModal':
    React.lazy(() => import('@/components/orchestration/TaskListModal')),
  // ...
}

// Wrap in Suspense
<React.Suspense fallback={<LoadingSpinner />}>
  <Component {...props} />
</React.Suspense>

Bundle Size: ~100KB initial, ~50KB per component
Load Time: 0.5s initial, 0.2s per component
```

---

**Visual aids complete** - Use these diagrams to understand and explain the architecture.
