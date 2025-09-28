import React from 'react'
import ReactMarkdown from 'react-markdown'
import remarkGfm from 'remark-gfm'
import { Card } from '@/components/ui/card'
import { MessageActions } from './MessageActions'

export interface ChatMessage {
  id: string
  role: 'user' | 'assistant'
  md: string
  isBookmarked?: boolean
}

interface ChatTranscriptProps {
  messages: ChatMessage[]
  onMessageDelete?: (messageId: string) => void
  onMessageBookmarkToggle?: (messageId: string, bookmarked: boolean) => void
  className?: string
}

export function ChatTranscript({ 
  messages, 
  onMessageDelete, 
  onMessageBookmarkToggle,
  className = ""
}: ChatTranscriptProps) {
  const scrollerRef = React.useRef<HTMLDivElement>(null)

  // Auto-scroll when messages change
  React.useEffect(() => {
    if (scrollerRef.current) {
      scrollerRef.current.scrollTo({ 
        top: scrollerRef.current.scrollHeight, 
        behavior: 'smooth' 
      })
    }
  }, [messages])

  if (messages.length === 0) {
    return (
      <Card ref={scrollerRef} className={`p-4 h-[60vh] md:h-[70vh] overflow-y-auto ${className}`}>
        <div className="flex items-center justify-center h-full">
          <div className="text-center text-muted-foreground">
            <div className="text-lg font-medium mb-2">Start a conversation</div>
            <div className="text-sm">
              Type a message below to begin chatting. Use / for commands, [[ for links, # for tags.
            </div>
          </div>
        </div>
      </Card>
    )
  }

  return (
    <Card ref={scrollerRef} className={`p-4 h-[60vh] md:h-[70vh] overflow-y-auto ${className}`}>
      <div className="space-y-4">
        {messages.map((message) => (
          <div key={message.id} className="group flex gap-3 relative">
            {/* Avatar */}
            <div className="w-8 h-8 rounded-full shrink-0 bg-primary text-primary-foreground grid place-items-center text-xs font-medium">
              {message.role === 'user' ? 'CB' : 'AI'}
            </div>
            
            {/* Message Content */}
            <div className="flex-1 min-w-0">
              <div className="prose prose-sm max-w-none">
                <ReactMarkdown 
                  remarkPlugins={[remarkGfm]}
                  components={{
                    // Custom link rendering for wiki-style links
                    a: ({ href, children, ...props }) => {
                      if (href?.startsWith('[[') && href?.endsWith(']]')) {
                        return (
                          <span className="bg-blue-100 text-blue-800 px-1 rounded text-sm">
                            {children}
                          </span>
                        )
                      }
                      return <a href={href} {...props}>{children}</a>
                    },
                    // Custom handling for hashtags
                    p: ({ children, ...props }) => {
                      if (typeof children === 'string') {
                        const parts = children.split(/(#\w+)/g)
                        return (
                          <p {...props}>
                            {parts.map((part, index) => 
                              part.match(/^#\w+/) ? (
                                <span key={index} className="bg-green-100 text-green-800 px-1 rounded text-sm mr-1">
                                  {part}
                                </span>
                              ) : part
                            )}
                          </p>
                        )
                      }
                      return <p {...props}>{children}</p>
                    }
                  }}
                >
                  {message.md}
                </ReactMarkdown>
              </div>
            </div>
            
            {/* Message Actions */}
            <MessageActions
              messageId={message.id}
              content={message.md}
              isBookmarked={message.isBookmarked}
              onDelete={onMessageDelete}
              onBookmarkToggle={onMessageBookmarkToggle}
              className="absolute top-0 right-0"
            />
          </div>
        ))}
      </div>
    </Card>
  )
}