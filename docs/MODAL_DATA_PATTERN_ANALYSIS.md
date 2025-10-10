# Modal Data Pattern Analysis

## Current State Analysis

### Data Patterns Identified

After analyzing all modals and commands, there are **3 distinct data patterns**:

#### Pattern 1: Direct Props (Most Common - 90% of modals)
**Format:** `{ sprints: [...] }` or `{ tasks: [...] }` or `{ bookmarks: [...] }`

**Examples:**
- `SprintListModal` expects: `sprints: Sprint[]`
- `TaskListModal` expects: `tasks: Task[]`
- `BookmarkListModal` expects: `bookmarks: BookmarkItem[]`
- `ProjectListModal` expects: `projects: Project[]`
- `VaultListModal` expects: `vaults: Vault[]`

**Backend Pattern:**
```php
return $this->respond(['items' => $data]);
// Frontend receives: result.data.items
```

**Routing in CommandResultModal:**
```typescript
const dataKey = getDataPropName(componentName); // 'sprints', 'tasks', etc.
<Component {...{ [dataKey]: result.data }} />
```

---

#### Pattern 2: Nested Structure (UnifiedListModal only)
**Format:** `{ data: { items: [...], typeConfig: {...} } }`

**Example:**
- `UnifiedListModal` expects: `data: { items: [], typeConfig: {} }`

**Backend Pattern:**
```php
return $this->respond(['items' => $data]);
// Frontend wraps: { data: result.data, typeConfig: result.config }
```

---

#### Pattern 3: panelData.fragments (TodoManagementModal ONLY)
**Format:** `{ panelData: { fragments: [...] } }`

**Example:**
- `TodoManagementModal` uses `useTodoData` hook
- Hook looks for: `result.panelData.fragments`

**Why it's different:**
- TodoManagementModal is self-contained with its own data hook
- `useTodoData` calls `/todo list` command internally
- Expects fragments in a specific nested format
- Has complex transformation logic (fragment → TodoItem)

**Backend Pattern (current workaround):**
```php
$response = $this->respond(['items' => $data]);
$response['panelData'] = ['fragments' => $data]; // Manual addition
return $response;
```

---

## The Problem

### Issue 1: TodoCommand is Special-Cased
- Only command that needs `panelData` structure
- Requires manual addition after `$this->respond()`
- Breaks the unified pattern
- Future commands might break if they don't know about this

### Issue 2: Inconsistent Frontend Expectations
- Most modals: expect direct prop (sprints, tasks, bookmarks)
- UnifiedListModal: expects nested structure (data.items)
- TodoManagementModal: expects panelData.fragments

### Issue 3: CommandResultModal Routing Fragile
- Hardcoded prop name mapping (`getDataPropName()`)
- Assumes `result.data` contains the array
- Special case for UnifiedListModal
- Will need special case for TodoManagementModal

---

## Recommendation: Refactor TodoManagementModal

### Why TodoManagementModal is the Outlier

1. **Only modal in `/islands/chat/`** - All others in `/components/`
2. **Has dedicated data hook** - `useTodoData` does its own API calls
3. **Complex transformation** - Fragment → TodoItem conversion
4. **Nested panelData structure** - Different from all other modals

### Root Cause Analysis

TodoManagementModal was likely built before the unified pattern was established. It:
- Manages its own data loading (doesn't rely on command response)
- Has CRUD operations built-in
- More like a "mini-application" than a simple list modal

---

## Solution Options

### Option A: Refactor TodoManagementModal (RECOMMENDED)

**Make it follow the standard pattern like other modals:**

```typescript
// NEW: Standard pattern
interface TodoManagementModalProps {
  isOpen: boolean
  onClose: () => void
  todos: TodoItem[]  // ← Direct prop like other modals
  onRefresh?: () => void
}

export function TodoManagementModal({ isOpen, onClose, todos, onRefresh }: TodoManagementModalProps) {
  // Receive transformed data, don't fetch/transform internally
}
```

**Backend (TodoCommand):**
```php
public function handle(): array
{
    $fragments = $this->getTodos();
    
    // Transform to TodoItem format in backend
    $todos = array_map(fn($f) => $this->transformToTodoItem($f), $fragments);
    
    return $this->respond(['items' => $todos]);
}
```

**Benefits:**
- ✅ Consistent with all other modals
- ✅ No special cases in BaseCommand
- ✅ Data transformation in one place (backend)
- ✅ CommandResultModal routing works automatically

**Effort:** 1-2 hours (move transformation logic, update hook)

---

### Option B: Add panelData to BaseCommand (NOT RECOMMENDED)

**Add panelData wrapper in BaseCommand:**

```php
// In BaseCommand::respond()
protected function respond(array $data, ?string $component = null): array
{
    $response = [
        'type' => $this->getResponseType(),
        'data' => $data,
        'panelData' => $data, // ← Add for backward compatibility
    ];
    // ...
}
```

**Problems:**
- ❌ Doubles data payload size
- ❌ Maintains inconsistency
- ❌ Doesn't solve the root problem
- ❌ Future developers confused by dual structure

---

### Option C: Create DataAdapter in Frontend (MIDDLE GROUND)

**Add adapter in CommandResultModal:**

```typescript
const adaptDataForComponent = (result: CommandResult, componentName: string) => {
  // Special cases
  if (componentName === 'TodoManagementModal') {
    return { panelData: { fragments: result.data.items } };
  }
  
  if (componentName === 'UnifiedListModal') {
    return { data: result.data };
  }
  
  // Standard pattern - map to component prop name
  const dataKey = getDataPropName(componentName);
  return { [dataKey]: result.data.items };
};
```

**Benefits:**
- ✅ Centralizes special cases
- ✅ Backend stays clean
- ✅ Easy to add more patterns

**Problems:**
- ⚠️ Still maintains inconsistency
- ⚠️ Frontend becomes more complex

**Effort:** 30 minutes

---

## Recommended Approach

### Phase 1: Immediate Fix (Option C - 30 min)
Add data adapter to handle current inconsistencies:
- Centralizes the panelData special case
- Prevents breaking other commands
- Documents the pattern clearly

### Phase 2: Long-term Fix (Option A - 1-2 hours)
Refactor TodoManagementModal to standard pattern:
- Move data transformation to TodoCommand
- Make it accept `todos` prop like other modals
- Remove `useTodoData` hook or simplify it
- Align with unified architecture

---

## Implementation Plan

### Step 1: Document Current State
- ✅ Identify all 3 patterns (done in this doc)
- ✅ Find why TodoManagementModal is different
- ✅ Propose solutions

### Step 2: Implement Adapter (30 min)

**File:** `resources/js/islands/chat/CommandResultModal.tsx`

```typescript
// Add after getComponentName function
const adaptDataForComponent = (
  result: CommandResult, 
  componentName: string
): Record<string, any> => {
  // Special case: TodoManagementModal expects panelData.fragments
  if (componentName === 'TodoManagementModal') {
    return {
      panelData: {
        fragments: result.data?.items || result.data?.fragments || []
      }
    };
  }
  
  // Special case: UnifiedListModal expects nested structure
  if (componentName === 'UnifiedListModal') {
    return {
      data: result.data
    };
  }
  
  // Standard pattern: direct prop with plural name
  const dataKey = getDataPropName(componentName);
  return {
    [dataKey]: result.data?.items || result.data || []
  };
};

// Update getComponentProps to use adapter
const getComponentProps = (result: CommandResult, componentName: string) => {
  const adaptedData = adaptDataForComponent(result, componentName);
  
  return {
    isOpen: true,
    onClose,
    ...adaptedData,
    config: result.config,
    onRefresh: () => console.log('Refresh requested'),
  };
};
```

### Step 3: Remove TodoCommand Special Case

**File:** `app/Commands/TodoCommand.php`

```php
public function handle(): array
{
    $fragments = $this->getTodos();
    
    // Just use standard respond - adapter handles the rest
    return $this->respond(['items' => $fragments]);
}
```

### Step 4: Test All Commands

```bash
# Test each command type
/sprints    # Standard pattern
/tasks      # Standard pattern
/bookmarks  # Standard pattern
/todos      # Special case via adapter
/notes      # UnifiedListModal via adapter
```

### Step 5: Document Patterns (add to this file)

Add section explaining when to use each pattern and how the adapter works.

---

## Pattern Documentation (For Future Developers)

### When to Use Each Pattern

**Pattern 1: Direct Props (DEFAULT)**
- Use for: All new list/detail modals
- Example: `/sprints`, `/tasks`, `/bookmarks`
- Backend: `return $this->respond(['items' => $data]);`
- Frontend: Component receives direct prop (sprints, tasks, etc.)

**Pattern 2: UnifiedListModal (GENERIC)**
- Use for: Types without dedicated modal components
- Backend: Same as Pattern 1
- Frontend: Adapter wraps in `{ data: result.data }`

**Pattern 3: Legacy (AVOID)**
- Use for: Only TodoManagementModal (legacy)
- Backend: Same as Pattern 1
- Frontend: Adapter converts to panelData.fragments

### Adding New Commands

1. Create command extending BaseCommand
2. Use `$this->respond(['items' => $data])`
3. Add type to TypesSeeder
4. Add command to CommandsSeeder
5. Done! Adapter handles routing automatically

---

## Migration Path for TodoManagementModal

### Goal
Make TodoManagementModal follow Pattern 1 (standard).

### Changes Needed

**1. Update TodoCommand (backend):**
```php
public function handle(): array
{
    $fragments = $this->getTodos();
    
    // Transform in backend
    $todos = array_map(function ($fragment) {
        return [
            'id' => $fragment['id'],
            'fragment_id' => $fragment['id'],
            'title' => $this->cleanTitle($fragment['title']),
            'message' => $fragment['message'],
            'status' => $this->mapStatus($fragment['state']),
            'priority' => $fragment['metadata']['priority'] ?? 'medium',
            'tags' => $this->parseTags($fragment['tags']),
            'created_at' => $fragment['created_at'],
            'is_pinned' => $fragment['pinned'] ?? false,
        ];
    }, $fragments);
    
    return $this->respond(['items' => $todos]);
}
```

**2. Update TodoManagementModal (frontend):**
```typescript
interface TodoManagementModalProps {
  isOpen: boolean
  onClose: () => void
  todos: TodoItem[]
  onRefresh?: () => void
}

export function TodoManagementModal({ 
  isOpen, 
  onClose, 
  todos: initialTodos,
  onRefresh 
}: TodoManagementModalProps) {
  const [todos, setTodos] = useState(initialTodos);
  
  // Keep CRUD operations in useTodoData hook
  const { createTodo, updateTodo, deleteTodo } = useTodoData();
  
  // Remove loadTodos - data comes from props
}
```

**3. Simplify useTodoData hook:**
```typescript
// Remove loadTodos, keep only mutations
export function useTodoData() {
  return {
    createTodo: async (text: string) => { /* POST */ },
    updateTodo: async (id: string, updates: any) => { /* PATCH */ },
    deleteTodo: async (id: string) => { /* DELETE */ },
    toggleStatus: async (id: string) => { /* PATCH */ },
  };
}
```

**Effort:** 1-2 hours
**Impact:** Low (contained to todo functionality)
**Benefit:** Eliminates special case, aligns with architecture

---

## Summary

### Current Patterns
1. **Direct Props** - 90% of modals (sprints, tasks, bookmarks, etc.)
2. **Nested Structure** - UnifiedListModal only
3. **panelData.fragments** - TodoManagementModal only (OUTLIER)

### The Problem
- TodoManagementModal requires special handling
- Breaks unified command pattern
- Confusing for future developers

### Immediate Solution (30 min)
- Add data adapter in CommandResultModal
- Centralize special case handling
- Remove manual panelData from TodoCommand

### Long-term Solution (1-2 hours)
- Refactor TodoManagementModal to Pattern 1
- Move data transformation to backend
- Eliminate the outlier

### Recommendation
**Do both:**
1. Implement adapter now (prevents future bugs)
2. Schedule TodoManagementModal refactor for next sprint

---

**Created:** October 10, 2025  
**Status:** Analysis Complete, Awaiting Implementation Decision
