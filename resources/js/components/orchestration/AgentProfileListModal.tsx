import React from 'react'
import { DataManagementModal, ColumnDefinition } from '@/components/ui/DataManagementModal'
import { Badge } from '@/components/ui/badge'
import { Bot } from 'lucide-react'

interface AgentProfile {
  id: string
  name: string
  slug: string
  type?: string
  mode?: string
  status?: string
  description?: string
  capabilities?: string[]
  constraints?: string[]
  tools?: string[]
  active_assignments: number
  total_assignments: number
  updated_at: string
}

interface AgentProfileListModalProps {
  isOpen: boolean
  onClose: () => void
  agents: AgentProfile[]
  loading?: boolean
  error?: string | null
  onRefresh?: () => void
  onAgentSelect?: (agent: AgentProfile) => void
}

export function AgentProfileListModal({ 
  isOpen, 
  onClose, 
  agents, 
  loading = false, 
  error = null,
  onRefresh,
  onAgentSelect 
}: AgentProfileListModalProps) {

  const getStatusColor = (status?: string) => {
    switch (status?.toLowerCase()) {
      case 'active':
        return 'bg-green-100 text-green-800'
      case 'inactive':
        return 'bg-gray-100 text-gray-800'
      case 'pending':
        return 'bg-yellow-100 text-yellow-800'
      case 'blocked':
        return 'bg-red-100 text-red-800'
      default:
        return 'bg-blue-100 text-blue-800'
    }
  }

  const getTypeBadgeColor = (type?: string) => {
    switch (type?.toLowerCase()) {
      case 'specialist':
        return 'bg-purple-100 text-purple-800'
      case 'generalist':
        return 'bg-blue-100 text-blue-800'
      case 'senior':
        return 'bg-green-100 text-green-800'
      case 'junior':
        return 'bg-yellow-100 text-yellow-800'
      default:
        return 'bg-gray-100 text-gray-800'
    }
  }



  const columns: ColumnDefinition<AgentProfile>[] = [
    {
      key: 'name',
      label: 'Agent Profile',
      render: (agent) => (
        <div className="flex flex-col">
          <span className="font-medium">{agent.name}</span>
          <span className="text-xs text-muted-foreground">
            {agent.slug}
          </span>
        </div>
      )
    },
    {
      key: 'type',
      label: 'Type',
      width: 'w-24',
      render: (agent) => agent.type ? (
        <Badge variant="outline" className={`text-xs ${getTypeBadgeColor(agent.type)}`}>
          {agent.type}
        </Badge>
      ) : <span className="text-muted-foreground text-xs">-</span>
    },
    {
      key: 'status',
      label: 'Status',
      width: 'w-24',
      render: (agent) => (
        <Badge variant="outline" className={`text-xs ${getStatusColor(agent.status)}`}>
          {agent.status || 'active'}
        </Badge>
      )
    },
    {
      key: 'capabilities',
      label: 'Capabilities',
      width: 'w-32',
      render: (agent) => (
        <div className="flex flex-wrap gap-1">
          {(agent.capabilities || []).slice(0, 2).map(cap => (
            <Badge key={cap} variant="outline" className="text-xs">
              {cap}
            </Badge>
          ))}
          {(agent.capabilities?.length || 0) > 2 && (
            <Badge variant="outline" className="text-xs">
              +{(agent.capabilities?.length || 0) - 2}
            </Badge>
          )}
        </div>
      )
    },
    {
      key: 'active_assignments',
      label: 'Active',
      width: 'w-16',
      render: (agent) => (
        <div className="text-center">
          <span className="font-medium">{agent.active_assignments}</span>
        </div>
      )
    },
    {
      key: 'total_assignments',
      label: 'Total',
      width: 'w-16',
      render: (agent) => (
        <div className="text-center">
          <span className="text-muted-foreground">{agent.total_assignments}</span>
        </div>
      )
    },
    {
      key: 'updated_at',
      label: 'Updated',
      width: 'w-24',
      render: (agent) => (
        <span className="text-xs text-muted-foreground">
          {new Date(agent.updated_at).toLocaleDateString()}
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
        { value: 'all', label: 'All', count: agents.length },
        { value: 'active', label: 'Active', count: agents.filter(a => !a.status || a.status.toLowerCase() === 'active').length },
        { value: 'inactive', label: 'Inactive', count: agents.filter(a => a.status?.toLowerCase() === 'inactive').length },
        { value: 'pending', label: 'Pending', count: agents.filter(a => a.status?.toLowerCase() === 'pending').length }
      ]
    },
    {
      key: 'type',
      label: 'Type',
      options: [
        { value: 'all', label: 'All', count: agents.length },
        { value: 'specialist', label: 'Specialist', count: agents.filter(a => a.type?.toLowerCase() === 'specialist').length },
        { value: 'generalist', label: 'Generalist', count: agents.filter(a => a.type?.toLowerCase() === 'generalist').length },
        { value: 'senior', label: 'Senior', count: agents.filter(a => a.type?.toLowerCase() === 'senior').length },
        { value: 'junior', label: 'Junior', count: agents.filter(a => a.type?.toLowerCase() === 'junior').length }
      ]
    },
    {
      key: 'availability',
      label: 'Availability',
      options: [
        { value: 'all', label: 'All', count: agents.length },
        { value: 'available', label: 'Available', count: agents.filter(a => a.active_assignments === 0).length },
        { value: 'busy', label: 'Busy', count: agents.filter(a => a.active_assignments > 0).length }
      ]
    }
  ]

  const actionItems = [
    { key: 'view', label: 'View Profile' },
    { key: 'assign', label: 'Assign Task' }
  ]

  return (
    <DataManagementModal
      isOpen={isOpen}
      onClose={onClose}
      title="Agent Profile Management"
      data={agents}
      columns={columns}
      loading={loading}
      error={error ?? undefined}
      filters={filters}
      searchPlaceholder="Search agent profiles..."
      searchFields={['name', 'slug', 'description', 'capabilities', 'tools']}
      onAction={(action, agent) => {
        if (action === 'view' || action === 'assign') {
          onAgentSelect?.(agent)
        }
      }}
      actionItems={actionItems}
      clickableRows={true}
      onRowClick={onAgentSelect}
      onRefresh={onRefresh}
      customHeader={
        <div className="text-sm text-muted-foreground">
          Manage AI agent profile configurations and capabilities
        </div>
      }
      emptyStateMessage="No agent profiles found"
      emptyStateIcon={<Bot className="h-8 w-8" />}
      defaultSort="name"
      defaultSortDirection="asc"
    />
  )
}

export { AgentProfileListModal as AgentListModal }
