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
      {messages.map((message) => {
        // Debug log
        if (message.approvalRequest) {
          console.log('Message with approval:', {
            id: message.approvalRequest.id,
            status: message.approvalRequest.status,
            isPending: message.approvalRequest.status === 'pending',
          })
        }
        
        return (
        <div
          key={message.id}
          className={`flex gap-3 px-4 py-3 ${
            index === 0 ? '' : 'border-t border-border'
          }`}
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
        )
      })}
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
