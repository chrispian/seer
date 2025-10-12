import React, { useState } from 'react'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'

interface Sprint {
  id?: string
  code: string
  starts_on?: string
  ends_on?: string
  status?: string
  priority?: string
  title?: string
  meta?: Record<string, any>
}

interface SprintFormModalProps {
  isOpen: boolean
  onClose: () => void
  onBack?: () => void
  mode: 'create' | 'edit'
  default_values?: Partial<Sprint>
  sprint?: Sprint
  available_tasks?: any[]
  onSubmit?: (data: Sprint) => void
}

export function SprintFormModal({
  isOpen,
  onClose,
  onBack,
  mode,
  default_values = {},
  sprint,
  available_tasks = [],
}: SprintFormModalProps) {
  const initialData = mode === 'edit' ? sprint : default_values
  
  const [formData, setFormData] = useState<Partial<Sprint>>({
    code: initialData?.code || '',
    title: initialData?.title || '',
    starts_on: initialData?.starts_on || '',
    ends_on: initialData?.ends_on || '',
    status: initialData?.status || 'planned',
    priority: initialData?.priority || 'medium',
  })

  const [errors, setErrors] = useState<Record<string, string>>({})
  const [isSubmitting, setIsSubmitting] = useState(false)

  const validate = (): boolean => {
    const newErrors: Record<string, string> = {}

    if (!formData.code || formData.code.trim() === '') {
      newErrors.code = 'Sprint code is required'
    }

    if (formData.starts_on && formData.ends_on) {
      const start = new Date(formData.starts_on)
      const end = new Date(formData.ends_on)
      if (end <= start) {
        newErrors.ends_on = 'End date must be after start date'
      }
    }

    setErrors(newErrors)
    return Object.keys(newErrors).length === 0
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()

    if (!validate()) {
      return
    }

    setIsSubmitting(true)

    try {
      // Call the sprint-save command
      const csrf = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || ''
      
      const response = await fetch('/api/commands/execute', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrf,
        },
        body: JSON.stringify({
          command: '/sprint-save',
          arguments: {
            code: formData.code,
            title: formData.title || null,
            starts_on: formData.starts_on || null,
            ends_on: formData.ends_on || null,
            status: formData.status || null,
            priority: formData.priority || null,
            upsert: mode === 'edit',
          },
        }),
      })

      const result = await response.json()

      if (result.success) {
        if (onBack) {
          onBack()
        } else {
          onClose()
        }
      } else {
        setErrors({ general: result.error || 'Failed to save sprint' })
      }
    } catch (error) {
      setErrors({ general: 'An error occurred while saving the sprint' })
      console.error('Save error:', error)
    } finally {
      setIsSubmitting(false)
    }
  }

  const handleChange = (field: keyof Sprint, value: string) => {
    setFormData(prev => ({ ...prev, [field]: value }))
    // Clear error for this field when user starts typing
    if (errors[field]) {
      setErrors(prev => {
        const newErrors = { ...prev }
        delete newErrors[field]
        return newErrors
      })
    }
  }

  return (
    <Dialog open={isOpen} onOpenChange={(open) => {
      console.log('[SprintFormModal] onOpenChange called, open:', open, 'has onBack?', !!onBack)
      if (!open) {
        if (onBack) {
          console.log('[SprintFormModal] Calling onBack()')
          onBack()
        } else {
          console.log('[SprintFormModal] Calling onClose()')
          onClose()
        }
      }
    }}>
      <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle>
            {mode === 'create' ? 'Create New Sprint' : 'Edit Sprint'}
          </DialogTitle>
        </DialogHeader>

        <form onSubmit={handleSubmit} className="space-y-4 py-4">
          {errors.general && (
            <div className="p-3 text-sm text-red-600 bg-red-50 border border-red-200 rounded">
              {errors.general}
            </div>
          )}

          <div className="space-y-2">
            <Label htmlFor="code">Sprint Code *</Label>
            <Input
              id="code"
              value={formData.code}
              onChange={(e) => handleChange('code', e.target.value)}
              placeholder="SPRINT-2025-41"
              disabled={mode === 'edit'}
              className={errors.code ? 'border-red-500' : ''}
            />
            {errors.code && (
              <p className="text-sm text-red-600">{errors.code}</p>
            )}
          </div>

          <div className="space-y-2">
            <Label htmlFor="title">Sprint Title</Label>
            <Input
              id="title"
              value={formData.title || ''}
              onChange={(e) => handleChange('title', e.target.value)}
              placeholder="Week 41 Development Sprint"
            />
          </div>

          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label htmlFor="starts_on">Start Date</Label>
              <Input
                id="starts_on"
                type="date"
                value={formData.starts_on || ''}
                onChange={(e) => handleChange('starts_on', e.target.value)}
              />
            </div>

            <div className="space-y-2">
              <Label htmlFor="ends_on">End Date</Label>
              <Input
                id="ends_on"
                type="date"
                value={formData.ends_on || ''}
                onChange={(e) => handleChange('ends_on', e.target.value)}
                className={errors.ends_on ? 'border-red-500' : ''}
              />
              {errors.ends_on && (
                <p className="text-sm text-red-600">{errors.ends_on}</p>
              )}
            </div>
          </div>

          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label htmlFor="status">Status</Label>
              <select
                id="status"
                value={formData.status || 'planned'}
                onChange={(e) => handleChange('status', e.target.value)}
                className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
              >
                <option value="planned">Planned</option>
                <option value="active">Active</option>
                <option value="paused">Paused</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
              </select>
            </div>

            <div className="space-y-2">
              <Label htmlFor="priority">Priority</Label>
              <select
                id="priority"
                value={formData.priority || 'medium'}
                onChange={(e) => handleChange('priority', e.target.value)}
                className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
              >
                <option value="low">Low</option>
                <option value="medium">Medium</option>
                <option value="high">High</option>
                <option value="urgent">Urgent</option>
              </select>
            </div>
          </div>

          {available_tasks && available_tasks.length > 0 && mode === 'create' && (
            <div className="space-y-2">
              <Label>Available Tasks ({available_tasks.length})</Label>
              <p className="text-sm text-muted-foreground">
                {available_tasks.length} unassigned tasks available. 
                You can assign tasks to this sprint after creation.
              </p>
            </div>
          )}

          <DialogFooter className="gap-2">
            <Button 
              type="button" 
              variant="outline" 
              onClick={onClose}
              disabled={isSubmitting}
            >
              Cancel
            </Button>
            <Button type="submit" disabled={isSubmitting}>
              {isSubmitting ? 'Saving...' : mode === 'create' ? 'Create Sprint' : 'Update Sprint'}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  )
}
