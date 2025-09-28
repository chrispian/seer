import React, { createContext, useContext, useCallback } from 'react'
import { useAppStore } from '../stores/useAppStore'
import { useCurrentContext, useAppContext } from '../hooks/useContext'
import { useChatSessions, usePinnedChatSessions, useCreateChatSession, useUpdateChatSession, useDeleteChatSession, useTogglePinChatSession } from '../hooks/useChatSessions'

export interface ChatSession {
  id: number
  title: string
  channel_display: string
  message_count: number
  last_activity_at: string
  is_pinned: boolean
  sort_order: number
  vault_id: number
  project_id: number | null
  messages?: any[]
  metadata?: any
}

export interface AppContext {
  vaults: Array<{
    id: number
    name: string
    description: string
    is_default: boolean
  }>
  projects: Array<{
    id: number
    name: string
    description: string
    vault_id: number
  }>
  current_vault_id: number | null
  current_project_id: number | null
}

interface ChatSessionContextType {
  // Current session
  currentSession: ChatSession | null
  isLoadingSession: boolean
  
  // Session lists
  recentSessions: ChatSession[]
  pinnedSessions: ChatSession[]
  
  // App context
  appContext: AppContext | null
  isLoadingContext: boolean
  
  // Actions
  createNewSession: () => Promise<ChatSession>
  switchToSession: (sessionId: number) => Promise<void>
  updateSession: (sessionId: number, updates: Partial<ChatSession>) => Promise<void>
  deleteSession: (sessionId: number) => Promise<void>
  togglePinSession: (sessionId: number) => Promise<void>
  loadSessions: () => Promise<void>
  loadContext: () => Promise<void>
  
  // Utilities
  refreshCurrentSession: () => Promise<void>
}

const ChatSessionContext = createContext<ChatSessionContextType | undefined>(undefined)

function useCsrf() {
  return (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || ''
}

export function ChatSessionProvider({ children }: { children: React.ReactNode }) {
  // Use Zustand store and hooks instead of local state
  const { 
    setCurrentSession, 
    getCurrentSession,
    chatSessions, 
    isLoadingSessions,
    vaults,
    projects,
    currentVaultId,
    currentProjectId
  } = useAppStore()
  
  // Use the new hook system (simplified to avoid conflicts)
  // const contextQuery = useAppContext() // Moved to AppContent to avoid conflicts
  const chatSessionsQuery = useChatSessions()
  const pinnedSessionsQuery = usePinnedChatSessions()
  const createChatMutation = useCreateChatSession()
  const updateChatMutation = useUpdateChatSession()
  const deleteChatMutation = useDeleteChatSession()
  const togglePinMutation = useTogglePinChatSession()
  
  const csrf = useCsrf()
  
  // Transform data for compatibility with existing interface
  const currentSession = getCurrentSession()
  const recentSessions = chatSessions.filter(session => !session.is_pinned)
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
  
  const appContext: AppContext | null = currentVaultId ? {
    vaults: vaults.map(vault => ({
      id: vault.id,
      name: vault.name,
      description: vault.description || '',
      is_default: vault.is_default,
    })),
    projects: projects.map(project => ({
      id: project.id,
      name: project.name,
      description: project.description || '',
      vault_id: project.vault_id,
    })),
    current_vault_id: currentVaultId,
    current_project_id: currentProjectId,
  } : null

  // Delegate to new hook system
  const loadContext = useCallback(async () => {
    // contextQuery.refetch() // Simplified to avoid conflicts
    console.log('loadContext called - delegating to AppContent useAppContext')
  }, [])

  const loadSessions = useCallback(async () => {
    chatSessionsQuery.refetch()
    pinnedSessionsQuery.refetch()
  }, [chatSessionsQuery, pinnedSessionsQuery])

  const createNewSession = useCallback(async (): Promise<ChatSession> => {
    const result = await createChatMutation.mutateAsync({})
    
    // The mutation already updates the store, but we need to return a compatible format
    return {
      id: result.session.id,
      title: result.session.title,
      channel_display: result.session.channel_display,
      message_count: result.session.message_count,
      last_activity_at: result.session.last_activity_at,
      is_pinned: result.session.is_pinned,
      sort_order: result.session.sort_order,
      vault_id: result.session.vault_id,
      project_id: result.session.project_id,
      messages: result.session.messages,
      metadata: result.session.metadata,
    }
  }, [createChatMutation])

  const switchToSession = useCallback(async (sessionId: number) => {
    // Simply update the current session in the store
    setCurrentSession(sessionId)
  }, [setCurrentSession])

  const updateSession = useCallback(async (sessionId: number, updates: Partial<ChatSession>) => {
    const result = await updateChatMutation.mutateAsync({ id: sessionId, ...updates })
    
    return {
      id: result.session.id,
      title: result.session.title,
      channel_display: result.session.channel_display,
      message_count: result.session.message_count,
      last_activity_at: result.session.last_activity_at,
      is_pinned: result.session.is_pinned,
      sort_order: result.session.sort_order,
      vault_id: result.session.vault_id,
      project_id: result.session.project_id,
      messages: result.session.messages,
      metadata: result.session.metadata,
    }
  }, [updateChatMutation])

  const deleteSession = useCallback(async (sessionId: number) => {
    await deleteChatMutation.mutateAsync(sessionId)
  }, [deleteChatMutation])

  const togglePinSession = useCallback(async (sessionId: number) => {
    await togglePinMutation.mutateAsync(sessionId)
  }, [togglePinMutation])

  const refreshCurrentSession = useCallback(async () => {
    if (!currentSession?.id) return
    
    // Refetch current session data (the hooks will handle updating the store)
    chatSessionsQuery.refetch()
  }, [currentSession?.id, chatSessionsQuery])

  const contextValue: ChatSessionContextType = {
    currentSession,
    isLoadingSession: isLoadingSessions,
    recentSessions,
    pinnedSessions,
    appContext,
    isLoadingContext: false, // Simplified for debugging
    createNewSession,
    switchToSession,
    updateSession,
    deleteSession,
    togglePinSession,
    loadSessions,
    loadContext,
    refreshCurrentSession,
  }

  return (
    <ChatSessionContext.Provider value={contextValue}>
      {children}
    </ChatSessionContext.Provider>
  )
}

export function useChatSession() {
  const context = useContext(ChatSessionContext)
  if (context === undefined) {
    throw new Error('useChatSession must be used within a ChatSessionProvider')
  }
  return context
}