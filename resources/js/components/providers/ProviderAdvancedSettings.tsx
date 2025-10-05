import React, { useState, useEffect } from 'react'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Switch } from '@/components/ui/switch'
import { Slider } from '@/components/ui/slider'
import { Badge } from '@/components/ui/badge'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { Separator } from '@/components/ui/separator'
import { LoadingSpinner } from '@/components/ui/loading-spinner'
import { 
  Settings, 
  Cpu, 
  Database, 
  Clock, 
  TrendingUp,
  AlertTriangle,
  Shield,
  Zap,
  ChevronDown,
  ChevronRight,
  Info,
  BarChart3
} from 'lucide-react'
import {
  Collapsible,
  CollapsibleContent,
  CollapsibleTrigger,
} from '@/components/ui/collapsible'
import { providersApi } from '@/lib/api/providers'
import type { Provider } from '@/types/provider'

interface AdvancedSettings {
  performance: {
    cache_enabled: boolean
    cache_ttl: number
    connection_pooling: boolean
    max_connections: number
    keepalive_timeout: number
  }
  reliability: {
    circuit_breaker_enabled: boolean
    failure_threshold: number
    recovery_timeout: number
    retry_backoff_multiplier: number
    max_retry_delay: number
  }
  monitoring: {
    detailed_logging: boolean
    metrics_collection: boolean
    alert_on_failures: boolean
    health_check_interval: number
  }
  security: {
    request_signing: boolean
    ip_whitelist: string[]
    user_agent_rotation: boolean
    request_encryption: boolean
  }
  experimental: {
    streaming_optimization: boolean
    async_processing: boolean
    batch_requests: boolean
    smart_routing: boolean
  }
}

interface ProviderAdvancedSettingsProps {
  provider: Provider
  onSettingsChange?: (settings: AdvancedSettings) => void
}

export function ProviderAdvancedSettings({ 
  provider, 
  onSettingsChange 
}: ProviderAdvancedSettingsProps) {
  const [settings, setSettings] = useState<AdvancedSettings>({
    performance: {
      cache_enabled: true,
      cache_ttl: 300,
      connection_pooling: true,
      max_connections: 10,
      keepalive_timeout: 30
    },
    reliability: {
      circuit_breaker_enabled: true,
      failure_threshold: 5,
      recovery_timeout: 60,
      retry_backoff_multiplier: 2,
      max_retry_delay: 30
    },
    monitoring: {
      detailed_logging: false,
      metrics_collection: true,
      alert_on_failures: true,
      health_check_interval: 60
    },
    security: {
      request_signing: false,
      ip_whitelist: [],
      user_agent_rotation: false,
      request_encryption: true
    },
    experimental: {
      streaming_optimization: false,
      async_processing: false,
      batch_requests: false,
      smart_routing: false
    }
  })

  const [openSections, setOpenSections] = useState<Set<string>>(new Set(['performance']))
  const [loading, setLoading] = useState(false)

  // Handle settings updates
  const updateSetting = (section: keyof AdvancedSettings, key: string, value: any) => {
    setSettings(prev => ({
      ...prev,
      [section]: {
        ...prev[section],
        [key]: value
      }
    }))
  }

  // Toggle section visibility
  const toggleSection = (section: string) => {
    setOpenSections(prev => {
      const newSet = new Set(prev)
      if (newSet.has(section)) {
        newSet.delete(section)
      } else {
        newSet.add(section)
      }
      return newSet
    })
  }

  // Notify parent of changes
  useEffect(() => {
    onSettingsChange?.(settings)
  }, [settings, onSettingsChange])

  return (
    <div className="space-y-4">
      {/* Performance Settings */}
      <Card>
        <Collapsible 
          open={openSections.has('performance')} 
          onOpenChange={() => toggleSection('performance')}
        >
          <CollapsibleTrigger asChild>
            <CardHeader className="cursor-pointer hover:bg-muted/50 transition-colors">
              <CardTitle className="flex items-center justify-between">
                <div className="flex items-center gap-2">
                  <Cpu className="h-5 w-5" />
                  Performance Optimization
                </div>
                {openSections.has('performance') ? (
                  <ChevronDown className="h-4 w-4" />
                ) : (
                  <ChevronRight className="h-4 w-4" />
                )}
              </CardTitle>
              <CardDescription>
                Cache, connection pooling, and performance tuning
              </CardDescription>
            </CardHeader>
          </CollapsibleTrigger>
          <CollapsibleContent>
            <CardContent className="space-y-4">
              <div className="grid grid-cols-2 gap-4">
                <div className="flex items-center justify-between">
                  <div>
                    <Label className="text-sm font-medium">Response Caching</Label>
                    <p className="text-xs text-muted-foreground">Cache responses to improve speed</p>
                  </div>
                  <Switch
                    checked={settings.performance.cache_enabled}
                    onCheckedChange={(value) => updateSetting('performance', 'cache_enabled', value)}
                  />
                </div>
                <div className="flex items-center justify-between">
                  <div>
                    <Label className="text-sm font-medium">Connection Pooling</Label>
                    <p className="text-xs text-muted-foreground">Reuse connections for efficiency</p>
                  </div>
                  <Switch
                    checked={settings.performance.connection_pooling}
                    onCheckedChange={(value) => updateSetting('performance', 'connection_pooling', value)}
                  />
                </div>
              </div>

              {settings.performance.cache_enabled && (
                <div>
                  <Label className="text-sm font-medium">Cache TTL (seconds)</Label>
                  <div className="mt-2">
                    <Slider
                      value={[settings.performance.cache_ttl]}
                      onValueChange={([value]) => updateSetting('performance', 'cache_ttl', value)}
                      max={3600}
                      min={60}
                      step={60}
                      className="w-full"
                    />
                    <div className="flex justify-between text-xs text-muted-foreground mt-1">
                      <span>1 min</span>
                      <span>{Math.floor(settings.performance.cache_ttl / 60)} min</span>
                      <span>60 min</span>
                    </div>
                  </div>
                </div>
              )}

              <div className="grid grid-cols-2 gap-4">
                <div>
                  <Label htmlFor="max-connections">Max Connections</Label>
                  <Input
                    id="max-connections"
                    type="number"
                    min="1"
                    max="100"
                    value={settings.performance.max_connections}
                    onChange={(e) => updateSetting('performance', 'max_connections', parseInt(e.target.value))}
                    className="mt-1"
                  />
                </div>
                <div>
                  <Label htmlFor="keepalive">Keepalive Timeout</Label>
                  <Input
                    id="keepalive"
                    type="number"
                    min="5"
                    max="300"
                    value={settings.performance.keepalive_timeout}
                    onChange={(e) => updateSetting('performance', 'keepalive_timeout', parseInt(e.target.value))}
                    className="mt-1"
                  />
                </div>
              </div>
            </CardContent>
          </CollapsibleContent>
        </Collapsible>
      </Card>

      {/* Reliability Settings */}
      <Card>
        <Collapsible 
          open={openSections.has('reliability')} 
          onOpenChange={() => toggleSection('reliability')}
        >
          <CollapsibleTrigger asChild>
            <CardHeader className="cursor-pointer hover:bg-muted/50 transition-colors">
              <CardTitle className="flex items-center justify-between">
                <div className="flex items-center gap-2">
                  <Shield className="h-5 w-5" />
                  Reliability & Error Handling
                </div>
                {openSections.has('reliability') ? (
                  <ChevronDown className="h-4 w-4" />
                ) : (
                  <ChevronRight className="h-4 w-4" />
                )}
              </CardTitle>
              <CardDescription>
                Circuit breakers, retry logic, and failure recovery
              </CardDescription>
            </CardHeader>
          </CollapsibleTrigger>
          <CollapsibleContent>
            <CardContent className="space-y-4">
              <div className="flex items-center justify-between">
                <div>
                  <Label className="text-sm font-medium">Circuit Breaker</Label>
                  <p className="text-xs text-muted-foreground">Temporarily disable failing providers</p>
                </div>
                <Switch
                  checked={settings.reliability.circuit_breaker_enabled}
                  onCheckedChange={(value) => updateSetting('reliability', 'circuit_breaker_enabled', value)}
                />
              </div>

              {settings.reliability.circuit_breaker_enabled && (
                <div className="space-y-4">
                  <div className="grid grid-cols-2 gap-4">
                    <div>
                      <Label htmlFor="failure-threshold">Failure Threshold</Label>
                      <Input
                        id="failure-threshold"
                        type="number"
                        min="1"
                        max="20"
                        value={settings.reliability.failure_threshold}
                        onChange={(e) => updateSetting('reliability', 'failure_threshold', parseInt(e.target.value))}
                        className="mt-1"
                      />
                      <p className="text-xs text-muted-foreground mt-1">
                        Failures before circuit opens
                      </p>
                    </div>
                    <div>
                      <Label htmlFor="recovery-timeout">Recovery Timeout (s)</Label>
                      <Input
                        id="recovery-timeout"
                        type="number"
                        min="30"
                        max="600"
                        value={settings.reliability.recovery_timeout}
                        onChange={(e) => updateSetting('reliability', 'recovery_timeout', parseInt(e.target.value))}
                        className="mt-1"
                      />
                    </div>
                  </div>
                </div>
              )}

              <Separator />

              <div>
                <Label className="text-sm font-medium mb-2 block">Retry Backoff Strategy</Label>
                <div className="grid grid-cols-2 gap-4">
                  <div>
                    <Label htmlFor="backoff-multiplier">Backoff Multiplier</Label>
                    <Input
                      id="backoff-multiplier"
                      type="number"
                      min="1"
                      max="10"
                      step="0.1"
                      value={settings.reliability.retry_backoff_multiplier}
                      onChange={(e) => updateSetting('reliability', 'retry_backoff_multiplier', parseFloat(e.target.value))}
                      className="mt-1"
                    />
                  </div>
                  <div>
                    <Label htmlFor="max-retry-delay">Max Retry Delay (s)</Label>
                    <Input
                      id="max-retry-delay"
                      type="number"
                      min="1"
                      max="120"
                      value={settings.reliability.max_retry_delay}
                      onChange={(e) => updateSetting('reliability', 'max_retry_delay', parseInt(e.target.value))}
                      className="mt-1"
                    />
                  </div>
                </div>
              </div>
            </CardContent>
          </CollapsibleContent>
        </Collapsible>
      </Card>

      {/* Monitoring Settings */}
      <Card>
        <Collapsible 
          open={openSections.has('monitoring')} 
          onOpenChange={() => toggleSection('monitoring')}
        >
          <CollapsibleTrigger asChild>
            <CardHeader className="cursor-pointer hover:bg-muted/50 transition-colors">
              <CardTitle className="flex items-center justify-between">
                <div className="flex items-center gap-2">
                  <BarChart3 className="h-5 w-5" />
                  Monitoring & Observability
                </div>
                {openSections.has('monitoring') ? (
                  <ChevronDown className="h-4 w-4" />
                ) : (
                  <ChevronRight className="h-4 w-4" />
                )}
              </CardTitle>
              <CardDescription>
                Logging, metrics, and health monitoring configuration
              </CardDescription>
            </CardHeader>
          </CollapsibleTrigger>
          <CollapsibleContent>
            <CardContent className="space-y-4">
              <div className="grid grid-cols-2 gap-4">
                <div className="flex items-center justify-between">
                  <div>
                    <Label className="text-sm font-medium">Detailed Logging</Label>
                    <p className="text-xs text-muted-foreground">Log request/response details</p>
                  </div>
                  <Switch
                    checked={settings.monitoring.detailed_logging}
                    onCheckedChange={(value) => updateSetting('monitoring', 'detailed_logging', value)}
                  />
                </div>
                <div className="flex items-center justify-between">
                  <div>
                    <Label className="text-sm font-medium">Metrics Collection</Label>
                    <p className="text-xs text-muted-foreground">Collect performance metrics</p>
                  </div>
                  <Switch
                    checked={settings.monitoring.metrics_collection}
                    onCheckedChange={(value) => updateSetting('monitoring', 'metrics_collection', value)}
                  />
                </div>
              </div>

              <div className="flex items-center justify-between">
                <div>
                  <Label className="text-sm font-medium">Alert on Failures</Label>
                  <p className="text-xs text-muted-foreground">Send notifications for provider failures</p>
                </div>
                <Switch
                  checked={settings.monitoring.alert_on_failures}
                  onCheckedChange={(value) => updateSetting('monitoring', 'alert_on_failures', value)}
                />
              </div>

              <div>
                <Label htmlFor="health-check-interval">Health Check Interval (seconds)</Label>
                <div className="mt-2">
                  <Slider
                    value={[settings.monitoring.health_check_interval]}
                    onValueChange={([value]) => updateSetting('monitoring', 'health_check_interval', value)}
                    max={300}
                    min={30}
                    step={30}
                    className="w-full"
                  />
                  <div className="flex justify-between text-xs text-muted-foreground mt-1">
                    <span>30s</span>
                    <span>{settings.monitoring.health_check_interval}s</span>
                    <span>5min</span>
                  </div>
                </div>
              </div>
            </CardContent>
          </CollapsibleContent>
        </Collapsible>
      </Card>

      {/* Experimental Features */}
      <Card>
        <Collapsible 
          open={openSections.has('experimental')} 
          onOpenChange={() => toggleSection('experimental')}
        >
          <CollapsibleTrigger asChild>
            <CardHeader className="cursor-pointer hover:bg-muted/50 transition-colors">
              <CardTitle className="flex items-center justify-between">
                <div className="flex items-center gap-2">
                  <Zap className="h-5 w-5" />
                  Experimental Features
                  <Badge variant="secondary" className="text-xs">Beta</Badge>
                </div>
                {openSections.has('experimental') ? (
                  <ChevronDown className="h-4 w-4" />
                ) : (
                  <ChevronRight className="h-4 w-4" />
                )}
              </CardTitle>
              <CardDescription>
                Early-access features that may change or be removed
              </CardDescription>
            </CardHeader>
          </CollapsibleTrigger>
          <CollapsibleContent>
            <CardContent className="space-y-4">
              <Alert>
                <AlertTriangle className="h-4 w-4" />
                <AlertDescription>
                  Experimental features are not guaranteed to be stable and may impact performance.
                </AlertDescription>
              </Alert>

              <div className="grid grid-cols-2 gap-4">
                <div className="flex items-center justify-between">
                  <div>
                    <Label className="text-sm font-medium">Streaming Optimization</Label>
                    <p className="text-xs text-muted-foreground">Enhanced streaming response handling</p>
                  </div>
                  <Switch
                    checked={settings.experimental.streaming_optimization}
                    onCheckedChange={(value) => updateSetting('experimental', 'streaming_optimization', value)}
                  />
                </div>
                <div className="flex items-center justify-between">
                  <div>
                    <Label className="text-sm font-medium">Async Processing</Label>
                    <p className="text-xs text-muted-foreground">Background request processing</p>
                  </div>
                  <Switch
                    checked={settings.experimental.async_processing}
                    onCheckedChange={(value) => updateSetting('experimental', 'async_processing', value)}
                  />
                </div>
              </div>

              <div className="grid grid-cols-2 gap-4">
                <div className="flex items-center justify-between">
                  <div>
                    <Label className="text-sm font-medium">Batch Requests</Label>
                    <p className="text-xs text-muted-foreground">Combine multiple requests</p>
                  </div>
                  <Switch
                    checked={settings.experimental.batch_requests}
                    onCheckedChange={(value) => updateSetting('experimental', 'batch_requests', value)}
                  />
                </div>
                <div className="flex items-center justify-between">
                  <div>
                    <Label className="text-sm font-medium">Smart Routing</Label>
                    <p className="text-xs text-muted-foreground">AI-driven model selection</p>
                  </div>
                  <Switch
                    checked={settings.experimental.smart_routing}
                    onCheckedChange={(value) => updateSetting('experimental', 'smart_routing', value)}
                  />
                </div>
              </div>
            </CardContent>
          </CollapsibleContent>
        </Collapsible>
      </Card>
    </div>
  )
}