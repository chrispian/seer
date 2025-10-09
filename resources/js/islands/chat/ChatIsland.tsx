import React, { useState, useEffect, useRef, useMemo } from 'react'
import { ChatComposer } from './ChatComposer'
import { ChatTranscript, ChatMessage } from './ChatTranscript'
import { CommandResultModal } from './CommandResultModal'
import { TodoManagementModal } from './TodoManagementModal'
import { InboxModal } from '../system/InboxModal'
import { TypeSystemModal } from '../system/TypeSystemModal'
import { SchedulerModal } from '../system/SchedulerModal'
import { useAppStore } from '@/stores/useAppStore'
import { useChatSessionDetails, useUpdateChatSession } from '@/hooks/useChatSessions'
import { useQuery, useQueryClient } from '@tanstack/react-query'
import { useModelSelection } from '@/hooks/useModelSelection'
import { useUser } from '@/hooks/useUser'
import { providersApi } from '@/lib/api/providers'

const uuid = (prefix?: string) => {
  const id = crypto.randomUUID()
  return prefix ? `${prefix}-${id}` : id
}

function useCsrf() {
  return (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || ''
}

const parseModelIdentifier = (value: string): [string, string] | null => {
  if (!value) return null

  if (value.includes('/')) {
    const [provider, ...rest] = value.split('/')
    if (!provider || rest.length === 0) return null
    return [provider, rest.join('/')]
  }

  if (value.includes(':')) {
    const [provider, ...rest] = value.split(':')
    if (!provider || rest.length === 0) return null
    return [provider, rest.join(':')]
  }

  return null
}

export default function ChatIsland() {
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
  const userQuery = useUser()
  const modelsQuery = useQuery({
    queryKey: ['models', 'available'],
    queryFn: async () => {
      const response = await fetch('/api/models/available')

      if (!response.ok) {
        throw new Error('Failed to load available models')
      }

      const json = await response.json()
      return Array.isArray(json?.data) ? json.data : []
    },
    staleTime: 5 * 60 * 1000,
  })
  const providersQuery = useQuery({
    queryKey: ['providers', 'chat'],
    queryFn: async () => {
      try {
        const providers = await providersApi.getProviders()
        return Array.isArray(providers) ? providers : []
      } catch (error) {
        console.error('Failed to load providers', error)
        return []
      }
    },
    staleTime: 5 * 60 * 1000,
  })
  const updateSessionMutation = useUpdateChatSession()

  // Get current session from store (includes messages if loaded)
  const currentSession = getCurrentSession()
  const isLoadingSession = sessionDetailsQuery.isLoading

  // Model selection state - use session details data which includes model info
  const sessionData = sessionDetailsQuery.data?.session
  const sessionModelValue = sessionData?.model_provider && sessionData?.model_name
    ? `${sessionData.model_provider}/${sessionData.model_name}`
    : ''
  const userDefaultModel = userQuery.data?.user?.profile_settings?.ai?.default_model ?? ''
  const userDefaultProvider = userQuery.data?.user?.profile_settings?.ai?.default_provider ?? ''

  const availableProviders: Array<{ provider: string | number; models: Array<{ value: string; model_key?: string }> }> = useMemo(
    () => (Array.isArray(modelsQuery.data) ? modelsQuery.data : []),
    [modelsQuery.data],
  )

  const providerCatalog = useMemo(() => (Array.isArray(providersQuery.data) ? providersQuery.data : []), [providersQuery.data])

  const modelKeyToAvailableValue = useMemo(() => {
    const map = new Map<string, string>()
    availableProviders.forEach((entry) => {
      entry.models?.forEach((model) => {
        const key = model.model_key ?? ''
        if (key) {
          map.set(key, model.value)
        }
      })
    })
    return map
  }, [availableProviders])

  const providerIdToFirstValue = useMemo(() => {
    const map = new Map<string, string>()
    availableProviders.forEach((entry) => {
      const id = String(entry.provider)
      if (!map.has(id)) {
        const firstValue = entry.models?.[0]?.value
        if (firstValue) {
          map.set(id, firstValue)
        }
      }
    })
    return map
  }, [availableProviders])

  const modelKeyToProviderSlug = useMemo(() => {
    const map = new Map<string, string>()
    providerCatalog.forEach((provider: any) => {
      const providerId = provider?.id
      const models = Array.isArray(provider?.models) ? provider.models : []
      models.forEach((model: any) => {
        if (model?.id) {
          map.set(model.id, providerId)
        }
      })
    })
    return map
  }, [providerCatalog])

  const providerSlugToFirstModelKey = useMemo(() => {
    const map = new Map<string, string>()
    providerCatalog.forEach((provider: any) => {
      const providerId = provider?.id
      if (!providerId) return
      const models = Array.isArray(provider?.models) ? provider.models : []
      const firstEnabled = models.find((model: any) => model?.enabled)
      if (firstEnabled?.id) {
        map.set(providerId, firstEnabled.id)
      }
    })
    return map
  }, [providerCatalog])

  const modelValueToProviderSlug = useMemo(() => {
    const map = new Map<string, string>()
    availableProviders.forEach((entry) => {
      entry.models?.forEach((model) => {
        const slug = modelKeyToProviderSlug.get(model.model_key ?? '')
        if (slug) {
          map.set(model.value, slug)
        }
      })
    })
    return map
  }, [availableProviders, modelKeyToProviderSlug])

  const modelValueToModelKey = useMemo(() => {
    const map = new Map<string, string>()
    availableProviders.forEach((entry) => {
      entry.models?.forEach((model) => {
        const key = model.model_key ?? ''
        if (key) {
          map.set(model.value, key)
        }
      })
    })
    return map
  }, [availableProviders])

  const availableModelValues = Array.from(modelValueToModelKey.keys())

  const getFirstModelForProvider = (providerIdentifier: string): string => {
    if (!providerIdentifier) {
      return ''
    }

    // Attempt provider slug first
    const modelKey = providerSlugToFirstModelKey.get(providerIdentifier)
    if (modelKey) {
      const value = modelKeyToAvailableValue.get(modelKey)
      if (value) {
        return value
      }
    }

    // Fallback to numeric provider id via available models
    const byId = providerIdToFirstValue.get(String(providerIdentifier))
    return byId ?? ''
  }

  const firstAvailableModel = availableModelValues[0] ?? ''

  const initialModelValue = useMemo(() => {
    const resolveFromPreference = (value: string): string => {
      if (!value) {
        return ''
      }

      if (availableModelValues.includes(value)) {
        return value
      }

      const parsed = parseModelIdentifier(value)
      const providerSlug = parsed?.[0] ?? ''
      const modelKey = parsed?.[1] ?? ''

      if (modelKey) {
        const candidate = modelKeyToAvailableValue.get(modelKey)
        if (candidate) {
          return candidate
        }
      }

      if (providerSlug) {
        const fallback = getFirstModelForProvider(providerSlug)
        if (fallback) {
          return fallback
        }
      }

      return ''
    }

    const resolvedSession = resolveFromPreference(sessionModelValue)
    if (resolvedSession) {
      return resolvedSession
    }

    const resolvedUserDefault = resolveFromPreference(userDefaultModel)
    if (resolvedUserDefault) {
      return resolvedUserDefault
    }

    const providerFallback = getFirstModelForProvider(userDefaultProvider)
    if (providerFallback) {
      return providerFallback
    }

    return firstAvailableModel
  }, [availableModelValues, availableProviders, firstAvailableModel, sessionModelValue, userDefaultModel, userDefaultProvider])
  
  const normaliseModelForApi = useMemo(() => {
    return (value: string): string => {
      const parsed = parseModelIdentifier(value)
      const fallbackProvider = parsed?.[0] ?? ''
      const fallbackModelKey = parsed?.[1] ?? ''

      const providerSlug = modelValueToProviderSlug.get(value) ?? fallbackProvider
      const modelKey = modelValueToModelKey.get(value) ?? fallbackModelKey

      if (providerSlug && modelKey) {
        return `${providerSlug}/${modelKey}`
      }

      return value
    }
  }, [modelValueToModelKey, modelValueToProviderSlug])

  const { selectedModel, updateModel } = useModelSelection({
    sessionId: currentSessionId,
    defaultModel: initialModelValue,
    transformModelForApi: normaliseModelForApi,
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
    if (!content.trim() || isSending || !currentSessionId) {
      if (!currentSessionId) {
        console.error('No chat session selected. Please select or create a chat session first.')
      }
      return
    }

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
      if (selectedModel) {
        const parsedModel = parseModelIdentifier(selectedModel)
        if (parsedModel) {
          let [provider, model] = parsedModel

          const slugFromValue = modelValueToProviderSlug.get(selectedModel)
          const modelKey = modelValueToModelKey.get(selectedModel)

          if (slugFromValue) {
            provider = slugFromValue
          }

          // Ensure we send model key rather than numeric id component
          if (modelKey) {
            model = modelKey
          }

          payload.provider = provider
          payload.model = model
        }
      }
      
      if (attachments && attachments.length > 0) {
        payload.attachments = attachments
      }

      const resp = await fetch('/api/messages', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
        body: JSON.stringify(payload),
      })
      
      if (!resp.ok) {
        const errorText = await resp.text()
        throw new Error(`Failed to send message: ${resp.status} ${resp.statusText}. ${errorText}`)
      }

      const responseData = await resp.json()
      const { message_id, skip_stream, assistant_message, requires_approval, approval_request } = responseData

      // Update user message with server message ID
      const messagesWithMessageId = updatedMessages.map(msg =>
        msg.id === userId ? { ...msg, messageId: message_id } : msg
      )
      setMessages(messagesWithMessageId)

      // If requires approval, show approval UI
      if (requires_approval && approval_request) {
        console.log('Approval required - creating approval message', approval_request)
        const assistantId = uuid(`assistant-${streamSessionId}`)
        const approvalMessage: ChatMessage = {
          id: assistantId,
          role: 'assistant' as const,
          md: responseData.message || 'This operation requires your approval.',
          messageId: message_id,
          approvalRequest: approval_request,
        }
        console.log('Approval message created:', approvalMessage)
        const finalMessages = [...messagesWithMessageId, approvalMessage]
        setMessages(finalMessages)
        setSending(false)
        saveMessagesToSession(finalMessages)
        return
      }

      // If skip_stream is true (e.g., for exec-tool), display message directly
      if (skip_stream && assistant_message) {
        const assistantId = uuid(`assistant-${streamSessionId}`)
        const finalMessages = [...messagesWithMessageId, { 
          id: assistantId, 
          role: 'assistant' as const, 
          md: assistant_message, 
          messageId: message_id 
        }]
        setMessages(finalMessages)
        setSending(false)
        saveMessagesToSession(finalMessages)
        return
      }

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
          
          // Handle tool-aware pipeline events
          if (data.type === 'pipeline_start' || data.type === 'context_assembled') {
            // Optional: Show status indicator
            return
          }
          
          if (data.type === 'router_decision') {
            if (data.needs_tools) {
              acc = `_Selecting tools for: ${data.goal}_\n\n`
              setMessages(m => {
                const last = m[m.length - 1]
                if (last?.id === assistantId) {
                  const copy = [...m]; copy[copy.length - 1] = { ...last, md: acc }; return copy
                }
                return [...m, { id: assistantId, role: 'assistant', md: acc, messageId: message_id }]
              })
            }
            return
          }
          
          if (data.type === 'tool_plan') {
            acc += `_Executing ${data.step_count} tool(s)..._\n\n`
            setMessages(m => {
              const last = m[m.length - 1]
              if (last?.id === assistantId) {
                const copy = [...m]; copy[copy.length - 1] = { ...last, md: acc }; return copy
              }
              return [...m, { id: assistantId, role: 'assistant', md: acc, messageId: message_id }]
            })
            return
          }
          
          if (data.type === 'tool_result') {
            const status = data.result?.success ? '✓' : '✗'
            acc += `${status} ${data.tool_id}\n`
            setMessages(m => {
              const last = m[m.length - 1]
              if (last?.id === assistantId) {
                const copy = [...m]; copy[copy.length - 1] = { ...last, md: acc }; return copy
              }
              return [...m, { id: assistantId, role: 'assistant', md: acc, messageId: message_id }]
            })
            return
          }
          
          if (data.type === 'summarizing' || data.type === 'composing') {
            return
          }
          
          if (data.type === 'final_message') {
            acc = data.message
            setMessages(m => {
              const last = m[m.length - 1]
              if (last?.id === assistantId) {
                const copy = [...m]; copy[copy.length - 1] = { ...last, md: acc }; return copy
              }
              return [...m, { id: assistantId, role: 'assistant', md: acc, messageId: message_id }]
            })
            return
          }
          
          // Standard LLM streaming
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
      
      // Show error to user
      const errorMessage: ChatMessage = { 
        id: uuid(`error-${streamSessionId}`), 
        role: 'assistant', 
        md: `❌ **Error sending message**: ${error instanceof Error ? error.message : 'Unknown error occurred'}. Please try again.` 
      }
      setMessages(prevMessages => [...prevMessages, errorMessage])
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

  const handleApprovalApprove = async (approvalId: string) => {
    try {
      const response = await fetch(`/api/approvals/${approvalId}/approve`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrf,
        },
      })

      if (!response.ok) {
        throw new Error('Failed to approve request')
      }

      const data = await response.json()

      // Update message with approved status
      setMessages(msgs => msgs.map(m =>
        m.approvalRequest?.id === approvalId
          ? { ...m, approvalRequest: { ...m.approvalRequest, status: 'approved', approvedAt: new Date().toISOString() } }
          : m
      ))

      console.log('Approval granted:', data)
    } catch (error) {
      console.error('Failed to approve:', error)
    }
  }

  const handleApprovalReject = async (approvalId: string) => {
    try {
      const response = await fetch(`/api/approvals/${approvalId}/reject`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrf,
        },
      })

      if (!response.ok) {
        throw new Error('Failed to reject request')
      }

      const data = await response.json()

      // Update message with rejected status
      setMessages(msgs => msgs.map(m =>
        m.approvalRequest?.id === approvalId
          ? { ...m, approvalRequest: { ...m.approvalRequest, status: 'rejected', rejectedAt: new Date().toISOString() } }
          : m
      ))

      console.log('Approval rejected:', data)
    } catch (error) {
      console.error('Failed to reject:', error)
    }
  }

  const handleCommand = async (command: string) => {
    console.log('Executing command:', command)
    setLastCommand(command)

    // Handle system management commands locally
    if (command === 'inbox-ui' || command === 'inbox' || command === 'pending' || command === 'review') {
      setIsInboxModalOpen(true)
      return
    }
    
    if (command === 'types-ui' || command === 'type-system' || command === 'typepacks') {
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
          onApprovalApprove={handleApprovalApprove}
          onApprovalReject={handleApprovalReject}
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
