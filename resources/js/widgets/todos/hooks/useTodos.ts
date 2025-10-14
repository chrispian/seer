import { useState, useCallback } from 'react'
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



  // Fetch todos
  const { data: todos = [], isLoading, error, refetch } = useQuery({
    queryKey: ['widget-todos', searchQuery],
    queryFn: async () => {
      let command = '/todos' // Use correct registered command
      if (searchQuery) {
        command += ` search:"${searchQuery}"`
      }
      
      const result = await executeCommand(command)
      
      if (result.success && result.data?.items) {
        const todoItems: TodoItem[] = result.data.items
        // Sort to show pinned first, then by priority and date
        return todoItems.sort((a: TodoItem, b: TodoItem) => {
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
      const todo = todos.find((t: TodoItem) => t.id === todoId)
      if (!todo) throw new Error('Todo not found')

      const newStatus = todo.status === 'completed' ? 'open' : 'completed'
      const command = newStatus === 'completed' 
        ? `todo complete:${todoId}`
        : `todo reopen:${todoId}`

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