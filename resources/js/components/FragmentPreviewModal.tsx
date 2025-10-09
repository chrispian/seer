import React from 'react'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import ReactMarkdown from 'react-markdown'
import remarkGfm from 'remark-gfm'
import { CheckIcon, XIcon, FileTextIcon } from 'lucide-react'

interface FragmentPreviewModalProps {
  isOpen: boolean
  onClose: () => void
  fragmentId: string
  title: string
  content: string
  wordCount: number
  readTimeMinutes: number
  riskScore?: number
  riskLevel?: string
  onApprove?: () => void
  onReject?: () => void
}

export function FragmentPreviewModal({
  isOpen,
  onClose,
  fragmentId,
  title,
  content,
  wordCount,
  readTimeMinutes,
  riskScore,
  riskLevel,
  onApprove,
  onReject,
}: FragmentPreviewModalProps) {
  const [isProcessing, setProcessing] = React.useState(false)

  return (
    <Dialog open={isOpen} onOpenChange={onClose}>
      <DialogContent className="max-w-4xl max-h-[90vh] overflow-hidden flex flex-col">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2">
            <FileTextIcon className="w-5 h-5" />
            {title}
          </DialogTitle>
          <div className="flex gap-2 text-sm text-muted-foreground">
            <Badge variant="outline">{wordCount} words</Badge>
            <Badge variant="outline">{readTimeMinutes} min read</Badge>
            {riskScore && (
              <Badge
                variant={
                  riskLevel === 'high' || riskLevel === 'critical'
                    ? 'destructive'
                    : 'secondary'
                }
              >
                Risk: {riskScore}/100
              </Badge>
            )}
          </div>
        </DialogHeader>

        <div className="flex-1 overflow-y-auto prose prose-sm dark:prose-invert max-w-none p-4">
          <ReactMarkdown remarkPlugins={[remarkGfm]}>{content}</ReactMarkdown>
        </div>

        {(onApprove || onReject) && (
          <DialogFooter>
            {onReject && (
              <Button
                variant="outline"
                disabled={isProcessing}
                onClick={async () => {
                  setProcessing(true)
                  await onReject()
                  setProcessing(false)
                  onClose()
                }}
              >
                <XIcon className="w-4 h-4 mr-2" />
                Reject
              </Button>
            )}
            {onApprove && (
              <Button
                disabled={isProcessing}
                onClick={async () => {
                  setProcessing(true)
                  await onApprove()
                  setProcessing(false)
                  onClose()
                }}
              >
                <CheckIcon className="w-4 h-4 mr-2" />
                Approve
              </Button>
            )}
          </DialogFooter>
        )}
      </DialogContent>
    </Dialog>
  )
}
