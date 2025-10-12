import { DataManagementModal, ColumnDefinition } from '@/components/ui/DataManagementModal'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Plus } from 'lucide-react'

interface Sprint {
  id: string
  code: string
  title: string
  status: string
  task_count: number
  completed_tasks: number
  in_progress_tasks: number
  todo_tasks: number
  backlog_tasks: number
  priority?: string
  created_at: string
  updated_at: string
  meta?: {
    title?: string
    priority?: string
    impact?: string
    estimated?: string
  }
}

interface Task {
  task_code: string
  task_name: string
  delegation_status: string
  status: string
  agent_recommendation?: string
  current_agent?: string
  estimate_text?: string
  priority?: string
  type?: string
}

interface SprintListModalProps {
  isOpen: boolean
  onClose: () => void
  sprints?: Sprint[]
  unassigned_tasks?: Task[]
  loading?: boolean
  error?: string | null
  onRefresh?: () => void
  onSprintSelect?: (sprint: Sprint) => void
  onItemSelect?: (sprint: Sprint) => void
  onCreate?: () => void
}

export function SprintListModal({ 
  isOpen, 
  onClose, 
  sprints = [], 
  unassigned_tasks = [],
  loading = false, 
  error = null,
  onRefresh,
  onSprintSelect,
  onItemSelect,
  onCreate
}: SprintListModalProps) {
  const handleSprintClick = onSprintSelect || onItemSelect

  const handleCreateClick = () => {
    console.log('[SprintListModal] Create button clicked, onCreate exists?', !!onCreate)
    if (onCreate) {
      onCreate()
    } else {
      console.error('[SprintListModal] onCreate handler not provided!')
    }
  }

  const getStatusColor = (status: string) => {
    switch (status?.toLowerCase()) {
      case 'active':
      case 'in-progress':
        return 'bg-blue-100 text-blue-800'
      case 'completed':
      case 'done':
        return 'bg-green-100 text-green-800'
      case 'planning':
        return 'bg-yellow-100 text-yellow-800'
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

  const getProgressPercentage = (sprint: Sprint) => {
    const total = sprint.task_count
    if (total === 0) return 0
    return Math.round((sprint.completed_tasks / total) * 100)
  }



  const columns: ColumnDefinition<Sprint | any>[] = [
    {
      key: 'code',
      label: 'Sprint',
      render: (sprint) => (
        <div className="flex flex-col">
          <span className="font-medium">{sprint.code}</span>
          <span className="text-xs text-muted-foreground truncate max-w-[200px]">
            {sprint.title || sprint.meta?.title || 'No title'}
          </span>
        </div>
      )
    },
    {
      key: 'status',
      label: 'Status',
      width: 'w-24',
      render: (sprint) => (
        <Badge variant="outline" className={`text-xs ${getStatusColor(sprint.status)}`}>
          {sprint.status || 'Active'}
        </Badge>
      )
    },
    {
      key: 'priority',
      label: 'Priority',
      width: 'w-20',
      render: (sprint) => sprint.meta?.priority ? (
        <Badge variant="outline" className={`text-xs ${getPriorityColor(sprint.meta.priority)}`}>
          {sprint.meta.priority}
        </Badge>
      ) : <span className="text-muted-foreground text-xs">-</span>
    },
    {
      key: 'progress',
      label: 'Progress',
      width: 'w-32',
      render: (sprint) => (
        <div className="flex items-center gap-2">
          <div className="w-16 bg-gray-200 rounded-full h-1.5">
            <div 
              className="bg-green-500 h-1.5 rounded-full" 
              style={{ width: `${getProgressPercentage(sprint)}%` }}
            />
          </div>
          <span className="text-xs text-muted-foreground">
            {sprint.completed_tasks}/{sprint.task_count}
          </span>
        </div>
      )
    },
    {
      key: 'task_count',
      label: 'Tasks',
      width: 'w-16',
      render: (sprint) => (
        <div className="text-center">
          <span className="font-medium">{sprint.task_count}</span>
        </div>
      )
    },
    {
      key: 'updated_at',
      label: 'Updated',
      width: 'w-24',
      render: (sprint) => (
        <span className="text-xs text-muted-foreground">
          {new Date(sprint.updated_at).toLocaleDateString()}
        </span>
      )
    },
    {
      key: 'actions',
      label: '',
      width: 'w-12'
    }
  ]

  // Create virtual sprint for unassigned tasks
  const unassignedSprint: Sprint | null = unassigned_tasks.length > 0 ? {
    id: 'unassigned',
    code: 'UNASSIGNED',
    title: 'Tasks Without Sprint',
    status: 'Unassigned',
    task_count: unassigned_tasks.length,
    completed_tasks: unassigned_tasks.filter(t => t.delegation_status === 'completed').length,
    in_progress_tasks: unassigned_tasks.filter(t => t.delegation_status === 'in_progress' || t.delegation_status === 'assigned').length,
    todo_tasks: unassigned_tasks.filter(t => t.delegation_status === 'unassigned').length,
    backlog_tasks: 0,
    created_at: new Date().toISOString(),
    updated_at: new Date().toISOString(),
  } : null

  // Combine sprints with unassigned virtual sprint at top if it exists
  const displaySprints = unassignedSprint ? [unassignedSprint, ...sprints] : sprints

  const filters = [
    {
      key: 'status',
      label: 'Status',
      options: [
        { value: 'all', label: 'All', count: displaySprints.length },
        { value: 'active', label: 'Active', count: displaySprints.filter(s => !s.status || s.status.toLowerCase() === 'active').length },
        { value: 'completed', label: 'Completed', count: displaySprints.filter(s => s.status?.toLowerCase() === 'completed' || s.status?.toLowerCase() === 'done').length },
        { value: 'planning', label: 'Planning', count: displaySprints.filter(s => s.status?.toLowerCase() === 'planning').length },
        { value: 'unassigned', label: 'Unassigned', count: unassigned_tasks.length }
      ]
    },
    {
      key: 'priority',
      label: 'Priority',
      options: [
        { value: 'all', label: 'All', count: displaySprints.length },
        { value: 'high', label: 'High', count: displaySprints.filter(s => s.meta?.priority?.toLowerCase() === 'high').length },
        { value: 'medium', label: 'Medium', count: displaySprints.filter(s => s.meta?.priority?.toLowerCase() === 'medium').length },
        { value: 'low', label: 'Low', count: displaySprints.filter(s => s.meta?.priority?.toLowerCase() === 'low').length }
      ]
    }
  ]

  const actionItems = [
    { key: 'view', label: 'View Sprint' },
    { key: 'tasks', label: 'View Tasks' }
  ]

  return (
    <>
      <DataManagementModal
        isOpen={isOpen}
        onClose={onClose}
        title="Sprint Management"
        data={displaySprints}
        columns={columns}
        loading={loading}
        error={error || undefined}
        filters={filters}
        searchPlaceholder="Search sprints..."
        searchFields={['code', 'title']}
        onAction={(action, sprint) => {
          if (action === 'view' || action === 'tasks') {
            onSprintSelect?.(sprint)
          }
        }}
        actionItems={actionItems}
        clickableRows={true}
        onRowClick={handleSprintClick}
        onRefresh={onRefresh}
        customHeader={
          <div className="flex items-center gap-2 mt-2">
            <Button 
              size="sm" 
              onClick={handleCreateClick}
              className="gap-2"
            >
              <Plus className="h-4 w-4" />
              Create Sprint
            </Button>
          </div>
        }
      />
    </>
  )
}