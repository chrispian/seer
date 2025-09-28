import React, { useEffect, useRef, useState } from 'react'
import ReactMarkdown from 'react-markdown'
import remarkGfm from 'remark-gfm'
import { Card } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Textarea } from '@/components/ui/textarea'

type Msg = { id: string; role: 'user'|'assistant'; md: string }
const uuid = () => crypto.randomUUID()

function useCsrf() {
  return (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || ''
}

export default function ChatIsland() {
  const [messages, setMessages] = useState<Msg[]>([])
  const [input, setInput] = useState('')
  const [isSending, setSending] = useState(false)
  const csrf = useCsrf()
  const scrollerRef = useRef<HTMLDivElement>(null)

  // autoscroll when messages grow
  useEffect(() => {
    scrollerRef.current?.scrollTo({ top: scrollerRef.current.scrollHeight, behavior: 'smooth' })
  }, [messages])

  async function onSend() {
    const content = input.trim()
    if (!content || isSending) return
    setInput('')
    const userId = uuid()
    setMessages(m => [...m, { id: userId, role: 'user', md: content }])
    setSending(true)

    // 1) Create message -> get message_id
    const resp = await fetch('/api/messages', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
      body: JSON.stringify({ content }),   // you can add provider/model here later
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
  }

  return (
    <div className="flex flex-col gap-4">
      {/* Transcript */}
      <Card ref={scrollerRef} className="p-4 h-[60vh] md:h-[70vh] overflow-y-auto">
        <div className="space-y-4">
          {messages.map(m => (
            <div key={m.id} className="flex gap-3">
              <div className="w-8 h-8 rounded-full shrink-0 bg-black text-white grid place-items-center text-xs">
                {m.role === 'user' ? 'U' : 'AI'}
              </div>
              <div className="prose prose-sm max-w-none">
                <ReactMarkdown remarkPlugins={[remarkGfm]}>{m.md}</ReactMarkdown>
              </div>
            </div>
          ))}
          {messages.length === 0 && (
            <div className="text-sm text-neutral-500">Type a message and hit Send to stream from Ollama…</div>
          )}
        </div>
      </Card>

      {/* Composer */}
      <Card className="p-3">
        <div className="flex items-end gap-3">
          <Textarea
            value={input}
            onChange={(e) => setInput(e.target.value)}
            placeholder="Type a message…"
            className="min-h-[72px]"
            onKeyDown={(e) => {
              if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) onSend()
            }}
          />
          <Button onClick={onSend} disabled={isSending || input.trim().length === 0}>
            {isSending ? 'Sending…' : 'Send'}
          </Button>
        </div>
      </Card>
    </div>
  )
}

