import React, { useState, useEffect, useMemo } from 'react'
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
import { Search, Filter, MoreHorizontal, Plus, Check, X } from 'lucide-react'

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

  // Load todos when modal opens
  useEffect(() => {
    if (isOpen) {
      const todoFilters: TodoFilters = {
        status: filters.status,
        search: filters.search || undefined,
        tags: filters.tags.length > 0 ? filters.tags : undefined,
        limit: 100,
      }
      loadTodos(todoFilters)
    }
  }, [isOpen, loadTodos])

  // Reload todos when filters change
  useEffect(() => {
    if (isOpen) {
      const todoFilters: TodoFilters = {
        status: filters.status,
        search: filters.search || undefined,
        tags: filters.tags.length > 0 ? filters.tags : undefined,
        limit: 100,
      }
      loadTodos(todoFilters)
    }
  }, [filters, isOpen, loadTodos])

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

  const getPriorityBadgeVariant = (priority: string) => {
    switch (priority) {
      case 'urgent': return 'destructive'
      case 'high': return 'default'
      case 'medium': return 'secondary'
      case 'low': return 'outline'
      default: return 'outline'
    }
  }

  const getStatusIcon = (status: string) => {
    switch (status) {
      case 'completed': return <Check className="h-4 w-4 text-green-600" />
      case 'in_progress': return <div className="h-3 w-3 bg-blue-500 rounded-full" />
      case 'blocked': return <X className="h-4 w-4 text-red-500" />
      default: return <div className="h-3 w-3 border-2 border-muted-foreground rounded-full" />
    }
  }

  if (!isOpen) return null

  return (
    <Dialog open={isOpen} onOpenChange={onClose}>
      <DialogContent className="max-w-6xl max-h-[90vh] w-[95vw] sm:w-full rounded-sm">
        <DialogHeader>
          <DialogTitle className="text-foreground flex items-center gap-2">
            <span>Todo Management</span>
            <Badge variant="secondary" className="text-xs">
              {filteredTodos.length} of {todos.length}
            </Badge>
          </DialogTitle>
          <div className="text-xs text-muted-foreground">
            Shortcuts: Ctrl+N (New), Esc (Close)
          </div>
        </DialogHeader>

        {/* Filters */}
        <div className="flex flex-col sm:flex-row gap-3 p-3 sm:p-4 bg-muted/20 rounded-sm">
          <div className="flex items-center gap-2 flex-1">
            <Search className="h-4 w-4 text-muted-foreground" />
            <Input
              placeholder="Search todos..."
              value={filters.search}
              onChange={(e) => setFilters(prev => ({ ...prev, search: e.target.value }))}
              className="rounded-sm"
            />
          </div>

          <div className="flex flex-col sm:flex-row gap-2">
            <div className="flex gap-2">
              <Select
                value={filters.status}
                onValueChange={(value) => setFilters(prev => ({ ...prev, status: value as any }))}
              >
                <SelectTrigger className="w-32 sm:w-36 rounded-sm text-sm">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All Status</SelectItem>
                  <SelectItem value="open">Open</SelectItem>
                  <SelectItem value="completed">Completed</SelectItem>
                </SelectContent>
              </Select>

              <Select
                value={filters.priority}
                onValueChange={(value) => setFilters(prev => ({ ...prev, priority: value as any }))}
              >
                <SelectTrigger className="w-32 sm:w-36 rounded-sm text-sm">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All Priority</SelectItem>
                  <SelectItem value="urgent">Urgent</SelectItem>
                  <SelectItem value="high">High</SelectItem>
                  <SelectItem value="medium">Medium</SelectItem>
                  <SelectItem value="low">Low</SelectItem>
                </SelectContent>
              </Select>
            </div>

            <Button 
              variant="outline" 
              size="sm" 
              className="rounded-sm w-full sm:w-auto"
              onClick={() => setShowNewTodo(!showNewTodo)}
            >
              <Plus className="h-4 w-4 mr-1" />
              New Todo
            </Button>
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
        <ScrollArea className="flex-1 max-h-[50vh] sm:max-h-[60vh]">
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
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead className="w-12" aria-label="Status"></TableHead>
                  <TableHead>Todo</TableHead>
                  <TableHead className="w-20 sm:w-24 hidden sm:table-cell">Priority</TableHead>
                  <TableHead className="w-24 sm:w-32 hidden md:table-cell">Tags</TableHead>
                  <TableHead className="w-24 sm:w-32 hidden lg:table-cell">Created</TableHead>
                  <TableHead className="w-12" aria-label="Actions"></TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {filteredTodos.map((todo) => (
                  <TableRow key={todo.id} className="hover:bg-muted/50">
                    <TableCell>
                      <button
                        onClick={() => handleToggleStatus(todo.id)}
                        className="flex items-center justify-center p-1 rounded hover:bg-muted focus:outline-none focus:ring-2 focus:ring-ring"
                        aria-label={`Mark todo as ${todo.status === 'completed' ? 'open' : 'completed'}`}
                      >
                        {getStatusIcon(todo.status)}
                      </button>
                    </TableCell>
                    <TableCell>
                      <div className="flex flex-col">
                        <span className={`font-medium text-sm sm:text-base ${todo.status === 'completed' ? 'line-through text-muted-foreground' : ''}`}>
                          {todo.title}
                        </span>
                        <span className="text-xs sm:text-sm text-muted-foreground truncate max-w-[200px] sm:max-w-md">
                          {todo.message}
                        </span>
                        {/* Show priority and tags on mobile */}
                        <div className="flex gap-1 mt-1 sm:hidden">
                          <Badge variant={getPriorityBadgeVariant(todo.priority)} className="text-xs">
                            {todo.priority}
                          </Badge>
                          {todo.tags.slice(0, 1).map(tag => (
                            <Badge key={tag} variant="outline" className="text-xs">
                              {tag}
                            </Badge>
                          ))}
                          {todo.tags.length > 1 && (
                            <Badge variant="outline" className="text-xs">
                              +{todo.tags.length - 1}
                            </Badge>
                          )}
                        </div>
                      </div>
                    </TableCell>
                    <TableCell className="hidden sm:table-cell">
                      <Badge variant={getPriorityBadgeVariant(todo.priority)} className="text-xs">
                        {todo.priority}
                      </Badge>
                    </TableCell>
                    <TableCell className="hidden md:table-cell">
                      <div className="flex flex-wrap gap-1">
                        {todo.tags.slice(0, 2).map(tag => (
                          <Badge key={tag} variant="outline" className="text-xs">
                            {tag}
                          </Badge>
                        ))}
                        {todo.tags.length > 2 && (
                          <Badge variant="outline" className="text-xs">
                            +{todo.tags.length - 2}
                          </Badge>
                        )}
                      </div>
                    </TableCell>
                    <TableCell className="text-xs sm:text-sm text-muted-foreground hidden lg:table-cell">
                      {new Date(todo.created_at).toLocaleDateString()}
                    </TableCell>
                    <TableCell>
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
                            onClick={() => handleDeleteTodo(todo.id, deleteTodo)}
                          >
                            Delete
                          </DropdownMenuItem>
                        </DropdownMenuContent>
                      </DropdownMenu>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          )}
        </ScrollArea>

        {/* Footer */}
        <div className="flex justify-between items-center">
          <span className="text-sm text-muted-foreground">
            Showing {filteredTodos.length} of {todos.length} todos
          </span>
          <Button variant="outline" onClick={onClose} className="rounded-sm">
            Close
          </Button>
        </div>
      </DialogContent>
    </Dialog>
  )
}