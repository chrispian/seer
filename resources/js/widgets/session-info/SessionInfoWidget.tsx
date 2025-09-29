import React from 'react'
import { Card, CardContent, CardHeader } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Info, Hash, Bot, Database, Folder, MessageSquare, Clock } from 'lucide-react'
import { useAppStore } from '@/stores/useAppStore'
import { useChatSessionDetails } from '@/hooks/useChatSessions'

export function SessionInfoWidget() {
  const { currentSessionId } = useAppStore()
  const { data: sessionData, isLoading } = useChatSessionDetails(currentSessionId)

  if (!currentSessionId) {
    return (
      <Card className="border-0 shadow-none bg-muted/30">
        <CardHeader className="pb-2">
          <h4 className="text-xs font-medium flex items-center gap-1">
            <Info className="w-3 h-3" />
            Session Info
          </h4>
        </CardHeader>
        <CardContent className="pt-0">
          <div className="text-center text-muted-foreground text-xs py-2">
            No session selected
          </div>
        </CardContent>
      </Card>
    )
  }

  if (isLoading) {
    return (
      <Card className="border-0 shadow-none bg-muted/30">
        <CardHeader className="pb-2">
          <h4 className="text-xs font-medium flex items-center gap-1">
            <Info className="w-3 h-3" />
            Session Info
          </h4>
        </CardHeader>
        <CardContent className="pt-0 space-y-1">
          {[...Array(4)].map((_, i) => (
            <div key={i} className="flex justify-between items-center">
              <div className="h-3 bg-muted rounded w-16 animate-pulse"></div>
              <div className="h-3 bg-muted rounded w-20 animate-pulse"></div>
            </div>
          ))}
        </CardContent>
      </Card>
    )
  }

  const session = sessionData?.session

  const formatLastActivity = (dateString: string | null | undefined) => {
    if (!dateString) return 'Never'
    
    try {
      const date = new Date(dateString)
      if (isNaN(date.getTime())) return 'Invalid date'
      
      const now = new Date()
      const diffInMinutes = Math.floor((now.getTime() - date.getTime()) / (1000 * 60))
      
      if (diffInMinutes < 1) return 'Just now'
      if (diffInMinutes < 60) return `${diffInMinutes}m ago`
      const diffInHours = Math.floor(diffInMinutes / 60)
      if (diffInHours < 24) return `${diffInHours}h ago`
      return date.toLocaleDateString()
    } catch (error) {
      return 'Invalid date'
    }
  }

  return (
    <Card className="border-0 shadow-none bg-muted/30">
      <CardHeader className="pb-2">
        <h4 className="text-xs font-medium flex items-center gap-1">
          <Info className="w-3 h-3" />
          Session Info
        </h4>
      </CardHeader>
      <CardContent className="pt-0 space-y-1">
        {/* Session ID */}
        <div className="flex justify-between items-center">
          <span className="text-xs text-muted-foreground flex items-center gap-1">
            <Hash className="w-3 h-3" />
            ID
          </span>
          <Badge variant="outline" className="text-xs font-mono">
            {session?.short_code || session?.id}
          </Badge>
        </div>

        {/* Model Info */}
        {(session?.model_provider || session?.model_name) && (
          <div className="flex justify-between items-center">
            <span className="text-xs text-muted-foreground flex items-center gap-1">
              <Bot className="w-3 h-3" />
              Model
            </span>
            <Badge variant="secondary" className="text-xs">
              {session.model_name || session.model_provider || 'Unknown'}
            </Badge>
          </div>
        )}

        {/* Vault */}
        {session?.vault && (
          <div className="flex justify-between items-center">
            <span className="text-xs text-muted-foreground flex items-center gap-1">
              <Database className="w-3 h-3" />
              Vault
            </span>
            <Badge variant="outline" className="text-xs">
              {session.vault.name}
            </Badge>
          </div>
        )}

        {/* Project */}
        {session?.project && (
          <div className="flex justify-between items-center">
            <span className="text-xs text-muted-foreground flex items-center gap-1">
              <Folder className="w-3 h-3" />
              Project
            </span>
            <Badge variant="outline" className="text-xs">
              {session.project.name}
            </Badge>
          </div>
        )}

        {/* Message Count */}
        <div className="flex justify-between items-center">
          <span className="text-xs text-muted-foreground flex items-center gap-1">
            <MessageSquare className="w-3 h-3" />
            Messages
          </span>
          <Badge variant="secondary" className="text-xs">
            {session?.message_count || 0}
          </Badge>
        </div>

        {/* Last Activity */}
        {session?.last_activity_at && (
          <div className="flex justify-between items-center">
            <span className="text-xs text-muted-foreground flex items-center gap-1">
              <Clock className="w-3 h-3" />
              Last Activity
            </span>
            <span className="text-xs text-muted-foreground">
              {formatLastActivity(session.last_activity_at)}
            </span>
          </div>
        )}

        {/* Session Status */}
        <div className="flex justify-between items-center pt-1 border-t border-gray-100">
          <span className="text-xs text-muted-foreground">Status</span>
          <Badge variant={session?.is_active ? "default" : "secondary"} className="text-xs">
            {session?.is_active ? "Active" : "Inactive"}
          </Badge>
        </div>
      </CardContent>
    </Card>
  )
}