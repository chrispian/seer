import React, { useState, useEffect, useRef } from 'react'
import { ChatComposer } from './ChatComposer'
import { ChatTranscript, ChatMessage } from './ChatTranscript'
import { CommandResultModal } from './CommandResultModal'
import { TodoManagementModal } from './TodoManagementModal'
import { InboxModal } from '../system/InboxModal'
import { TypeSystemModal } from '../system/TypeSystemModal'
import { SchedulerModal } from '../system/SchedulerModal'
import { useAppStore } from '@/stores/useAppStore'
import { useChatSessionDetails, useUpdateChatSession } from '@/hooks/useChatSessions'
import { useQueryClient } from '@tanstack/react-query'
import { useModelSelection } from '@/hooks/useModelSelection'

const uuid = (prefix?: string) => {
  const id = crypto.randomUUID()
  return prefix ? `${prefix}-${id}` : id
}

function useCsrf() {
  return (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || ''
}

export default function ChatIsland() {
  console.log('DEBUG: ChatIsland component mounting/rendering')
  const [messages, setMessages] = useState<ChatMessage[]>([])
  const [isSending, setSending] = useState(false)
  const [commandResult, setCommandResult] = useState<any>(null)
  const [isCommandModalOpen, setIsCommandModalOpen] = useState(false)
  const [lastCommand, setLastCommand] = useState('')
  
  // System management modals
  const [isInboxModalOpen, setIsInboxModalOpen] = useState(false)
  const [isTypeSystemModalOpen, setIsTypeSystemModalOpen] = useState(false)
  const [isSchedulerModalOpen, setIsSchedulerModalOpen] = useState(false)
  const [isTodoModalOpen, setIsTodoModalOpen] = useState(false)
  const csrf = useCsrf()
  const activeStreamRef = useRef<{ eventSource: EventSource; sessionId: number } | null>(null)
  const queryClient = useQueryClient()

  // Use Zustand store and React Query directly
  const { currentSessionId, getCurrentSession } = useAppStore()
  const sessionDetailsQuery = useChatSessionDetails(currentSessionId)
  const updateSessionMutation = useUpdateChatSession()

  // Get current session from store (includes messages if loaded)
  const currentSession = getCurrentSession()
  const isLoadingSession = sessionDetailsQuery.isLoading

  // Model selection state - use session details data which includes model info
  const sessionData = sessionDetailsQuery.data?.session
  const currentModelValue = sessionData?.model_provider && sessionData?.model_name 
    ? `${sessionData.model_provider}:${sessionData.model_name}` 
    : ''
  
  const { selectedModel, updateModel } = useModelSelection({
    sessionId: currentSessionId,
    defaultModel: currentModelValue,
  })

  // Load messages from current session (loaded via React Query)
  useEffect(() => {
    if (sessionDetailsQuery.data?.session?.messages) {
      console.log(`DEBUG: Loading messages for session ${sessionDetailsQuery.data.session.id}`)
      console.log(`DEBUG: Raw messages count: ${sessionDetailsQuery.data.session.messages.length}`)
      console.log(`DEBUG: Raw messages data:`, sessionDetailsQuery.data.session.messages.map((m: any) => ({
        id: m.id,
        type: m.type,
        fragment_id: m.fragment_id,
        message_preview: (m.message || '').substring(0, 50) + '...'
      })))

      const sessionMessages: ChatMessage[] = sessionDetailsQuery.data.session.messages.map((msg: any, index: number) => {
        // Create unique React key by combining session ID, message type, and original ID
        const messageKey = `session-${sessionDetailsQuery.data.session.id}-${msg.type}-${msg.id || index}`
        console.log(`DEBUG: Creating message with key: ${messageKey}, type: ${msg.type}`)
        return {
          id: messageKey,
          role: msg.type === 'user' ? 'user' : 'assistant',
          md: msg.message || '',
          messageId: msg.id,
          fragmentId: msg.fragment_id,
          isBookmarked: msg.is_bookmarked,
        }
      })
      setMessages(sessionMessages)
    } else if (currentSession && !sessionDetailsQuery.isLoading) {
      // Session exists but has no messages or messages haven't loaded yet
      setMessages([])
    }
  }, [sessionDetailsQuery.data, currentSession, sessionDetailsQuery.isLoading])

  // Cleanup active streams when session changes
  useEffect(() => {
    return () => {
      if (activeStreamRef.current) {
        activeStreamRef.current.eventSource.close()
        activeStreamRef.current = null
      }
    }
  }, [currentSessionId])

  // Save messages to current session
  const saveMessagesToSession = async (updatedMessages: ChatMessage[]) => {
    if (!currentSessionId) return

    try {
      const sessionMessages = updatedMessages.map(msg => ({
        id: msg.messageId || msg.id,
        type: msg.role,
        message: msg.md,
        fragment_id: msg.fragmentId,
        is_bookmarked: msg.isBookmarked,
        created_at: new Date().toISOString(),
      }))

      await updateSessionMutation.mutateAsync({
        id: currentSessionId,
        messages: sessionMessages,
      })
    } catch (error) {
      console.error('Failed to save messages to session:', error)
    }
  }

  async function onSend(content: string, attachments?: Array<{markdown: string, url: string, filename: string}>) {
    if (!content.trim() || isSending || !currentSessionId) return

    // Close any existing stream before starting a new one
    if (activeStreamRef.current) {
      activeStreamRef.current.eventSource.close()
      activeStreamRef.current = null
    }

    const streamSessionId = currentSessionId // Capture session ID at start of stream
    const userId = uuid(`user-${streamSessionId}`)
    const userMessage: ChatMessage = { id: userId, role: 'user', md: content }
    const updatedMessages = [...messages, userMessage]
    setMessages(updatedMessages)
    setSending(true)

    try {
      // 1) Create message -> get message_id (include attachments if any)
      const payload: any = {
        content,
        session_id: streamSessionId,
      }
      
      // Add selected model if available
      if (selectedModel && selectedModel.includes(':')) {
        const [provider, model] = selectedModel.split(':', 2)
        payload.provider = provider
        payload.model = model
      }
      
      if (attachments && attachments.length > 0) {
        payload.attachments = attachments
      }

      const resp = await fetch('/api/messages', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
        body: JSON.stringify(payload),
      })
      const { message_id } = await resp.json()

      // Update user message with server message ID
      const messagesWithMessageId = updatedMessages.map(msg =>
        msg.id === userId ? { ...msg, messageId: message_id } : msg
      )
      setMessages(messagesWithMessageId)

      // 2) Stream reply
      const es = new EventSource(`/api/chat/stream/${message_id}`)
      const assistantId = uuid(`assistant-${streamSessionId}`)
      let acc = ''

      // Track the active stream
      activeStreamRef.current = { eventSource: es, sessionId: streamSessionId }

      es.onmessage = (evt) => {
        try {
          // Check if this stream is still for the current session
          if (currentSessionId !== streamSessionId) {
            es.close()
            activeStreamRef.current = null
            return
          }

          const data = JSON.parse(evt.data)
          if (data.type === 'assistant_delta') {
            acc += data.content
            setMessages(m => {
              const last = m[m.length - 1]
              if (last?.id === assistantId) {
                const copy = [...m]; copy[copy.length - 1] = { ...last, md: acc }; return copy
              }
              return [...m, { id: assistantId, role: 'assistant', md: acc, messageId: message_id }]
            })
          }
          if (data.type === 'done') {
            es.close()
            activeStreamRef.current = null
            setSending(false)
            // Only save if still in the same session
            if (currentSessionId === streamSessionId) {
              setMessages(currentMessages => {
                saveMessagesToSession(currentMessages)
                return currentMessages
              })
              
              // Invalidate activity cache when messages complete (creates fragments)
              queryClient.invalidateQueries({ queryKey: ['widgets', 'today-activity'] })
            }
          }
        } catch {/* ignore */}
      }

      es.onerror = () => {
        es.close()
        activeStreamRef.current = null
        setSending(false)
      }
    } catch (error) {
      console.error('Failed to send message:', error)
      setSending(false)
      activeStreamRef.current = null
    }
  }

  const handleMessageDelete = (messageId: string) => {
    const updatedMessages = messages.filter(msg => msg.id !== messageId)
    setMessages(updatedMessages)
    saveMessagesToSession(updatedMessages)
  }

  const handleMessageBookmarkToggle = (messageId: string, bookmarked: boolean, fragmentId?: string) => {
    const updatedMessages = messages.map(msg =>
      msg.id === messageId ? {
        ...msg,
        isBookmarked: bookmarked,
        fragmentId: fragmentId || msg.fragmentId
      } : msg
    )
    setMessages(updatedMessages)
    saveMessagesToSession(updatedMessages)
  }

  const handleCommand = async (command: string) => {
    console.log('Executing command:', command)
    setLastCommand(command)

    // Handle system management commands locally
    if (command === 'inbox-ui' || command === 'inbox' || command === 'pending' || command === 'review') {
      setIsInboxModalOpen(true)
      return
    }
    
    if (command === 'types-ui' || command === 'types' || command === 'type-system' || command === 'typepacks') {
      setIsTypeSystemModalOpen(true)
      return
    }
    
    if (command === 'scheduler-ui' || command === 'scheduler' || command === 'schedules' || command === 'cron' || command === 'automation') {
      setIsSchedulerModalOpen(true)
      return
    }
    
    if (command === 'todos-ui' || command === 'todos' || command === 'todo-list' || command === 'todo-manager' || command === 'todo-management') {
      setIsTodoModalOpen(true)
      return
    }

    try {
      const response = await fetch('/api/commands/execute', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrf
        },
        body: JSON.stringify({ command })
      })

      const result = await response.json()

      // Show result in modal
      setCommandResult(result)
      setIsCommandModalOpen(true)

      // Invalidate activity cache when command is executed successfully
      if (result.success) {
        queryClient.invalidateQueries({ queryKey: ['widgets', 'today-activity'] })
      }

      // Handle special actions
      if (result.success && result.shouldResetChat) {
        setMessages([])
      }

      // Handle navigation actions
      if (result.success && result.shouldOpenPanel && result.panelData?.action === 'navigate') {
        const url = result.panelData.url
        if (url) {
          window.location.href = url
          return // Don't show modal for navigation commands
        }
      }

    } catch (error) {
      console.error('Command execution failed:', error)
      // Show error in modal
      setCommandResult({
        success: false,
        error: 'Failed to execute command. Please try again.',
        type: 'error'
      })
      setIsCommandModalOpen(true)
    }
  }

  // Show "no session selected" only when there's truly no session selected
  if (!currentSessionId) {
    return (
      <div className="flex flex-col items-center justify-center h-full text-center">
        <div className="text-muted-foreground mb-4">
          <div className="text-lg font-medium mb-2">No chat session selected</div>
          <div className="text-sm">Select a chat from the sidebar or create a new one to get started.</div>
        </div>
      </div>
    )
  }

  // Show loading state when we have a session ID but are fetching its details
  if (currentSessionId && sessionDetailsQuery.isLoading) {
    return (
      <div className="flex flex-col items-center justify-center h-full">
        <div className="text-muted-foreground">Loading chat session...</div>
      </div>
    )
  }

  // Show error state if session details failed to load
  if (currentSessionId && sessionDetailsQuery.isError) {
    return (
      <div className="flex flex-col items-center justify-center h-full text-center">
        <div className="text-muted-foreground mb-4">
          <div className="text-lg font-medium mb-2">Failed to load chat session</div>
          <div className="text-sm">There was an error loading the selected chat session.</div>
        </div>
      </div>
    )
  }

  return (
    <div className="flex flex-col h-full">
      {/* Enhanced Transcript with Message Actions - Adaptive Middle Row */}
      <div className="flex-1 min-h-0 pb-3">
        <ChatTranscript
          messages={messages}
          onMessageDelete={handleMessageDelete}
          onMessageBookmarkToggle={handleMessageBookmarkToggle}
        />
      </div>

      {/* Enhanced Composer with TipTap - Fixed Bottom Row */}
      <div className="flex-shrink-0 border-t border-border">
        <div className="px-3 pb-3">
        <ChatComposer
          onSend={onSend}
          onCommand={handleCommand}
          disabled={isSending || !currentSessionId}
          placeholder={
            currentSessionId
              ? "Type a message... Use / for commands, [[ for links, # for tags"
              : "Select a chat session to start messaging"
          }
          selectedModel={selectedModel}
          onModelChange={updateModel}
        />
      </div>

      {/* Command Result Modal */}
      <CommandResultModal
        isOpen={isCommandModalOpen}
        onClose={() => setIsCommandModalOpen(false)}
        result={commandResult}
        command={lastCommand}
      />

      {/* System Management Modals */}
      <InboxModal
        isOpen={isInboxModalOpen}
        onClose={() => setIsInboxModalOpen(false)}
      />
      
      <TypeSystemModal
        isOpen={isTypeSystemModalOpen}
        onClose={() => setIsTypeSystemModalOpen(false)}
      />
      
      <SchedulerModal
        isOpen={isSchedulerModalOpen}
        onClose={() => setIsSchedulerModalOpen(false)}
      />
      
      <TodoManagementModal
        isOpen={isTodoModalOpen}
        onClose={() => setIsTodoModalOpen(false)}
      />
        </div>
      </div>
  )
}

