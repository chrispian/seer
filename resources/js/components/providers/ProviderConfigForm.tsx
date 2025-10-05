import React, { useState, useEffect } from 'react'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Switch } from '@/components/ui/switch'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Badge } from '@/components/ui/badge'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { Separator } from '@/components/ui/separator'
import { LoadingSpinner } from '@/components/ui/loading-spinner'
import { 
  Settings, 
  Zap, 
  Shield, 
  DollarSign, 
  Activity,
  AlertCircle,
  CheckCircle,
  Save,
  TestTube,
  RotateCcw,
  Eye,
  EyeOff
} from 'lucide-react'
import { providersApi } from '@/lib/api/providers'
import type { Provider, UpdateProviderRequest } from '@/types/provider'

interface ProviderConfigFormProps {
  provider: Provider
  onProviderUpdate?: (provider: Provider) => void
  onClose?: () => void
}

interface ProviderCapabilityConfig {
  timeout?: number
  retries?: number
  concurrent_requests?: number
  rate_limit?: {
    requests_per_minute?: number
    tokens_per_minute?: number
  }
  model_preferences?: {
    default_model?: string
    preferred_models?: string[]
    excluded_models?: string[]
  }
  ui_preferences?: {
    theme?: 'default' | 'dark' | 'light'
    display_name?: string
    icon_color?: string
    priority?: number
  }
}

export function ProviderConfigForm({ 
  provider, 
  onProviderUpdate,
  onClose 
}: ProviderConfigFormProps) {
  const [loading, setLoading] = useState(false)
  const [testing, setTesting] = useState(false)
  const [saving, setSaving] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [success, setSuccess] = useState<string | null>(null)
  
  // Form state
  const [enabled, setEnabled] = useState(provider.enabled)
  const [config, setConfig] = useState<ProviderCapabilityConfig>(
    provider.ui_preferences || {}
  )
  
  // Advanced settings visibility
  const [showAdvanced, setShowAdvanced] = useState(false)

  // Handle configuration changes
  const updateConfig = (path: string, value: any) => {
    setConfig(prev => {
      const newConfig = { ...prev }
      const keys = path.split('.')
      let current = newConfig as any
      
      // Navigate to the parent of the target key
      for (let i = 0; i < keys.length - 1; i++) {
        if (!current[keys[i]]) {
          current[keys[i]] = {}
        }
        current = current[keys[i]]
      }
      
      // Set the value
      current[keys[keys.length - 1]] = value
      return newConfig
    })
  }

  // Test provider connectivity
  const handleTest = async () => {
    try {
      setTesting(true)
      setError(null)
      
      const result = await providersApi.testProvider(provider.id)
      
      if (result.success) {
        setSuccess('Provider connection test successful!')
      } else {
        setError(result.error_message || 'Provider test failed')
      }
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Test failed')
    } finally {
      setTesting(false)
    }
  }

  // Save configuration
  const handleSave = async () => {
    try {
      setSaving(true)
      setError(null)
      
      const updateData: UpdateProviderRequest = {
        enabled,
        ui_preferences: config
      }
      
      const updatedProvider = await providersApi.updateProvider(provider.id, updateData)
      
      setSuccess('Configuration saved successfully!')
      onProviderUpdate?.(updatedProvider)
      
      // Close after successful save
      setTimeout(() => {
        onClose?.()
      }, 1500)
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Save failed')
    } finally {
      setSaving(false)
    }
  }

  // Reset to defaults
  const handleReset = () => {
    setEnabled(provider.enabled)
    setConfig(provider.ui_preferences || {})
    setError(null)
    setSuccess(null)
  }

  // Clear alerts
  useEffect(() => {
    if (success) {
      const timer = setTimeout(() => setSuccess(null), 3000)
      return () => clearTimeout(timer)
    }
  }, [success])

  return (
    <div className="space-y-6 max-w-4xl">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-2xl font-bold">Configure {provider.name}</h2>
          <p className="text-muted-foreground">
            Customize provider settings and capabilities
          </p>
        </div>
        <div className="flex items-center gap-2">
          <Badge 
            variant={provider.status === 'healthy' ? 'default' : 'destructive'}
            className="capitalize"
          >
            {provider.status}
          </Badge>
          <Badge variant={enabled ? 'default' : 'secondary'}>
            {enabled ? 'Enabled' : 'Disabled'}
          </Badge>
        </div>
      </div>

      {/* Alerts */}
      {error && (
        <Alert variant="destructive">
          <AlertCircle className="h-4 w-4" />
          <AlertDescription>{error}</AlertDescription>
        </Alert>
      )}
      
      {success && (
        <Alert>
          <CheckCircle className="h-4 w-4" />
          <AlertDescription>{success}</AlertDescription>
        </Alert>
      )}

      <Tabs defaultValue="general" className="w-full">
        <TabsList>
          <TabsTrigger value="general">General</TabsTrigger>
          <TabsTrigger value="performance">Performance</TabsTrigger>
          <TabsTrigger value="models">Models</TabsTrigger>
          <TabsTrigger value="appearance">Appearance</TabsTrigger>
        </TabsList>

        {/* General Settings */}
        <TabsContent value="general" className="space-y-4">
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <Settings className="h-5 w-5" />
                General Settings
              </CardTitle>
              <CardDescription>
                Basic provider configuration and status
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="flex items-center justify-between">
                <div className="space-y-0.5">
                  <Label className="text-base">Enable Provider</Label>
                  <div className="text-sm text-muted-foreground">
                    Allow this provider to be used for AI requests
                  </div>
                </div>
                <Switch
                  checked={enabled}
                  onCheckedChange={setEnabled}
                />
              </div>

              <Separator />

              <div className="grid grid-cols-2 gap-4">
                <div>
                  <Label className="text-sm font-medium">Provider ID</Label>
                  <Input value={provider.id} disabled className="mt-1" />
                </div>
                <div>
                  <Label className="text-sm font-medium">Models Available</Label>
                  <Input value={provider.models.length} disabled className="mt-1" />
                </div>
              </div>

              <div>
                <Label className="text-sm font-medium">Capabilities</Label>
                <div className="flex flex-wrap gap-2 mt-2">
                  {provider.capabilities.map(capability => (
                    <Badge key={capability} variant="outline">
                      {capability}
                    </Badge>
                  ))}
                </div>
              </div>

              <div className="flex gap-2">
                <Button
                  onClick={handleTest}
                  disabled={testing || !enabled}
                  variant="outline"
                  className="flex items-center gap-2"
                >
                  {testing ? (
                    <LoadingSpinner className="h-4 w-4" />
                  ) : (
                    <TestTube className="h-4 w-4" />
                  )}
                  Test Connection
                </Button>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        {/* Performance Settings */}
        <TabsContent value="performance" className="space-y-4">
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <Zap className="h-5 w-5" />
                Performance Configuration
              </CardTitle>
              <CardDescription>
                Timeout, retry, and rate limiting settings
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <Label htmlFor="timeout">Request Timeout (seconds)</Label>
                  <Input
                    id="timeout"
                    type="number"
                    min="1"
                    max="300"
                    value={config.timeout || 30}
                    onChange={(e) => updateConfig('timeout', parseInt(e.target.value))}
                    className="mt-1"
                  />
                </div>
                <div>
                  <Label htmlFor="retries">Max Retries</Label>
                  <Input
                    id="retries"
                    type="number"
                    min="0"
                    max="10"
                    value={config.retries || 3}
                    onChange={(e) => updateConfig('retries', parseInt(e.target.value))}
                    className="mt-1"
                  />
                </div>
              </div>

              <div>
                <Label htmlFor="concurrent">Concurrent Requests</Label>
                <Input
                  id="concurrent"
                  type="number"
                  min="1"
                  max="50"
                  value={config.concurrent_requests || 5}
                  onChange={(e) => updateConfig('concurrent_requests', parseInt(e.target.value))}
                  className="mt-1"
                />
                <p className="text-xs text-muted-foreground mt-1">
                  Maximum number of simultaneous requests to this provider
                </p>
              </div>

              <div className="space-y-3">
                <Label className="text-base">Rate Limiting</Label>
                <div className="grid grid-cols-2 gap-4">
                  <div>
                    <Label htmlFor="rpm">Requests per minute</Label>
                    <Input
                      id="rpm"
                      type="number"
                      min="1"
                      value={config.rate_limit?.requests_per_minute || 60}
                      onChange={(e) => updateConfig('rate_limit.requests_per_minute', parseInt(e.target.value))}
                      className="mt-1"
                    />
                  </div>
                  <div>
                    <Label htmlFor="tpm">Tokens per minute</Label>
                    <Input
                      id="tpm"
                      type="number"
                      min="1000"
                      value={config.rate_limit?.tokens_per_minute || 50000}
                      onChange={(e) => updateConfig('rate_limit.tokens_per_minute', parseInt(e.target.value))}
                      className="mt-1"
                    />
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        {/* Model Settings */}
        <TabsContent value="models" className="space-y-4">
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <Activity className="h-5 w-5" />
                Model Preferences
              </CardTitle>
              <CardDescription>
                Configure default and preferred models for this provider
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div>
                <Label htmlFor="default-model">Default Model</Label>
                <Select
                  value={config.model_preferences?.default_model || ''}
                  onValueChange={(value) => updateConfig('model_preferences.default_model', value)}
                >
                  <SelectTrigger className="mt-1">
                    <SelectValue placeholder="Select default model" />
                  </SelectTrigger>
                  <SelectContent>
                    {provider.models.map(model => (
                      <SelectItem key={model.id} value={model.id}>
                        {model.name}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>

              <div>
                <Label className="text-base">Available Models</Label>
                <div className="space-y-2 mt-2 max-h-40 overflow-y-auto">
                  {provider.models.map(model => (
                    <div key={model.id} className="flex items-center justify-between p-2 border rounded">
                      <div>
                        <div className="font-medium">{model.name}</div>
                        <div className="text-sm text-muted-foreground">
                          {model.context_length?.toLocaleString()} tokens
                        </div>
                      </div>
                      <div className="flex gap-1">
                        {model.capabilities.map(cap => (
                          <Badge key={cap} variant="outline" className="text-xs">
                            {cap}
                          </Badge>
                        ))}
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        {/* Appearance Settings */}
        <TabsContent value="appearance" className="space-y-4">
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <Eye className="h-5 w-5" />
                UI Preferences
              </CardTitle>
              <CardDescription>
                Customize how this provider appears in the interface
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div>
                <Label htmlFor="display-name">Display Name</Label>
                <Input
                  id="display-name"
                  value={config.ui_preferences?.display_name || provider.name}
                  onChange={(e) => updateConfig('ui_preferences.display_name', e.target.value)}
                  className="mt-1"
                  placeholder={provider.name}
                />
              </div>

              <div className="grid grid-cols-2 gap-4">
                <div>
                  <Label htmlFor="theme">Theme</Label>
                  <Select
                    value={config.ui_preferences?.theme || 'default'}
                    onValueChange={(value) => updateConfig('ui_preferences.theme', value)}
                  >
                    <SelectTrigger className="mt-1">
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="default">Default</SelectItem>
                      <SelectItem value="light">Light</SelectItem>
                      <SelectItem value="dark">Dark</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <div>
                  <Label htmlFor="priority">Display Priority</Label>
                  <Input
                    id="priority"
                    type="number"
                    min="1"
                    max="100"
                    value={config.ui_preferences?.priority || 50}
                    onChange={(e) => updateConfig('ui_preferences.priority', parseInt(e.target.value))}
                    className="mt-1"
                  />
                </div>
              </div>

              <div>
                <Label htmlFor="icon-color">Icon Color</Label>
                <Input
                  id="icon-color"
                  type="color"
                  value={config.ui_preferences?.icon_color || '#3b82f6'}
                  onChange={(e) => updateConfig('ui_preferences.icon_color', e.target.value)}
                  className="mt-1 h-10 w-20"
                />
              </div>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>

      {/* Action Buttons */}
      <div className="flex items-center justify-between pt-4 border-t">
        <Button
          onClick={handleReset}
          variant="outline"
          className="flex items-center gap-2"
        >
          <RotateCcw className="h-4 w-4" />
          Reset
        </Button>
        
        <div className="flex gap-2">
          <Button onClick={onClose} variant="outline">
            Cancel
          </Button>
          <Button
            onClick={handleSave}
            disabled={saving}
            className="flex items-center gap-2"
          >
            {saving ? (
              <LoadingSpinner className="h-4 w-4" />
            ) : (
              <Save className="h-4 w-4" />
            )}
            Save Configuration
          </Button>
        </div>
      </div>
    </div>
  )
}