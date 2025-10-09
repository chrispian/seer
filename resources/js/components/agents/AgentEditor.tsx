import { useState, useEffect, useRef } from 'react'
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
import { Switch } from '@/components/ui/switch'
import {
  Tabs,
  TabsContent,
  TabsList,
  TabsTrigger,
} from '@/components/ui/tabs'
import { ScrollArea } from '@/components/ui/scroll-area'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Sparkles, Upload } from 'lucide-react'
import type { Agent } from '@/types/agent'
import type { AgentProfile } from '@/types/agent-profile'

interface AgentEditorProps {
  agent?: Agent | null
  agentProfiles: AgentProfile[]
  isOpen: boolean
  onClose: () => void
  onSave: (agent: Partial<Agent>) => void
  onDelete?: (agent: Agent) => void
  onGenerateDesignation?: () => Promise<string>
  onAvatarUpload?: (file: File) => Promise<void>
}

export function AgentEditor({
  agent,
  agentProfiles,
  isOpen,
  onClose,
  onSave,
  onGenerateDesignation,
  onAvatarUpload
}: AgentEditorProps) {
  const isEditMode = !!agent

  const [formData, setFormData] = useState<Partial<Agent>>({
    name: '',
    designation: '',
    agent_profile_id: '',
    persona: '',
    tool_config: {},
    metadata: {},
    status: 'active',
  })

  const [errors, setErrors] = useState<Record<string, string>>({})
  const [isDirty, setIsDirty] = useState(false)
  const [isGenerating, setIsGenerating] = useState(false)
  const [selectedFile, setSelectedFile] = useState<File | null>(null)
  const [previewUrl, setPreviewUrl] = useState<string | null>(null)
  const fileInputRef = useRef<HTMLInputElement>(null)

  useEffect(() => {
    if (agent) {
      setFormData(agent)
      setPreviewUrl(agent.avatar_url || null)
    } else {
      setFormData({
        name: '',
        designation: '',
        agent_profile_id: '',
        persona: '',
        tool_config: {},
        metadata: {},
        status: 'active',
      })
      setPreviewUrl(null)
    }
    setIsDirty(false)
    setErrors({})
    setSelectedFile(null)
  }, [agent, isOpen])

  const handleChange = (field: keyof Agent, value: any) => {
    setFormData(prev => ({ ...prev, [field]: value }))
    setIsDirty(true)
    if (errors[field]) {
      setErrors(prev => ({ ...prev, [field]: '' }))
    }
  }

  const handleGenerateDesignation = async () => {
    if (!onGenerateDesignation) {
      return
    }

    setIsGenerating(true)
    try {
      const designation = await onGenerateDesignation()
      setFormData(prev => ({ ...prev, designation }))
      setIsDirty(true)
    } catch (err) {
      console.error('Failed to generate designation:', err)
    } finally {
      setIsGenerating(false)
    }
  }

  const handleFileSelect = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0]
    if (!file) return

    if (!file.type.startsWith('image/')) {
      setErrors(prev => ({ ...prev, avatar: 'Please select an image file' }))
      return
    }

    if (file.size > 5 * 1024 * 1024) {
      setErrors(prev => ({ ...prev, avatar: 'Image must be less than 5MB' }))
      return
    }

    setSelectedFile(file)
    setPreviewUrl(URL.createObjectURL(file))
    setErrors(prev => ({ ...prev, avatar: '' }))
  }

  const handleAvatarUpload = async () => {
    if (!selectedFile || !onAvatarUpload || !isEditMode) return

    try {
      await onAvatarUpload(selectedFile)
      setSelectedFile(null)
    } catch (err) {
      console.error('Failed to upload avatar:', err)
      setErrors(prev => ({ ...prev, avatar: 'Failed to upload avatar' }))
    }
  }

  const validate = (): boolean => {
    const newErrors: Record<string, string> = {}

    if (!formData.name?.trim()) {
      newErrors.name = 'Name is required'
    }

    if (!isEditMode && !formData.designation?.trim()) {
      newErrors.designation = 'Designation is required'
    }

    if (!formData.agent_profile_id) {
      newErrors.agent_profile_id = 'Agent Profile is required'
    }

    setErrors(newErrors)
    return Object.keys(newErrors).length === 0
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    
    if (!validate()) {
      return
    }

    onSave(formData)
    
    if (selectedFile && isEditMode) {
      await handleAvatarUpload()
    }
    
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

  const selectedProfile = agentProfiles.find(p => p.id === formData.agent_profile_id)

  return (
    <Dialog open={isOpen} onOpenChange={handleClose}>
      <DialogContent className="max-w-2xl max-h-[85vh] overflow-hidden flex flex-col">
        <DialogHeader>
          <DialogTitle className="text-xl">
            {isEditMode ? `Edit: ${agent.name}` : 'Create Agent'}
          </DialogTitle>
        </DialogHeader>

        <ScrollArea className="flex-1 pr-4">
          <form onSubmit={handleSubmit} className="space-y-4">
            <div className="flex items-center gap-4 pb-4 border-b">
              <Avatar className="w-20 h-20 border-2 border-background shadow-sm">
                <AvatarImage 
                  src={previewUrl || formData.avatar_url || '/interface/avatars/default/avatar-1.png'} 
                  alt={formData.name || 'Agent'} 
                />
                <AvatarFallback>
                  {formData.name?.substring(0, 2).toUpperCase() || 'AG'}
                </AvatarFallback>
              </Avatar>
              <div className="flex-1 space-y-2">
                <Label className="text-sm">Avatar</Label>
                <input
                  ref={fileInputRef}
                  type="file"
                  accept="image/*"
                  onChange={handleFileSelect}
                  className="hidden"
                />
                <Button
                  type="button"
                  variant="outline"
                  size="sm"
                  onClick={() => fileInputRef.current?.click()}
                  className="h-9"
                >
                  <Upload className="h-4 w-4 mr-2" />
                  {selectedFile ? 'Change Image' : 'Upload Image'}
                </Button>
                {errors.avatar && (
                  <p className="text-xs text-destructive">{errors.avatar}</p>
                )}
                {selectedFile && (
                  <p className="text-xs text-muted-foreground">
                    Selected: {selectedFile.name}
                  </p>
                )}
              </div>
            </div>

            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label htmlFor="name" className="text-sm">Name *</Label>
                <Input
                  id="name"
                  value={formData.name || ''}
                  onChange={(e) => handleChange('name', e.target.value)}
                  placeholder="Alice"
                  className={errors.name ? 'border-destructive h-9' : 'h-9'}
                />
                {errors.name && (
                  <p className="text-xs text-destructive">{errors.name}</p>
                )}
              </div>

              {!isEditMode && (
                <div className="space-y-2">
                  <Label htmlFor="designation" className="text-sm">Designation *</Label>
                  <div className="flex gap-2">
                    <Input
                      id="designation"
                      value={formData.designation || ''}
                      onChange={(e) => handleChange('designation', e.target.value.toUpperCase())}
                      placeholder="R2-D2"
                      maxLength={5}
                      className={errors.designation ? 'border-destructive h-9' : 'h-9'}
                    />
                    <Button
                      type="button"
                      variant="outline"
                      size="sm"
                      onClick={handleGenerateDesignation}
                      disabled={isGenerating}
                      className="h-9 w-9 p-0"
                    >
                      <Sparkles className="h-4 w-4" />
                    </Button>
                  </div>
                  {errors.designation && (
                    <p className="text-xs text-destructive">{errors.designation}</p>
                  )}
                </div>
              )}
            </div>

            <div className="space-y-2">
              <Label htmlFor="agent_profile_id" className="text-sm">Agent Profile *</Label>
              <Select
                value={formData.agent_profile_id || ''}
                onValueChange={(value) => handleChange('agent_profile_id', value)}
              >
                <SelectTrigger className={errors.agent_profile_id ? 'border-destructive h-9' : 'h-9'}>
                  <SelectValue placeholder="Select profile" />
                </SelectTrigger>
                <SelectContent>
                  {agentProfiles.map(profile => (
                    <SelectItem key={profile.id} value={profile.id!}>
                      <span className="font-medium">{profile.name}</span>
                      <span className="text-xs text-muted-foreground ml-2">({profile.type})</span>
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              {errors.agent_profile_id && (
                <p className="text-xs text-destructive">{errors.agent_profile_id}</p>
              )}
              {selectedProfile && (
                <p className="text-xs text-muted-foreground">
                  {selectedProfile.description || 'No description'}
                </p>
              )}
            </div>

            <div className="flex items-center justify-between space-x-2 py-2">
              <div className="space-y-0.5">
                <Label htmlFor="status" className="text-sm font-medium">Active Status</Label>
                <p className="text-xs text-muted-foreground">
                  {formData.status === 'active' ? 'Agent is active' : 'Agent is inactive'}
                </p>
              </div>
              <Switch
                id="status"
                checked={formData.status === 'active'}
                onCheckedChange={(checked) => handleChange('status', checked ? 'active' : 'inactive')}
              />
            </div>

            <Tabs defaultValue="persona" className="w-full">
              <TabsList className="grid w-full grid-cols-2">
                <TabsTrigger value="persona" className="text-sm">Persona</TabsTrigger>
                <TabsTrigger value="config" className="text-sm">Configuration</TabsTrigger>
              </TabsList>
              <TabsContent value="persona" className="space-y-2 mt-3">
                <Textarea
                  id="persona"
                  value={formData.persona || ''}
                  onChange={(e) => handleChange('persona', e.target.value)}
                  placeholder="Describe personality, communication style, and behavior..."
                  rows={6}
                  className="text-sm"
                />
                <p className="text-xs text-muted-foreground">
                  Define how this agent communicates and behaves
                </p>
              </TabsContent>
              <TabsContent value="config" className="space-y-2 mt-3">
                <Textarea
                  id="tool_config"
                  value={JSON.stringify(formData.tool_config || {}, null, 2)}
                  onChange={(e) => {
                    try {
                      const parsed = JSON.parse(e.target.value)
                      handleChange('tool_config', parsed)
                    } catch {
                      // Invalid JSON, ignore
                    }
                  }}
                  placeholder='{"temperature": 0.7, "max_tokens": 1000}'
                  rows={6}
                  className="font-mono text-xs"
                />
                <p className="text-xs text-muted-foreground">
                  Tool-specific configuration (JSON format)
                </p>
              </TabsContent>
            </Tabs>
          </form>
        </ScrollArea>

        <DialogFooter className="mt-3">
          <Button type="button" variant="outline" onClick={handleClose} size="sm">
            Cancel
          </Button>
          <Button 
            type="button"
            onClick={handleSubmit}
            disabled={!isDirty && isEditMode}
            size="sm"
          >
            {isEditMode ? 'Save' : 'Create'}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}
