import React from 'react'
import { useState, useCallback } from 'react'

export interface TodoItem {
  id: string
  fragment_id: string
  title: string
  message: string
  status: 'open' | 'completed' | 'in_progress' | 'blocked'
  priority: 'low' | 'medium' | 'high' | 'urgent'
  tags: string[]
  project?: string
  created_at: string
  completed_at?: string
  due_at?: string
  order: number
  is_pinned: boolean
}

export interface TodoFilters {
  search?: string
  status?: 'all' | 'open' | 'completed'
  priority?: 'all' | 'low' | 'medium' | 'high' | 'urgent'
  tags?: string[]
  limit?: number
}

interface UseTodoDataReturn {
  todos: TodoItem[]
  loading: boolean
  error: string | null
  loadTodos: (filters?: TodoFilters) => Promise<void>
  toggleTodoStatus: (todoId: string) => Promise<void>
  createTodo: (text: string, tags?: string[]) => Promise<void>
  deleteTodo: (todoId: string) => Promise<void>
  updateTodo: (todoId: string, updates: Partial<TodoItem>) => Promise<void>
}

export function useTodoData(): UseTodoDataReturn {
  const [todos, setTodos] = useState<TodoItem[]>([])
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)

  const executeCommand = useCallback(async (command: string) => {
    const response = await fetch('/api/commands/execute', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      body: JSON.stringify({ command }),
    })

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`)
    }

    return response.json()
  }, [])

  const transformFragmentToTodo = useCallback((fragment: any): TodoItem => {
    const state = fragment.state || {}
    
    // Parse tags - handle both array and PostgreSQL array formats
    let tags: string[] = []
    if (Array.isArray(fragment.tags)) {
      tags = fragment.tags.flatMap(tag => {
        if (typeof tag === 'string') {
          // Handle PostgreSQL array format like '{"work","important","todo"}'
          if (tag.startsWith('{') && tag.endsWith('}')) {
            const cleaned = tag.slice(1, -1) // Remove { }
            return cleaned.split(',').map(t => t.replace(/"/g, '').trim()).filter(t => t)
          }
          // Handle JSON arrays like '["work","important","todo"]'
          try {
            const parsed = JSON.parse(tag)
            return Array.isArray(parsed) ? parsed : [tag]
          } catch {
            return [tag]
          }
        }
        return tag
      }).filter(tag => tag && tag !== 'todo') // Remove empty tags and default 'todo' tag
    }
    
    // Clean up title - remove "Todo: " prefix if present
    let title = fragment.title || fragment.message?.split('\n')[0] || 'Untitled Todo'
    if (title.startsWith('Todo: ')) {
      title = title.substring(6)
    }
    
    return {
      id: fragment.id.toString(),
      fragment_id: fragment.id.toString(),
      title: title.trim(),
      message: fragment.message || '',
      status: state.status === 'complete' ? 'completed' : (state.status || 'open'),
      priority: state.priority || 'medium',
      tags: tags,
      project: state.project,
      created_at: fragment.created_at,
      completed_at: state.completed_at,
      due_at: state.due_at,
      order: state.order || 0,
      is_pinned: fragment.pinned || false,
    }
  }, [])

  const loadTodos = useCallback(async (filters: TodoFilters = {}) => {
    setLoading(true)
    setError(null)

    try {
      // Build todo list command with filters
      let command = '/todos'
      
      if (filters.status && filters.status !== 'all') {
        command += ` status:${filters.status}`
      }
      
      if (filters.search) {
        command += ` search:"${filters.search}"`
      }
      
      if (filters.tags && filters.tags.length > 0) {
        command += ` ${filters.tags.map(tag => `#${tag}`).join(' ')}`
      }
      
      if (filters.limit) {
        command += ` limit:${filters.limit}`
      }

      const result = await executeCommand(command)
      
      // Handle new response format: result.data.items or legacy panelData.fragments
      if (result.success && result.data?.items) {
        // New format: todos already transformed by backend
        setTodos(result.data.items)
      } else if (result.success && result.panelData?.fragments) {
        // Legacy format: fragments need transformation
        const todoItems = result.panelData.fragments.map(transformFragmentToTodo)
        setTodos(todoItems)
      } else if (result.success) {
        // Empty result
        setTodos([])
      } else {
        throw new Error(result.error || 'Failed to load todos')
      }
    } catch (err) {
      const errorMessage = err instanceof Error ? err.message : 'Failed to load todos'
      setError(errorMessage)
      console.error('Error loading todos:', err)
    } finally {
      setLoading(false)
    }
  }, [executeCommand, transformFragmentToTodo])

  const toggleTodoStatus = useCallback(async (todoId: string) => {
    const todo = todos.find(t => t.id === todoId)
    if (!todo) return

    const newStatus = todo.status === 'completed' ? 'open' : 'completed'
    const now = new Date().toISOString()

    // Optimistic update
    setTodos(prev => prev.map(t => 
      t.id === todoId 
        ? { 
            ...t, 
            status: newStatus,
            completed_at: newStatus === 'completed' ? now : undefined
          }
        : t
    ))

    try {
      // Update fragment state via API
      const response = await fetch(`/api/fragment/${todo.fragment_id}`, {
        method: 'PATCH',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
        body: JSON.stringify({
          state: {
            status: newStatus === 'completed' ? 'complete' : 'open',
            completed_at: newStatus === 'completed' ? now : null,
          }
        }),
      })

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
    } catch (err) {
      // Revert optimistic update on error
      setTodos(prev => prev.map(t => 
        t.id === todoId 
          ? { 
              ...t, 
              status: todo.status,
              completed_at: todo.completed_at
            }
          : t
      ))
      
      const errorMessage = err instanceof Error ? err.message : 'Failed to update todo status'
      setError(errorMessage)
      throw err
    }
  }, [todos, executeCommand])

  const createTodo = useCallback(async (text: string, tags: string[] = []) => {
    try {
      const tagString = tags.length > 0 ? ` ${tags.map(tag => `#${tag}`).join(' ')}` : ''
      const command = `todo ${text}${tagString}`
      
      const result = await executeCommand(command)
      
      if (!result.success) {
        throw new Error(result.error || 'Failed to create todo')
      }

      // Refresh todos after creation
      await loadTodos()
    } catch (err) {
      const errorMessage = err instanceof Error ? err.message : 'Failed to create todo'
      setError(errorMessage)
      throw err
    }
  }, [executeCommand, loadTodos])

  const deleteTodo = useCallback(async (todoId: string) => {
    try {
      const todo = todos.find(t => t.id === todoId)
      if (!todo) throw new Error('Todo not found')

      // Use ID-based delete command
      const command = `todo delete:${todoId}`
      
      const result = await executeCommand(command)
      
      if (!result.success) {
        throw new Error(result.error || 'Failed to delete todo')
      }

      // Remove from local state
      setTodos(prev => prev.filter(t => t.id !== todoId))
    } catch (err) {
      const errorMessage = err instanceof Error ? err.message : 'Failed to delete todo'
      setError(errorMessage)
      throw err
    }
  }, [todos, executeCommand])

  const updateTodo = useCallback(async (todoId: string, updates: Partial<TodoItem>) => {
    const todo = todos.find(t => t.id === todoId)
    if (!todo) throw new Error('Todo not found')

    // Optimistic update
    setTodos(prev => prev.map(t => 
      t.id === todoId ? { ...t, ...updates } : t
    ))

    try {
      // For now, we'll handle updates by recreating the todo
      // In a real implementation, you'd have specific update commands
      if (updates.title || updates.message) {
        const newText = updates.title || updates.message || todo.title
        const tags = updates.tags || todo.tags
        
        await deleteTodo(todoId)
        await createTodo(newText, tags.filter(tag => tag !== 'todo'))
      }
    } catch (err) {
      // Revert optimistic update
      setTodos(prev => prev.map(t => 
        t.id === todoId ? todo : t
      ))
      
      const errorMessage = err instanceof Error ? err.message : 'Failed to update todo'
      setError(errorMessage)
      throw err
    }
  }, [todos, createTodo, deleteTodo])

  return {
    todos,
    loading,
    error,
    loadTodos,
    toggleTodoStatus,
    createTodo,
    deleteTodo,
    updateTodo,
  }
}