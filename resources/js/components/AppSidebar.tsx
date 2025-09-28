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
import {
  Sidebar,
  SidebarContent,
  SidebarFooter,
  SidebarGroup,
  SidebarGroupAction,
  SidebarGroupContent,
  SidebarGroupLabel,
  SidebarHeader,
  SidebarMenu,
  SidebarMenuAction,
  SidebarMenuBadge,
  SidebarMenuButton,
  SidebarMenuItem,
  SidebarMenuSkeleton,
  SidebarSeparator,
} from '@/components/ui/sidebar'
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
    <SidebarMenuItem key={session.id}>
      <div
        className={`relative ${
          currentSession?.id === session.id
            ? 'bg-sidebar-accent border-l-2 border-l-sidebar-primary'
            : ''
        } ${dragOverIndex === index ? 'border-t-2 border-t-blue-500' : ''}`}
        draggable={showPinHandle}
        onDragStart={showPinHandle ? (e) => handleDragStart(e, session) : undefined}
        onDragEnd={showPinHandle ? handleDragEnd : undefined}
        onDragOver={showPinHandle && typeof index === 'number' ? (e) => handleDragOver(e, index) : undefined}
        onDragLeave={showPinHandle ? handleDragLeave : undefined}
        onDrop={showPinHandle && typeof index === 'number' ? (e) => handleDrop(e, index) : undefined}
      >
        <SidebarMenuButton
          onClick={() => handleSwitchSession(session.id)}
          isActive={currentSession?.id === session.id}
          className="w-full justify-start"
        >
          {showPinHandle && (
            <GripVertical className="w-3 h-3 text-sidebar-foreground/50 cursor-grab" />
          )}
          <span className="truncate">{session.channel_display}</span>
          <SidebarMenuBadge className="ml-auto">
            {session.message_count}
          </SidebarMenuBadge>
        </SidebarMenuButton>
        
        <DropdownMenu>
          <DropdownMenuTrigger asChild>
            <SidebarMenuAction showOnHover>
              <MoreVertical className="w-4 h-4" />
            </SidebarMenuAction>
          </DropdownMenuTrigger>
          <DropdownMenuContent side="right" align="start">
            <DropdownMenuItem onClick={(e) => handleTogglePin(session.id, e)}>
              {session.is_pinned ? (
                <>
                  <PinOff className="w-4 h-4 mr-2" />
                  Unpin
                </>
              ) : (
                <>
                  <Pin className="w-4 h-4 mr-2" />
                  Pin
                </>
              )}
            </DropdownMenuItem>
            <DropdownMenuItem 
              onClick={(e) => handleDeleteSession(session.id, e)}
              disabled={deletingSessions.has(session.id)}
            >
              <Trash2 className="w-4 h-4 mr-2" />
              Delete
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
      </div>
    </SidebarMenuItem>
  )

  // Show skeleton loading when initial data is loading
  if (isLoadingVaults && vaults.length === 0) {
    return (
      <Sidebar className="border-r">
        <SidebarContent>
          <div className="p-4">
            <SidebarSkeleton />
          </div>
        </SidebarContent>
      </Sidebar>
    );
  }

  return (
    <ErrorBoundary context="sidebar">
      <Sidebar className="border-r">
        {/* Vault Selection Header */}
        <SidebarHeader>
          <SidebarMenu>
            <SidebarMenuItem>
              <DropdownMenu>
                <DropdownMenuTrigger asChild>
                  <SidebarMenuButton 
                    size="lg" 
                    className="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground"
                  >
                    <div className="flex aspect-square size-8 items-center justify-center rounded-lg bg-sidebar-primary text-sidebar-primary-foreground">
                      <Archive className="size-4" />
                    </div>
                    <div className="grid flex-1 text-left text-sm leading-tight">
                      <span className="truncate font-semibold">
                        {currentVault?.name || 'Loading...'}
                      </span>
                      <span className="truncate text-xs">Vault</span>
                    </div>
                    <ChevronDown className="ml-auto" />
                  </SidebarMenuButton>
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
                    <div className="font-medium text-muted-foreground">Add vault</div>
                  </DropdownMenuItem>
                </DropdownMenuContent>
              </DropdownMenu>
            </SidebarMenuItem>
          </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
          {/* Pinned Chats */}
          {(pinnedSessionsQuery.isLoading || pinnedSessions.length > 0) && (
            <>
              <SidebarGroup>
                <SidebarGroupLabel>
                  <Pin className="w-4 h-4" />
                  Pinned Chats
                </SidebarGroupLabel>
                <SidebarGroupContent>
                  <ScrollArea className="h-[200px]">
                    <SidebarMenu>
                      {pinnedSessionsQuery.isLoading ? (
                        Array.from({ length: 2 }).map((_, i) => (
                          <SidebarMenuItem key={`pinned-skeleton-${i}`}>
                            <SidebarMenuSkeleton showIcon />
                          </SidebarMenuItem>
                        ))
                      ) : (
                        pinnedSessions.map((session, index) => renderSessionItem(session, true, index))
                      )}
                    </SidebarMenu>
                  </ScrollArea>
                </SidebarGroupContent>
              </SidebarGroup>
              <SidebarSeparator />
            </>
          )}

          {/* Recent Chats */}
          <SidebarGroup>
            <SidebarGroupLabel>
              <MessageSquare className="w-4 h-4" />
              Recent Chats
            </SidebarGroupLabel>
            <SidebarGroupAction onClick={handleNewChat} disabled={isCreating}>
              <Plus className="w-4 h-4" />
              <span className="sr-only">Add Chat</span>
            </SidebarGroupAction>
            <SidebarGroupContent>
              <ScrollArea className="h-[300px]">
                <SidebarMenu>
                  {isLoadingSessions ? (
                    Array.from({ length: 3 }).map((_, i) => (
                      <SidebarMenuItem key={`recent-skeleton-${i}`}>
                        <SidebarMenuSkeleton showIcon />
                      </SidebarMenuItem>
                    ))
                  ) : recentSessions.length === 0 ? (
                    <div className="text-center text-sidebar-foreground/50 text-xs py-8">
                      No recent chats
                    </div>
                  ) : (
                    recentSessions.map((session) => renderSessionItem(session))
                  )}
                </SidebarMenu>
              </ScrollArea>
            </SidebarGroupContent>
          </SidebarGroup>

          <SidebarSeparator />

          {/* Projects */}
          <SidebarGroup>
            <SidebarGroupLabel>
              <Folder className="w-4 h-4" />
              Projects
            </SidebarGroupLabel>
            <SidebarGroupAction onClick={() => setProjectDialogOpen(true)} disabled={!currentVault}>
              <Plus className="w-4 h-4" />
              <span className="sr-only">Add Project</span>
            </SidebarGroupAction>
            <SidebarGroupContent>
              <ScrollArea className="h-[200px]">
                <SidebarMenu>
                  {isLoadingProjects ? (
                    Array.from({ length: 2 }).map((_, i) => (
                      <SidebarMenuItem key={`project-skeleton-${i}`}>
                        <SidebarMenuSkeleton showIcon />
                      </SidebarMenuItem>
                    ))
                  ) : projectsForCurrentVault.length === 0 ? (
                    <div className="text-center text-sidebar-foreground/50 text-xs py-8">
                      No projects available
                    </div>
                  ) : (
                    projectsForCurrentVault.map((project) => (
                      <SidebarMenuItem key={project.id}>
                        <SidebarMenuButton
                          onClick={() => switchProjectMutation.mutate(project.id)}
                          isActive={currentProject?.id === project.id}
                          disabled={switchProjectMutation.isPending}
                        >
                          <Folder className="w-4 h-4" />
                          <span className="truncate">{project.name}</span>
                          {switchProjectMutation.isPending && (
                            <Loader2 className="w-4 h-4 ml-auto animate-spin" />
                          )}
                        </SidebarMenuButton>
                      </SidebarMenuItem>
                    ))
                  )}
                </SidebarMenu>
              </ScrollArea>
            </SidebarGroupContent>
          </SidebarGroup>
        </SidebarContent>

        {/* User Menu Footer */}
        <SidebarFooter>
          <SidebarMenu>
            <SidebarMenuItem>
              <DropdownMenu>
                <DropdownMenuTrigger asChild>
                  <SidebarMenuButton
                    size="lg"
                    className="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground"
                  >
                    <Avatar className="h-8 w-8 rounded-lg">
                      <AvatarImage 
                        src={`https://www.gravatar.com/avatar/${GRAVATAR_HASH}?d=mp&s=32`} 
                        alt="chrispian@gmail.com" 
                      />
                      <AvatarFallback className="rounded-lg">CP</AvatarFallback>
                    </Avatar>
                    <div className="grid flex-1 text-left text-sm leading-tight">
                      <span className="truncate font-semibold">chrispian</span>
                      <span className="truncate text-xs">chrispian@gmail.com</span>
                    </div>
                    <ChevronUp className="ml-auto size-4" />
                  </SidebarMenuButton>
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
            </SidebarMenuItem>
          </SidebarMenu>
        </SidebarFooter>

        {/* Creation Dialogs */}
        <VaultCreateDialog 
          open={vaultDialogOpen} 
          onOpenChange={setVaultDialogOpen} 
        />
        <ProjectCreateDialog 
          open={projectDialogOpen} 
          onOpenChange={setProjectDialogOpen} 
        />
      </Sidebar>
    </ErrorBoundary>
  )
}