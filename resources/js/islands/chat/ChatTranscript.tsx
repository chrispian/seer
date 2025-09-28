import React from 'react'
import ReactMarkdown from 'react-markdown'
import remarkGfm from 'remark-gfm'
import { Card } from '@/components/ui/card'
import { MessageActions } from './MessageActions'

export interface ChatMessage {
  id: string // Client-side UUID for React keys
  role: 'user' | 'assistant'
  md: string
  isBookmarked?: boolean
  messageId?: string // Server-side message ID from API
  fragmentId?: string // Server-side fragment ID if message becomes a fragment
}

interface ChatTranscriptProps {
  messages: ChatMessage[]
  onMessageDelete?: (messageId: string) => void
  onMessageBookmarkToggle?: (messageId: string, bookmarked: boolean, fragmentId?: string) => void
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
      <Card ref={scrollerRef} className={`p-3 h-full overflow-y-auto border-0 bg-background ${className}`}>
        <div className="flex items-center justify-center h-full">
          <div className="text-center text-foreground">
            <div className="text-base font-medium mb-2">Start a conversation</div>
            <div className="text-sm text-muted-foreground">
              Type a message below to begin chatting. Use / for commands, [[ for links, # for tags.
            </div>
          </div>
        </div>
      </Card>
    )
  }

  return (
    <Card ref={scrollerRef} className={`p-3 h-full overflow-y-auto border-0 bg-background ${className}`}>
      <div className="space-y-2">
        {messages.map((message) => (
          <div key={message.id} className="group flex gap-2 relative hover:bg-accent/5 p-2 transition-colors">
            {/* Avatar */}
            <div className="w-6 h-6 rounded-sm shrink-0 bg-primary text-primary-foreground grid place-items-center text-xs font-medium">
              {message.role === 'user' ? 'U' : 'A'}
            </div>

            {/* Message Content */}
            <div className="flex-1 min-w-0">
              <div className="prose prose-sm max-w-none text-foreground">
                <ReactMarkdown
                  remarkPlugins={[remarkGfm]}
                  components={{
                    // Custom link rendering for wiki-style links
                    a: ({ href, children, ...props }) => {
                      if (href?.startsWith('[[') && href?.endsWith(']]')) {
                        return (
                          <span className="bg-blue-50 text-blue-700 px-1 rounded-sm text-sm">
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
                          <p className="text-foreground" {...props}>
                            {parts.map((part, index) =>
                              part.match(/^#\w+/) ? (
                                <span key={index} className="bg-green-50 text-green-700 px-1 rounded-sm text-sm mr-1">
                                  {part}
                                </span>
                              ) : part
                            )}
                          </p>
                        )
                      }
                      return <p className="text-foreground" {...props}>{children}</p>
                    },
                    // Ensure all text elements use proper foreground color
                    h1: ({ children, ...props }) => <h1 className="text-foreground" {...props}>{children}</h1>,
                    h2: ({ children, ...props }) => <h2 className="text-foreground" {...props}>{children}</h2>,
                    h3: ({ children, ...props }) => <h3 className="text-foreground" {...props}>{children}</h3>,
                    h4: ({ children, ...props }) => <h4 className="text-foreground" {...props}>{children}</h4>,
                    h5: ({ children, ...props }) => <h5 className="text-foreground" {...props}>{children}</h5>,
                    h6: ({ children, ...props }) => <h6 className="text-foreground" {...props}>{children}</h6>,
                    strong: ({ children, ...props }) => <strong className="text-foreground" {...props}>{children}</strong>,
                    em: ({ children, ...props }) => <em className="text-foreground" {...props}>{children}</em>,
                    code: ({ children, ...props }) => <code className="text-foreground bg-muted px-1 py-0.5 rounded-sm text-sm" {...props}>{children}</code>,
                    pre: ({ children, ...props }) => <pre className="bg-muted p-2 rounded-sm overflow-x-auto" {...props}>{children}</pre>,
                    ul: ({ children, ...props }) => <ul className="text-foreground" {...props}>{children}</ul>,
                    ol: ({ children, ...props }) => <ol className="text-foreground" {...props}>{children}</ol>,
                    li: ({ children, ...props }) => <li className="text-foreground" {...props}>{children}</li>,
                  }}
                >
                  {message.md}
                </ReactMarkdown>
              </div>
            </div>

            {/* Message Actions */}
            <MessageActions
              messageId={message.id}
              serverMessageId={message.messageId}
              serverFragmentId={message.fragmentId}
              content={message.md}
              isBookmarked={message.isBookmarked}
              onDelete={onMessageDelete}
              onBookmarkToggle={onMessageBookmarkToggle}
              className="absolute top-1 right-1"
            />
          </div>
        ))}
      </div>
    </Card>
  )
}
