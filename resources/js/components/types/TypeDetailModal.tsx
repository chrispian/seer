import React from 'react'
import { useState } from 'react'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Badge } from '@/components/ui/badge'
import { ScrollArea } from '@/components/ui/scroll-area'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { ArrowLeft, Save, Shield, HelpCircle } from 'lucide-react'
import { toast } from 'sonner'
import { renderIcon } from '@/lib/icons'

interface FragmentType {
  id: string
  slug: string
  display_name: string
  plural_name: string
  description: string | null
  icon: string | null
  color: string | null
  is_enabled: boolean
  is_system: boolean
  hide_from_admin: boolean
  can_disable: boolean
  can_delete: boolean
  fragments_count: number
  pagination_default: number
  version: string
  list_columns: any
  filters: any
  actions: any
  default_sort: any
  container_component: string
  row_display_mode: string
  detail_component: string | null
  detail_fields: any
}

interface TypeDetailModalProps {
  isOpen: boolean
  onClose: () => void
  onBack: () => void
  type: FragmentType
  onUpdate: (type: FragmentType) => void
}

export function TypeDetailModal({ isOpen, onClose, onBack, type, onUpdate }: TypeDetailModalProps) {
  const [formData, setFormData] = useState({
    display_name: type.display_name,
    plural_name: type.plural_name,
    description: type.description || '',
    icon: type.icon || '',
    color: type.color || '',
    pagination_default: type.pagination_default,
    container_component: type.container_component || 'DataManagementModal',
    row_display_mode: type.row_display_mode || 'list',
    detail_component: type.detail_component || '',
  })
  const [saving, setSaving] = useState(false)

  const handleSave = async () => {
    setSaving(true)
    try {
      const response = await fetch(`/api/types/${type.slug}/update`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(formData),
      })

      if (!response.ok) throw new Error('Failed to update type')

      const data = await response.json()
      toast.success('Type updated successfully')
      onUpdate({ ...type, ...formData })
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Failed to update type')
    } finally {
      setSaving(false)
    }
  }

  return (
    <Dialog open={isOpen} onOpenChange={onClose}>
      <DialogContent className="max-w-2xl max-h-[90vh]">
        <DialogHeader>
          <div className="flex items-center gap-2">
            <Button
              variant="ghost"
              size="sm"
              onClick={onBack}
              className="h-8 w-8 p-0"
            >
              <ArrowLeft className="h-4 w-4" />
            </Button>
            <div 
              className="flex-shrink-0 w-8 h-8 rounded flex items-center justify-center"
              style={{ backgroundColor: type.color || '#94a3b8' }}
            >
              {type.icon ? (
                renderIcon(type.icon, { className: 'h-4 w-4 text-white' })
              ) : (
                <HelpCircle className="h-4 w-4 text-white" />
              )}
            </div>
            <DialogTitle className="flex items-center gap-2">
              {type.is_system && <Shield className="h-4 w-4 text-muted-foreground" />}
              {type.display_name}
              <Badge variant="outline" className="text-xs">{type.slug}</Badge>
            </DialogTitle>
          </div>
        </DialogHeader>

        <ScrollArea className="max-h-[70vh] pr-4">
          <div className="space-y-4">
            {/* System Type Warning */}
            {type.is_system && (
              <div className="bg-muted p-3 rounded-md text-sm">
                <div className="flex items-center gap-2 font-medium">
                  <Shield className="h-4 w-4" />
                  System Type
                </div>
                <p className="text-muted-foreground mt-1">
                  This is a system type. Some settings cannot be modified.
                </p>
              </div>
            )}

            {/* Stats */}
            <div className="grid grid-cols-3 gap-4">
              <div>
                <Label className="text-xs text-muted-foreground">Fragments</Label>
                <div className="text-2xl font-bold">{type.fragments_count}</div>
              </div>
              <div>
                <Label className="text-xs text-muted-foreground">Version</Label>
                <div className="text-lg font-medium">{type.version}</div>
              </div>
              <div>
                <Label className="text-xs text-muted-foreground">Status</Label>
                <div>
                  <Badge variant={type.is_enabled ? 'default' : 'secondary'}>
                    {type.is_enabled ? 'Enabled' : 'Disabled'}
                  </Badge>
                </div>
              </div>
            </div>

            {/* Display Settings */}
            <div className="space-y-3">
              <h3 className="text-sm font-medium">Display Settings</h3>
              
              <div className="space-y-2">
                <Label htmlFor="display_name">Display Name</Label>
                <Input
                  id="display_name"
                  value={formData.display_name}
                  onChange={(e) => setFormData({ ...formData, display_name: e.target.value })}
                />
              </div>

              <div className="space-y-2">
                <Label htmlFor="plural_name">Plural Name</Label>
                <Input
                  id="plural_name"
                  value={formData.plural_name}
                  onChange={(e) => setFormData({ ...formData, plural_name: e.target.value })}
                />
              </div>

              <div className="space-y-2">
                <Label htmlFor="description">Description</Label>
                <Textarea
                  id="description"
                  value={formData.description}
                  onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                  rows={3}
                />
              </div>

              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label htmlFor="icon">Icon</Label>
                  <Input
                    id="icon"
                    value={formData.icon}
                    onChange={(e) => setFormData({ ...formData, icon: e.target.value })}
                    placeholder="lucide-icon-name"
                  />
                </div>

                <div className="space-y-2">
                  <Label htmlFor="color">Color</Label>
                  <Input
                    id="color"
                    value={formData.color}
                    onChange={(e) => setFormData({ ...formData, color: e.target.value })}
                    placeholder="#000000"
                  />
                </div>
              </div>
            </div>

            {/* List Settings */}
            <div className="space-y-3">
              <h3 className="text-sm font-medium">List Settings</h3>
              
              <div className="space-y-2">
                <Label htmlFor="pagination_default">Default Pagination</Label>
                <Input
                  id="pagination_default"
                  type="number"
                  value={formData.pagination_default}
                  onChange={(e) => setFormData({ ...formData, pagination_default: parseInt(e.target.value) })}
                  min={10}
                  max={500}
                />
              </div>
            </div>

            {/* UI Component Settings */}
            <div className="space-y-3">
              <h3 className="text-sm font-medium">UI Components</h3>
              
              <div className="space-y-2">
                <Label htmlFor="container_component">Container Component</Label>
                <Select
                  value={formData.container_component}
                  onValueChange={(value) => setFormData({ ...formData, container_component: value })}
                >
                  <SelectTrigger id="container_component">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="DataManagementModal">Data Management Modal</SelectItem>
                    <SelectItem value="Dialog">Dialog</SelectItem>
                    <SelectItem value="Drawer">Drawer</SelectItem>
                  </SelectContent>
                </Select>
                <p className="text-xs text-muted-foreground">
                  The wrapper component for displaying this type's list
                </p>
              </div>

              <div className="space-y-2">
                <Label htmlFor="row_display_mode">Row Display Mode</Label>
                <Select
                  value={formData.row_display_mode}
                  onValueChange={(value) => setFormData({ ...formData, row_display_mode: value })}
                >
                  <SelectTrigger id="row_display_mode">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="list">List (Table Rows)</SelectItem>
                    <SelectItem value="grid">Grid (Card Grid)</SelectItem>
                    <SelectItem value="card">Card (Stacked Cards)</SelectItem>
                  </SelectContent>
                </Select>
                <p className="text-xs text-muted-foreground">
                  How individual items are displayed
                </p>
              </div>

              <div className="space-y-2">
                <Label htmlFor="detail_component">Detail Component (Click-through)</Label>
                <Select
                  value={formData.detail_component}
                  onValueChange={(value) => setFormData({ ...formData, detail_component: value })}
                >
                  <SelectTrigger id="detail_component">
                    <SelectValue placeholder="None (No click-through)" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="">None</SelectItem>
                    <SelectItem value="UnifiedDetailModal">Unified Detail Modal</SelectItem>
                    <SelectItem value="Dialog">Dialog</SelectItem>
                    <SelectItem value="Drawer">Drawer</SelectItem>
                  </SelectContent>
                </Select>
                <p className="text-xs text-muted-foreground">
                  Component shown when clicking an item (leave blank to disable click-through)
                </p>
              </div>
            </div>
          </div>
        </ScrollArea>

        <div className="flex justify-between gap-2 pt-4 border-t">
          <Button variant="outline" onClick={onBack}>
            Back
          </Button>
          <div className="flex gap-2">
            <Button variant="outline" onClick={onClose}>
              Cancel
            </Button>
            <Button onClick={handleSave} disabled={saving}>
              <Save className="h-4 w-4 mr-2" />
              {saving ? 'Saving...' : 'Save Changes'}
            </Button>
          </div>
        </div>
      </DialogContent>
    </Dialog>
  )
}
