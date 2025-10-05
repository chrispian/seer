import React from 'react'
import { Badge } from '@/components/ui/badge'
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip'
import { CheckCircle, AlertCircle, Clock, Loader2 } from 'lucide-react'
import type { ProviderStatus } from '@/types/provider'

interface HealthStatusBadgeProps {
  status: ProviderStatus
  lastCheck?: string
  isLoading?: boolean
  size?: 'sm' | 'md' | 'lg'
}

export function HealthStatusBadge({ 
  status, 
  lastCheck, 
  isLoading = false,
  size = 'md' 
}: HealthStatusBadgeProps) {
  const getStatusConfig = () => {
    if (isLoading) {
      return {
        variant: 'secondary' as const,
        icon: Loader2,
        text: 'Checking...',
        className: 'text-muted-foreground'
      }
    }

    switch (status) {
      case 'healthy':
        return {
          variant: 'default' as const,
          icon: CheckCircle,
          text: 'Healthy',
          className: 'bg-green-100 text-green-800 border-green-200 hover:bg-green-200'
        }
      case 'unhealthy':
        return {
          variant: 'destructive' as const,
          icon: AlertCircle,
          text: 'Unhealthy',
          className: 'bg-red-100 text-red-800 border-red-200 hover:bg-red-200'
        }
      case 'unknown':
      default:
        return {
          variant: 'secondary' as const,
          icon: Clock,
          text: 'Unknown',
          className: 'bg-gray-100 text-gray-800 border-gray-200 hover:bg-gray-200'
        }
    }
  }

  const config = getStatusConfig()
  const Icon = config.icon
  
  const iconSize = size === 'sm' ? 'w-3 h-3' : size === 'lg' ? 'w-5 h-5' : 'w-4 h-4'
  const badgeSize = size === 'sm' ? 'text-xs px-2 py-1' : size === 'lg' ? 'text-sm px-3 py-1.5' : 'text-xs px-2.5 py-1'

  const formatLastCheck = (timestamp?: string) => {
    if (!timestamp) return 'Never checked'
    
    try {
      const date = new Date(timestamp)
      const now = new Date()
      const diffMs = now.getTime() - date.getTime()
      const diffMinutes = Math.floor(diffMs / (1000 * 60))
      
      if (diffMinutes < 1) return 'Just now'
      if (diffMinutes < 60) return `${diffMinutes}m ago`
      
      const diffHours = Math.floor(diffMinutes / 60)
      if (diffHours < 24) return `${diffHours}h ago`
      
      const diffDays = Math.floor(diffHours / 24)
      return `${diffDays}d ago`
    } catch {
      return 'Invalid date'
    }
  }

  const badge = (
    <Badge 
      variant={config.variant}
      className={`${config.className} ${badgeSize} flex items-center gap-1.5 transition-colors`}
    >
      <Icon className={`${iconSize} ${isLoading ? 'animate-spin' : ''}`} />
      <span>{config.text}</span>
    </Badge>
  )

  if (lastCheck) {
    return (
      <TooltipProvider>
        <Tooltip>
          <TooltipTrigger asChild>
            {badge}
          </TooltipTrigger>
          <TooltipContent>
            <div className="text-center">
              <div className="font-medium">{config.text}</div>
              <div className="text-xs text-muted-foreground">
                Last checked: {formatLastCheck(lastCheck)}
              </div>
            </div>
          </TooltipContent>
        </Tooltip>
      </TooltipProvider>
    )
  }

  return badge
}