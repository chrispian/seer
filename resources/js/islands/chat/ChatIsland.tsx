import React, { useState } from 'react'
import { ChatComposer } from './ChatComposer'
import { ChatTranscript, ChatMessage } from './ChatTranscript'

const uuid = () => crypto.randomUUID()

function useCsrf() {
  return (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || ''
}

export default function ChatIsland() {
  const [messages, setMessages] = useState<ChatMessage[]>([])
  const [isSending, setSending] = useState(false)
  const csrf = useCsrf()

  async function onSend(content: string, attachments?: Array<{markdown: string, url: string, filename: string}>) {
    if (!content.trim() || isSending) return
    
    const userId = uuid()
    setMessages(m => [...m, { id: userId, role: 'user', md: content }])
    setSending(true)

    try {
      // 1) Create message -> get message_id (include attachments if any)
      const payload: any = { content }
      if (attachments && attachments.length > 0) {
        payload.attachments = attachments
      }
      
      const resp = await fetch('/api/messages', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
        body: JSON.stringify(payload),
      })
      const { message_id } = await resp.json()

      // 2) Stream reply
      const es = new EventSource(`/api/chat/stream/${message_id}`)
      const assistantId = uuid()
      let acc = ''

      es.onmessage = (evt) => {
        try {
          const data = JSON.parse(evt.data)
          if (data.type === 'assistant_delta') {
            acc += data.content
            setMessages(m => {
              const last = m[m.length - 1]
              if (last?.id === assistantId) {
                const copy = [...m]; copy[copy.length - 1] = { ...last, md: acc }; return copy
              }
              return [...m, { id: assistantId, role: 'assistant', md: acc }]
            })
          }
          if (data.type === 'done') { es.close(); setSending(false) }
        } catch {/* ignore */}
      }
      es.onerror = () => { es.close(); setSending(false) }
    } catch (error) {
      console.error('Failed to send message:', error)
      setSending(false)
    }
  }

  const handleMessageDelete = (messageId: string) => {
    setMessages(m => m.filter(msg => msg.id !== messageId))
  }

  const handleMessageBookmarkToggle = (messageId: string, bookmarked: boolean) => {
    setMessages(m => m.map(msg => 
      msg.id === messageId ? { ...msg, isBookmarked: bookmarked } : msg
    ))
  }

  return (
    <div className="flex flex-col gap-4">
      {/* Enhanced Transcript with Message Actions */}
      <ChatTranscript 
        messages={messages}
        onMessageDelete={handleMessageDelete}
        onMessageBookmarkToggle={handleMessageBookmarkToggle}
      />

      {/* Enhanced Composer with TipTap */}
      <ChatComposer 
        onSend={onSend}
        disabled={isSending}
        placeholder="Type a message... Use / for commands, [[ for links, # for tags"
      />
    </div>
  )
}

