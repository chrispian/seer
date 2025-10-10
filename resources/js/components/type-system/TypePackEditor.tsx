import { useState, useEffect } from 'react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Badge } from '@/components/ui/badge'
import { Switch } from '@/components/ui/switch'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { Package, Info, Save, X } from 'lucide-react'
import { toast } from 'sonner'
import { useTypePacks } from '@/hooks/useTypePacks'
import { SchemaEditor } from './SchemaEditor'
import type { TypePack, CreateTypePackData, UpdateTypePackData } from '@/lib/api/typePacks'

interface TypePackEditorProps {
  isOpen: boolean
  onClose: () => void
  typePack?: TypePack | null
  onSave?: (typePack: TypePack) => void
}

export function TypePackEditor({ 
  isOpen, 
  onClose, 
  typePack,
  onSave 
}: TypePackEditorProps) {
  const { createTypePack, updateTypePack, validateSchema, isLoading } = useTypePacks()
  
  const [formData, setFormData] = useState<Partial<CreateTypePackData>>({
    slug: '',
    display_name: '',
    plural_name: '',
    description: '',
    icon: 'package',
    color: '#6366f1',
    is_enabled: true,
    hide_from_admin: false,
    pagination_default: 25,
    row_display_mode: 'compact',
    container_component: 'default',
    schema: null
  })

  useEffect(() => {
    if (typePack) {
      setFormData({
        slug: typePack.slug,
        display_name: typePack.display_name,
        plural_name: typePack.plural_name,
        description: typePack.description || '',
        icon: typePack.icon || 'package',
        color: typePack.color || '#6366f1',
        is_enabled: typePack.is_enabled,
        hide_from_admin: typePack.hide_from_admin,
        pagination_default: typePack.pagination_default,
        row_display_mode: typePack.row_display_mode,
        container_component: typePack.container_component,
        schema: typePack.schema
      })
    } else {
      setFormData({
        slug: '',
        display_name: '',
        plural_name: '',
        description: '',
        icon: 'package',
        color: '#6366f1',
        is_enabled: true,
        hide_from_admin: false,
        pagination_default: 25,
        row_display_mode: 'compact',
        container_component: 'default',
        schema: null
      })
    }
  }, [typePack, isOpen])

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()

    if (!formData.slug || !formData.display_name) {
      toast.error('Slug and display name are required')
      return
    }

    try {
      let result: TypePack

      if (typePack) {
        result = await updateTypePack(typePack.slug, formData as UpdateTypePackData)
        toast.success('Type pack updated successfully')
      } else {
        result = await createTypePack(formData as CreateTypePackData)
        toast.success('Type pack created successfully')
      }

      onSave?.(result)
      onClose()
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Failed to save type pack')
    }
  }

  const handleValidateSchema = async (schema: Record<string, any>) => {
    if (!typePack) {
      try {
        JSON.parse(JSON.stringify(schema))
        return { valid: true }
      } catch {
        return { valid: false, errors: ['Invalid JSON schema'] }
      }
    }

    try {
      const validation = await validateSchema(typePack.slug, schema)
      return validation
    } catch (err) {
      return { 
        valid: false, 
        errors: [err instanceof Error ? err.message : 'Validation failed'] 
      }
    }
  }

  return (
    <Dialog open={isOpen} onOpenChange={onClose}>
      <DialogContent className="max-w-4xl max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2">
            <Package className="h-5 w-5" />
            {typePack ? 'Edit Type Pack' : 'Create Type Pack'}
          </DialogTitle>
          <DialogDescription>
            {typePack 
              ? 'Update the configuration for this type pack' 
              : 'Create a new fragment type with custom schema and configuration'}
          </DialogDescription>
        </DialogHeader>

        <form onSubmit={handleSubmit}>
          <Tabs defaultValue="basic" className="w-full">
            <TabsList className="grid w-full grid-cols-3">
              <TabsTrigger value="basic">Basic Info</TabsTrigger>
              <TabsTrigger value="schema">Schema</TabsTrigger>
              <TabsTrigger value="advanced">Advanced</TabsTrigger>
            </TabsList>

            <TabsContent value="basic" className="space-y-4 mt-4">
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label htmlFor="slug">
                    Slug <span className="text-red-500">*</span>
                  </Label>
                  <Input
                    id="slug"
                    value={formData.slug}
                    onChange={(e) => setFormData({ ...formData, slug: e.target.value })}
                    placeholder="my-type"
                    disabled={!!typePack}
                    required
                  />
                  <p className="text-xs text-muted-foreground">
                    Unique identifier (lowercase, hyphens only)
                  </p>
                </div>

                <div className="space-y-2">
                  <Label htmlFor="display_name">
                    Display Name <span className="text-red-500">*</span>
                  </Label>
                  <Input
                    id="display_name"
                    value={formData.display_name}
                    onChange={(e) => setFormData({ ...formData, display_name: e.target.value })}
                    placeholder="My Type"
                    required
                  />
                </div>
              </div>

              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label htmlFor="plural_name">Plural Name</Label>
                  <Input
                    id="plural_name"
                    value={formData.plural_name}
                    onChange={(e) => setFormData({ ...formData, plural_name: e.target.value })}
                    placeholder="My Types"
                  />
                </div>

                <div className="space-y-2">
                  <Label htmlFor="icon">Icon</Label>
                  <Input
                    id="icon"
                    value={formData.icon}
                    onChange={(e) => setFormData({ ...formData, icon: e.target.value })}
                    placeholder="package"
                  />
                  <p className="text-xs text-muted-foreground">
                    Lucide icon name
                  </p>
                </div>
              </div>

              <div className="space-y-2">
                <Label htmlFor="color">Color</Label>
                <div className="flex gap-2">
                  <Input
                    id="color"
                    type="color"
                    value={formData.color}
                    onChange={(e) => setFormData({ ...formData, color: e.target.value })}
                    className="w-20"
                  />
                  <Input
                    value={formData.color}
                    onChange={(e) => setFormData({ ...formData, color: e.target.value })}
                    placeholder="#6366f1"
                  />
                </div>
              </div>

              <div className="space-y-2">
                <Label htmlFor="description">Description</Label>
                <Textarea
                  id="description"
                  value={formData.description}
                  onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                  placeholder="Describe this type pack..."
                  rows={3}
                />
              </div>

              <div className="flex items-center justify-between p-3 border rounded-md">
                <div className="space-y-0.5">
                  <Label>Enabled</Label>
                  <p className="text-xs text-muted-foreground">
                    Allow creating new fragments of this type
                  </p>
                </div>
                <Switch
                  checked={formData.is_enabled}
                  onCheckedChange={(checked) => setFormData({ ...formData, is_enabled: checked })}
                />
              </div>
            </TabsContent>

            <TabsContent value="schema" className="space-y-4 mt-4">
              <SchemaEditor
                schema={formData.schema}
                onChange={(schema) => setFormData({ ...formData, schema })}
                onValidate={handleValidateSchema}
              />
            </TabsContent>

            <TabsContent value="advanced" className="space-y-4 mt-4">
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label htmlFor="pagination_default">Default Pagination</Label>
                  <Input
                    id="pagination_default"
                    type="number"
                    value={formData.pagination_default}
                    onChange={(e) => setFormData({ ...formData, pagination_default: parseInt(e.target.value) })}
                    min={1}
                    max={100}
                  />
                </div>

                <div className="space-y-2">
                  <Label htmlFor="row_display_mode">Row Display Mode</Label>
                  <select
                    id="row_display_mode"
                    value={formData.row_display_mode}
                    onChange={(e) => setFormData({ ...formData, row_display_mode: e.target.value as 'compact' | 'comfortable' | 'spacious' })}
                    className="w-full px-3 py-2 border rounded-md"
                  >
                    <option value="compact">Compact</option>
                    <option value="comfortable">Comfortable</option>
                    <option value="spacious">Spacious</option>
                  </select>
                </div>
              </div>

              <div className="space-y-2">
                <Label htmlFor="container_component">Container Component</Label>
                <Input
                  id="container_component"
                  value={formData.container_component}
                  onChange={(e) => setFormData({ ...formData, container_component: e.target.value })}
                  placeholder="default"
                />
                <p className="text-xs text-muted-foreground">
                  Custom React component for rendering this type
                </p>
              </div>

              <div className="flex items-center justify-between p-3 border rounded-md">
                <div className="space-y-0.5">
                  <Label>Hide from Admin</Label>
                  <p className="text-xs text-muted-foreground">
                    Hide this type from admin interfaces
                  </p>
                </div>
                <Switch
                  checked={formData.hide_from_admin}
                  onCheckedChange={(checked) => setFormData({ ...formData, hide_from_admin: checked })}
                />
              </div>

              {typePack && (
                <div className="p-3 border rounded-md bg-muted/30">
                  <div className="flex items-start gap-2">
                    <Info className="h-4 w-4 text-muted-foreground mt-0.5" />
                    <div className="text-xs text-muted-foreground space-y-1">
                      <div><strong>Version:</strong> {typePack.version}</div>
                      <div><strong>Fragments:</strong> {typePack.fragments_count}</div>
                      <div><strong>System Type:</strong> {typePack.is_system ? 'Yes' : 'No'}</div>
                    </div>
                  </div>
                </div>
              )}
            </TabsContent>
          </Tabs>

          <DialogFooter className="mt-6">
            <Button type="button" variant="outline" onClick={onClose}>
              <X className="h-4 w-4 mr-2" />
              Cancel
            </Button>
            <Button type="submit" disabled={isLoading}>
              <Save className="h-4 w-4 mr-2" />
              {typePack ? 'Update' : 'Create'} Type Pack
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  )
}
