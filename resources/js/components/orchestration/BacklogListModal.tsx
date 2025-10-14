
import { DataManagementModal, ColumnDefinition } from '@/components/ui/DataManagementModal'
import { Badge } from '@/components/ui/badge'
import { Inbox } from 'lucide-react'

interface BacklogTask {
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
  tags?: string[]
  created_at: string
  updated_at: string
  has_content?: {
    agent: boolean
    plan: boolean
    context: boolean
    todo: boolean
    summary: boolean
  }
}

interface BacklogListModalProps {
  isOpen: boolean
  onClose: () => void
  tasks: BacklogTask[]
  loading?: boolean
  error?: string | null
  onRefresh?: () => void
  onTaskSelect?: (task: BacklogTask) => void
}

export function BacklogListModal({ 
  isOpen, 
  onClose, 
  tasks, 
  loading = false, 
  error = null,
  onRefresh,
  onTaskSelect
}: BacklogListModalProps) {

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

  const sortedTasks = [...tasks].sort((a, b) => {
    const priorityOrder = { 'high': 1, 'medium': 2, 'low': 3 }
    const aPriority = priorityOrder[a.priority?.toLowerCase() as keyof typeof priorityOrder] || 999
    const bPriority = priorityOrder[b.priority?.toLowerCase() as keyof typeof priorityOrder] || 999
    
    if (aPriority !== bPriority) {
      return aPriority - bPriority
    }
    
    return new Date(b.created_at).getTime() - new Date(a.created_at).getTime()
  })

  const expandedContent = (task: BacklogTask) => (
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
            <div>Created: {new Date(task.created_at).toLocaleDateString()}</div>
            <div>Updated: {new Date(task.updated_at).toLocaleDateString()}</div>
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

  const columns: ColumnDefinition<BacklogTask>[] = [
    {
      key: 'task_code',
      label: 'Task',
      render: (task) => (
        <div className="flex-col">
          <span className="font-medium">{task.task_code}</span>
          <span className="text-xs text-muted-foreground truncate max-w-[200px]">
            {task.task_name}
          </span>
        </div>
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
      width: 'w-24',
      render: (task) => (
        <span className="text-xs text-muted-foreground">
          {task.estimate_text || '-'}
        </span>
      )
    },
    {
      key: 'created_at',
      label: 'Created',
      width: 'w-24',
      render: (task) => (
        <span className="text-xs text-muted-foreground">
          {new Date(task.created_at).toLocaleDateString()}
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
      key: 'priority',
      label: 'Priority',
      options: [
        { value: 'all', label: 'All', count: sortedTasks.length },
        { value: 'high', label: 'High', count: sortedTasks.filter(t => t.priority?.toLowerCase() === 'high').length },
        { value: 'medium', label: 'Medium', count: sortedTasks.filter(t => t.priority?.toLowerCase() === 'medium').length },
        { value: 'low', label: 'Low', count: sortedTasks.filter(t => t.priority?.toLowerCase() === 'low').length }
      ]
    },
    {
      key: 'delegation_status',
      label: 'Assignment',
      options: [
        { value: 'all', label: 'All', count: sortedTasks.length },
        { value: 'assigned', label: 'Assigned', count: sortedTasks.filter(t => t.delegation_status?.toLowerCase() === 'assigned').length },
        { value: 'unassigned', label: 'Unassigned', count: sortedTasks.filter(t => t.delegation_status?.toLowerCase() === 'unassigned').length }
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
      title="Backlog Items"
      data={sortedTasks}
      columns={columns}
      loading={loading}
      error={error ?? undefined}
      filters={filters}
      searchPlaceholder="Search backlog items..."
      searchFields={['task_code', 'task_name', 'description', 'current_agent', 'agent_recommendation']}
      onAction={(action, task) => {
        console.log('BacklogListModal action clicked:', action, task)
        if (action === 'view' || action === 'assign') {
          onTaskSelect?.(task)
        }
      }}
      actionItems={actionItems}
      clickableRows={true}
      onRowClick={(task) => {
        console.log('BacklogListModal row clicked:', task)
        onTaskSelect?.(task)
      }}
      onRefresh={onRefresh}
      customHeader={
        <div className="text-sm text-muted-foreground">
          Plan and prioritize future work items not yet assigned to sprints
        </div>
      }
      emptyStateMessage="No backlog items found"
      emptyStateIcon={<Inbox className="h-8 w-8" />}
      expandedContent={expandedContent}
    />
  )
}
