import { useState } from 'react'
import { Button } from '@/components/ui/button'
import { Textarea } from '@/components/ui/textarea'
import { 
  Save, 
  RefreshCw, 
  User, 
  FileText,
  Settings,
  CheckCircle,
  Eye,
  AlertCircle,
  Undo
} from 'lucide-react'
import {
  Tabs,
  TabsContent,
  TabsList,
  TabsTrigger,
} from '@/components/ui/tabs'
import {
  Alert,
  AlertDescription,
} from '@/components/ui/alert'

interface TaskContent {
  agent?: string
  plan?: string
  context?: string
  todo?: string
  summary?: string
}

interface TaskContentEditorProps {
  taskId: string
  content: TaskContent
  loading?: boolean
  error?: string | null
  onSave?: (field: string, value: string) => Promise<void>
  onRefresh?: () => void
  readOnly?: boolean
  className?: string
}

export function TaskContentEditor({ 
  content,
  loading = false,
  error = null,
  onSave,
  onRefresh,
  readOnly = false,
  className = ''
}: TaskContentEditorProps) {
  const [editMode, setEditMode] = useState<Record<string, boolean>>({})
  const [editedContent, setEditedContent] = useState<TaskContent>(content)
  const [saving, setSaving] = useState<Record<string, boolean>>({})
  const [saveErrors, setSaveErrors] = useState<Record<string, string>>({})

  const fields = [
    { key: 'agent', label: 'Agent Profile', icon: <User className="h-4 w-4" /> },
    { key: 'plan', label: 'Plan', icon: <FileText className="h-4 w-4" /> },
    { key: 'context', label: 'Context', icon: <Settings className="h-4 w-4" /> },
    { key: 'todo', label: 'Todo', icon: <CheckCircle className="h-4 w-4" /> },
    { key: 'summary', label: 'Summary', icon: <Eye className="h-4 w-4" /> },
  ]

  const handleEdit = (field: string) => {
    setEditMode({ ...editMode, [field]: true })
    setEditedContent({ ...editedContent, [field]: content[field as keyof TaskContent] || '' })
  }

  const handleCancel = (field: string) => {
    setEditMode({ ...editMode, [field]: false })
    setEditedContent({ ...editedContent, [field]: content[field as keyof TaskContent] || '' })
    setSaveErrors({ ...saveErrors, [field]: '' })
  }

  const handleSave = async (field: string) => {
    if (!onSave) return

    setSaving({ ...saving, [field]: true })
    setSaveErrors({ ...saveErrors, [field]: '' })

    try {
      const value = editedContent[field as keyof TaskContent] || ''
      await onSave(field, value)
      setEditMode({ ...editMode, [field]: false })
    } catch (err) {
      const message = err instanceof Error ? err.message : 'Failed to save'
      setSaveErrors({ ...saveErrors, [field]: message })
    } finally {
      setSaving({ ...saving, [field]: false })
    }
  }

  const handleContentChange = (field: string, value: string) => {
    setEditedContent({ ...editedContent, [field]: value })
  }

  const hasChanges = (field: string) => {
    return editedContent[field as keyof TaskContent] !== (content[field as keyof TaskContent] || '')
  }

  const getCharCount = (text: string | undefined) => {
    return (text || '').length
  }

  const getWordCount = (text: string | undefined) => {
    return (text || '').trim().split(/\s+/).filter(Boolean).length
  }

  if (loading) {
    return (
      <div className={`flex items-center justify-center h-64 ${className}`}>
        <div className="text-center">
          <RefreshCw className="h-8 w-8 text-muted-foreground mx-auto mb-2 animate-spin" />
          <p className="text-sm text-muted-foreground">Loading content...</p>
        </div>
      </div>
    )
  }

  if (error) {
    return (
      <div className={`flex items-center justify-center h-64 ${className}`}>
        <div className="text-center">
          <AlertCircle className="h-8 w-8 text-red-500 mx-auto mb-2" />
          <p className="text-sm text-muted-foreground">{error}</p>
          {onRefresh && (
            <Button onClick={onRefresh} variant="outline" size="sm" className="mt-4">
              Try Again
            </Button>
          )}
        </div>
      </div>
    )
  }

  return (
    <div className={`flex flex-col h-full ${className}`}>
      <div className="flex items-center justify-between mb-4 pb-2 border-b">
        <div className="flex items-center gap-2">
          <FileText className="h-4 w-4 text-muted-foreground" />
          <h3 className="font-medium">Task Content</h3>
        </div>
        {onRefresh && (
          <Button 
            onClick={onRefresh} 
            variant="ghost" 
            size="sm"
            disabled={loading}
          >
            <RefreshCw className={`h-3 w-3 ${loading ? 'animate-spin' : ''}`} />
          </Button>
        )}
      </div>

      <Tabs defaultValue="agent" className="flex-1">
        <TabsList className="grid w-full grid-cols-5">
          {fields.map(field => (
            <TabsTrigger key={field.key} value={field.key} className="flex items-center gap-1">
              {field.icon}
              <span className="hidden sm:inline">{field.label}</span>
            </TabsTrigger>
          ))}
        </TabsList>

        {fields.map(field => {
          const fieldKey = field.key as keyof TaskContent
          const isEditing = editMode[field.key]
          const isSaving = saving[field.key]
          const saveError = saveErrors[field.key]
          const fieldContent = isEditing ? editedContent[fieldKey] : content[fieldKey]

          return (
            <TabsContent key={field.key} value={field.key} className="flex-1 mt-4 space-y-3">
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-2">
                  {field.icon}
                  <h4 className="text-sm font-medium">{field.label}</h4>
                  {fieldContent && (
                    <div className="flex gap-2 text-xs text-muted-foreground">
                      <span>{getWordCount(fieldContent)} words</span>
                      <span>·</span>
                      <span>{getCharCount(fieldContent)} chars</span>
                    </div>
                  )}
                </div>
                
                {!readOnly && (
                  <div className="flex items-center gap-2">
                    {isEditing ? (
                      <>
                        <Button 
                          onClick={() => handleCancel(field.key)}
                          variant="ghost"
                          size="sm"
                          disabled={isSaving}
                        >
                          <Undo className="h-3 w-3 mr-1" />
                          Cancel
                        </Button>
                        <Button 
                          onClick={() => handleSave(field.key)}
                          size="sm"
                          disabled={isSaving || !hasChanges(field.key)}
                        >
                          {isSaving ? (
                            <RefreshCw className="h-3 w-3 mr-1 animate-spin" />
                          ) : (
                            <Save className="h-3 w-3 mr-1" />
                          )}
                          Save
                        </Button>
                      </>
                    ) : (
                      <Button 
                        onClick={() => handleEdit(field.key)}
                        variant="outline"
                        size="sm"
                      >
                        Edit
                      </Button>
                    )}
                  </div>
                )}
              </div>

              {saveError && (
                <Alert variant="destructive">
                  <AlertCircle className="h-4 w-4" />
                  <AlertDescription>{saveError}</AlertDescription>
                </Alert>
              )}

              {isEditing ? (
                <Textarea
                  value={editedContent[fieldKey] || ''}
                  onChange={(e) => handleContentChange(field.key, e.target.value)}
                  className="min-h-[400px] font-mono text-sm"
                  placeholder={`Enter ${field.label.toLowerCase()} content...`}
                  disabled={isSaving}
                />
              ) : (
                <div className="border rounded-md bg-muted/20 p-4 min-h-[400px]">
                  {fieldContent ? (
                    <pre className="text-sm whitespace-pre-wrap font-mono text-foreground">
                      {fieldContent}
                    </pre>
                  ) : (
                    <div className="flex items-center justify-center h-full text-muted-foreground">
                      <div className="text-center">
                        {field.icon}
                        <p className="text-sm mt-2">No {field.label.toLowerCase()} content</p>
                        {!readOnly && (
                          <Button 
                            onClick={() => handleEdit(field.key)}
                            variant="outline"
                            size="sm"
                            className="mt-4"
                          >
                            Add Content
                          </Button>
                        )}
                      </div>
                    </div>
                  )}
                </div>
              )}
            </TabsContent>
          )
        })}
      </Tabs>
    </div>
  )
}
