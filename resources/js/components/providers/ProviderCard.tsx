import React, { useState } from 'react'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Switch } from '@/components/ui/switch'
import { Badge } from '@/components/ui/badge'
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu'
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip'
import { 
  Settings, 
  TestTube, 
  MoreVertical, 
  Key, 
  Activity,
  Users,
  Zap,
  Eye,
  Power
} from 'lucide-react'
import { HealthStatusBadge } from './HealthStatusBadge'
import type { Provider } from '@/types/provider'

interface ProviderCardProps {
  provider: Provider
  onToggle: (providerId: string) => void
  onTest: (providerId: string) => void
  onConfigure: (providerId: string) => void
  onViewCredentials: (providerId: string) => void
  onViewDetails: (providerId: string) => void
  isLoading?: boolean
  isTestLoading?: boolean
}

export function ProviderCard({
  provider,
  onToggle,
  onTest,
  onConfigure,
  onViewCredentials,
  onViewDetails,
  isLoading = false,
  isTestLoading = false
}: ProviderCardProps) {
  const [isToggleLoading, setIsToggleLoading] = useState(false)

  const handleToggle = async () => {
    setIsToggleLoading(true)
    try {
      await onToggle(provider.id)
    } finally {
      setIsToggleLoading(false)
    }
  }

  const getProviderIcon = (providerId: string) => {
    // You can customize these icons based on actual providers
    const icons: Record<string, string> = {
      openai: '🤖',
      anthropic: '🧠',
      ollama: '🦙',
      openrouter: '🌐',
      google: '📱',
      cohere: '🔵',
      default: '⚡'
    }
    return icons[providerId] || icons.default
  }

  return (
    <Card className={`transition-all duration-200 hover:shadow-md ${
      provider.enabled ? 'border-primary/20' : 'border-muted-foreground/20 opacity-75'
    }`}>
      <CardHeader className="pb-3">
        <div className="flex items-start justify-between">
          <div className="flex items-center gap-3">
            <div className="text-2xl">{getProviderIcon(provider.id)}</div>
            <div>
              <CardTitle className="text-lg">{provider.name}</CardTitle>
              <CardDescription className="text-sm">
                {provider.model_counts ? 
                  `${provider.model_counts.total} model${provider.model_counts.total !== 1 ? 's' : ''} (${provider.model_counts.enabled} enabled)` :
                  `${provider.models.length} model${provider.models.length !== 1 ? 's' : ''} available`
                }
              </CardDescription>
            </div>
          </div>
          
          <div className="flex items-center gap-2">
            <HealthStatusBadge 
              status={provider.status} 
              lastCheck={provider.last_health_check}
              isLoading={isTestLoading}
              size="sm"
            />
            
            <DropdownMenu>
              <DropdownMenuTrigger asChild>
                <Button variant="ghost" size="sm" className="h-8 w-8 p-0">
                  <MoreVertical className="h-4 w-4" />
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="end">
                <DropdownMenuItem onClick={() => onViewDetails(provider.id)}>
                  <Eye className="mr-2 h-4 w-4" />
                  View Details
                </DropdownMenuItem>
                <DropdownMenuItem onClick={() => onConfigure(provider.id)}>
                  <Settings className="mr-2 h-4 w-4" />
                  Configure
                </DropdownMenuItem>
                <DropdownMenuItem onClick={() => onViewCredentials(provider.id)}>
                  <Key className="mr-2 h-4 w-4" />
                  Credentials
                </DropdownMenuItem>
                <DropdownMenuSeparator />
                <DropdownMenuItem onClick={() => onTest(provider.id)} disabled={isTestLoading}>
                  <TestTube className="mr-2 h-4 w-4" />
                  Test Connection
                </DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>
          </div>
        </div>
      </CardHeader>
      
      <CardContent className="pt-0">
        <div className="space-y-4">
          {/* Provider Status and Toggle */}
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-2">
              <Power className="h-4 w-4 text-muted-foreground" />
              <span className="text-sm font-medium">
                {provider.enabled ? 'Enabled' : 'Disabled'}
              </span>
            </div>
            
            <TooltipProvider>
              <Tooltip>
                <TooltipTrigger asChild>
                  <div>
                    <Switch
                      checked={provider.enabled}
                      onCheckedChange={handleToggle}
                      disabled={isLoading || isToggleLoading}
                    />
                  </div>
                </TooltipTrigger>
                <TooltipContent>
                  {provider.enabled ? 'Disable provider' : 'Enable provider'}
                </TooltipContent>
              </Tooltip>
            </TooltipProvider>
          </div>

          {/* Statistics */}
          <div className="grid grid-cols-3 gap-4 text-center">
            <div className="space-y-1">
              <div className="flex items-center justify-center gap-1">
                <Zap className="h-3 w-3 text-muted-foreground" />
                <span className="text-xs text-muted-foreground">Models</span>
              </div>
              <div className="text-sm font-medium">
                {provider.model_counts ? provider.model_counts.total : provider.models.length}
              </div>
            </div>
            
            <div className="space-y-1">
              <div className="flex items-center justify-center gap-1">
                <Key className="h-3 w-3 text-muted-foreground" />
                <span className="text-xs text-muted-foreground">Credentials</span>
              </div>
              <div className="text-sm font-medium">{provider.credentials_count}</div>
            </div>
            
            <div className="space-y-1">
              <div className="flex items-center justify-center gap-1">
                <Activity className="h-3 w-3 text-muted-foreground" />
                <span className="text-xs text-muted-foreground">Usage</span>
              </div>
              <div className="text-sm font-medium">{provider.usage_count}</div>
            </div>
          </div>

          {/* Capabilities */}
          {provider.capabilities.length > 0 && (
            <div className="space-y-2">
              <div className="text-xs font-medium text-muted-foreground">Capabilities</div>
              <div className="flex flex-wrap gap-1">
                {provider.capabilities.slice(0, 3).map((capability) => (
                  <Badge key={capability} variant="secondary" className="text-xs">
                    {capability}
                  </Badge>
                ))}
                {provider.capabilities.length > 3 && (
                  <Badge variant="outline" className="text-xs">
                    +{provider.capabilities.length - 3} more
                  </Badge>
                )}
              </div>
            </div>
          )}

          {/* Quick Actions */}
          <div className="flex gap-2">
            <Button 
              size="sm" 
              variant="outline" 
              onClick={() => onTest(provider.id)}
              disabled={isTestLoading || !provider.enabled}
              className="flex-1"
            >
              <TestTube className="mr-2 h-3 w-3" />
              Test
            </Button>
            
            <Button 
              size="sm" 
              variant="outline" 
              onClick={() => onViewCredentials(provider.id)}
              className="flex-1"
            >
              <Key className="mr-2 h-3 w-3" />
              Credentials
            </Button>
          </div>
        </div>
      </CardContent>
    </Card>
  )
}