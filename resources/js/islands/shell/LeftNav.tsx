import React, { useState } from 'react'
import { Card, CardContent, CardHeader } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { 
  DropdownMenu, 
  DropdownMenuContent, 
  DropdownMenuItem, 
  DropdownMenuTrigger 
} from '@/components/ui/dropdown-menu'
import { Plus, MessageSquare, Terminal, Pin, Trash2, PinOff, MoreVertical, GripVertical, Archive, Folder } from 'lucide-react'
import { useCurrentContext } from '@/hooks/useContext'
import { useChatSessions, usePinnedChatSessions, useCreateChatSession, useDeleteChatSession, useTogglePinChatSession } from '@/hooks/useChatSessions'
import { useSwitchToVault } from '@/hooks/useVaults'
import { useSwitchToProject } from '@/hooks/useProjects'
import { useAppStore, type ChatSession } from '@/stores/useAppStore'
import { VaultCreateDialog } from '@/components/VaultCreateDialog'
import { ProjectCreateDialog } from '@/components/ProjectCreateDialog'

export function LeftNav() {
  // Use direct hooks instead of context
  const {
    currentVault,
    currentProject,
    currentSession,
    vaults,
    projectsForCurrentVault,
    sessionsForCurrentContext,
  } = useCurrentContext()
  
  const { setCurrentSession } = useAppStore()
  const chatSessionsQuery = useChatSessions()
  const pinnedSessionsQuery = usePinnedChatSessions()
  const createChatMutation = useCreateChatSession()
  const deleteChatMutation = useDeleteChatSession()
  const togglePinMutation = useTogglePinChatSession()
  const switchVaultMutation = useSwitchToVault()
  const switchProjectMutation = useSwitchToProject()
  
  // Transform data for UI
  const recentSessions = sessionsForCurrentContext.filter(session => !session.is_pinned)
  const pinnedSessions = pinnedSessionsQuery.data?.sessions.map(session => ({
    id: session.id,
    title: session.title,
    channel_display: session.channel_display,
    message_count: session.message_count,
    last_activity_at: session.last_activity_at,
    is_pinned: session.is_pinned,
    sort_order: session.sort_order,
    vault_id: session.vault_id,
    project_id: session.project_id,
  })) || []

  const [isCreating, setIsCreating] = useState(false)
  const [deletingSessions, setDeletingSessions] = useState<Set<number>>(new Set())
  const [pinningSessions, setPinningSessions] = useState<Set<number>>(new Set())
  const [draggedSession, setDraggedSession] = useState<ChatSession | null>(null)
  const [dragOverIndex, setDragOverIndex] = useState<number | null>(null)
  const [vaultDialogOpen, setVaultDialogOpen] = useState(false)
  const [projectDialogOpen, setProjectDialogOpen] = useState(false)

  const handleNewChat = async () => {
    if (isCreating) return
    setIsCreating(true)
    try {
      await createChatMutation.mutateAsync({})
    } catch (error) {
      console.error('Failed to create new chat:', error)
    } finally {
      setIsCreating(false)
    }
  }

  const handleSwitchSession = (sessionId: number) => {
    setCurrentSession(sessionId)
  }

  const handleDeleteSession = async (sessionId: number, e: React.MouseEvent) => {
    e.stopPropagation()
    if (deletingSessions.has(sessionId)) return
    
    setDeletingSessions(prev => new Set(prev).add(sessionId))
    try {
      await deleteChatMutation.mutateAsync(sessionId)
    } catch (error) {
      console.error('Failed to delete session:', error)
    } finally {
      setDeletingSessions(prev => {
        const newSet = new Set(prev)
        newSet.delete(sessionId)
        return newSet
      })
    }
  }

  const handleTogglePin = async (sessionId: number, e: React.MouseEvent) => {
    e.stopPropagation()
    if (pinningSessions.has(sessionId)) return
    
    setPinningSessions(prev => new Set(prev).add(sessionId))
    try {
      await togglePinMutation.mutateAsync(sessionId)
    } catch (error) {
      console.error('Failed to toggle pin:', error)
    } finally {
      setPinningSessions(prev => {
        const newSet = new Set(prev)
        newSet.delete(sessionId)
        return newSet
      })
    }
  }

  const handleDragStart = (e: React.DragEvent, session: ChatSession) => {
    setDraggedSession(session)
    e.dataTransfer.effectAllowed = 'move'
    e.dataTransfer.setData('text/html', e.currentTarget.outerHTML)
    e.currentTarget.style.opacity = '0.5'
  }

  const handleDragEnd = (e: React.DragEvent) => {
    e.currentTarget.style.opacity = '1'
    setDraggedSession(null)
    setDragOverIndex(null)
  }

  const handleDragOver = (e: React.DragEvent, index: number) => {
    e.preventDefault()
    e.dataTransfer.dropEffect = 'move'
    setDragOverIndex(index)
  }

  const handleDragLeave = () => {
    setDragOverIndex(null)
  }

  const handleDrop = async (e: React.DragEvent, targetIndex: number) => {
    e.preventDefault()
    setDragOverIndex(null)
    
    if (!draggedSession) return

    const draggedIndex = pinnedSessions.findIndex(s => s.id === draggedSession.id)
    if (draggedIndex === -1 || draggedIndex === targetIndex) return

    // Create new order for all pinned sessions
    const reorderedSessions = [...pinnedSessions]
    const [removed] = reorderedSessions.splice(draggedIndex, 1)
    reorderedSessions.splice(targetIndex, 0, removed)

    // Update sort_order for all affected sessions
    const sessionsToUpdate = reorderedSessions.map((session, index) => ({
      id: session.id,
      sort_order: index + 1
    }))

    try {
      const response = await fetch('/api/chat-sessions/pin-order', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
        },
        body: JSON.stringify({ sessions: sessionsToUpdate }),
      })

      if (response.ok) {
        // Refetch sessions to reflect new order
        pinnedSessionsQuery.refetch()
        chatSessionsQuery.refetch()
      } else {
        console.error('Failed to update pin order')
      }
    } catch (error) {
      console.error('Failed to update pin order:', error)
    }
  }

  const renderSessionItem = (session: ChatSession, showPinHandle = false, index?: number) => (
    <Card
      key={session.id}
      className={`p-2 cursor-pointer transition-all ${
        currentSession?.id === session.id
          ? 'bg-accent border-l-2 border-l-primary'
          : 'hover:bg-accent/50'
      } ${dragOverIndex === index ? 'border-t-2 border-t-blue-500' : ''}`}
      onClick={() => handleSwitchSession(session.id)}
      draggable={showPinHandle}
      onDragStart={showPinHandle ? (e) => handleDragStart(e, session) : undefined}
      onDragEnd={showPinHandle ? handleDragEnd : undefined}
      onDragOver={showPinHandle && typeof index === 'number' ? (e) => handleDragOver(e, index) : undefined}
      onDragLeave={showPinHandle ? handleDragLeave : undefined}
      onDrop={showPinHandle && typeof index === 'number' ? (e) => handleDrop(e, index) : undefined}
    >
      <div className="flex items-center justify-between">
        {showPinHandle && (
          <div className="flex items-center mr-2" style={{ cursor: 'grab' }}>
            <GripVertical className="w-3 h-3 text-muted-foreground" />
          </div>
        )}
        <div className="flex-1 min-w-0 mr-2">
          <span className="text-sm truncate block">
            {session.channel_display}
          </span>
        </div>
        <Badge variant="secondary">
          {session.message_count}
        </Badge>
        <div className="flex items-center space-x-1 ml-2">
          <DropdownMenu>
            <DropdownMenuTrigger asChild>
              <Button variant="ghost" size="icon" className="h-6 w-6">
                <MoreVertical className="w-2.5 h-2.5" />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent>
              <DropdownMenuItem onClick={(e) => handleTogglePin(session.id, e)}>
                {session.is_pinned ? (
                  <>
                    <PinOff className="w-3 h-3 mr-2" />
                    Unpin
                  </>
                ) : (
                  <>
                    <Pin className="w-3 h-3 mr-2" />
                    Pin
                  </>
                )}
              </DropdownMenuItem>
              <DropdownMenuItem 
                onClick={(e) => handleDeleteSession(session.id, e)}
                disabled={deletingSessions.has(session.id)}
              >
                <Trash2 className="w-3 h-3 mr-2" />
                Delete
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        </div>
      </div>
    </Card>
  )

  return (
    <div className="w-72 bg-white border-r flex flex-col">
      {/* Vault Selection */}
      <Card className="m-4">
        <CardHeader className="pb-2">
          <h3 className="text-xs font-medium text-muted-foreground flex items-center">
            <Archive className="w-3 h-3 mr-1" />
            Vault
          </h3>
        </CardHeader>
        <CardContent className="pt-0">
          <div className="flex space-x-1">
            <select 
              className="flex-1 text-sm rounded-l p-2 border border-input bg-background"
              value={currentVault?.id || ''}
              onChange={(e) => {
                const vaultId = parseInt(e.target.value)
                if (vaultId && vaultId !== currentVault?.id) {
                  switchVaultMutation.mutate(vaultId)
                }
              }}
              disabled={switchVaultMutation.isPending}
              className={switchVaultMutation.isPending ? 'opacity-50' : ''}
            >
              {vaults.map(vault => (
                <option key={vault.id} value={vault.id}>
                  {vault.name}
                </option>
              ))}
            </select>
            <Button 
              variant="outline" 
              size="icon" 
              className="px-3"
              title="Create New Vault"
              onClick={() => setVaultDialogOpen(true)}
            >
              <Plus className="w-4 h-4" />
            </Button>
          </div>
        </CardContent>
      </Card>

      {/* Project Selection */}
      <Card className="mx-4 mb-4">
        <CardHeader className="pb-2">
          <h3 className="text-xs font-medium text-muted-foreground flex items-center">
            <Folder className="w-3 h-3 mr-1" />
            Project
          </h3>
        </CardHeader>
        <CardContent className="pt-0">
          <div className="flex space-x-1">
            <select 
              className="flex-1 text-sm rounded-l p-2 border border-input bg-background"
              value={currentProject?.id || ''}
              onChange={(e) => {
                const projectId = parseInt(e.target.value)
                if (projectId && projectId !== currentProject?.id) {
                  switchProjectMutation.mutate(projectId)
                }
              }}
              disabled={switchProjectMutation.isPending || !projectsForCurrentVault.length}
              className={switchProjectMutation.isPending ? 'opacity-50' : ''}
            >
              {projectsForCurrentVault.length === 0 ? (
                <option value="">No projects available</option>
              ) : (
                projectsForCurrentVault.map(project => (
                  <option key={project.id} value={project.id}>
                    {project.name}
                  </option>
                ))
              )}
            </select>
            <Button 
              variant="outline" 
              size="icon" 
              className="px-3"
              title="Create New Project"
              disabled={!currentVault}
              onClick={() => setProjectDialogOpen(true)}
            >
              <Plus className="w-4 h-4" />
            </Button>
          </div>
        </CardContent>
      </Card>

      {/* Chat History */}
      <div className="flex-1 px-4 overflow-y-auto">
        {/* Pinned Chats */}
        {pinnedSessions.length > 0 && (
          <div className="mb-4">
            <div className="flex items-center justify-between mb-3">
              <h3 className="text-xs font-medium text-muted-foreground flex items-center">
                <Pin className="w-3 h-3 mr-1" />
                Pinned Chats
              </h3>
            </div>
            <div className="space-y-1">
              {pinnedSessions.map((session, index) => renderSessionItem(session, true, index))}
            </div>
          </div>
        )}

        {/* Recent Chats */}
        <div className="flex items-center justify-between mb-3">
          <h3 className="text-xs font-medium text-muted-foreground flex items-center">
            <MessageSquare className="w-3 h-3 mr-1" />
            Recent Chats
          </h3>
          <Button 
            variant="ghost" 
            size="icon" 
            className="h-6 w-6"
            onClick={handleNewChat}
            disabled={isCreating}
          >
            <Plus className="w-3 h-3" />
          </Button>
        </div>

        <div className="space-y-1">
          {recentSessions.length === 0 ? (
            <div className="text-center text-muted-foreground text-xs py-4">
              No recent chats
            </div>
          ) : (
            recentSessions.map((session) => renderSessionItem(session))
          )}
        </div>
      </div>

      {/* Commands */}
      <div className="p-4 border-t space-y-2">
        <Button className="w-full">
          <Terminal className="w-4 h-4 mr-1" />
          Commands
        </Button>
      </div>

      {/* Creation Dialogs */}
      <VaultCreateDialog 
        open={vaultDialogOpen} 
        onOpenChange={setVaultDialogOpen} 
      />
      <ProjectCreateDialog 
        open={projectDialogOpen} 
        onOpenChange={setProjectDialogOpen} 
      />
    </div>
  )
}