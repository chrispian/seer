import React, { useState, useEffect } from 'react'
import { DataManagementModal, ColumnDefinition } from '@/components/ui/DataManagementModal'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { CheckCircle, Clock, AlertCircle, User, Calendar, FileText } from 'lucide-react'

interface Task {
  id: string
  task_code: string
  task_name: string
  description?: string
  status: string
  delegation_status: string
  priority?: string
  agent_recommendation?: string
  current_agent?: string
  estimate_text?: string
  estimated_hours?: number
  sprint_code?: string
  tags?: string[]
  created_at: string
  updated_at: string
  completed_at?: string
  has_content?: {
    agent: boolean
    plan: boolean
    context: boolean
    todo: boolean
    summary: boolean
  }
}

interface TaskListModalProps {
  isOpen: boolean
  onClose: () => void
  tasks: Task[]
  loading?: boolean
  error?: string | null
  onRefresh?: () => void
  onTaskSelect?: (task: Task) => void
  title?: string
  showBacklogOnly?: boolean
  currentSprintOnly?: boolean
}

export function TaskListModal({ 
  isOpen, 
  onClose, 
  tasks, 
  loading = false, 
  error = null,
  onRefresh,
  onTaskSelect,
  title = "Task Management",
  showBacklogOnly = false,
  currentSprintOnly = false
}: TaskListModalProps) {

  const getStatusColor = (status: string) => {
    switch (status?.toLowerCase()) {
      case 'done':
      case 'completed':
        return 'bg-green-100 text-green-800'
      case 'in-progress':
      case 'in_progress':
        return 'bg-blue-100 text-blue-800'
      case 'todo':
      case 'ready':
        return 'bg-yellow-100 text-yellow-800'
      case 'blocked':
        return 'bg-red-100 text-red-800'
      case 'backlog':
        return 'bg-gray-100 text-gray-800'
      case 'review':
        return 'bg-purple-100 text-purple-800'
      default:
        return 'bg-gray-100 text-gray-800'
    }
  }

  const getDelegationStatusColor = (status: string) => {
    switch (status?.toLowerCase()) {
      case 'completed':
        return 'bg-green-100 text-green-800'
      case 'assigned':
      case 'in_progress':
        return 'bg-blue-100 text-blue-800'
      case 'unassigned':
        return 'bg-gray-100 text-gray-800'
      case 'blocked':
        return 'bg-red-100 text-red-800'
      default:
        return 'bg-gray-100 text-gray-800'
    }
  }

  const getPriorityColor = (priority?: string) => {
    switch (priority?.toLowerCase()) {
      case 'high':
        return 'bg-red-100 text-red-800'
      case 'medium':
        return 'bg-yellow-100 text-yellow-800'
      case 'low':
        return 'bg-green-100 text-green-800'
      default:
        return 'bg-gray-100 text-gray-800'
    }
  }

  // Filter tasks based on view type
  const filteredTasks = tasks.filter(task => {
    if (showBacklogOnly) {
      return task.status?.toLowerCase() === 'backlog'
    }
    if (currentSprintOnly) {
      return task.status?.toLowerCase() !== 'backlog'
    }
    return true
  })

  // Sort tasks: In Progress, then Open (Todo), then Closed (Done)
  const sortedTasks = [...filteredTasks].sort((a, b) => {
    const statusOrder = {
      'in-progress': 1,
      'in_progress': 1,
      'todo': 2,
      'ready': 2,
      'review': 3,
      'done': 4,
      'completed': 4,
      'blocked': 5,
      'backlog': 6
    }
    
    const aOrder = statusOrder[a.status?.toLowerCase()] || 999
    const bOrder = statusOrder[b.status?.toLowerCase()] || 999
    
    if (aOrder !== bOrder) {
      return aOrder - bOrder
    }
    
    // Within same status, sort by updated date (newest first)
    return new Date(b.updated_at).getTime() - new Date(a.updated_at).getTime()
  })

  const expandedContent = (task: Task) => (
    <div className="space-y-3 text-sm">
      {task.description && (
        <div>
          <span className="font-medium">Description:</span>
          <p className="text-muted-foreground mt-1">{task.description}</p>
        </div>
      )}
      
      <div className="grid grid-cols-2 gap-4">
        <div>
          <span className="font-medium">Assignment:</span>
          <div className="text-muted-foreground mt-1 space-y-1">
            <div>Status: {task.delegation_status || 'Unassigned'}</div>
            {task.current_agent && (
              <div>Agent: {task.current_agent}</div>
            )}
            {task.agent_recommendation && (
              <div>Recommended: {task.agent_recommendation}</div>
            )}
          </div>
        </div>
        
        <div>
          <span className="font-medium">Details:</span>
          <div className="text-muted-foreground mt-1 space-y-1">
            {task.estimate_text && (
              <div>Estimate: {task.estimate_text}</div>
            )}
            {task.sprint_code && (
              <div>Sprint: {task.sprint_code}</div>
            )}
            <div>Updated: {new Date(task.updated_at).toLocaleDateString()}</div>
            {task.completed_at && (
              <div>Completed: {new Date(task.completed_at).toLocaleDateString()}</div>
            )}
          </div>
        </div>
      </div>

      {task.has_content && (
        <div>
          <span className="font-medium">Content Available:</span>
          <div className="flex flex-wrap gap-1 mt-1">
            {task.has_content.agent && (
              <Badge variant="outline" className="text-xs bg-blue-50 text-blue-700">
                Agent Profile
              </Badge>
            )}
            {task.has_content.plan && (
              <Badge variant="outline" className="text-xs bg-green-50 text-green-700">
                Plan
              </Badge>
            )}
            {task.has_content.context && (
              <Badge variant="outline" className="text-xs bg-purple-50 text-purple-700">
                Context
              </Badge>
            )}
            {task.has_content.todo && (
              <Badge variant="outline" className="text-xs bg-yellow-50 text-yellow-700">
                Todo
              </Badge>
            )}
            {task.has_content.summary && (
              <Badge variant="outline" className="text-xs bg-gray-50 text-gray-700">
                Summary
              </Badge>
            )}
          </div>
        </div>
      )}

      {task.tags && task.tags.length > 0 && (
        <div>
          <span className="font-medium">Tags:</span>
          <div className="flex flex-wrap gap-1 mt-1">
            {task.tags.map(tag => (
              <Badge key={tag} variant="outline" className="text-xs">
                {tag}
              </Badge>
            ))}
          </div>
        </div>
      )}
    </div>
  )

  const columns: ColumnDefinition<Task>[] = [
    {
      key: 'task_code',
      label: 'Task',
      render: (task) => (
        <div className="flex flex-col">
          <span className="font-medium">{task.task_code}</span>
          <span className="text-xs text-muted-foreground truncate max-w-[200px]">
            {task.task_name}
          </span>
        </div>
      )
    },
    {
      key: 'status',
      label: 'Status',
      width: 'w-24',
      render: (task) => (
        <Badge variant="outline" className={`text-xs ${getStatusColor(task.status)}`}>
          {task.status}
        </Badge>
      )
    },
    {
      key: 'delegation_status',
      label: 'Assignment',
      width: 'w-28',
      render: (task) => (
        <Badge variant="outline" className={`text-xs ${getDelegationStatusColor(task.delegation_status)}`}>
          {task.delegation_status}
        </Badge>
      )
    },
    {
      key: 'priority',
      label: 'Priority',
      width: 'w-20',
      render: (task) => task.priority ? (
        <Badge variant="outline" className={`text-xs ${getPriorityColor(task.priority)}`}>
          {task.priority}
        </Badge>
      ) : <span className="text-muted-foreground text-xs">-</span>
    },
    {
      key: 'current_agent',
      label: 'Agent',
      width: 'w-32',
      render: (task) => (
        <div className="text-xs text-muted-foreground truncate">
          {task.current_agent || task.agent_recommendation || '-'}
        </div>
      )
    },
    {
      key: 'estimate_text',
      label: 'Estimate',
      width: 'w-20',
      render: (task) => (
        <span className="text-xs text-muted-foreground">
          {task.estimate_text || '-'}
        </span>
      )
    },
    {
      key: 'updated_at',
      label: 'Updated',
      width: 'w-24',
      render: (task) => (
        <span className="text-xs text-muted-foreground">
          {new Date(task.updated_at).toLocaleDateString()}
        </span>
      )
    },
    {
      key: 'actions',
      label: '',
      width: 'w-12'
    }
  ]

  const filters = [
    {
      key: 'status',
      label: 'Status',
      options: [
        { value: 'all', label: 'All', count: sortedTasks.length },
        { value: 'in-progress', label: 'In Progress', count: sortedTasks.filter(t => t.status?.toLowerCase().includes('progress')).length },
        { value: 'todo', label: 'Todo', count: sortedTasks.filter(t => t.status?.toLowerCase() === 'todo' || t.status?.toLowerCase() === 'ready').length },
        { value: 'done', label: 'Done', count: sortedTasks.filter(t => t.status?.toLowerCase() === 'done' || t.status?.toLowerCase() === 'completed').length },
        { value: 'blocked', label: 'Blocked', count: sortedTasks.filter(t => t.status?.toLowerCase() === 'blocked').length },
        ...(showBacklogOnly ? [] : [{ value: 'backlog', label: 'Backlog', count: sortedTasks.filter(t => t.status?.toLowerCase() === 'backlog').length }])
      ]
    },
    {
      key: 'delegation_status',
      label: 'Assignment',
      options: [
        { value: 'all', label: 'All', count: sortedTasks.length },
        { value: 'assigned', label: 'Assigned', count: sortedTasks.filter(t => t.delegation_status?.toLowerCase() === 'assigned').length },
        { value: 'unassigned', label: 'Unassigned', count: sortedTasks.filter(t => t.delegation_status?.toLowerCase() === 'unassigned').length },
        { value: 'completed', label: 'Completed', count: sortedTasks.filter(t => t.delegation_status?.toLowerCase() === 'completed').length }
      ]
    }
  ]

  const actionItems = [
    { key: 'view', label: 'View Details' },
    { key: 'assign', label: 'Assign Agent' }
  ]

  // Custom expanded content that shows a "View Details" button instead of actual content
  const limitedExpandedContent = (task: Task) => (
    <div className="flex items-center justify-between py-2">
      <span className="text-sm text-muted-foreground">
        Click "View Details" to see full task information
      </span>
      <Button 
        size="sm" 
        onClick={() => onTaskSelect?.(task)}
        className="ml-4"
      >
        View Details
      </Button>
    </div>
  )

  return (
    <DataManagementModal
      isOpen={isOpen}
      onClose={onClose}
      title={title}
      data={sortedTasks}
      columns={columns}
      loading={loading}
      error={error}
      filters={filters}
      searchPlaceholder="Search tasks..."
      searchFields={['task_code', 'task_name', 'description', 'current_agent', 'agent_recommendation']}
      expandedContent={limitedExpandedContent}
      onAction={(action, task) => {
        if (action === 'view' || action === 'assign') {
          onTaskSelect?.(task)
        }
      }}
      actionItems={actionItems}
      clickableRows={true}
      onRefresh={onRefresh}
      customHeader={
        <div className="text-sm text-muted-foreground">
          {showBacklogOnly 
            ? "Manage backlog items and plan future work" 
            : currentSprintOnly 
              ? "View current sprint tasks - In Progress, Open, then Closed"
              : "Manage all tasks across sprints"
          }
        </div>
      }
      emptyStateMessage={showBacklogOnly ? "No backlog items found" : "No tasks found"}
      emptyStateIcon={<CheckCircle className="h-8 w-8" />}
    />
  )
}