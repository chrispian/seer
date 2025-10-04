import React, { useState } from 'react'
import { Card, CardContent, CardHeader } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { ScrollArea } from '@/components/ui/scroll-area'
import { Badge } from '@/components/ui/badge'
import { Checkbox } from '@/components/ui/checkbox'
import { Search, X, CheckSquare, Pin, Plus } from 'lucide-react'
import { useTodos } from './hooks/useTodos'

export function TodoWidget() {
  const [isSearchMode, setIsSearchMode] = useState(false)
  const { 
    todos, 
    searchQuery, 
    setSearchQuery, 
    isLoading,
    error,
    toggleTodoStatus,
    isTogglingStatus
  } = useTodos()

  const handleSearchToggle = () => {
    if (isSearchMode) {
      setSearchQuery('')
      setIsSearchMode(false)
    } else {
      setIsSearchMode(true)
    }
  }

  const handleTodoClick = (todo: any) => {
    // TODO: Open todo detail or navigate to todo modal
    console.log('Todo clicked:', todo)
  }

  const handleToggleStatus = (e: React.MouseEvent, todoId: string) => {
    e.stopPropagation() // Prevent todo click
    toggleTodoStatus(todoId)
  }

  const formatTimeAgo = (dateString: string) => {
    const date = new Date(dateString)
    const now = new Date()
    const diffInHours = Math.floor((now.getTime() - date.getTime()) / (1000 * 60 * 60))
    
    if (diffInHours < 1) return 'Just now'
    if (diffInHours < 24) return `${diffInHours}h ago`
    const diffInDays = Math.floor(diffInHours / 24)
    if (diffInDays < 7) return `${diffInDays}d ago`
    return date.toLocaleDateString()
  }

  const getPriorityColor = (priority: string) => {
    switch (priority) {
      case 'urgent': return 'bg-red-100 text-red-800'
      case 'high': return 'bg-orange-100 text-orange-800'
      case 'medium': return 'bg-blue-100 text-blue-800'
      case 'low': return 'bg-gray-100 text-gray-800'
      default: return 'bg-gray-100 text-gray-800'
    }
  }

  const openTodoModal = () => {
    // TODO: Trigger todo modal opening
    console.log('Open todo modal')
  }

  if (error) {
    return (
      <Card className="border-0 shadow-none border-b border-gray-200 overflow-hidden">
        <CardHeader className="pb-1">
          <div className="flex items-center justify-between">
            <h4 className="text-xs font-medium">Recent Todos</h4>
            <Button variant="ghost" size="icon" className="h-6 w-6" disabled>
              <Search className="w-3 h-3" />
            </Button>
          </div>
        </CardHeader>
        <CardContent className="pt-0 max-w-full overflow-hidden">
          <div className="text-center text-muted-foreground text-xs py-4">
            Failed to load todos
          </div>
        </CardContent>
      </Card>
    )
  }

  return (
    <Card className="border-0 shadow-none border-b border-gray-200 overflow-hidden">
      <CardHeader className="pb-1">
        <div className="flex items-center justify-between">
          <h4 className="text-xs font-medium">Recent Todos</h4>
          <div className="flex items-center gap-1">
            <Button
              variant="ghost"
              size="icon"
              className="h-6 w-6"
              onClick={openTodoModal}
              title="Open Todo Management"
            >
              <Plus className="w-3 h-3" />
            </Button>
            <Button
              variant="ghost"
              size="icon"
              className="h-6 w-6"
              onClick={handleSearchToggle}
            >
              {isSearchMode ? <X className="w-3 h-3" /> : <Search className="w-3 h-3" />}
            </Button>
          </div>
        </div>
      </CardHeader>
      <CardContent className="pt-0 max-w-full overflow-hidden">
        {/* Search Input */}
        {isSearchMode && (
          <div className="mb-3">
            <Input
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              placeholder="Search todos..."
              className="text-xs"
              autoFocus
            />
          </div>
        )}

        {/* Todos List */}
        <ScrollArea className="h-48">
          <div className="space-y-1 max-w-full overflow-hidden">
            {isLoading ? (
              // Loading skeleton
              [...Array(3)].map((_, i) => (
                <div key={i} className="flex items-center space-x-2 p-1">
                  <div className="w-3 h-3 bg-muted rounded animate-pulse"></div>
                  <div className="flex-1">
                    <div className="h-3 bg-muted rounded w-full mb-1 animate-pulse"></div>
                    <div className="h-2 bg-muted rounded w-16 animate-pulse"></div>
                  </div>
                </div>
              ))
            ) : todos.length === 0 ? (
              <div className="text-center text-muted-foreground text-xs py-4">
                {isSearchMode && searchQuery ? 'No results found' : 'No todos yet'}
              </div>
            ) : (
              <>
                {todos.map((todo) => (
                  <div
                    key={todo.id}
                    className="flex items-start space-x-2 text-xs cursor-pointer hover:bg-accent p-1 rounded transition-colors group overflow-hidden"
                    onClick={() => handleTodoClick(todo)}
                  >
                    <div className="pt-0.5">
                      <Checkbox
                        checked={todo.status === 'completed'}
                        onCheckedChange={(e) => handleToggleStatus(e as any, todo.id)}
                        className="h-3 w-3 rounded-none"
                        disabled={isTogglingStatus}
                      />
                    </div>
                    <div className="flex-1 min-w-0 overflow-hidden">
                      <div className="flex items-center gap-1 mb-0.5">
                        {todo.is_pinned && <Pin className="w-2 h-2 text-blue-600 flex-shrink-0" />}
                        <div 
                          className={`truncate font-medium ${todo.status === 'completed' ? 'line-through text-muted-foreground' : ''}`} 
                          title={todo.title}
                        >
                          {todo.title.length > 20 ? todo.title.substring(0, 20) + '...' : todo.title}
                        </div>
                      </div>
                      <div className="flex items-center gap-1 flex-wrap">
                        <Badge 
                          variant="outline" 
                          className={`text-xs px-1 py-0 ${getPriorityColor(todo.priority)}`}
                        >
                          {todo.priority}
                        </Badge>
                        {todo.tags.slice(0, 1).map(tag => (
                          <Badge key={tag} variant="outline" className="text-xs px-1 py-0">
                            {tag}
                          </Badge>
                        ))}
                        {todo.tags.length > 1 && (
                          <span className="text-muted-foreground text-xs">
                            +{todo.tags.length - 1}
                          </span>
                        )}
                      </div>
                    </div>
                    <div className="flex items-center gap-1 flex-shrink-0 pt-0.5">
                      <span className="text-muted-foreground text-xs">
                        {formatTimeAgo(todo.created_at)}
                      </span>
                      <CheckSquare className="w-3 h-3 opacity-0 group-hover:opacity-100 transition-opacity" />
                    </div>
                  </div>
                ))}
              </>
            )}
          </div>
        </ScrollArea>
      </CardContent>
    </Card>
  )
}