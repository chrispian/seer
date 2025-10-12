import React from 'react'
import { useState, useEffect } from 'react'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger
} from '@/components/ui/dropdown-menu'
import { TagInput } from '@/components/ui/tag-input'
import { MoreVertical, Copy, Trash2 } from 'lucide-react'
import { ScrollArea } from '@/components/ui/scroll-area'
import type { AgentProfile } from '@/types/agent-profile'

interface AgentProfileEditorProps {
  agent?: AgentProfile | null
  isOpen: boolean
  onClose: () => void
  onSave: (agent: Partial<AgentProfile>) => void
  onDelete?: (agent: AgentProfile) => void
  onDuplicate?: (agent: AgentProfile) => void
}

const TYPE_OPTIONS = [
  { value: 'backend-engineer', label: 'Backend Engineer', defaultMode: 'implementation' },
  { value: 'frontend-engineer', label: 'Frontend Engineer', defaultMode: 'implementation' },
  { value: 'full-stack-engineer', label: 'Full Stack Engineer', defaultMode: 'implementation' },
  { value: 'devops-engineer', label: 'DevOps Engineer', defaultMode: 'implementation' },
  { value: 'data-engineer', label: 'Data Engineer', defaultMode: 'implementation' },
  { value: 'qa-engineer', label: 'QA Engineer', defaultMode: 'review' },
]

const MODE_OPTIONS = [
  { value: 'implementation', label: 'Implementation' },
  { value: 'planning', label: 'Planning' },
  { value: 'review', label: 'Review' },
  { value: 'research', label: 'Research' },
]

const STATUS_OPTIONS = [
  { value: 'active', label: 'Active' },
  { value: 'inactive', label: 'Inactive' },
  { value: 'archived', label: 'Archived' },
]

export function AgentProfileEditor({
  agent,
  isOpen,
  onClose,
  onSave,
  onDelete,
  onDuplicate
}: AgentProfileEditorProps) {
  const isEditMode = !!agent

  const [formData, setFormData] = useState<Partial<AgentProfile>>({
    name: '',
    slug: '',
    type: '',
    mode: '',
    status: 'active',
    description: '',
    capabilities: [],
    constraints: [],
    tools: [],
    metadata: {}
  })

  const [errors, setErrors] = useState<Record<string, string>>({})
  const [isDirty, setIsDirty] = useState(false)

  useEffect(() => {
    if (agent) {
      setFormData(agent)
    } else {
      setFormData({
        name: '',
        slug: '',
        type: '',
        mode: '',
        status: 'active',
        description: '',
        capabilities: [],
        constraints: [],
        tools: [],
        metadata: {}
      })
    }
    setIsDirty(false)
    setErrors({})
  }, [agent, isOpen])

  useEffect(() => {
    if (!isEditMode && formData.name) {
      const slug = formData.name
        .toLowerCase()
        .replace(/[^\w\s-]/g, '')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-')
        .trim()
      setFormData(prev => ({ ...prev, slug }))
    }
  }, [formData.name, isEditMode])

  useEffect(() => {
    if (formData.type && !formData.mode) {
      const typeOption = TYPE_OPTIONS.find(t => t.value === formData.type)
      if (typeOption) {
        setFormData(prev => ({ ...prev, mode: typeOption.defaultMode }))
      }
    }
  }, [formData.type])

  const handleChange = (field: keyof AgentProfile, value: any) => {
    setFormData(prev => ({ ...prev, [field]: value }))
    setIsDirty(true)
    if (errors[field]) {
      setErrors(prev => ({ ...prev, [field]: '' }))
    }
  }

  const validate = (): boolean => {
    const newErrors: Record<string, string> = {}

    if (!formData.name?.trim()) {
      newErrors.name = 'Name is required'
    }

    if (!formData.slug?.trim()) {
      newErrors.slug = 'Slug is required'
    } else if (!/^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(formData.slug)) {
      newErrors.slug = 'Slug must be lowercase kebab-case'
    }

    if (!formData.type) {
      newErrors.type = 'Type is required'
    }

    if (!formData.mode) {
      newErrors.mode = 'Mode is required'
    }

    if (!formData.status) {
      newErrors.status = 'Status is required'
    }

    setErrors(newErrors)
    return Object.keys(newErrors).length === 0
  }

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    
    if (!validate()) {
      return
    }

    onSave(formData)
    setIsDirty(false)
  }

  const handleClose = () => {
    if (isDirty) {
      if (confirm('You have unsaved changes. Are you sure you want to close?')) {
        onClose()
      }
    } else {
      onClose()
    }
  }

  return (
    <Dialog open={isOpen} onOpenChange={handleClose}>
      <DialogContent className="max-w-3xl max-h-[90vh] overflow-hidden flex flex-col">
        <DialogHeader>
          <div className="flex justify-between items-center">
            <DialogTitle>
              {isEditMode ? `Edit Agent Profile: ${agent.name}` : 'Create Agent Profile'}
            </DialogTitle>
            
            {isEditMode && agent && (
              <DropdownMenu>
                <DropdownMenuTrigger asChild>
                  <Button variant="ghost" size="sm">
                    <MoreVertical className="h-4 w-4" />
                  </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                  <DropdownMenuItem onClick={() => onDuplicate?.(agent)}>
                    <Copy className="mr-2 h-4 w-4" />
                    Duplicate
                  </DropdownMenuItem>
                  <DropdownMenuItem 
                    onClick={() => onDelete?.(agent)}
                    className="text-destructive focus:text-destructive"
                  >
                    <Trash2 className="mr-2 h-4 w-4" />
                    Delete
                  </DropdownMenuItem>
                </DropdownMenuContent>
              </DropdownMenu>
            )}
          </div>
        </DialogHeader>

        <ScrollArea className="flex-1 pr-4">
          <form onSubmit={handleSubmit} className="space-y-6">
            <div className="space-y-4">
              <h3 className="font-semibold text-sm text-muted-foreground uppercase tracking-wide">
                Basic Information
              </h3>
              
              <div className="space-y-2">
                <Label htmlFor="name">Name *</Label>
                <Input
                  id="name"
                  value={formData.name || ''}
                  onChange={(e) => handleChange('name', e.target.value)}
                  placeholder="e.g., Senior Backend Engineer"
                  className={errors.name ? 'border-destructive' : ''}
                />
                {errors.name && (
                  <p className="text-sm text-destructive">{errors.name}</p>
                )}
              </div>

              <div className="space-y-2">
                <Label htmlFor="slug">Slug *</Label>
                <Input
                  id="slug"
                  value={formData.slug || ''}
                  onChange={(e) => handleChange('slug', e.target.value)}
                  placeholder="e.g., senior-backend-engineer"
                  className={errors.slug ? 'border-destructive' : ''}
                />
                {errors.slug && (
                  <p className="text-sm text-destructive">{errors.slug}</p>
                )}
                <p className="text-xs text-muted-foreground">
                  Auto-generated from name, can be edited
                </p>
              </div>

              <div className="space-y-2">
                <Label htmlFor="description">Description</Label>
                <Textarea
                  id="description"
                  value={formData.description || ''}
                  onChange={(e) => handleChange('description', e.target.value)}
                  placeholder="Describe this agent profile's purpose and expertise..."
                  rows={3}
                />
              </div>
            </div>

            <div className="space-y-4">
              <h3 className="font-semibold text-sm text-muted-foreground uppercase tracking-wide">
                Classification
              </h3>
              
              <div className="space-y-2">
                <Label htmlFor="type">Type *</Label>
                <Select
                  value={formData.type || ''}
                  onValueChange={(value) => handleChange('type', value)}
                >
                  <SelectTrigger className={errors.type ? 'border-destructive' : ''}>
                    <SelectValue placeholder="Select type" />
                  </SelectTrigger>
                  <SelectContent>
                    {TYPE_OPTIONS.map(type => (
                      <SelectItem key={type.value} value={type.value}>
                        {type.label}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
                {errors.type && (
                  <p className="text-sm text-destructive">{errors.type}</p>
                )}
              </div>

              <div className="space-y-2">
                <Label htmlFor="mode">Mode *</Label>
                <Select
                  value={formData.mode || ''}
                  onValueChange={(value) => handleChange('mode', value)}
                >
                  <SelectTrigger className={errors.mode ? 'border-destructive' : ''}>
                    <SelectValue placeholder="Select mode" />
                  </SelectTrigger>
                  <SelectContent>
                    {MODE_OPTIONS.map(mode => (
                      <SelectItem key={mode.value} value={mode.value}>
                        {mode.label}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
                {errors.mode && (
                  <p className="text-sm text-destructive">{errors.mode}</p>
                )}
              </div>

              <div className="space-y-2">
                <Label htmlFor="status">Status *</Label>
                <Select
                  value={formData.status || ''}
                  onValueChange={(value) => handleChange('status', value)}
                >
                  <SelectTrigger className={errors.status ? 'border-destructive' : ''}>
                    <SelectValue placeholder="Select status" />
                  </SelectTrigger>
                  <SelectContent>
                    {STATUS_OPTIONS.map(status => (
                      <SelectItem key={status.value} value={status.value}>
                        {status.label}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
                {errors.status && (
                  <p className="text-sm text-destructive">{errors.status}</p>
                )}
              </div>
            </div>

            <div className="space-y-4">
              <h3 className="font-semibold text-sm text-muted-foreground uppercase tracking-wide">
                Capabilities & Constraints
              </h3>
              
              <div className="space-y-2">
                <Label>Capabilities</Label>
                <TagInput
                  value={formData.capabilities || []}
                  onChange={(tags) => handleChange('capabilities', tags)}
                  placeholder="Add capability (press Enter)"
                />
                <p className="text-xs text-muted-foreground">
                  Press Enter to add, click X to remove
                </p>
              </div>

              <div className="space-y-2">
                <Label>Constraints</Label>
                <TagInput
                  value={formData.constraints || []}
                  onChange={(tags) => handleChange('constraints', tags)}
                  placeholder="Add constraint (press Enter)"
                />
              </div>

              <div className="space-y-2">
                <Label>Tools</Label>
                <TagInput
                  value={formData.tools || []}
                  onChange={(tags) => handleChange('tools', tags)}
                  placeholder="Add tool (press Enter)"
                />
              </div>
            </div>
          </form>
        </ScrollArea>

        <DialogFooter className="mt-4">
          <Button type="button" variant="outline" onClick={handleClose}>
            Cancel
          </Button>
          <Button 
            type="button"
            onClick={handleSubmit}
            disabled={!isDirty && isEditMode}
          >
            {isEditMode ? 'Save Changes' : 'Create Profile'}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}
