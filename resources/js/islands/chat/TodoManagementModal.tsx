import React, { useState, useEffect, useMemo, useRef } from 'react'
import { useTodoData, type TodoItem, type TodoFilters } from '@/hooks/useTodoData'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { ScrollArea } from '@/components/ui/scroll-area'
import { Badge } from '@/components/ui/badge'
import { Checkbox } from '@/components/ui/checkbox'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { LoadingSpinner } from '@/components/ui/loading-spinner'
import { Search, Filter, MoreHorizontal, Plus, Check, X, ChevronDown, ChevronRight, Pin } from 'lucide-react'

interface TodoFiltersState {
  search: string
  status: 'all' | 'open' | 'completed'
  priority: 'all' | 'low' | 'medium' | 'high' | 'urgent'
  tags: string[]
}

interface TodoManagementModalProps {
  isOpen: boolean
  onClose: () => void
}

// Utility functions
const handleDeleteTodo = async (todoId: string, deleteFn: (id: string) => Promise<void>) => {
  if (confirm('Are you sure you want to delete this todo?')) {
    try {
      await deleteFn(todoId)
    } catch (err) {
      console.error('Error deleting todo:', err)
    }
  }
}

// TodoRow component for rendering individual todos
const TodoRow = React.memo(({ 
  todo, 
  isExpanded, 
  onToggleExpanded, 
  onToggleStatus, 
  onDelete, 
  getPriorityBadgeVariant, 
  getStatusCheckbox 
}: {
  todo: TodoItem
  isExpanded: boolean
  onToggleExpanded: (todoId: string) => void
  onToggleStatus: (todoId: string) => void
  onDelete: (todoId: string) => void
  getPriorityBadgeVariant: (priority: string) => any
  getStatusCheckbox: (status: string, onToggle: () => void) => React.ReactNode
}) => (
  <TableRow key={todo.id} className="hover:bg-muted/50">
    <TableCell className="w-8">
      <div className="flex items-center justify-center p-1">
        {getStatusCheckbox(todo.status, () => onToggleStatus(todo.id))}
      </div>
    </TableCell>
    <TableCell className="min-w-0 flex-1">
      <div className="flex flex-col space-y-1">
        <div className="flex items-center gap-2">
          {todo.is_pinned && <Pin className="h-3 w-3 text-blue-600" />}
          <span className={`font-medium text-sm ${todo.status === 'completed' ? 'line-through text-muted-foreground' : ''}`}>
            {todo.title}
          </span>
          <Button
            variant="ghost"
            size="icon"
            className="h-6 w-6 ml-1 hover:bg-gray-100 text-gray-600 hover:text-black"
            onClick={() => onToggleExpanded(todo.id)}
            aria-label={`${isExpanded ? 'Collapse' : 'Expand'} todo details`}
          >
            {isExpanded ? <ChevronDown className="h-3 w-3" /> : <ChevronRight className="h-3 w-3" />}
          </Button>
          <Badge variant={getPriorityBadgeVariant(todo.priority)} className="text-xs hidden sm:inline-flex ml-auto">
            {todo.priority}
          </Badge>
        </div>
        
        {/* Tags row - always visible on second line */}
        {todo.tags.length > 0 && (
          <div className="flex flex-wrap gap-1">
            {todo.tags.slice(0, 3).map(tag => (
              <Badge key={tag} variant="outline" className="text-xs">
                {tag}
              </Badge>
            ))}
            {todo.tags.length > 3 && (
              <Badge variant="outline" className="text-xs">
                +{todo.tags.length - 3}
              </Badge>
            )}
          </div>
        )}
        
        {/* Expanded content */}
        {isExpanded && (
          <div className="pt-2 space-y-2 border-t border-muted">
            <p className="text-sm text-muted-foreground">
              {todo.message}
            </p>
            <div className="flex items-center gap-4 text-xs text-muted-foreground">
              <span>Created: {new Date(todo.created_at).toLocaleDateString()}</span>
              {todo.completed_at && (
                <span>Completed: {new Date(todo.completed_at).toLocaleDateString()}</span>
              )}
              {todo.due_at && (
                <span>Due: {new Date(todo.due_at).toLocaleDateString()}</span>
              )}
            </div>
          </div>
        )}
      </div>
    </TableCell>
    <TableCell className="w-12">
      <DropdownMenu>
        <DropdownMenuTrigger asChild>
          <Button 
            variant="ghost" 
            size="sm" 
            className="h-8 w-8 p-0 focus:ring-2 focus:ring-ring"
            aria-label="Todo options"
          >
            <MoreHorizontal className="h-4 w-4" />
          </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end">
          <DropdownMenuItem>Edit</DropdownMenuItem>
          <DropdownMenuItem>Pin</DropdownMenuItem>
          <DropdownMenuItem>Duplicate</DropdownMenuItem>
          <DropdownMenuItem 
            className="text-destructive"
            onClick={() => onDelete(todo.id)}
          >
            Delete
          </DropdownMenuItem>
        </DropdownMenuContent>
      </DropdownMenu>
    </TableCell>
  </TableRow>
))

export function TodoManagementModal({ isOpen, onClose }: TodoManagementModalProps) {
  const { todos, loading, error, loadTodos, toggleTodoStatus, createTodo, deleteTodo } = useTodoData()
  const [filters, setFilters] = useState<TodoFiltersState>({
    search: '',
    status: 'all',
    priority: 'all',
    tags: [],
  })
  const [showNewTodo, setShowNewTodo] = useState(false)
  const [newTodoText, setNewTodoText] = useState('')
  const [expandedTodos, setExpandedTodos] = useState<Set<string>>(new Set())
  const searchInputRef = useRef<HTMLInputElement>(null)

  // Load todos when modal opens and focus search
  useEffect(() => {
    if (isOpen) {
      const todoFilters: TodoFilters = {
        status: filters.status,
        search: filters.search || undefined,
        tags: filters.tags.length > 0 ? filters.tags : undefined,
        limit: 100,
      }
      loadTodos(todoFilters)
      
      // Focus search input after modal opens
      setTimeout(() => {
        searchInputRef.current?.focus()
      }, 100)
    }
  }, [isOpen, loadTodos])

  // Reload todos when status/tags filters change (not search - that's client-side)
  useEffect(() => {
    if (isOpen) {
      const todoFilters: TodoFilters = {
        status: filters.status,
        // Don't send search to server - handle client-side for instant results
        tags: filters.tags.length > 0 ? filters.tags : undefined,
        limit: 100,
      }
      loadTodos(todoFilters)
    }
  }, [filters.status, filters.tags, isOpen, loadTodos])

  // Keyboard shortcuts
  useEffect(() => {
    if (!isOpen) return

    const handleKeyDown = (e: KeyboardEvent) => {
      if (e.key === 'Escape' && !showNewTodo) {
        onClose()
      } else if (e.key === 'n' && (e.ctrlKey || e.metaKey)) {
        e.preventDefault()
        setShowNewTodo(true)
      }
    }

    document.addEventListener('keydown', handleKeyDown)
    return () => document.removeEventListener('keydown', handleKeyDown)
  }, [isOpen, showNewTodo, onClose])

  // Filter todos based on current filters
  const filteredTodos = useMemo(() => {
    return todos.filter(todo => {
      // Search filter
      if (filters.search) {
        const searchLower = filters.search.toLowerCase()
        if (!todo.title.toLowerCase().includes(searchLower) && 
            !todo.message.toLowerCase().includes(searchLower)) {
          return false
        }
      }

      // Status filter
      if (filters.status !== 'all') {
        if (filters.status === 'open' && !['open', 'in_progress', 'blocked'].includes(todo.status)) {
          return false
        }
        if (filters.status === 'completed' && todo.status !== 'completed') {
          return false
        }
      }

      // Priority filter
      if (filters.priority !== 'all' && todo.priority !== filters.priority) {
        return false
      }

      // Tags filter
      if (filters.tags.length > 0) {
        if (!filters.tags.some(tag => todo.tags.includes(tag))) {
          return false
        }
      }

      return true
    })
  }, [todos, filters])

  // Separate pinned and regular todos
  const pinnedTodos = useMemo(() => {
    return filteredTodos.filter(todo => todo.is_pinned)
  }, [filteredTodos])

  const regularTodos = useMemo(() => {
    return filteredTodos.filter(todo => !todo.is_pinned)
  }, [filteredTodos])

  const handleToggleStatus = async (todoId: string) => {
    try {
      await toggleTodoStatus(todoId)
    } catch (err) {
      console.error('Error toggling todo status:', err)
    }
  }

  const handleCreateTodo = async () => {
    if (!newTodoText.trim()) return
    
    try {
      await createTodo(newTodoText.trim())
      setNewTodoText('')
      setShowNewTodo(false)
    } catch (err) {
      console.error('Error creating todo:', err)
    }
  }

  const toggleExpanded = (todoId: string) => {
    setExpandedTodos(prev => {
      const newSet = new Set(prev)
      if (newSet.has(todoId)) {
        newSet.delete(todoId)
      } else {
        newSet.add(todoId)
      }
      return newSet
    })
  }

  const getPriorityBadgeVariant = (priority: string) => {
    switch (priority) {
      case 'urgent': return 'destructive'
      case 'high': return 'default'
      case 'medium': return 'secondary'
      case 'low': return 'outline'
      default: return 'outline'
    }
  }

  const getStatusCheckbox = (status: string, onToggle: () => void) => {
    const isCompleted = status === 'completed'
    const isInProgress = status === 'in_progress'
    const isBlocked = status === 'blocked'
    
    return (
      <Checkbox
        checked={isCompleted}
        onCheckedChange={onToggle}
        className={`
          rounded-none
          ${isInProgress ? 'data-[state=unchecked]:border-blue-500 data-[state=unchecked]:bg-blue-50' : ''}
          ${isBlocked ? 'data-[state=unchecked]:border-red-500 data-[state=unchecked]:bg-red-50' : ''}
        `}
        aria-label={`Mark todo as ${isCompleted ? 'open' : 'completed'}`}
      />
    )
  }

  if (!isOpen) return null

  return (
    <Dialog open={isOpen} onOpenChange={onClose}>
      <DialogContent className="max-w-4xl w-[90vw] sm:w-[75vw] h-[75vh] min-h-[600px] rounded-sm flex flex-col">
        <DialogHeader>
          <DialogTitle className="text-foreground flex items-center gap-2">
            <span>Todo Management</span>
            <div className="flex gap-1">
              {pinnedTodos.length > 0 && (
                <Badge variant="default" className="text-xs bg-blue-600">
                  <Pin className="h-3 w-3 mr-1" />
                  {pinnedTodos.length}
                </Badge>
              )}
              <Badge variant="secondary" className="text-xs">
                {filteredTodos.length} of {todos.length}
              </Badge>
            </div>
          </DialogTitle>
          <div className="text-xs text-muted-foreground">
            Shortcuts: Ctrl+N (New), Esc (Close)
          </div>
        </DialogHeader>

        {/* Filters */}
        <div className="flex flex-col gap-3 p-3 sm:p-4 bg-muted/20 rounded-sm">
          <div className="flex items-center gap-2">
            <Search className="h-4 w-4 text-muted-foreground" />
            <Input
              ref={searchInputRef}
              placeholder="Search todos..."
              value={filters.search}
              onChange={(e) => setFilters(prev => ({ ...prev, search: e.target.value }))}
              className="rounded-sm flex-1"
            />
            <Button 
              variant="outline" 
              size="sm" 
              className="rounded-sm"
              onClick={() => setShowNewTodo(!showNewTodo)}
            >
              <Plus className="h-4 w-4 mr-1" />
              New Todo
            </Button>
          </div>

          {/* Filter Chips */}
          <div className="flex flex-wrap gap-2">
            {/* Status Filter Chips */}
            <div className="flex gap-1">
              {['all', 'open', 'completed'].map(status => (
                <Button
                  key={status}
                  variant={filters.status === status ? 'default' : 'outline'}
                  size="sm"
                  className="h-7 px-3 text-xs rounded-full"
                  onClick={() => setFilters(prev => ({ ...prev, status: status as any }))}
                >
                  {status === 'all' ? 'All Status' : status.charAt(0).toUpperCase() + status.slice(1)}
                </Button>
              ))}
            </div>

            {/* Priority Filter Chips */}
            <div className="flex gap-1">
              {['all', 'urgent', 'high', 'medium', 'low'].map(priority => (
                <Button
                  key={priority}
                  variant={filters.priority === priority ? 'default' : 'outline'}
                  size="sm"
                  className="h-7 px-3 text-xs rounded-full"
                  onClick={() => setFilters(prev => ({ ...prev, priority: priority as any }))}
                >
                  {priority === 'all' ? 'All Priority' : priority.charAt(0).toUpperCase() + priority.slice(1)}
                </Button>
              ))}
            </div>

            {/* Clear Filters */}
            {(filters.status !== 'all' || filters.priority !== 'all' || filters.search) && (
              <Button
                variant="ghost"
                size="sm"
                className="h-7 px-3 text-xs rounded-full"
                onClick={() => setFilters({ search: '', status: 'all', priority: 'all', tags: [] })}
              >
                <X className="h-3 w-3 mr-1" />
                Clear
              </Button>
            )}
          </div>
        </div>

        {/* New Todo Input */}
        {showNewTodo && (
          <div className="px-4 py-3 bg-muted/10 border-t border-b">
            <div className="flex gap-2">
              <Input
                placeholder="Enter new todo..."
                value={newTodoText}
                onChange={(e) => setNewTodoText(e.target.value)}
                onKeyDown={(e) => {
                  if (e.key === 'Enter') {
                    handleCreateTodo()
                  } else if (e.key === 'Escape') {
                    setShowNewTodo(false)
                    setNewTodoText('')
                  }
                }}
                className="rounded-sm"
                autoFocus
              />
              <Button 
                onClick={handleCreateTodo}
                disabled={!newTodoText.trim()}
                className="rounded-sm"
              >
                Add
              </Button>
              <Button 
                variant="outline"
                onClick={() => {
                  setShowNewTodo(false)
                  setNewTodoText('')
                }}
                className="rounded-sm"
              >
                Cancel
              </Button>
            </div>
          </div>
        )}

        {/* Content */}
        <ScrollArea className="flex-1 pr-4">
          {loading ? (
            <div className="flex items-center justify-center py-8">
              <LoadingSpinner />
              <span className="ml-2 text-muted-foreground">Loading todos...</span>
            </div>
          ) : error ? (
            <div className="flex flex-col items-center justify-center py-8 text-destructive">
              <X className="h-8 w-8 mb-2" />
              <p className="text-center">{error}</p>
              <Button 
                variant="outline" 
                size="sm" 
                className="mt-2"
                onClick={() => loadTodos()}
              >
                Try Again
              </Button>
            </div>
          ) : filteredTodos.length === 0 ? (
            <div className="flex flex-col items-center justify-center py-8 text-muted-foreground">
              <div className="text-center">
                {todos.length === 0 ? (
                  <>
                    <div className="text-4xl mb-2">📝</div>
                    <p className="text-lg font-medium mb-1">No todos yet</p>
                    <p className="text-sm">Create your first todo to get started</p>
                    <Button 
                      variant="outline" 
                      size="sm" 
                      className="mt-3"
                      onClick={() => setShowNewTodo(true)}
                    >
                      <Plus className="h-4 w-4 mr-1" />
                      Create Todo
                    </Button>
                  </>
                ) : (
                  <>
                    <div className="text-4xl mb-2">🔍</div>
                    <p className="text-lg font-medium mb-1">No matching todos</p>
                    <p className="text-sm">Try adjusting your search or filters</p>
                  </>
                )}
              </div>
            </div>
          ) : (
            <div className="space-y-6">
              {/* Pinned Todos Section */}
              {pinnedTodos.length > 0 && (
                <div>
                  <div className="flex items-center gap-2 mb-3 px-2">
                    <Pin className="h-4 w-4 text-blue-600" />
                    <h3 className="text-sm font-medium text-foreground">Pinned Todos</h3>
                    <Badge variant="default" className="text-xs bg-blue-600">
                      {pinnedTodos.length}
                    </Badge>
                  </div>
                  <Table>
                    <TableHeader>
                      <TableRow>
                        <TableHead className="w-8" aria-label="Status"></TableHead>
                        <TableHead>Todo</TableHead>
                        <TableHead className="w-12" aria-label="Actions"></TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      {pinnedTodos.map((todo) => (
                        <TodoRow
                          key={todo.id}
                          todo={todo}
                          isExpanded={expandedTodos.has(todo.id)}
                          onToggleExpanded={toggleExpanded}
                          onToggleStatus={handleToggleStatus}
                          onDelete={(todoId) => handleDeleteTodo(todoId, deleteTodo)}
                          getPriorityBadgeVariant={getPriorityBadgeVariant}
                          getStatusCheckbox={getStatusCheckbox}
                        />
                      ))}
                    </TableBody>
                  </Table>
                </div>
              )}

              {/* Regular Todos Section */}
              {regularTodos.length > 0 && (
                <div>
                  {pinnedTodos.length > 0 && (
                    <div className="flex items-center gap-2 mb-3 px-2">
                      <h3 className="text-sm font-medium text-foreground">All Todos</h3>
                      <Badge variant="secondary" className="text-xs">
                        {regularTodos.length}
                      </Badge>
                    </div>
                  )}
                  <Table>
                    <TableHeader>
                      <TableRow>
                        <TableHead className="w-8" aria-label="Status"></TableHead>
                        <TableHead>Todo</TableHead>
                        <TableHead className="w-12" aria-label="Actions"></TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      {regularTodos.map((todo) => (
                        <TodoRow
                          key={todo.id}
                          todo={todo}
                          isExpanded={expandedTodos.has(todo.id)}
                          onToggleExpanded={toggleExpanded}
                          onToggleStatus={handleToggleStatus}
                          onDelete={(todoId) => handleDeleteTodo(todoId, deleteTodo)}
                          getPriorityBadgeVariant={getPriorityBadgeVariant}
                          getStatusCheckbox={getStatusCheckbox}
                        />
                      ))}
                    </TableBody>
                  </Table>
                </div>
              )}
            </div>
          )}
        </ScrollArea>

        {/* Footer */}
        <div className="flex justify-between items-center">
          <span className="text-sm text-muted-foreground">
            Showing {filteredTodos.length} of {todos.length} todos
            {pinnedTodos.length > 0 && ` (${pinnedTodos.length} pinned)`}
          </span>
          <Button variant="outline" onClick={onClose} className="rounded-sm">
            Close
          </Button>
        </div>
      </DialogContent>
    </Dialog>
  )
}