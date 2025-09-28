import React, { useState } from 'react'
import { 
  Archive, 
  ChevronDown, 
  ChevronUp, 
  MessageSquare, 
  Pin, 
  PinOff, 
  Plus, 
  Trash2, 
  MoreVertical, 
  GripVertical, 
  Folder, 
  User,
  Terminal,
  Loader2 
} from 'lucide-react'
import { ScrollArea } from '@/components/ui/scroll-area'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { 
  DropdownMenu, 
  DropdownMenuContent, 
  DropdownMenuItem, 
  DropdownMenuSeparator,
  DropdownMenuTrigger 
} from '@/components/ui/dropdown-menu'
import { useCurrentContext } from '@/hooks/useContext'
import { useChatSession } from '@/contexts/ChatSessionContext'
import { useChatSessions, usePinnedChatSessions, useCreateChatSession, useDeleteChatSession, useTogglePinChatSession } from '@/hooks/useChatSessions'
import { useSwitchToVault } from '@/hooks/useVaults'
import { useSwitchToProject } from '@/hooks/useProjects'
import { useAppStore, type ChatSession } from '@/stores/useAppStore'
import { VaultCreateDialog } from '@/components/VaultCreateDialog'
import { ProjectCreateDialog } from '@/components/ProjectCreateDialog'
import { LoadingSpinner } from '@/components/ui/loading-spinner'
import { SidebarSkeleton, ChatSessionSkeleton, VaultProjectSelectorSkeleton } from '@/components/ui/skeleton'
import { ErrorBoundary } from '@/components/ui/error-boundary'
import { useScreenReaderAnnouncements } from '@/hooks/useKeyboardNavigation'
import { trackUserFlow } from '@/lib/performance'

const GRAVATAR_HASH = '2fe0965808f1f8b6d61ea0eb50d487be'

export function AppSidebar() {
  // Use direct hooks instead of context
  const {
    currentVault,
    currentProject,
    currentSession,
    vaults,
    projectsForCurrentVault,
    sessionsForCurrentContext,
    isLoadingVaults,
    isLoadingProjects,
    isLoadingSessions,
  } = useCurrentContext()
  
  const { createNewSession } = useChatSession()
  const { setCurrentSession } = useAppStore()
  const chatSessionsQuery = useChatSessions()
  const pinnedSessionsQuery = usePinnedChatSessions()
  const createChatMutation = useCreateChatSession()
  const deleteChatMutation = useDeleteChatSession()
  const togglePinMutation = useTogglePinChatSession()
  const switchVaultMutation = useSwitchToVault()
  const switchProjectMutation = useSwitchToProject()
  
  // Screen reader announcements
  const { announceSessionSwitch, announceSuccess, announceError } = useScreenReaderAnnouncements()
  
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
    console.log('Creating new chat...')
    setIsCreating(true)
    
    try {
      const result = await createNewSession()
      console.log('Chat created successfully:', result)
    } catch (error) {
      console.error('Failed to create new chat:', error)
      announceError('Failed to create new chat')
    } finally {
      setIsCreating(false)
    }
  }

  const handleSwitchSession = (sessionId: number) => {
    const endTracking = trackUserFlow.chatSwitch(sessionId)
    
    setCurrentSession(sessionId)
    
    const session = [...recentSessions, ...pinnedSessions].find(s => s.id === sessionId)
    if (session) {
      announceSessionSwitch(session.title)
    }
    
    setTimeout(endTracking, 0)
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

    const reorderedSessions = [...pinnedSessions]
    const [removed] = reorderedSessions.splice(draggedIndex, 1)
    reorderedSessions.splice(targetIndex, 0, removed)

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
    <div
      key={session.id}
      className={`flex items-center justify-between p-2 rounded-md cursor-pointer transition-all w-full ${
        currentSession?.id === session.id
          ? 'bg-gray-100 border-l-2 border-l-black'
          : 'hover:bg-gray-50'
      } ${dragOverIndex === index ? 'border-t-2 border-t-blue-500' : ''}`}
      onClick={() => handleSwitchSession(session.id)}
      draggable={showPinHandle}
      onDragStart={showPinHandle ? (e) => handleDragStart(e, session) : undefined}
      onDragEnd={showPinHandle ? handleDragEnd : undefined}
      onDragOver={showPinHandle && typeof index === 'number' ? (e) => handleDragOver(e, index) : undefined}
      onDragLeave={showPinHandle ? handleDragLeave : undefined}
      onDrop={showPinHandle && typeof index === 'number' ? (e) => handleDrop(e, index) : undefined}
    >
      <div className="flex items-center min-w-0 flex-1 max-w-[180px]">
        {showPinHandle && (
          <GripVertical className="w-3 h-3 text-gray-400 mr-2 cursor-grab flex-shrink-0" />
        )}
        <span className="text-sm truncate block">{session.channel_display}</span>
      </div>
      <div className="flex items-center space-x-1 ml-2 flex-shrink-0">
        <Badge variant="secondary" className="text-xs">
          {session.message_count}
        </Badge>
        <DropdownMenu>
          <DropdownMenuTrigger asChild>
            <Button 
              variant="ghost" 
              size="icon" 
              className="h-6 w-6"
              onClick={(e) => e.stopPropagation()}
            >
              <MoreVertical className="w-3 h-3" />
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
  )

  // Show skeleton loading when initial data is loading
  if (isLoadingVaults && vaults.length === 0) {
    return (
      <div className="w-72 bg-white border-r flex flex-col">
        <div className="p-4">
          <SidebarSkeleton />
        </div>
      </div>
    );
  }

  return (
    <ErrorBoundary context="sidebar">
      <div className="w-72 bg-white border-r flex flex-col">
        {/* Vault Selection Header */}
        <div className="p-3 border-b">
          <DropdownMenu>
            <DropdownMenuTrigger asChild>
              <Button 
                variant="ghost"
                className="w-full justify-start h-12 px-3 data-[state=open]:bg-gray-100"
              >
                <div className="flex aspect-square size-8 items-center justify-center rounded-lg bg-black text-white mr-3">
                  <Archive className="size-4" />
                </div>
                <div className="grid flex-1 text-left text-sm leading-tight">
                  <span className="truncate font-semibold">
                    {currentVault?.name || 'Loading...'}
                  </span>
                  <span className="truncate text-xs text-gray-500">Vault</span>
                </div>
                <ChevronDown className="ml-auto" />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent
              className="w-[--radix-dropdown-menu-trigger-width] min-w-56 rounded-lg"
              side="bottom"
              align="start"
              sideOffset={4}
            >
              {vaults.map((vault) => (
                <DropdownMenuItem
                  key={vault.id}
                  onClick={() => switchVaultMutation.mutate(vault.id)}
                  className="gap-2 p-2"
                >
                  <div className="flex size-6 items-center justify-center rounded-sm border">
                    <Archive className="size-4 shrink-0" />
                  </div>
                  {vault.name}
                </DropdownMenuItem>
              ))}
              <DropdownMenuSeparator />
              <DropdownMenuItem onClick={() => setVaultDialogOpen(true)} className="gap-2 p-2">
                <div className="flex size-6 items-center justify-center rounded-md border border-dashed">
                  <Plus className="size-4" />
                </div>
                <div className="font-medium text-gray-500">Add vault</div>
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        </div>

        {/* Main Content */}
        <div className="flex-1 overflow-hidden px-2">
          {/* Pinned Chats */}
          {(pinnedSessionsQuery.isLoading || pinnedSessions.length > 0) && (
            <>
              <div className="py-2">
                <div className="flex items-center justify-between mb-2 px-2">
                  <h3 className="text-xs font-medium text-gray-500 flex items-center">
                    <Pin className="w-3 h-3 mr-1" />
                    Pinned Chats
                  </h3>
                </div>
                <ScrollArea className="h-[200px]">
                  <div className="space-y-1">
                    {pinnedSessionsQuery.isLoading ? (
                      Array.from({ length: 2 }).map((_, i) => (
                        <ChatSessionSkeleton key={`pinned-skeleton-${i}`} />
                      ))
                    ) : (
                      pinnedSessions.map((session, index) => renderSessionItem(session, true, index))
                    )}
                  </div>
                </ScrollArea>
              </div>
              <div className="border-t mx-2 mb-2" />
            </>
          )}

          {/* Recent Chats */}
          <div className="py-2">
            <div className="flex items-center justify-between mb-2 px-2">
              <h3 className="text-xs font-medium text-gray-500 flex items-center">
                <MessageSquare className="w-3 h-3 mr-1" />
                Recent Chats
              </h3>
              <Button 
                variant="ghost" 
                size="icon" 
                className="h-6 w-6"
                onClick={(e) => {
                  e.stopPropagation()
                  handleNewChat()
                }}
                disabled={isCreating}
                title="Create New Chat"
              >
                {isCreating ? (
                  <Loader2 className="w-3 h-3 animate-spin" />
                ) : (
                  <Plus className="w-3 h-3" />
                )}
              </Button>
            </div>
            <ScrollArea className="h-[300px]">
              <div className="space-y-1">
                {isLoadingSessions ? (
                  Array.from({ length: 3 }).map((_, i) => (
                    <ChatSessionSkeleton key={`recent-skeleton-${i}`} />
                  ))
                ) : recentSessions.length === 0 ? (
                  <div className="text-center text-gray-400 text-xs py-8">
                    No recent chats
                  </div>
                ) : (
                  recentSessions.map((session) => renderSessionItem(session))
                )}
              </div>
            </ScrollArea>
          </div>

          <div className="border-t mx-2 mb-2" />

          {/* Projects */}
          <div className="py-2">
            <div className="flex items-center justify-between mb-2 px-2">
              <h3 className="text-xs font-medium text-gray-500 flex items-center">
                <Folder className="w-3 h-3 mr-1" />
                Projects
              </h3>
              <Button 
                variant="ghost" 
                size="icon" 
                className="h-6 w-6"
                onClick={(e) => {
                  e.stopPropagation()
                  setProjectDialogOpen(true)
                }} 
                disabled={!currentVault}
                title="Create New Project"
              >
                <Plus className="w-3 h-3" />
              </Button>
            </div>
            <ScrollArea className="h-[200px]">
              <div className="space-y-1">
                {isLoadingProjects ? (
                  Array.from({ length: 2 }).map((_, i) => (
                    <ChatSessionSkeleton key={`project-skeleton-${i}`} />
                  ))
                ) : projectsForCurrentVault.length === 0 ? (
                  <div className="text-center text-gray-400 text-xs py-8">
                    No projects available
                  </div>
                ) : (
                  projectsForCurrentVault.map((project) => (
                    <div
                      key={project.id}
                      className={`flex items-center justify-between p-2 rounded-md cursor-pointer transition-all w-full ${
                        currentProject?.id === project.id
                          ? 'bg-gray-100'
                          : 'hover:bg-gray-50'
                      }`}
                      onClick={() => switchProjectMutation.mutate(project.id)}
                    >
                      <div className="flex items-center min-w-0 flex-1 max-w-[220px]">
                        <Folder className="w-4 h-4 mr-2 text-gray-400 flex-shrink-0" />
                        <span className="text-sm truncate block">{project.name}</span>
                      </div>
                      {switchProjectMutation.isPending && (
                        <Loader2 className="w-4 h-4 animate-spin text-gray-400 flex-shrink-0" />
                      )}
                    </div>
                  ))
                )}
              </div>
            </ScrollArea>
          </div>
        </div>

        {/* User Menu Footer */}
        <div className="p-3 border-t">
          <DropdownMenu>
            <DropdownMenuTrigger asChild>
              <Button
                variant="ghost"
                className="w-full justify-start h-12 px-3 data-[state=open]:bg-gray-100"
              >
                <Avatar className="h-8 w-8 rounded-lg mr-3">
                  <AvatarImage 
                    src={`https://www.gravatar.com/avatar/${GRAVATAR_HASH}?d=mp&s=32`} 
                    alt="chrispian@gmail.com" 
                  />
                  <AvatarFallback className="rounded-lg">CP</AvatarFallback>
                </Avatar>
                <div className="grid flex-1 text-left text-sm leading-tight">
                  <span className="truncate font-semibold">chrispian</span>
                  <span className="truncate text-xs text-gray-500">chrispian@gmail.com</span>
                </div>
                <ChevronUp className="ml-auto size-4" />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent
              className="w-[--radix-dropdown-menu-trigger-width] min-w-56 rounded-lg"
              side="top"
              align="start"
              sideOffset={4}
            >
              <DropdownMenuItem>
                <User className="w-4 h-4 mr-2" />
                Account
              </DropdownMenuItem>
              <DropdownMenuItem onClick={() => {
                // Future: Open command palette (Ctrl+K)
                console.log('Command palette not yet implemented')
              }}>
                <Terminal className="w-4 h-4 mr-2" />
                Commands
              </DropdownMenuItem>
              <DropdownMenuSeparator />
              <DropdownMenuItem>
                Sign out
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
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
    </ErrorBoundary>
  )
}