# Todo Management Modal Context

## Current Todo System Architecture

### Backend Components
```php
// Fragment Model (app/Models/Fragment.php)
- Core data model with todo relationship
- JSON state field for todo-specific data: {'status': 'open', 'completed_at': null}
- Scopes: todosByStatus(), openTodos(), completedTodos(), overdueTodos()
- Performance indexes on state->>'status' and state->>'priority'

// Todo Model (app/Models/Todo.php)
- BelongsTo Fragment relationship
- State casting to array for JSON operations
- Fragment-based primary key (fragment_id)

// TodoCommand (app/Actions/Commands/TodoCommand.php)
- Handles /todo creation, listing, completion operations
- Returns CommandResponse with panelData for UI display
- Supports search, status filters, tag filters, limit parameters
```

### Current UI Integration
```typescript
// CommandResultModal (resources/js/islands/chat/CommandResultModal.tsx)
- Existing modal pattern using Shadcn Dialog components
- ReactMarkdown rendering with custom component styling
- ScrollArea for content overflow handling
- Proper modal state management and accessibility

// Chat Integration
- Commands executed via /api/commands/execute endpoint
- Results displayed in CommandResultModal
- Todo results currently shown as fragments list in panelData
```

## Target Modal Architecture

### Component Structure
```
TodoManagementModal/
├── TodoManagementModal.tsx     # Main modal component
├── TodoDataTable.tsx           # Table with search/filter/sort
├── TodoRow.tsx                 # Individual todo row with actions
├── TodoFilters.tsx             # Filter controls (status, tags, date)
├── TodoContextMenu.tsx         # Right-click/... menu actions
└── hooks/
    ├── useTodoData.tsx         # Data fetching and caching
    ├── useTodoFilters.tsx      # Filter state management
    └── useTodoDragDrop.tsx     # Drag-drop functionality
```

### Required Shadcn Components
```bash
# Install these Shadcn components if not present:
npx shadcn-ui@latest add table
npx shadcn-ui@latest add dropdown-menu
npx shadcn-ui@latest add input
npx shadcn-ui@latest add select
npx shadcn-ui@latest add calendar
npx shadcn-ui@latest add date-picker
```

### API Integration Points
```typescript
// Existing endpoints to leverage:
POST /api/commands/execute
  - Command: "todo list status:open limit:50"
  - Command: "todo complete:1" 
  - Command: "todo create:Fix bug #urgent"

// New endpoints may be needed:
GET /api/todos/search?q=query&status=open&tags=urgent&limit=50
PUT /api/todos/{id}/reorder
PUT /api/todos/{id}/toggle-status
PUT /api/todos/{id}/pin
```

## Data Flow Architecture

### State Management
```typescript
interface TodoModalState {
  isOpen: boolean
  todos: TodoItem[]
  filters: {
    search: string
    status: 'all' | 'open' | 'completed'
    tags: string[]
    dateRange: { from: Date, to: Date }
    project: string
  }
  sort: {
    field: 'created_at' | 'status' | 'priority'
    direction: 'asc' | 'desc'
  }
  loading: boolean
  error: string | null
}

interface TodoItem {
  id: string
  fragment_id: string
  title: string
  message: string
  status: 'open' | 'completed'
  priority: 'low' | 'medium' | 'high'
  tags: string[]
  project?: string
  created_at: string
  completed_at?: string
  due_at?: string
  order: number
  is_pinned: boolean
}
```

### Search & Filter Logic
```typescript
// Real-time search implementation
const filteredTodos = useMemo(() => {
  return todos.filter(todo => {
    // Search in title and message
    if (filters.search && !todo.title.toLowerCase().includes(filters.search.toLowerCase()) 
        && !todo.message.toLowerCase().includes(filters.search.toLowerCase())) {
      return false
    }
    
    // Status filter
    if (filters.status !== 'all' && todo.status !== filters.status) {
      return false
    }
    
    // Tag filter
    if (filters.tags.length > 0 && !filters.tags.some(tag => todo.tags.includes(tag))) {
      return false
    }
    
    // Date range filter
    if (filters.dateRange.from && new Date(todo.created_at) < filters.dateRange.from) {
      return false
    }
    
    return true
  })
}, [todos, filters])
```

## Drag & Drop Implementation

### Using @dnd-kit/sortable
```typescript
// Installation required:
npm install @dnd-kit/core @dnd-kit/sortable @dnd-kit/utilities

// Component structure:
<DndContext onDragEnd={handleDragEnd}>
  <SortableContext items={todoIds} strategy={verticalListSortingStrategy}>
    <Table>
      {todos.map(todo => (
        <SortableTodoRow key={todo.id} todo={todo} />
      ))}
    </Table>
  </SortableContext>
</DndContext>
```

## Context Menu Actions

### Action Definitions
```typescript
interface TodoAction {
  id: string
  label: string
  icon: React.ComponentType
  handler: (todo: TodoItem) => void
  disabled?: (todo: TodoItem) => boolean
}

const todoActions: TodoAction[] = [
  {
    id: 'toggle-pin',
    label: todo => todo.is_pinned ? 'Unpin' : 'Pin',
    icon: Pin,
    handler: handleTogglePin
  },
  {
    id: 'edit',
    label: 'Edit',
    icon: Edit,
    handler: handleEdit
  },
  {
    id: 'move-to-project',
    label: 'Move to Project',
    icon: FolderOpen,
    handler: handleMoveToProject
  },
  {
    id: 'set-reminder',
    label: 'Set Reminder',
    icon: Bell,
    handler: handleSetReminder
  },
  {
    id: 'duplicate',
    label: 'Duplicate',
    icon: Copy,
    handler: handleDuplicate
  },
  {
    id: 'delete',
    label: 'Delete',
    icon: Trash,
    handler: handleDelete,
    className: 'text-destructive'
  }
]
```

## Performance Considerations

### Virtual Scrolling
```typescript
// For large todo lists (>100 items), implement virtual scrolling:
import { useVirtualizer } from '@tanstack/react-virtual'

const virtualizer = useVirtualizer({
  count: filteredTodos.length,
  getScrollElement: () => tableContainerRef.current,
  estimateSize: () => 50, // Estimated row height
})
```

### Optimistic Updates
```typescript
// Implement optimistic updates for better UX:
const handleToggleStatus = async (todoId: string) => {
  // Optimistically update UI
  setTodos(prev => prev.map(todo => 
    todo.id === todoId 
      ? { ...todo, status: todo.status === 'open' ? 'completed' : 'open' }
      : todo
  ))
  
  try {
    await api.toggleTodoStatus(todoId)
  } catch (error) {
    // Revert on error
    setTodos(prev => prev.map(todo => 
      todo.id === todoId 
        ? { ...todo, status: todo.status === 'completed' ? 'open' : 'completed' }
        : todo
    ))
    showErrorToast('Failed to update todo status')
  }
}
```

## Integration Points

### Command System Integration
```typescript
// Integration with existing chat command system:
export const useTodoCommands = () => {
  const executeCommand = async (command: string) => {
    const response = await fetch('/api/commands/execute', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ command })
    })
    return response.json()
  }
  
  return {
    createTodo: (text: string, tags: string[]) => 
      executeCommand(`todo ${text} ${tags.map(t => `#${t}`).join(' ')}`),
    completeTodo: (id: string) => 
      executeCommand(`todo complete:${id}`),
    listTodos: (status?: string, limit?: number) => 
      executeCommand(`todo list ${status ? `status:${status}` : ''} ${limit ? `limit:${limit}` : ''}`)
  }
}
```

### Accessibility Requirements
- Keyboard navigation for all interactive elements
- Screen reader support with proper ARIA labels
- Focus management for modal and dropdown interactions
- High contrast mode compatibility
- Reduced motion preferences support

### Responsive Design Breakpoints
- Mobile (< 640px): Single column layout, simplified filters
- Tablet (640px - 1024px): Condensed table view, touch-optimized
- Desktop (> 1024px): Full table with all columns and features
- Large (> 1280px): Expanded layout with sidebar integration