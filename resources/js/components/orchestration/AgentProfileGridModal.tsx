import { useState } from 'react'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import { Bot, Search, RefreshCw } from 'lucide-react'
import { AgentProfileMiniCard } from '@/components/agents/AgentProfileMiniCard'
import type { AgentProfile } from '@/types/agent-profile'

interface AgentProfileGridModalProps {
  isOpen: boolean
  onClose: () => void
  agents: AgentProfile[]
  loading?: boolean
  error?: string | null
  onRefresh?: () => void
  onAgentSelect?: (agent: AgentProfile) => void
}

export function AgentProfileGridModal({ 
  isOpen, 
  onClose, 
  agents, 
  loading = false, 
  error = null,
  onRefresh,
  onAgentSelect 
}: AgentProfileGridModalProps) {
  const [searchQuery, setSearchQuery] = useState('')
  const [statusFilter, setStatusFilter] = useState<string>('all')
  const [typeFilter, setTypeFilter] = useState<string>('all')

  // Filter agents based on search and filters
  const filteredAgents = agents.filter(agent => {
    // Search filter
    const searchLower = searchQuery.toLowerCase()
    const matchesSearch = !searchQuery || 
      agent.name.toLowerCase().includes(searchLower) ||
      agent.slug.toLowerCase().includes(searchLower) ||
      agent.description?.toLowerCase().includes(searchLower) ||
      agent.capabilities?.some(cap => cap.toLowerCase().includes(searchLower))

    // Status filter
    const matchesStatus = statusFilter === 'all' || 
      (agent.status?.toLowerCase() === statusFilter.toLowerCase()) ||
      (statusFilter === 'active' && !agent.status)

    // Type filter
    const matchesType = typeFilter === 'all' || 
      agent.type?.toLowerCase() === typeFilter.toLowerCase()

    return matchesSearch && matchesStatus && matchesType
  })

  // Calculate filter counts
  const statusCounts = {
    all: agents.length,
    active: agents.filter(a => !a.status || a.status.toLowerCase() === 'active').length,
    inactive: agents.filter(a => a.status?.toLowerCase() === 'inactive').length,
    archived: agents.filter(a => a.status?.toLowerCase() === 'archived').length,
  }

  const typeCounts = {
    all: agents.length,
    'backend-engineer': agents.filter(a => a.type?.toLowerCase() === 'backend-engineer').length,
    'frontend-engineer': agents.filter(a => a.type?.toLowerCase() === 'frontend-engineer').length,
    'full-stack-engineer': agents.filter(a => a.type?.toLowerCase() === 'full-stack-engineer').length,
    'devops-engineer': agents.filter(a => a.type?.toLowerCase() === 'devops-engineer').length,
  }

  const handleAgentClick = (agent: AgentProfile) => {
    if (onAgentSelect) {
      onAgentSelect(agent)
    }
  }

  // Empty handlers for mini card actions (modal context doesn't need edit/delete)
  const handleEdit = () => {}
  const handleDelete = () => {}
  const handleDuplicate = () => {}

  return (
    <Dialog open={isOpen} onOpenChange={onClose}>
      <DialogContent className="max-w-7xl h-[90vh] p-0 flex flex-col">
        <DialogHeader className="px-6 py-4 border-b flex-shrink-0">
          <div className="flex items-center justify-between">
            <div>
              <DialogTitle className="text-2xl">Agent Profiles</DialogTitle>
              <p className="text-sm text-muted-foreground mt-1">
                Browse and select agent profile configurations
              </p>
            </div>
            {onRefresh && (
              <Button
                variant="outline"
                size="sm"
                onClick={onRefresh}
                disabled={loading}
              >
                <RefreshCw className={`h-4 w-4 mr-2 ${loading ? 'animate-spin' : ''}`} />
                Refresh
              </Button>
            )}
          </div>
        </DialogHeader>

        {/* Filters and Search */}
        <div className="px-6 py-4 border-b space-y-4 flex-shrink-0">
          {/* Search */}
          <div className="relative">
            <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
            <Input
              placeholder="Search by name, slug, description, or capabilities..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              className="pl-10"
            />
          </div>

          {/* Filter Tabs */}
          <div className="space-y-2">
            {/* Status Filter */}
            <div className="flex items-center gap-2">
              <span className="text-sm font-medium text-muted-foreground min-w-[60px]">Status:</span>
              <div className="flex gap-2 flex-wrap">
                {(Object.keys(statusCounts) as Array<keyof typeof statusCounts>).map(status => (
                  <Button
                    key={status}
                    variant={statusFilter === status ? 'default' : 'outline'}
                    size="sm"
                    onClick={() => setStatusFilter(status)}
                    className="text-xs"
                  >
                    {status.charAt(0).toUpperCase() + status.slice(1)}
                    <Badge variant="secondary" className="ml-2 text-xs">
                      {statusCounts[status]}
                    </Badge>
                  </Button>
                ))}
              </div>
            </div>

            {/* Type Filter */}
            <div className="flex items-center gap-2">
              <span className="text-sm font-medium text-muted-foreground min-w-[60px]">Type:</span>
              <div className="flex gap-2 flex-wrap">
                {(Object.keys(typeCounts) as Array<keyof typeof typeCounts>).map(type => {
                  if (typeCounts[type] === 0 && type !== 'all') return null
                  const displayName = type === 'all' ? 'All' : 
                    type.split('-').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ')
                  return (
                    <Button
                      key={type}
                      variant={typeFilter === type ? 'default' : 'outline'}
                      size="sm"
                      onClick={() => setTypeFilter(type)}
                      className="text-xs"
                    >
                      {displayName}
                      <Badge variant="secondary" className="ml-2 text-xs">
                        {typeCounts[type]}
                      </Badge>
                    </Button>
                  )
                })}
              </div>
            </div>
          </div>

          {/* Results Count */}
          <div className="text-sm text-muted-foreground">
            Showing {filteredAgents.length} of {agents.length} agent profiles
          </div>
        </div>

        {/* Content */}
        <div className="flex-1 overflow-y-auto px-6 py-4">
          {loading ? (
            <SkeletonGrid />
          ) : error ? (
            <div className="flex flex-col items-center justify-center py-16 text-center">
              <div className="text-destructive mb-2">Error loading agent profiles</div>
              <p className="text-sm text-muted-foreground">{error}</p>
            </div>
          ) : filteredAgents.length === 0 ? (
            <EmptyState hasAgents={agents.length > 0} searchQuery={searchQuery} />
          ) : (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 pb-4">
              {filteredAgents.map(agent => (
                <AgentProfileMiniCard
                  key={agent.id || agent.slug}
                  agent={agent}
                  onClick={handleAgentClick}
                  onEdit={handleEdit}
                  onDelete={handleDelete}
                  onDuplicate={handleDuplicate}
                />
              ))}
            </div>
          )}
        </div>
      </DialogContent>
    </Dialog>
  )
}

function SkeletonGrid() {
  return (
    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
      {[1, 2, 3, 4, 5, 6, 7, 8].map(i => (
        <div key={i} className="border rounded-lg p-4 space-y-3 animate-pulse">
          <div className="h-4 bg-gray-200 rounded w-3/4" />
          <div className="h-3 bg-gray-200 rounded w-1/2" />
          <div className="flex gap-2">
            <div className="h-6 bg-gray-200 rounded w-16" />
            <div className="h-6 bg-gray-200 rounded w-16" />
          </div>
          <div className="h-16 bg-gray-200 rounded" />
          <div className="h-4 bg-gray-200 rounded w-24" />
        </div>
      ))}
    </div>
  )
}

function EmptyState({ hasAgents, searchQuery }: { hasAgents: boolean; searchQuery: string }) {
  return (
    <div className="flex flex-col items-center justify-center py-16 text-center">
      <Bot className="h-16 w-16 text-muted-foreground mb-4" />
      <h3 className="text-lg font-semibold mb-2">
        {hasAgents ? 'No matching agent profiles' : 'No agent profiles found'}
      </h3>
      <p className="text-muted-foreground max-w-sm">
        {hasAgents && searchQuery
          ? `No agent profiles match your search "${searchQuery}". Try adjusting your filters.`
          : 'No agent profiles have been created yet.'}
      </p>
    </div>
  )
}

// Export as AgentListModal for compatibility
export { AgentProfileGridModal as AgentListModal }
