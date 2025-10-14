import { useState } from 'react'
import { DataManagementModal, ColumnDefinition } from '@/components/ui/DataManagementModal'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Shield, AlertTriangle, CheckCircle, XCircle, Clock } from 'lucide-react'
import { toast } from 'sonner'

interface ApprovalRequest {
  id: string
  operation_type: string
  status: string
  risk_score: number
  risk_level: string
  fragment_id: string
  fragment_title: string
  conversation_id: string
  operation_details: Record<string, any>
  risk_factors: string[]
  timeout_at?: string
  approved_at?: string
  approved_by_user_id?: string
  approved_by_name?: string
  created_at: string
}

interface SecurityStats {
  pending_count: number
  approved_today: number
  rejected_today: number
  timed_out_count: number
  high_risk_pending: number
}

interface SecurityDashboardModalProps {
  isOpen: boolean
  onClose: () => void
  approval_requests: ApprovalRequest[]
  stats: SecurityStats
  loading?: boolean
  error?: string | null
  onRefresh?: () => void
}

export function SecurityDashboardModal({
  isOpen,
  onClose,
  approval_requests = [],
  stats = {
    pending_count: 0,
    approved_today: 0,
    rejected_today: 0,
    timed_out_count: 0,
    high_risk_pending: 0
  },
  loading = false,
  error = null,
  onRefresh
}: SecurityDashboardModalProps) {
  const [processing, setProcessing] = useState<Set<string>>(new Set())

  const getRiskLevelColor = (level: string) => {
    switch (level.toLowerCase()) {
      case 'critical':
        return 'bg-red-100 text-red-800 border-red-300'
      case 'high':
        return 'bg-orange-100 text-orange-800 border-orange-300'
      case 'medium':
        return 'bg-yellow-100 text-yellow-800 border-yellow-300'
      case 'low':
        return 'bg-green-100 text-green-800 border-green-300'
      default:
        return 'bg-gray-100 text-gray-800'
    }
  }

  const getStatusColor = (status: string) => {
    switch (status.toLowerCase()) {
      case 'approved':
        return 'bg-green-100 text-green-800'
      case 'rejected':
        return 'bg-red-100 text-red-800'
      case 'pending':
        return 'bg-yellow-100 text-yellow-800'
      case 'timed_out':
        return 'bg-gray-100 text-gray-800'
      default:
        return 'bg-blue-100 text-blue-800'
    }
  }

  const getStatusIcon = (status: string) => {
    switch (status.toLowerCase()) {
      case 'approved':
        return <CheckCircle className="w-3 h-3" />
      case 'rejected':
        return <XCircle className="w-3 h-3" />
      case 'pending':
        return <Clock className="w-3 h-3" />
      default:
        return <AlertTriangle className="w-3 h-3" />
    }
  }

  const handleApprove = async (request: ApprovalRequest) => {
    setProcessing(prev => new Set(prev).add(request.id))
    try {
      const response = await fetch(`/api/approvals/${request.id}/approve`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        }
      })

      if (!response.ok) throw new Error('Failed to approve request')

      toast.success('Request approved successfully')
      onRefresh?.()
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Failed to approve request')
    } finally {
      setProcessing(prev => {
        const next = new Set(prev)
        next.delete(request.id)
        return next
      })
    }
  }

  const handleReject = async (request: ApprovalRequest) => {
    setProcessing(prev => new Set(prev).add(request.id))
    try {
      const response = await fetch(`/api/approvals/${request.id}/reject`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        }
      })

      if (!response.ok) throw new Error('Failed to reject request')

      toast.success('Request rejected successfully')
      onRefresh?.()
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Failed to reject request')
    } finally {
      setProcessing(prev => {
        const next = new Set(prev)
        next.delete(request.id)
        return next
      })
    }
  }

  const columns: ColumnDefinition<ApprovalRequest>[] = [
    {
      key: 'operation_type',
      label: 'Operation',
      render: (request) => (
        <div className="flex flex-col">
          <span className="font-medium text-sm">{request.operation_type}</span>
          <span className="text-xs text-muted-foreground truncate max-w-[200px]">
            {request.fragment_title}
          </span>
        </div>
      )
    },
    {
      key: 'risk_level',
      label: 'Risk',
      width: 'w-24',
      render: (request) => (
        <div className="flex items-center gap-2">
          <Badge variant="outline" className={`text-xs ${getRiskLevelColor(request.risk_level)}`}>
            {request.risk_level}
          </Badge>
          <span className="text-xs text-muted-foreground">{request.risk_score}</span>
        </div>
      )
    },
    {
      key: 'status',
      label: 'Status',
      width: 'w-28',
      render: (request) => (
        <Badge variant="outline" className={`text-xs flex items-center gap-1 ${getStatusColor(request.status)}`}>
          {getStatusIcon(request.status)}
          {request.status}
        </Badge>
      )
    },
    {
      key: 'risk_factors',
      label: 'Risk Factors',
      render: (request) => (
        <div className="flex flex-wrap gap-1">
          {(request.risk_factors || []).slice(0, 2).map((factor, i) => (
            <Badge key={i} variant="outline" className="text-xs">
              {factor}
            </Badge>
          ))}
          {(request.risk_factors?.length || 0) > 2 && (
            <Badge variant="outline" className="text-xs">
              +{(request.risk_factors?.length || 0) - 2}
            </Badge>
          )}
        </div>
      )
    },
    {
      key: 'created_at',
      label: 'Created',
      width: 'w-32',
      render: (request) => (
        <span className="text-xs text-muted-foreground">
          {new Date(request.created_at).toLocaleString()}
        </span>
      )
    },
    {
      key: 'actions',
      label: 'Actions',
      width: 'w-40',
      render: (request) => request.status === 'pending' ? (
        <div className="flex gap-2">
          <Button
            size="sm"
            variant="default"
            onClick={(e) => {
              e.stopPropagation()
              handleApprove(request)
            }}
            disabled={processing.has(request.id)}
            className="h-7 px-2 text-xs"
          >
            {processing.has(request.id) ? 'Processing...' : 'Approve'}
          </Button>
          <Button
            size="sm"
            variant="outline"
            onClick={(e) => {
              e.stopPropagation()
              handleReject(request)
            }}
            disabled={processing.has(request.id)}
            className="h-7 px-2 text-xs"
          >
            Reject
          </Button>
        </div>
      ) : request.approved_by_name ? (
        <span className="text-xs text-muted-foreground">
          By {request.approved_by_name}
        </span>
      ) : null
    }
  ]

  const filters = [
    {
      key: 'status',
      label: 'Status',
      options: [
        { value: 'all', label: 'All', count: approval_requests.length },
        { value: 'pending', label: 'Pending', count: approval_requests.filter(r => r.status === 'pending').length },
        { value: 'approved', label: 'Approved', count: approval_requests.filter(r => r.status === 'approved').length },
        { value: 'rejected', label: 'Rejected', count: approval_requests.filter(r => r.status === 'rejected').length }
      ]
    },
    {
      key: 'risk_level',
      label: 'Risk Level',
      options: [
        { value: 'all', label: 'All', count: approval_requests.length },
        { value: 'critical', label: 'Critical', count: approval_requests.filter(r => r.risk_level === 'critical').length },
        { value: 'high', label: 'High', count: approval_requests.filter(r => r.risk_level === 'high').length },
        { value: 'medium', label: 'Medium', count: approval_requests.filter(r => r.risk_level === 'medium').length },
        { value: 'low', label: 'Low', count: approval_requests.filter(r => r.risk_level === 'low').length }
      ]
    }
  ]

  return (
    <DataManagementModal
      isOpen={isOpen}
      onClose={onClose}
      title="Security Dashboard"
      data={approval_requests}
      columns={columns}
      loading={loading}
      error={error ?? undefined}
      filters={filters}
      searchPlaceholder="Search approval requests..."
      searchFields={['operation_type', 'fragment_title', 'risk_factors']}
      onRefresh={onRefresh}
      customHeader={
        <div className="space-y-3">
          <div className="text-sm text-muted-foreground">
            Monitor and manage approval requests and security policies
          </div>
          <div className="grid grid-cols-5 gap-3">
            <div className="border rounded-lg p-3 bg-yellow-50">
              <div className="text-xs text-muted-foreground">Pending</div>
              <div className="text-2xl font-bold text-yellow-700">{stats.pending_count}</div>
            </div>
            <div className="border rounded-lg p-3 bg-red-50">
              <div className="text-xs text-muted-foreground">High Risk</div>
              <div className="text-2xl font-bold text-red-700">{stats.high_risk_pending}</div>
            </div>
            <div className="border rounded-lg p-3 bg-green-50">
              <div className="text-xs text-muted-foreground">Approved Today</div>
              <div className="text-2xl font-bold text-green-700">{stats.approved_today}</div>
            </div>
            <div className="border rounded-lg p-3 bg-red-50">
              <div className="text-xs text-muted-foreground">Rejected Today</div>
              <div className="text-2xl font-bold text-red-700">{stats.rejected_today}</div>
            </div>
            <div className="border rounded-lg p-3 bg-gray-50">
              <div className="text-xs text-muted-foreground">Timed Out</div>
              <div className="text-2xl font-bold text-gray-700">{stats.timed_out_count}</div>
            </div>
          </div>
        </div>
      }
      emptyStateMessage="No approval requests found"
      emptyStateIcon={<Shield className="h-8 w-8" />}
      defaultSort="created_at"
      defaultSortDirection="desc"
    />
  )
}
