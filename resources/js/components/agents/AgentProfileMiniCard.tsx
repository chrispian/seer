import React from 'react'
import { useMemo } from 'react'
import { Badge } from '@/components/ui/badge'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import { 
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger 
} from '@/components/ui/dropdown-menu'
import { MoreVertical, Edit, Copy, Trash2 } from 'lucide-react'
import type { AgentProfile } from '@/types/agent-profile'

interface AgentProfileMiniCardProps {
  agent: AgentProfile
  onClick: (agent: AgentProfile) => void
  onEdit: (agent: AgentProfile) => void
  onDelete: (agent: AgentProfile) => void
  onDuplicate: (agent: AgentProfile) => void
}

export function AgentProfileMiniCard({ 
  agent, 
  onClick, 
  onEdit, 
  onDelete, 
  onDuplicate 
}: AgentProfileMiniCardProps) {
  const statusColor = useMemo(() => {
    const colors = ['bg-green-500', 'bg-yellow-500', 'bg-red-500']
    const hash = (agent.id || agent.slug).split('').reduce((acc, char) => acc + char.charCodeAt(0), 0)
    return colors[hash % colors.length]
  }, [agent.id, agent.slug])

  const getTypeBadgeColor = (type?: string) => {
    switch (type?.toLowerCase()) {
      case 'backend-engineer':
        return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300'
      case 'frontend-engineer':
        return 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300'
      case 'full-stack-engineer':
        return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
      case 'devops-engineer':
        return 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300'
      case 'data-engineer':
        return 'bg-pink-100 text-pink-800 dark:bg-pink-900 dark:text-pink-300'
      case 'qa-engineer':
        return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300'
      default:
        return 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300'
    }
  }

  const getModeBadgeColor = (mode?: string) => {
    switch (mode?.toLowerCase()) {
      case 'implementation':
        return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
      case 'planning':
        return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300'
      case 'review':
        return 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300'
      case 'research':
        return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300'
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
            <DropdownMenuItem onClick={(e) => handleMenuAction(e, () => onDuplicate(agent))}>
              <Copy className="mr-2 h-4 w-4" />
              Duplicate
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
          <Avatar className="w-12 h-12 border-2 border-background shadow-sm flex-shrink-0">
            <AvatarFallback className="text-lg font-semibold">
              {agent.name.charAt(0).toUpperCase()}
            </AvatarFallback>
          </Avatar>
          <div className="flex-1 min-w-0">
            <h3 className="font-semibold text-lg truncate pr-12">{agent.name}</h3>
            <p className="text-xs text-muted-foreground truncate">{agent.slug}</p>
          </div>
        </div>

        <div className="flex gap-2 flex-wrap">
          {agent.type && (
            <Badge variant="secondary" className={`text-xs ${getTypeBadgeColor(agent.type)}`}>
              {agent.type}
            </Badge>
          )}
          {agent.mode && (
            <Badge variant="outline" className={`text-xs ${getModeBadgeColor(agent.mode)}`}>
              {agent.mode}
            </Badge>
          )}
        </div>

        {agent.description && (
          <p className="text-sm text-muted-foreground line-clamp-2">
            {agent.description}
          </p>
        )}

        {agent.capabilities && agent.capabilities.length > 0 && (
          <div className="flex gap-1 items-center text-xs text-muted-foreground">
            <span>{agent.capabilities.length} capabilities</span>
          </div>
        )}
      </div>
    </div>
  )
}
