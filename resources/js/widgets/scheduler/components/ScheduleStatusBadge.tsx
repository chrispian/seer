import React from 'react'
import { Badge } from '@/components/ui/badge'
import { 
  Clock, 
  Play, 
  CheckCircle, 
  XCircle, 
  Pause,
  Lock,
  AlertTriangle
} from 'lucide-react'

interface ScheduleStatusBadgeProps {
  status: 'active' | 'inactive' | 'completed'
  isDue?: boolean
  isLocked?: boolean
  variant?: 'default' | 'secondary' | 'outline' | 'destructive'
  size?: 'sm' | 'default'
  showIcon?: boolean
}

interface RunStatusBadgeProps {
  status: 'pending' | 'running' | 'completed' | 'failed'
  variant?: 'default' | 'secondary' | 'outline' | 'destructive'
  size?: 'sm' | 'default'
  showIcon?: boolean
}

export function ScheduleStatusBadge({ 
  status, 
  isDue = false,
  isLocked = false,
  variant,
  size = 'default',
  showIcon = true 
}: ScheduleStatusBadgeProps) {
  const getStatusConfig = () => {
    if (isLocked) {
      return {
        variant: 'outline' as const,
        icon: Lock,
        label: 'Locked',
        color: '#F59E0B'
      }
    }

    if (isDue && status === 'active') {
      return {
        variant: 'destructive' as const,
        icon: AlertTriangle,
        label: 'Due Now',
        color: '#EF4444'
      }
    }

    switch (status) {
      case 'active':
        return {
          variant: 'default' as const,
          icon: Play,
          label: 'Active',
          color: '#10B981'
        }
      case 'inactive':
        return {
          variant: 'secondary' as const,
          icon: Pause,
          label: 'Inactive',
          color: '#6B7280'
        }
      case 'completed':
        return {
          variant: 'outline' as const,
          icon: CheckCircle,
          label: 'Completed',
          color: '#10B981'
        }
      default:
        return {
          variant: 'outline' as const,
          icon: Clock,
          label: status,
          color: '#6B7280'
        }
    }
  }

  const config = getStatusConfig()
  const IconComponent = config.icon

  return (
    <Badge
      variant={variant || config.variant}
      className={`${
        size === 'sm' ? 'text-xs h-5' : 'text-xs h-6'
      }`}
    >
      {showIcon && (
        <IconComponent className={`${
          size === 'sm' ? 'w-3 h-3' : 'w-3 h-3'
        } mr-1 flex-shrink-0`} />
      )}
      <span>{config.label}</span>
    </Badge>
  )
}

export function RunStatusBadge({ 
  status, 
  variant,
  size = 'default',
  showIcon = true 
}: RunStatusBadgeProps) {
  const getStatusConfig = () => {
    switch (status) {
      case 'pending':
        return {
          variant: 'outline' as const,
          icon: Clock,
          label: 'Pending',
          color: '#6B7280'
        }
      case 'running':
        return {
          variant: 'default' as const,
          icon: Play,
          label: 'Running',
          color: '#3B82F6'
        }
      case 'completed':
        return {
          variant: 'secondary' as const,
          icon: CheckCircle,
          label: 'Completed',
          color: '#10B981'
        }
      case 'failed':
        return {
          variant: 'destructive' as const,
          icon: XCircle,
          label: 'Failed',
          color: '#EF4444'
        }
      default:
        return {
          variant: 'outline' as const,
          icon: Clock,
          label: status,
          color: '#6B7280'
        }
    }
  }

  const config = getStatusConfig()
  const IconComponent = config.icon

  return (
    <Badge
      variant={variant || config.variant}
      className={`${
        size === 'sm' ? 'text-xs h-5' : 'text-xs h-6'
      }`}
    >
      {showIcon && (
        <IconComponent className={`${
          size === 'sm' ? 'w-3 h-3' : 'w-3 h-3'
        } mr-1 flex-shrink-0`} />
      )}
      <span>{config.label}</span>
    </Badge>
  )
}