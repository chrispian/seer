import React from 'react'
import ReactMarkdown from 'react-markdown'
import remarkGfm from 'remark-gfm'

import { MessageActions } from './MessageActions'
import { UserAvatar } from '@/components/UserAvatar'
import { ApprovalButtonSimple } from '@/components/security/ApprovalButtonSimple'
import { FragmentPreviewModal } from '@/components/FragmentPreviewModal'

export interface ApprovalRequest {
  id: string
  operationType: string
  operationSummary: string
  riskScore: number
  riskLevel: 'low' | 'medium' | 'high' | 'critical'
  riskFactors: string[]
  status: 'pending' | 'approved' | 'rejected' | 'timeout'
  approvedAt?: string
  rejectedAt?: string
  useModal: boolean
  fragmentId?: string
  fragmentTitle?: string
  fragmentContent?: string
  wordCount?: number
  readTimeMinutes?: number
}

export interface ChatMessage {
  id: string // Client-side UUID for React keys
  role: 'user' | 'assistant'
  md: string
  isBookmarked?: boolean
  messageId?: string // Server-side message ID from API
  fragmentId?: string // Server-side fragment ID if message becomes a fragment
  approvalRequest?: ApprovalRequest // For operations requiring approval
  executionResult?: any // Execution result when approval is granted
}

interface ChatTranscriptProps {
  messages: ChatMessage[]
  onMessageDelete?: (messageId: string) => void
  onMessageBookmarkToggle?: (messageId: string, bookmarked: boolean, fragmentId?: string) => void
  onApprovalApprove?: (approvalId: string) => void
  onApprovalReject?: (approvalId: string) => void
  className?: string
}

export function ChatTranscript({
  messages,
  onMessageDelete,
  onMessageBookmarkToggle,
  onApprovalApprove,
  onApprovalReject,
  className = ""
}: ChatTranscriptProps) {
  const scrollerRef = React.useRef<HTMLDivElement>(null)
  const [previewModal, setPreviewModal] = React.useState<{
    isOpen: boolean
    fragmentId?: string
    title?: string
    content?: string
    wordCount?: number
    readTimeMinutes?: number
    approvalId?: string
  }>({ isOpen: false })

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
      <div ref={scrollerRef} className={`p-3 h-full overflow-y-auto bg-background ${className}`}>
        <div className="flex items-center justify-center h-full">
          <div className="text-center text-foreground">
            <div className="text-base font-medium mb-2">Start a conversation</div>
            <div className="text-sm text-muted-foreground">
              Type a message below to begin chatting. Use / for commands, [[ for links, # for tags.
            </div>
          </div>
        </div>
      </div>
    )
  }

  return (
    <div ref={scrollerRef} className={`p-3 h-full overflow-y-auto bg-background ${className}`}>
      <div className="space-y-2">
        {messages.map((message) => (
          <div key={message.id} className="group flex gap-2 relative hover:bg-accent/5 p-2 transition-colors">
            {/* Avatar */}
            {message.role === 'user' ? (
              <UserAvatar className="rounded-sm shrink-0" size="sm" />
            ) : (
              <div className="w-6 h-6 rounded-sm shrink-0 bg-primary text-primary-foreground grid place-items-center text-xs font-medium">
                A
              </div>
            )}

            {/* Message Content with floating actions */}
            <div className="flex-1 min-w-0 relative">
              {/* Message Actions - Floating */}
              <MessageActions
                messageId={message.id}
                serverMessageId={message.messageId}
                serverFragmentId={message.fragmentId}
                content={message.md}
                isBookmarked={message.isBookmarked}
                onDelete={onMessageDelete}
                onBookmarkToggle={onMessageBookmarkToggle}
                className="float-right ml-2 mb-2 clear-right"
              />
              
              {/* Message Content */}
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
                
                {/* Approval Status Box (for approved/rejected) */}
                {message.approvalRequest && message.approvalRequest.status !== 'pending' && (
                  <div className="mt-3">
                    <ApprovalButtonSimple
                      requestId={message.approvalRequest.id}
                      riskScore={message.approvalRequest.riskScore}
                      status={message.approvalRequest.status}
                      approvedAt={message.approvalRequest.approvedAt}
                      rejectedAt={message.approvalRequest.rejectedAt}
                      onApprove={() => onApprovalApprove?.(message.approvalRequest!.id)}
                      onReject={() => onApprovalReject?.(message.approvalRequest!.id)}
                    />
                  </div>
                )}
                
                {/* Execution Result (after approval box) */}
                {message.executionResult?.executed && (
                  <div className="mt-3">
                    <div className="prose prose-sm max-w-none text-foreground">
                      <p><strong>Execution Result:</strong></p>
                      <pre className="bg-muted p-2 rounded-sm overflow-x-auto">
                        <code>{message.executionResult.output || message.executionResult.error}</code>
                      </pre>
                    </div>
                  </div>
                )}
                
                {/* Pending approval buttons */}
                {message.approvalRequest && message.approvalRequest.status === 'pending' && (
                  <div className="mt-3">
                    <ApprovalButtonSimple
                      requestId={message.approvalRequest.id}
                      riskScore={message.approvalRequest.riskScore}
                      status={message.approvalRequest.status}
                      approvedAt={message.approvalRequest.approvedAt}
                      rejectedAt={message.approvalRequest.rejectedAt}
                      onApprove={() => onApprovalApprove?.(message.approvalRequest!.id)}
                      onReject={() => onApprovalReject?.(message.approvalRequest!.id)}
                    />
                  </div>
                )}
              </div>
            </div>
          </div>
        ))}
      </div>

      {/* Fragment Preview Modal */}
      <FragmentPreviewModal
        isOpen={previewModal.isOpen}
        onClose={() => setPreviewModal({ isOpen: false })}
        fragmentId={previewModal.fragmentId || ''}
        title={previewModal.title || ''}
        content={previewModal.content || ''}
        wordCount={previewModal.wordCount || 0}
        readTimeMinutes={previewModal.readTimeMinutes || 0}
        onApprove={previewModal.approvalId ? async () => {
          onApprovalApprove?.(previewModal.approvalId!)
        } : undefined}
        onReject={previewModal.approvalId ? async () => {
          onApprovalReject?.(previewModal.approvalId!)
        } : undefined}
      />
    </div>
  )
}
