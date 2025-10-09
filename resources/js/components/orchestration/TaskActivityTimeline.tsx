import { useState } from 'react'
import { ScrollArea } from '@/components/ui/scroll-area'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Textarea } from '@/components/ui/textarea'
import { 
  Clock, 
  AlertCircle, 
  User, 
  FileText,
  MessageSquare,
  GitBranch,
  Paperclip,
  RefreshCw,
  Send
} from 'lucide-react'

interface TaskActivity {
  id: string
  task_id: string
  agent_id?: string | null
  user_id?: number | null
  activity_type: 'status_change' | 'content_update' | 'assignment' | 'note' | 'error' | 'artifact_attached'
  action: string
  description: string
  changes?: Record<string, any> | null
  metadata?: Record<string, any> | null
  created_at: string
  agent?: {
    id: string
    name: string
    slug?: string
  }
  user?: {
    id: number
    name: string
  }
}

interface TaskActivityTimelineProps {
  taskId: string
  activities?: TaskActivity[]
  loading?: boolean
  error?: string | null
  onRefresh?: () => void
  onAddNote?: (note: string) => Promise<void>
  className?: string
}

export function TaskActivityTimeline({ 
  activities = [],
  loading = false,
  error = null,
  onRefresh,
  onAddNote,
  className = ''
}: TaskActivityTimelineProps) {
  const [newNote, setNewNote] = useState('')
  const [submitting, setSubmitting] = useState(false)

  const handleAddNote = async () => {
    if (!newNote.trim() || !onAddNote) return

    setSubmitting(true)
    try {
      await onAddNote(newNote)
      setNewNote('')
    } catch (err) {
      console.error('Failed to add note:', err)
    } finally {
      setSubmitting(false)
    }
  }

  const getActivityIcon = (type: string) => {
    switch (type) {
      case 'status_change':
        return <GitBranch className="h-4 w-4" />
      case 'content_update':
        return <FileText className="h-4 w-4" />
      case 'assignment':
        return <User className="h-4 w-4" />
      case 'note':
        return <MessageSquare className="h-4 w-4" />
      case 'error':
        return <AlertCircle className="h-4 w-4" />
      case 'artifact_attached':
        return <Paperclip className="h-4 w-4" />
      default:
        return <Clock className="h-4 w-4" />
    }
  }

  const getActivityColor = (type: string) => {
    switch (type) {
      case 'status_change':
        return 'text-blue-600 bg-blue-50'
      case 'content_update':
        return 'text-green-600 bg-green-50'
      case 'assignment':
        return 'text-purple-600 bg-purple-50'
      case 'note':
        return 'text-gray-600 bg-gray-50'
      case 'error':
        return 'text-red-600 bg-red-50'
      case 'artifact_attached':
        return 'text-orange-600 bg-orange-50'
      default:
        return 'text-gray-600 bg-gray-50'
    }
  }

  const formatTimestamp = (timestamp: string) => {
    const date = new Date(timestamp)
    const now = new Date()
    const diffMs = now.getTime() - date.getTime()
    const diffMins = Math.floor(diffMs / 60000)
    const diffHours = Math.floor(diffMs / 3600000)
    const diffDays = Math.floor(diffMs / 86400000)

    if (diffMins < 1) return 'Just now'
    if (diffMins < 60) return `${diffMins}m ago`
    if (diffHours < 24) return `${diffHours}h ago`
    if (diffDays < 7) return `${diffDays}d ago`
    
    return date.toLocaleDateString('en-US', { 
      month: 'short', 
      day: 'numeric',
      year: date.getFullYear() !== now.getFullYear() ? 'numeric' : undefined 
    })
  }

  const renderActivityDetails = (activity: TaskActivity) => {
    if (activity.changes && Object.keys(activity.changes).length > 0) {
      return (
        <div className="mt-2 text-xs text-muted-foreground bg-muted/30 rounded p-2">
          {activity.activity_type === 'status_change' && activity.changes.from && activity.changes.to && (
            <div>
              <span className="font-medium">{activity.changes.from}</span>
              {' → '}
              <span className="font-medium">{activity.changes.to}</span>
            </div>
          )}
          {activity.activity_type === 'content_update' && activity.changes.field && (
            <div>Updated field: <span className="font-medium">{activity.changes.field}</span></div>
          )}
        </div>
      )
    }

    if (activity.metadata && Object.keys(activity.metadata).length > 0) {
      if (activity.activity_type === 'artifact_attached' && activity.metadata.filename) {
        return (
          <div className="mt-2 text-xs bg-muted/30 rounded p-2 flex items-center gap-2">
            <Paperclip className="h-3 w-3" />
            <span className="font-medium">{activity.metadata.filename}</span>
            {activity.metadata.size_bytes && (
              <span className="text-muted-foreground">
                ({(activity.metadata.size_bytes / 1024).toFixed(1)} KB)
              </span>
            )}
          </div>
        )
      }
    }

    return null
  }

  if (loading && activities.length === 0) {
    return (
      <div className={`flex items-center justify-center h-64 ${className}`}>
        <div className="text-center">
          <RefreshCw className="h-8 w-8 text-muted-foreground mx-auto mb-2 animate-spin" />
          <p className="text-sm text-muted-foreground">Loading activity...</p>
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
      <ScrollArea className="flex-1">
        <div className="space-y-4 pr-4">
          {activities.length === 0 ? (
            <div className="text-center py-8">
              <Clock className="h-8 w-8 text-muted-foreground mx-auto mb-2" />
              <p className="text-sm text-muted-foreground">No activity yet</p>
            </div>
          ) : (
            activities.map((activity, index) => (
              <div key={activity.id} className="relative">
                {index < activities.length - 1 && (
                  <div className="absolute left-[15px] top-8 bottom-0 w-px bg-border" />
                )}
                
                <div className="flex gap-3">
                  <div className={`flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center ${getActivityColor(activity.activity_type)}`}>
                    {getActivityIcon(activity.activity_type)}
                  </div>
                  
                  <div className="flex-1 min-w-0">
                    <div className="flex items-start justify-between gap-2">
                      <div className="flex-1 min-w-0">
                        <p className="text-sm font-medium text-foreground">
                          {activity.description}
                        </p>
                        <div className="flex items-center gap-2 mt-1 text-xs text-muted-foreground">
                          {activity.agent && (
                            <span className="flex items-center gap-1">
                              <User className="h-3 w-3" />
                              {activity.agent.name}
                            </span>
                          )}
                          {activity.user && !activity.agent && (
                            <span className="flex items-center gap-1">
                              <User className="h-3 w-3" />
                              {activity.user.name}
                            </span>
                          )}
                          <span>{formatTimestamp(activity.created_at)}</span>
                        </div>
                      </div>
                      
                      <Badge variant="outline" className="text-xs flex-shrink-0">
                        {activity.activity_type.replace('_', ' ')}
                      </Badge>
                    </div>
                    
                    {renderActivityDetails(activity)}
                  </div>
                </div>
              </div>
            ))
          )}
        </div>
      </ScrollArea>

      {onAddNote && (
        <div className="mt-4 pt-4 border-t">
          <div className="flex gap-2">
            <Textarea
              placeholder="Add a note to this task..."
              value={newNote}
              onChange={(e) => setNewNote(e.target.value)}
              className="min-h-[60px] resize-none"
              disabled={submitting}
            />
            <Button 
              onClick={handleAddNote}
              disabled={!newNote.trim() || submitting}
              size="sm"
              className="flex-shrink-0"
            >
              {submitting ? (
                <RefreshCw className="h-4 w-4 animate-spin" />
              ) : (
                <Send className="h-4 w-4" />
              )}
            </Button>
          </div>
        </div>
      )}
    </div>
  )
}
