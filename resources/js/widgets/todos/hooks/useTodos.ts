import { useState, useEffect, useCallback } from 'react'
import { useQuery, useQueryClient, useMutation } from '@tanstack/react-query'

interface TodoItem {
  id: string
  fragment_id: string
  title: string
  message: string
  status: 'open' | 'completed' | 'in_progress' | 'blocked'
  priority: 'low' | 'medium' | 'high' | 'urgent'
  tags: string[]
  created_at: string
  completed_at?: string
  due_at?: string
  is_pinned: boolean
}

export function useTodos() {
  const [searchQuery, setSearchQuery] = useState('')
  const queryClient = useQueryClient()

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
      created_at: fragment.created_at,
      completed_at: state.completed_at,
      due_at: state.due_at,
      is_pinned: fragment.pinned || false,
    }
  }, [])

  // Fetch todos
  const { data: todos = [], isLoading, error, refetch } = useQuery({
    queryKey: ['widget-todos', searchQuery],
    queryFn: async () => {
      let command = 'todo list limit:10' // Limit for widget
      if (searchQuery) {
        command += ` search:"${searchQuery}"`
      }
      
      const result = await executeCommand(command)
      
      if (result.success && result.panelData?.fragments) {
        const todoItems = result.panelData.fragments.map(transformFragmentToTodo)
        // Sort to show pinned first, then by priority and date
        return todoItems.sort((a, b) => {
          if (a.is_pinned && !b.is_pinned) return -1
          if (!a.is_pinned && b.is_pinned) return 1
          if (a.status === 'completed' && b.status !== 'completed') return 1
          if (a.status !== 'completed' && b.status === 'completed') return -1
          return new Date(b.created_at).getTime() - new Date(a.created_at).getTime()
        })
      }
      return []
    },
    staleTime: 5 * 60 * 1000, // 5 minutes
    refetchOnWindowFocus: false,
  })

  // Toggle todo status
  const toggleStatusMutation = useMutation({
    mutationFn: async (todoId: string) => {
      const todo = todos.find(t => t.id === todoId)
      if (!todo) throw new Error('Todo not found')

      const todoIndex = todos.findIndex(t => t.id === todoId)
      const newStatus = todo.status === 'completed' ? 'open' : 'completed'
      const command = newStatus === 'completed' 
        ? `todo complete:${todoIndex + 1}`
        : `todo reopen:${todoIndex + 1}` // Assuming reopen command exists

      const result = await executeCommand(command)
      
      if (!result.success) {
        throw new Error(result.error || 'Failed to update todo status')
      }
      
      return result
    },
    onSuccess: () => {
      // Invalidate and refetch todos
      queryClient.invalidateQueries({ queryKey: ['widget-todos'] })
    },
  })

  const toggleTodoStatus = useCallback((todoId: string) => {
    toggleStatusMutation.mutate(todoId)
  }, [toggleStatusMutation])

  return {
    todos,
    searchQuery,
    setSearchQuery,
    isLoading,
    error,
    refetch,
    toggleTodoStatus,
    isTogglingStatus: toggleStatusMutation.isPending,
  }
}