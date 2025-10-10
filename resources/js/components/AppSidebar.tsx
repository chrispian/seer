import React, { useState } from 'react'
import { 
  Archive, 
  ChevronDown, 
  ChevronUp, 
  MessageSquare, 
  Pin, 
  Plus, 
  Folder, 
  User,
  Terminal,
  Loader2,
  PanelLeftClose,
  PanelLeftOpen
} from 'lucide-react'
import { ScrollArea } from '@/components/ui/scroll-area'
import { Button } from '@/components/ui/button'
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
import { useLayoutStore } from '@/stores/useLayoutStore'
import { BlackButton } from '@/components/ui/black-button'
import { VaultCreateDialog } from '@/components/VaultCreateDialog'
import { ProjectCreateDialog } from '@/components/ProjectCreateDialog'
import { LoadingSpinner } from '@/components/ui/loading-spinner'
import { SidebarSkeleton, ChatSessionSkeleton, VaultProjectSelectorSkeleton } from '@/components/ui/skeleton'
import { ErrorBoundary } from '@/components/ui/error-boundary'
import { useScreenReaderAnnouncements } from '@/hooks/useKeyboardNavigation'
import { trackUserFlow } from '@/lib/performance'
import { UserAvatar, useUserDisplayName } from '@/components/UserAvatar'
import { ChatSessionItem } from '@/components/sidebar/ChatSessionItem'

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
  
  // Get pinned session IDs to exclude from recent sessions
  const pinnedSessionIds = new Set(pinnedSessions.map(session => session.id))
  
  // STRICT: Completely exclude any session that appears in pinned list from recent list
  // This prevents ANY possibility of the same session appearing in both lists
  const recentSessions = sessionsForCurrentContext.filter(session => {
    // Exclude if it's in the pinned sessions list (regardless of is_pinned flag)
    const isInPinnedList = pinnedSessionIds.has(session.id)
    // Exclude if it has is_pinned flag set to true
    const hasIsPinnedFlag = session.is_pinned === true
    
    // Only include if it's NOT in pinned list AND NOT marked as pinned
    return !isInPinnedList && !hasIsPinnedFlag
  })

  const [isCreating, setIsCreating] = useState(false)
  const [deletingSessions, setDeletingSessions] = useState<Set<number>>(new Set())
  const [pinningSessions, setPinningSessions] = useState<Set<number>>(new Set())
  const [draggedSession, setDraggedSession] = useState<ChatSession | null>(null)
  const [dragOverIndex, setDragOverIndex] = useState<number | null>(null)
  const [vaultDialogOpen, setVaultDialogOpen] = useState(false)
  const [projectDialogOpen, setProjectDialogOpen] = useState(false)
  // Use layout store for sidebar collapse state for keyboard shortcuts integration
  const { preferences, setSidebarCollapsed } = useLayoutStore()
  const isCollapsed = preferences.layout.sidebarCollapsed
  
  // Get user display name
  const userDisplayName = useUserDisplayName()

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

  const renderSessionItem = (session: ChatSession, showPinHandle = false, index?: number, keyPrefix = '') => (
    <ChatSessionItem
      session={session}
      isActive={currentSession?.id === session.id}
      showPinHandle={showPinHandle}
      index={index}
      keyPrefix={keyPrefix}
      isDragOver={dragOverIndex === index}
      isDeleting={deletingSessions.has(session.id)}
      onSessionClick={handleSwitchSession}
      onTogglePin={handleTogglePin}
      onDelete={handleDeleteSession}
      onDragStart={handleDragStart}
      onDragEnd={handleDragEnd}
      onDragOver={handleDragOver}
      onDragLeave={handleDragLeave}
      onDrop={handleDrop}
    />
  )

  // Show skeleton loading when initial data is loading
  if (isLoadingVaults && vaults.length === 0) {
    return (
      <div className={`${isCollapsed ? 'w-16' : 'w-72'} h-full bg-white border-r flex flex-col transition-all duration-200`}>
        <div className="p-4">
          <SidebarSkeleton />
        </div>
      </div>
    );
  }

  return (
    <ErrorBoundary context="sidebar">
      <div className={`${isCollapsed ? 'w-12 md:w-16' : 'w-64 md:w-72'} h-full bg-white border-r flex flex-col transition-all duration-200`}>
        {/* Collapse Toggle */}
        <div className="p-1 md:p-2 border-b flex justify-end">
          <BlackButton
            size="icon-sm"
            onClick={() => setSidebarCollapsed(!isCollapsed)}
            className="h-5 w-5 md:h-6 md:w-6"
          >
            {isCollapsed ? <PanelLeftOpen className="h-3 w-3 md:h-4 md:w-4" /> : <PanelLeftClose className="h-3 w-3 md:h-4 md:w-4" />}
          </BlackButton>
        </div>

        {/* Vault Selection Header */}
        {!isCollapsed && (
        <div className="p-2 md:p-3 border-b">
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
        )}

        {/* Main Content */}
        {!isCollapsed && (
        <div className="flex-1 overflow-hidden px-1 md:px-2">
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
                       pinnedSessions.map((session, index) => renderSessionItem(session, true, index, 'pinned-'))
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
              <BlackButton 
                size="icon-sm"
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
              </BlackButton>
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
                  recentSessions.map((session) => renderSessionItem(session, false, undefined, 'recent-'))
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
              <BlackButton 
                size="icon-sm"
                className="h-6 w-6"
                onClick={(e) => {
                  e.stopPropagation()
                  setProjectDialogOpen(true)
                }} 
                disabled={!currentVault}
                title="Create New Project"
              >
                <Plus className="w-3 h-3" />
              </BlackButton>
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
        )}

        {/* User Menu Footer */}
        {!isCollapsed && (
        <div className="p-2 md:p-3 border-t">
          <DropdownMenu>
            <DropdownMenuTrigger asChild>
              <Button
                variant="ghost"
                className="w-full justify-start h-12 px-3 data-[state=open]:bg-gray-100"
              >
                <UserAvatar className="rounded-lg mr-3" size="md" />
                <div className="grid flex-1 text-left text-sm leading-tight">
                  <span className="truncate font-semibold">{userDisplayName}</span>
                  <span className="truncate text-xs text-gray-500">Local User</span>
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
              <DropdownMenuItem onClick={() => window.location.href = '/settings'}>
                <User className="w-4 h-4 mr-2" />
                Settings
              </DropdownMenuItem>
              <DropdownMenuItem onClick={() => {
                // Future: Open command palette (Ctrl+K)
                console.log('Command palette not yet implemented')
              }}>
                <Terminal className="w-4 h-4 mr-2" />
                Commands
              </DropdownMenuItem>

            </DropdownMenuContent>
          </DropdownMenu>
        </div>
        )}

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
    </ErrorBoundary>
  )
}