import React, { useState, useEffect, useRef } from 'react'
import { ChatComposer } from './ChatComposer'
import { ChatTranscript, ChatMessage } from './ChatTranscript'
import { CommandResultModal } from './CommandResultModal'
import { useChatSession } from '@/contexts/ChatSessionContext'

const uuid = () => crypto.randomUUID()

function useCsrf() {
  return (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || ''
}

export default function ChatIsland() {
  const [messages, setMessages] = useState<ChatMessage[]>([])
  const [isSending, setSending] = useState(false)
  const [commandResult, setCommandResult] = useState<any>(null)
  const [isCommandModalOpen, setIsCommandModalOpen] = useState(false)
  const [lastCommand, setLastCommand] = useState('')
  const csrf = useCsrf()
  const activeStreamRef = useRef<{ eventSource: EventSource; sessionId: number } | null>(null)
  
  const { 
    currentSession, 
    isLoadingSession, 
    updateSession 
  } = useChatSession()

  // Load messages from current session
  useEffect(() => {
    if (currentSession?.messages) {
      const sessionMessages: ChatMessage[] = currentSession.messages.map((msg: any, index: number) => ({
        id: msg.id || `session-${currentSession.id}-${index}`,
        role: msg.type === 'user' ? 'user' : 'assistant',
        md: msg.message || '',
        messageId: msg.id,
        fragmentId: msg.fragment_id,
        isBookmarked: msg.is_bookmarked,
      }))
      setMessages(sessionMessages)
    } else if (currentSession) {
      // New session with no messages
      setMessages([])
    }
  }, [currentSession])

  // Cleanup active streams when session changes
  useEffect(() => {
    return () => {
      if (activeStreamRef.current) {
        activeStreamRef.current.eventSource.close()
        activeStreamRef.current = null
      }
    }
  }, [currentSession?.id])

  // Save messages to current session
  const saveMessagesToSession = async (updatedMessages: ChatMessage[]) => {
    if (!currentSession) return

    try {
      const sessionMessages = updatedMessages.map(msg => ({
        id: msg.messageId || msg.id,
        type: msg.role,
        message: msg.md,
        fragment_id: msg.fragmentId,
        is_bookmarked: msg.isBookmarked,
        created_at: new Date().toISOString(),
      }))

      await updateSession(currentSession.id, {
        messages: sessionMessages,
      })
    } catch (error) {
      console.error('Failed to save messages to session:', error)
    }
  }

  async function onSend(content: string, attachments?: Array<{markdown: string, url: string, filename: string}>) {
    if (!content.trim() || isSending || !currentSession) return
    
    // Close any existing stream before starting a new one
    if (activeStreamRef.current) {
      activeStreamRef.current.eventSource.close()
      activeStreamRef.current = null
    }

    const userId = uuid()
    const userMessage: ChatMessage = { id: userId, role: 'user', md: content }
    const updatedMessages = [...messages, userMessage]
    const streamSessionId = currentSession.id // Capture session ID at start of stream
    setMessages(updatedMessages)
    setSending(true)

    try {
      // 1) Create message -> get message_id (include attachments if any)
      const payload: any = { 
        content,
        session_id: streamSessionId,
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
      const assistantId = uuid()
      let acc = ''

      // Track the active stream
      activeStreamRef.current = { eventSource: es, sessionId: streamSessionId }

      es.onmessage = (evt) => {
        try {
          // Check if this stream is still for the current session
          if (currentSession?.id !== streamSessionId) {
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
            if (currentSession?.id === streamSessionId) {
              setMessages(currentMessages => {
                saveMessagesToSession(currentMessages)
                return currentMessages
              })
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
      
      // Handle special actions
      if (result.success && result.shouldResetChat) {
        setMessages([])
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

  if (!currentSession && !isLoadingSession) {
    return (
      <div className="flex flex-col items-center justify-center h-full text-center">
        <div className="text-muted-foreground mb-4">
          <div className="text-lg font-medium mb-2">No chat session selected</div>
          <div className="text-sm">Select a chat from the sidebar or create a new one to get started.</div>
        </div>
      </div>
    )
  }

  if (isLoadingSession) {
    return (
      <div className="flex flex-col items-center justify-center h-full">
        <div className="text-muted-foreground">Loading chat session...</div>
      </div>
    )
  }

  return (
    <div className="flex flex-col gap-4 h-full">
      {/* Enhanced Transcript with Message Actions */}
      <div className="flex-1 min-h-0">
        <ChatTranscript 
          messages={messages}
          onMessageDelete={handleMessageDelete}
          onMessageBookmarkToggle={handleMessageBookmarkToggle}
        />
      </div>

      {/* Enhanced Composer with TipTap */}
      <ChatComposer 
        onSend={onSend}
        onCommand={handleCommand}
        disabled={isSending || !currentSession}
        placeholder={
          currentSession 
            ? "Type a message... Use / for commands, [[ for links, # for tags"
            : "Select a chat session to start messaging"
        }
      />

      {/* Command Result Modal */}
      <CommandResultModal
        isOpen={isCommandModalOpen}
        onClose={() => setIsCommandModalOpen(false)}
        result={commandResult}
        command={lastCommand}
      />
    </div>
  )
}

