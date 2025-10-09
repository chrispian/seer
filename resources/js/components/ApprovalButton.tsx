import React from 'react'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { CheckIcon, XIcon, AlertTriangleIcon } from 'lucide-react'

interface ApprovalButtonProps {
  requestId: string
  riskScore: number
  riskLevel?: 'low' | 'medium' | 'high' | 'critical'
  riskFactors?: string[]
  onApprove: () => void
  onReject: () => void
  isApproved?: boolean
  isRejected?: boolean
  approvedAt?: string
  rejectedAt?: string
}

export function ApprovalButton({
  requestId,
  riskScore,
  riskLevel = 'medium',
  riskFactors = [],
  onApprove,
  onReject,
  isApproved,
  isRejected,
  approvedAt,
  rejectedAt,
}: ApprovalButtonProps) {
  const [isProcessing, setProcessing] = React.useState(false)

  const riskColors = {
    low: 'bg-green-500/10 text-green-700 border-green-500/20',
    medium: 'bg-yellow-500/10 text-yellow-700 border-yellow-500/20',
    high: 'bg-orange-500/10 text-orange-700 border-orange-500/20',
    critical: 'bg-red-500/10 text-red-700 border-red-500/20',
  }

  if (isApproved) {
    return (
      <div className="flex items-center gap-2 text-sm text-green-700 bg-green-50 dark:bg-green-950 dark:text-green-300 px-3 py-2 rounded border border-green-200 dark:border-green-800">
        <CheckIcon className="w-4 h-4" />
        <span>Approved by user at {new Date(approvedAt!).toLocaleTimeString()}</span>
      </div>
    )
  }

  if (isRejected) {
    return (
      <div className="flex items-center gap-2 text-sm text-red-700 bg-red-50 dark:bg-red-950 dark:text-red-300 px-3 py-2 rounded border border-red-200 dark:border-red-800">
        <XIcon className="w-4 h-4" />
        <span>Rejected by user at {new Date(rejectedAt!).toLocaleTimeString()}</span>
      </div>
    )
  }

  return (
    <div className="space-y-2 py-2">
      <div className={`inline-flex items-center gap-2 px-3 py-1.5 rounded border text-sm ${riskColors[riskLevel]}`}>
        <AlertTriangleIcon className="w-4 h-4" />
        <span className="font-medium">
          Risk: {riskLevel.charAt(0).toUpperCase() + riskLevel.slice(1)} ({riskScore}/100)
        </span>
      </div>

      {riskFactors.length > 0 && (
        <ul className="text-sm text-muted-foreground space-y-0.5 ml-6">
          {riskFactors.slice(0, 3).map((factor, i) => (
            <li key={i}>• {factor}</li>
          ))}
        </ul>
      )}

      <div className="flex gap-2">
        <Button
          size="sm"
          variant="default"
          disabled={isProcessing}
          onClick={async () => {
            setProcessing(true)
            await onApprove()
            setProcessing(false)
          }}
        >
          <CheckIcon className="w-4 h-4 mr-1" />
          Approve
        </Button>

        <Button
          size="sm"
          variant="outline"
          disabled={isProcessing}
          onClick={async () => {
            setProcessing(true)
            await onReject()
            setProcessing(false)
          }}
        >
          <XIcon className="w-4 h-4 mr-1" />
          Reject
        </Button>
      </div>
    </div>
  )
}
