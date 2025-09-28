import React, { createContext, useContext, useState, useEffect, useCallback } from 'react'

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
  const [currentSession, setCurrentSession] = useState<ChatSession | null>(null)
  const [isLoadingSession, setIsLoadingSession] = useState(false)
  const [recentSessions, setRecentSessions] = useState<ChatSession[]>([])
  const [pinnedSessions, setPinnedSessions] = useState<ChatSession[]>([])
  const [appContext, setAppContext] = useState<AppContext | null>(null)
  const [isLoadingContext, setIsLoadingContext] = useState(true)
  const csrf = useCsrf()

  const loadContext = useCallback(async () => {
    setIsLoadingContext(true)
    try {
      const response = await fetch('/api/chat-sessions/context')
      if (response.ok) {
        const data = await response.json()
        setAppContext(data)
      }
    } catch (error) {
      console.error('Failed to load app context:', error)
    } finally {
      setIsLoadingContext(false)
    }
  }, [])

  const loadSessions = useCallback(async () => {
    if (!appContext?.current_vault_id) return

    try {
      const [recentResponse, pinnedResponse] = await Promise.all([
        fetch(`/api/chat-sessions?vault_id=${appContext.current_vault_id}&project_id=${appContext.current_project_id || ''}`),
        fetch(`/api/chat-sessions/pinned?vault_id=${appContext.current_vault_id}&project_id=${appContext.current_project_id || ''}`)
      ])

      if (recentResponse.ok && pinnedResponse.ok) {
        const [recentData, pinnedData] = await Promise.all([
          recentResponse.json(),
          pinnedResponse.json()
        ])
        
        setRecentSessions(recentData.sessions)
        setPinnedSessions(pinnedData.sessions)
      }
    } catch (error) {
      console.error('Failed to load sessions:', error)
    }
  }, [appContext?.current_vault_id, appContext?.current_project_id])

  const createNewSession = useCallback(async (): Promise<ChatSession> => {
    const response = await fetch('/api/chat-sessions', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrf,
      },
      body: JSON.stringify({
        vault_id: appContext?.current_vault_id,
        project_id: appContext?.current_project_id,
      }),
    })

    if (!response.ok) {
      throw new Error('Failed to create new session')
    }

    const data = await response.json()
    const newSession = data.session

    // Update session lists
    setRecentSessions(prev => [newSession, ...prev])
    setCurrentSession(newSession)

    return newSession
  }, [csrf, appContext?.current_vault_id, appContext?.current_project_id])

  const switchToSession = useCallback(async (sessionId: number) => {
    setIsLoadingSession(true)
    try {
      const response = await fetch(`/api/chat-sessions/${sessionId}`)
      if (response.ok) {
        const data = await response.json()
        setCurrentSession(data.session)
      } else {
        throw new Error('Failed to load session')
      }
    } catch (error) {
      console.error('Failed to switch to session:', error)
      throw error
    } finally {
      setIsLoadingSession(false)
    }
  }, [])

  const updateSession = useCallback(async (sessionId: number, updates: Partial<ChatSession>) => {
    const response = await fetch(`/api/chat-sessions/${sessionId}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrf,
      },
      body: JSON.stringify(updates),
    })

    if (!response.ok) {
      throw new Error('Failed to update session')
    }

    const data = await response.json()
    const updatedSession = data.session

    // Update current session if it's the one being updated
    if (currentSession?.id === sessionId) {
      setCurrentSession(updatedSession)
    }

    // Update in session lists
    setRecentSessions(prev => 
      prev.map(session => session.id === sessionId ? updatedSession : session)
    )
    setPinnedSessions(prev => 
      prev.map(session => session.id === sessionId ? updatedSession : session)
    )

    return updatedSession
  }, [csrf, currentSession?.id])

  const deleteSession = useCallback(async (sessionId: number) => {
    const response = await fetch(`/api/chat-sessions/${sessionId}`, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': csrf,
      },
    })

    if (!response.ok) {
      throw new Error('Failed to delete session')
    }

    // Remove from session lists
    setRecentSessions(prev => prev.filter(session => session.id !== sessionId))
    setPinnedSessions(prev => prev.filter(session => session.id !== sessionId))

    // If deleting current session, clear it
    if (currentSession?.id === sessionId) {
      setCurrentSession(null)
    }
  }, [csrf, currentSession?.id])

  const togglePinSession = useCallback(async (sessionId: number) => {
    const response = await fetch(`/api/chat-sessions/${sessionId}/pin`, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': csrf,
      },
    })

    if (!response.ok) {
      throw new Error('Failed to toggle pin status')
    }

    // Reload sessions to get updated pin status and ordering
    await loadSessions()
  }, [csrf, loadSessions])

  const refreshCurrentSession = useCallback(async () => {
    if (!currentSession?.id) return

    try {
      const response = await fetch(`/api/chat-sessions/${currentSession.id}`)
      if (response.ok) {
        const data = await response.json()
        setCurrentSession(data.session)
      }
    } catch (error) {
      console.error('Failed to refresh current session:', error)
    }
  }, [currentSession?.id])

  // Load context on mount
  useEffect(() => {
    loadContext()
  }, [loadContext])

  // Load sessions when context is available
  useEffect(() => {
    if (appContext?.current_vault_id) {
      loadSessions()
    }
  }, [appContext?.current_vault_id, appContext?.current_project_id, loadSessions])

  // Auto-create session if none exist and context is loaded
  useEffect(() => {
    if (
      appContext?.current_vault_id && 
      !isLoadingSession && 
      !currentSession && 
      recentSessions.length === 0 && 
      pinnedSessions.length === 0
    ) {
      createNewSession().catch(console.error)
    }
  }, [appContext?.current_vault_id, isLoadingSession, currentSession, recentSessions.length, pinnedSessions.length, createNewSession])

  const contextValue: ChatSessionContextType = {
    currentSession,
    isLoadingSession,
    recentSessions,
    pinnedSessions,
    appContext,
    isLoadingContext,
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