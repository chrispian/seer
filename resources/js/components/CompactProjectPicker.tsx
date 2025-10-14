import { useState, useEffect } from 'react'
import { Check, ChevronsUpDown, FolderOpen } from 'lucide-react'
import { cn } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import {
  Command,
  CommandEmpty,
  CommandGroup,
  CommandInput,
  CommandItem,
  CommandList,
} from '@/components/ui/command'
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from '@/components/ui/popover'

interface Project {
  id: number
  name: string
  description?: string
  path?: string
}

interface CompactProjectPickerProps {
  value?: number | null
  onValueChange?: (value: number | null) => void
  disabled?: boolean
  className?: string
}

export function CompactProjectPicker({
  value = null,
  onValueChange,
  disabled = false,
  className,
}: CompactProjectPickerProps) {
  const [open, setOpen] = useState(false)
  const [projects, setProjects] = useState<Project[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    const fetchProjects = async () => {
      try {
        setLoading(true)
        setError(null)
        
        const response = await fetch('/api/chat/projects')
        
        if (!response.ok) {
          throw new Error(`HTTP ${response.status}: ${response.statusText}`)
        }
        
        const result = await response.json()
        
        if (result.success && Array.isArray(result.data)) {
          setProjects(result.data)
        } else {
          setError('Invalid response format')
        }
      } catch (err) {
        const errorMessage = err instanceof Error ? err.message : 'Failed to load projects'
        setError(errorMessage)
        console.error('Error fetching projects:', err)
      } finally {
        setLoading(false)
      }
    }

    fetchProjects()
  }, [])

  const selectedProject = projects.find(p => p.id === value)

  const handleSelect = (selectedValue: number) => {
    onValueChange?.(selectedValue === value ? null : selectedValue)
    setOpen(false)
  }

  const getDisplayText = () => {
    if (selectedProject) {
      return selectedProject.path 
        ? `${selectedProject.name} (${selectedProject.path})`
        : selectedProject.name
    }
    return loading ? 'Loading...' : 'No project'
  }

  return (
    <Popover open={open} onOpenChange={setOpen}>
      <PopoverTrigger asChild>
        <Button
          variant="ghost"
          role="combobox"
          aria-expanded={open}
          disabled={disabled}
          className={cn(
            'h-6 px-2 py-1 text-xs font-normal justify-start gap-1.5 text-gray-300 hover:text-white hover:bg-gray-800/50',
            !selectedProject && 'text-gray-400',
            className
          )}
        >
          <FolderOpen className="h-3 w-3 shrink-0" />
          <span className="truncate max-w-[150px]">
            {getDisplayText()}
          </span>
          <ChevronsUpDown className="ml-auto h-3 w-3 shrink-0 opacity-50" />
        </Button>
      </PopoverTrigger>
      <PopoverContent className="w-[300px] p-0" align="start" side="top">
        <Command>
          <CommandInput 
            placeholder="Search projects..." 
            className="h-8" 
          />
          <CommandList>
            <CommandEmpty>
              {loading 
                ? 'Loading projects...' 
                : error 
                  ? `Error: ${error}` 
                  : projects.length === 0 
                    ? 'No projects available.'
                    : 'No projects found.'
              }
            </CommandEmpty>
            
            {!loading && !error && projects.length > 0 && (
              <CommandGroup heading="Projects">
                {projects.map((project) => (
                  <CommandItem
                    key={project.id}
                    value={`${project.name} ${project.description || ''}`}
                    onSelect={() => handleSelect(project.id)}
                    className="flex items-center justify-between text-xs"
                  >
                    <div className="flex items-center gap-2 min-w-0">
                      <Check
                        className={cn(
                          'h-3 w-3',
                          value === project.id ? 'opacity-100' : 'opacity-0'
                        )}
                      />
                      <div className="min-w-0">
                        <div className="truncate font-medium">{project.name}</div>
                        {project.path && (
                          <div className="text-xs text-muted-foreground truncate">
                            {project.path}
                          </div>
                        )}
                        {project.description && !project.path && (
                          <div className="text-xs text-muted-foreground truncate">
                            {project.description}
                          </div>
                        )}
                      </div>
                    </div>
                  </CommandItem>
                ))}
              </CommandGroup>
            )}
          </CommandList>
        </Command>
      </PopoverContent>
    </Popover>
  )
}
