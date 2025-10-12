import React from 'react'
import { useMemo } from 'react'
import { Badge } from '@/components/ui/badge'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { 
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger 
} from '@/components/ui/dropdown-menu'
import { MoreVertical, Edit, Trash2 } from 'lucide-react'
import type { Agent } from '@/types/agent'

interface AgentMiniCardProps {
  agent: Agent
  onClick: (agent: Agent) => void
  onEdit: (agent: Agent) => void
  onDelete: (agent: Agent) => void
}

export function AgentMiniCard({ 
  agent, 
  onClick, 
  onEdit, 
  onDelete 
}: AgentMiniCardProps) {
  const statusColor = useMemo(() => {
    const colors = ['bg-green-500', 'bg-yellow-500', 'bg-red-500']
    const hash = (agent.id || agent.designation).split('').reduce((acc, char) => acc + char.charCodeAt(0), 0)
    return colors[hash % colors.length]
  }, [agent.id, agent.designation])

  const getStatusBadgeColor = (status?: string) => {
    switch (status?.toLowerCase()) {
      case 'active':
        return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
      case 'inactive':
        return 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300'
      case 'archived':
        return 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300'
      default:
        return 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300'
    }
  }

  const handleMenuAction = (e: React.MouseEvent, action: () => void) => {
    e.stopPropagation()
    action()
  }

  return (
    <div
      onClick={() => onClick(agent)}
      className="relative border rounded-lg p-4 bg-card hover:shadow-lg 
                 transition-all duration-200 cursor-pointer hover:-translate-y-1
                 active:scale-[0.98]"
    >
      <div className={`absolute top-3 right-3 w-2 h-2 rounded-full ${statusColor} shadow-sm`} />

      <div className="absolute top-3 right-8">
        <DropdownMenu>
          <DropdownMenuTrigger 
            onClick={(e) => e.stopPropagation()}
            className="hover:bg-accent rounded p-1 transition-colors"
          >
            <MoreVertical className="h-4 w-4 text-muted-foreground" />
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end">
            <DropdownMenuItem onClick={(e) => handleMenuAction(e, () => onEdit(agent))}>
              <Edit className="mr-2 h-4 w-4" />
              Edit
            </DropdownMenuItem>
            <DropdownMenuItem 
              onClick={(e) => handleMenuAction(e, () => onDelete(agent))}
              className="text-destructive focus:text-destructive"
            >
              <Trash2 className="mr-2 h-4 w-4" />
              Delete
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
      </div>

      <div className="space-y-3 mt-2">
        <div className="flex items-start gap-3">
          <Avatar className="w-12 h-12 border-2 border-background shadow-sm">
            <AvatarImage 
              src={agent.avatar_url || '/interface/avatars/default/avatar-1.png'} 
              alt={agent.name} 
            />
            <AvatarFallback>{agent.name.substring(0, 2).toUpperCase()}</AvatarFallback>
          </Avatar>
          <div className="flex-1">
            <div className="flex items-center gap-2">
              <h3 className="font-semibold text-lg">{agent.name}</h3>
              <Badge variant="outline" className="font-mono text-xs">
                {agent.designation}
              </Badge>
            </div>
            {agent.agent_profile && (
              <p className="text-xs text-muted-foreground mt-1">
                Profile: {agent.agent_profile.name}
              </p>
            )}
          </div>
        </div>

        <div className="flex gap-2 flex-wrap">
          {agent.status && (
            <Badge variant="secondary" className={`text-xs ${getStatusBadgeColor(agent.status)}`}>
              {agent.status}
            </Badge>
          )}
          <Badge variant="outline" className="text-xs">
            v{agent.version}
          </Badge>
        </div>

        {agent.persona && (
          <p className="text-sm text-muted-foreground line-clamp-2">
            {agent.persona}
          </p>
        )}
      </div>
    </div>
  )
}
