import React, { useState } from 'react'
import { DataManagementModal, ColumnDefinition } from '@/components/ui/DataManagementModal'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Calendar, CheckCircle, Clock, AlertCircle, BarChart3, Users, ArrowLeft } from 'lucide-react'

interface SprintDetail {
  id: string
  code: string
  title: string
  status: string
  meta?: {
    title?: string
    priority?: string
    impact?: string
    estimated?: string
  }
  created_at: string
  updated_at: string
}

interface Task {
  id: string
  task_code: string
  task_name: string
  status: string
  delegation_status: string
  agent_recommendation?: string
  current_agent?: string
  estimate_text?: string
  todo_progress?: {
    completed: number
    total: number
  }
  updated_at: string
}

interface Stats {
  total: number
  completed: number
  in_progress: number
  todo: number
  backlog: number
}

interface SprintDetailModalProps {
  isOpen: boolean
  onClose: () => void
  sprint: SprintDetail
  tasks: Task[]
  stats: Stats
  loading?: boolean
  error?: string | null
  onRefresh?: () => void
  onTaskSelect?: (task: Task) => void
  onBack?: () => void
  onEdit?: (sprint: SprintDetail) => void
}

export function SprintDetailModal({ 
  isOpen, 
  onClose, 
  sprint,
  tasks,
  stats,
  loading = false, 
  error = null,
  onRefresh,
  onTaskSelect,
  onBack,
  onEdit
}: SprintDetailModalProps) {

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

  const getProgressPercentage = () => {
    if (stats.total === 0) return 0
    return Math.round((stats.completed / stats.total) * 100)
  }

  // Sort tasks: In Progress, then Open (Todo), then Closed (Done)
  const sortedTasks = [...tasks].sort((a, b) => {
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

  // Pre-process tasks to normalize status values for filtering
  const tasksWithNormalizedStatus = sortedTasks.map(task => ({
    ...task,
    status_filter: task.status?.toLowerCase() === 'completed' ? 'done' : task.status?.toLowerCase()
  }))

  const filters = [
    {
      key: 'status_filter',
      label: 'Status',
      options: [
        { value: 'all', label: 'All', count: tasksWithNormalizedStatus.length },
        { value: 'in-progress', label: 'In Progress', count: stats.in_progress },
        { value: 'todo', label: 'Todo', count: stats.todo },
        { value: 'done', label: 'Done', count: stats.completed },
        { value: 'backlog', label: 'Backlog', count: stats.backlog }
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

  return (
    <DataManagementModal
      isOpen={isOpen}
      onClose={onClose}
      onBack={onBack}
      title={`Sprint: ${sprint.code}`}
      data={tasksWithNormalizedStatus}
      columns={columns}
      loading={loading}
      error={error}
      filters={filters}
      searchPlaceholder="Search tasks..."
      searchFields={['task_code', 'task_name', 'current_agent', 'agent_recommendation']}
      onAction={(action, task) => {
        if (action === 'view' || action === 'assign') {
          onTaskSelect?.(task)
        }
      }}
      actionItems={actionItems}
      clickableRows={true}
      onRowClick={onTaskSelect}
      onRefresh={onRefresh}
      customHeader={
        <div className="space-y-3">
          <div className="flex items-center justify-between gap-3">
            <div className="flex items-center gap-3">
              {onBack && (
                <Button 
                  variant="ghost" 
                  size="sm" 
                  onClick={onBack}
                  className="flex items-center gap-2 text-muted-foreground hover:text-foreground"
                >
                  <ArrowLeft className="h-4 w-4" />
                  Back to List
                </Button>
              )}
              <div className="text-sm text-muted-foreground">
                {sprint.title || sprint.meta?.title || 'Sprint Details'}
              </div>
            </div>
            {onEdit && (
              <Button 
                variant="outline" 
                size="sm" 
                onClick={() => onEdit(sprint)}
                className="flex items-center gap-2"
              >
                <BarChart3 className="h-4 w-4" />
                Edit Sprint
              </Button>
            )}
          </div>
          
          {/* Progress Section */}
          <div className="bg-muted/20 rounded-lg p-3">
            <div className="flex items-center justify-between mb-2">
              <span className="font-medium text-sm">Sprint Progress</span>
              <span className="text-sm text-muted-foreground">{getProgressPercentage()}%</span>
            </div>
            <div className="w-full bg-gray-200 rounded-full h-2 mb-3">
              <div 
                className="bg-green-500 h-2 rounded-full transition-all duration-300" 
                style={{ width: `${getProgressPercentage()}%` }}
              />
            </div>
            
            {/* Stats Grid */}
            <div className="grid grid-cols-4 gap-2 text-xs">
              <div className="text-center">
                <div className="font-medium text-green-600">{stats.completed}</div>
                <div className="text-muted-foreground">Completed</div>
              </div>
              <div className="text-center">
                <div className="font-medium text-blue-600">{stats.in_progress}</div>
                <div className="text-muted-foreground">In Progress</div>
              </div>
              <div className="text-center">
                <div className="font-medium text-yellow-600">{stats.todo}</div>
                <div className="text-muted-foreground">Todo</div>
              </div>
              <div className="text-center">
                <div className="font-medium text-gray-500">{stats.backlog}</div>
                <div className="text-muted-foreground">Backlog</div>
              </div>
            </div>
          </div>
        </div>
      }
      emptyStateMessage="No tasks found in this sprint"
      emptyStateIcon={<BarChart3 className="h-8 w-8" />}
    />
  )
}