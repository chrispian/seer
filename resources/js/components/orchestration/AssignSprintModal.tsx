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
import { Loader2, Calendar } from 'lucide-react'

interface Sprint {
  id: string
  code: string
  title?: string
  status?: string
  task_count?: number
  completed_tasks?: number
}

interface AssignSprintModalProps {
  isOpen: boolean
  onClose: () => void
  taskCode: string
  currentSprintCode?: string | null
  onAssign: (sprintCode: string) => Promise<void>
}

export function AssignSprintModal({
  isOpen,
  onClose,
  taskCode,
  currentSprintCode,
  onAssign,
}: AssignSprintModalProps) {
  const [sprints, setSprints] = useState<Sprint[]>([])
  const [loading, setLoading] = useState(false)
  const [assigning, setAssigning] = useState(false)

  useEffect(() => {
    if (!isOpen) return

    const loadSprints = async () => {
      setLoading(true)
      try {
        const response = await fetch('/api/orchestration/tasks/sprints/available')
        if (response.ok) {
          const data = await response.json()
          setSprints(data.sprints || [])
        }
      } catch (error) {
        console.error('Failed to load sprints:', error)
      } finally {
        setLoading(false)
      }
    }

    loadSprints()
  }, [isOpen])

  const handleSelect = async (sprintCode: string) => {
    setAssigning(true)
    try {
      await onAssign(sprintCode)
      onClose()
    } catch (error) {
      console.error('Failed to assign sprint:', error)
    } finally {
      setAssigning(false)
    }
  }

  const getStatusColor = (status?: string) => {
    switch (status?.toLowerCase()) {
      case 'active':
      case 'in-progress':
        return 'bg-blue-100 text-blue-800'
      case 'completed':
      case 'done':
        return 'bg-green-100 text-green-800'
      case 'planning':
        return 'bg-yellow-100 text-yellow-800'
      default:
        return 'bg-gray-100 text-gray-800'
    }
  }

  return (
    <CommandDialog open={isOpen} onOpenChange={onClose}>
      <CommandInput placeholder="Search sprints..." />
      <CommandList>
        {loading ? (
          <div className="flex items-center justify-center py-6">
            <Loader2 className="h-4 w-4 animate-spin" />
          </div>
        ) : (
          <>
            <CommandEmpty>No sprints found.</CommandEmpty>
            <CommandGroup heading={`Assign ${taskCode} to Sprint`}>
              {sprints.map((sprint) => (
                <CommandItem
                  key={sprint.id}
                  value={`${sprint.code} ${sprint.title}`}
                  onSelect={() => handleSelect(sprint.code)}
                  disabled={assigning || sprint.code === currentSprintCode}
                >
                  <Calendar className="mr-2 h-4 w-4" />
                  <div className="flex items-center gap-2 flex-1">
                    <span className="font-medium">{sprint.code}</span>
                    {sprint.title && (
                      <span className="text-xs text-muted-foreground truncate">
                        {sprint.title}
                      </span>
                    )}
                  </div>
                  <div className="flex items-center gap-2">
                    {sprint.status && (
                      <Badge variant="outline" className={`text-xs ${getStatusColor(sprint.status)}`}>
                        {sprint.status}
                      </Badge>
                    )}
                    {sprint.task_count !== undefined && (
                      <span className="text-xs text-muted-foreground">
                        {sprint.completed_tasks || 0}/{sprint.task_count}
                      </span>
                    )}
                    {sprint.code === currentSprintCode && (
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
