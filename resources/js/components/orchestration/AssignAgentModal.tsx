import { useState, useEffect } from 'react'
import {
  CommandDialog,
  CommandEmpty,
  CommandGroup,
  CommandInput,
  CommandItem,
  CommandList,
} from '@/components/ui/command'
import { Badge } from '@/components/ui/badge'
import { Loader2, User } from 'lucide-react'

interface Agent {
  id: string
  name: string
  designation?: string
  status?: string
}

interface AssignAgentModalProps {
  isOpen: boolean
  onClose: () => void
  taskCode: string
  currentAgentId?: string | null
  onAssign: (agentId: string) => Promise<void>
}

export function AssignAgentModal({
  isOpen,
  onClose,
  taskCode,
  currentAgentId,
  onAssign,
}: AssignAgentModalProps) {
  const [agents, setAgents] = useState<Agent[]>([])
  const [loading, setLoading] = useState(false)
  const [assigning, setAssigning] = useState(false)

  useEffect(() => {
    if (!isOpen) return

    const loadAgents = async () => {
      setLoading(true)
      try {
        const response = await fetch('/api/agent-profiles')
        if (response.ok) {
          const data = await response.json()
          console.log('[AssignAgentModal] Loaded agent profiles:', data)
          // API returns array directly, not wrapped in {data: ...}
          setAgents(Array.isArray(data) ? data : (data.data || data.agents || []))
        } else {
          console.error('[AssignAgentModal] Failed to fetch agent profiles:', response.status)
        }
      } catch (error) {
        console.error('Failed to load agents:', error)
      } finally {
        setLoading(false)
      }
    }

    loadAgents()
  }, [isOpen])

  const handleSelect = async (agentId: string) => {
    setAssigning(true)
    try {
      await onAssign(agentId)
      onClose()
    } catch (error) {
      console.error('Failed to assign agent:', error)
    } finally {
      setAssigning(false)
    }
  }

  const getStatusColor = (status?: string) => {
    switch (status?.toLowerCase()) {
      case 'active':
        return 'bg-green-100 text-green-800'
      case 'inactive':
        return 'bg-gray-100 text-gray-800'
      default:
        return 'bg-blue-100 text-blue-800'
    }
  }

  return (
    <CommandDialog open={isOpen} onOpenChange={onClose}>
      <CommandInput placeholder="Search agents..." />
      <CommandList>
        {loading ? (
          <div className="flex items-center justify-center py-6">
            <Loader2 className="h-4 w-4 animate-spin" />
          </div>
        ) : (
          <>
            <CommandEmpty>No agents found.</CommandEmpty>
            <CommandGroup heading={`Assign ${taskCode} to Agent`}>
              <CommandItem
                value="unassign"
                onSelect={() => handleSelect('')}
                disabled={assigning || !currentAgentId}
              >
                <User className="mr-2 h-4 w-4" />
                <div className="flex items-center gap-2 flex-1">
                  <span className="font-medium">Unassign</span>
                  <span className="text-xs text-muted-foreground">Remove current assignment</span>
                </div>
              </CommandItem>
              {agents.map((agent) => (
                <CommandItem
                  key={agent.id}
                  value={`${agent.name} ${agent.designation || ''}`}
                  onSelect={() => handleSelect(agent.id)}
                  disabled={assigning || agent.id === currentAgentId}
                >
                  <User className="mr-2 h-4 w-4" />
                  <div className="flex items-center gap-2 flex-1">
                    <span className="font-medium">{agent.name}</span>
                    {agent.designation && (
                      <span className="text-xs text-muted-foreground truncate">
                        {agent.designation}
                      </span>
                    )}
                  </div>
                  <div className="flex items-center gap-2">
                    {agent.status && (
                      <Badge variant="outline" className={`text-xs ${getStatusColor(agent.status)}`}>
                        {agent.status}
                      </Badge>
                    )}
                    {agent.id === currentAgentId && (
                      <Badge variant="secondary" className="text-xs">
                        Current
                      </Badge>
                    )}
                  </div>
                </CommandItem>
              ))}
            </CommandGroup>
          </>
        )}
      </CommandList>
    </CommandDialog>
  )
}
