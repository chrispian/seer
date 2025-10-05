import React, { useState } from 'react'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Textarea } from '@/components/ui/textarea'
import { Switch } from '@/components/ui/switch'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { Badge } from '@/components/ui/badge'
import { 
  Eye, 
  EyeOff, 
  TestTube, 
  Calendar,
  Key,
  AlertCircle,
  CheckCircle,
  Info
} from 'lucide-react'
import type { CreateCredentialRequest, CredentialType } from '@/types/provider'

interface AddCredentialDialogProps {
  open: boolean
  onOpenChange: (open: boolean) => void
  onSubmit: (data: CreateCredentialRequest) => Promise<void>
  onTest?: (data: CreateCredentialRequest) => Promise<{ success: boolean; message: string }>
  providerId: string
  providerName: string
  isLoading?: boolean
}

interface CredentialField {
  key: string
  label: string
  type: 'text' | 'password' | 'url'
  required: boolean
  placeholder?: string
  description?: string
}

const CREDENTIAL_TYPES: Record<CredentialType, { 
  label: string
  description: string
  fields: CredentialField[]
}> = {
  api_key: {
    label: 'API Key',
    description: 'Simple API key authentication',
    fields: [
      {
        key: 'api_key',
        label: 'API Key',
        type: 'password',
        required: true,
        placeholder: 'sk-...',
        description: 'Your API key from the provider dashboard'
      }
    ]
  },
  oauth: {
    label: 'OAuth 2.0',
    description: 'OAuth 2.0 token-based authentication',
    fields: [
      {
        key: 'access_token',
        label: 'Access Token',
        type: 'password',
        required: true,
        placeholder: 'ya29...',
        description: 'The OAuth access token'
      },
      {
        key: 'refresh_token',
        label: 'Refresh Token',
        type: 'password',
        required: false,
        placeholder: '1//...',
        description: 'Token to refresh the access token'
      },
      {
        key: 'client_id',
        label: 'Client ID',
        type: 'text',
        required: false,
        description: 'OAuth application client ID'
      }
    ]
  },
  basic_auth: {
    label: 'Basic Authentication',
    description: 'Username and password authentication',
    fields: [
      {
        key: 'username',
        label: 'Username',
        type: 'text',
        required: true,
        description: 'Your username or email'
      },
      {
        key: 'password',
        label: 'Password',
        type: 'password',
        required: true,
        description: 'Your password'
      }
    ]
  },
  custom: {
    label: 'Custom',
    description: 'Custom authentication method',
    fields: [
      {
        key: 'custom_field',
        label: 'Authentication Data',
        type: 'password',
        required: true,
        description: 'Provider-specific authentication data'
      }
    ]
  }
}

export function AddCredentialDialog({
  open,
  onOpenChange,
  onSubmit,
  onTest,
  providerId,
  providerName,
  isLoading = false
}: AddCredentialDialogProps) {
  const [credentialType, setCredentialType] = useState<CredentialType>('api_key')
  const [credentials, setCredentials] = useState<Record<string, string>>({})
  const [metadata, setMetadata] = useState({
    name: '',
    description: ''
  })
  const [expiresAt, setExpiresAt] = useState('')
  const [showPasswords, setShowPasswords] = useState<Record<string, boolean>>({})
  const [errors, setErrors] = useState<Record<string, string>>({})
  const [testResult, setTestResult] = useState<{ success: boolean; message: string } | null>(null)
  const [isTestLoading, setIsTestLoading] = useState(false)

  const currentTypeConfig = CREDENTIAL_TYPES[credentialType]

  const handleCredentialChange = (key: string, value: string) => {
    setCredentials(prev => ({ ...prev, [key]: value }))
    
    // Clear error when user starts typing
    if (errors[key]) {
      setErrors(prev => ({ ...prev, [key]: '' }))
    }
  }

  const togglePasswordVisibility = (fieldKey: string) => {
    setShowPasswords(prev => ({ ...prev, [fieldKey]: !prev[fieldKey] }))
  }

  const validateForm = () => {
    const newErrors: Record<string, string> = {}

    // Validate required credential fields
    currentTypeConfig.fields.forEach(field => {
      if (field.required && !credentials[field.key]?.trim()) {
        newErrors[field.key] = `${field.label} is required`
      }
    })

    // Validate expiration date if provided
    if (expiresAt) {
      const expiryDate = new Date(expiresAt)
      const now = new Date()
      if (expiryDate <= now) {
        newErrors.expires_at = 'Expiration date must be in the future'
      }
    }

    setErrors(newErrors)
    return Object.keys(newErrors).length === 0
  }

  const handleTest = async () => {
    if (!validateForm() || !onTest) return

    setIsTestLoading(true)
    setTestResult(null)

    try {
      const testData: CreateCredentialRequest = {
        credential_type: credentialType,
        credentials,
        metadata: metadata.name ? { name: metadata.name, description: metadata.description } : undefined,
        expires_at: expiresAt || undefined
      }

      const result = await onTest(testData)
      setTestResult(result)
    } catch (error) {
      setTestResult({
        success: false,
        message: error instanceof Error ? error.message : 'Test failed'
      })
    } finally {
      setIsTestLoading(false)
    }
  }

  const handleSubmit = async () => {
    if (!validateForm()) return

    const submitData: CreateCredentialRequest = {
      credential_type: credentialType,
      credentials,
      metadata: metadata.name ? { name: metadata.name, description: metadata.description } : undefined,
      expires_at: expiresAt || undefined
    }

    try {
      await onSubmit(submitData)
      
      // Reset form
      setCredentials({})
      setMetadata({ name: '', description: '' })
      setExpiresAt('')
      setErrors({})
      setTestResult(null)
      setShowPasswords({})
      
      onOpenChange(false)
    } catch (error) {
      // Error handling is managed by parent component
    }
  }

  const resetForm = () => {
    setCredentials({})
    setMetadata({ name: '', description: '' })
    setExpiresAt('')
    setErrors({})
    setTestResult(null)
    setShowPasswords({})
  }

  return (
    <Dialog open={open} onOpenChange={(open) => {
      if (!open) resetForm()
      onOpenChange(open)
    }}>
      <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2">
            <Key className="h-5 w-5" />
            Add Credential for {providerName}
          </DialogTitle>
          <DialogDescription>
            Configure authentication credentials to enable {providerName} functionality.
          </DialogDescription>
        </DialogHeader>

        <div className="space-y-6">
          {/* Credential Type Selection */}
          <Card>
            <CardHeader>
              <CardTitle className="text-base">Credential Type</CardTitle>
              <CardDescription>
                Select the authentication method supported by {providerName}
              </CardDescription>
            </CardHeader>
            <CardContent>
              <Select 
                value={credentialType} 
                onValueChange={(value: CredentialType) => {
                  setCredentialType(value)
                  setCredentials({})
                  setErrors({})
                  setTestResult(null)
                }}
              >
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {Object.entries(CREDENTIAL_TYPES).map(([type, config]) => (
                    <SelectItem key={type} value={type}>
                      <div>
                        <div className="font-medium">{config.label}</div>
                        <div className="text-xs text-muted-foreground">{config.description}</div>
                      </div>
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </CardContent>
          </Card>

          {/* Credential Fields */}
          <Card>
            <CardHeader>
              <CardTitle className="text-base">Authentication Details</CardTitle>
              <CardDescription>
                Enter your {currentTypeConfig.label.toLowerCase()} credentials
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              {currentTypeConfig.fields.map((field) => (
                <div key={field.key} className="space-y-2">
                  <Label htmlFor={field.key} className="flex items-center gap-2">
                    {field.label}
                    {field.required && <Badge variant="destructive" className="text-xs">Required</Badge>}
                  </Label>
                  
                  <div className="relative">
                    <Input
                      id={field.key}
                      type={field.type === 'password' && !showPasswords[field.key] ? 'password' : 'text'}
                      value={credentials[field.key] || ''}
                      onChange={(e) => handleCredentialChange(field.key, e.target.value)}
                      placeholder={field.placeholder}
                      className={errors[field.key] ? 'border-destructive' : ''}
                    />
                    
                    {field.type === 'password' && (
                      <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        className="absolute right-2 top-1/2 -translate-y-1/2 h-6 w-6 p-0"
                        onClick={() => togglePasswordVisibility(field.key)}
                      >
                        {showPasswords[field.key] ? (
                          <EyeOff className="h-3 w-3" />
                        ) : (
                          <Eye className="h-3 w-3" />
                        )}
                      </Button>
                    )}
                  </div>
                  
                  {field.description && (
                    <p className="text-xs text-muted-foreground">{field.description}</p>
                  )}
                  
                  {errors[field.key] && (
                    <p className="text-xs text-destructive">{errors[field.key]}</p>
                  )}
                </div>
              ))}
            </CardContent>
          </Card>

          {/* Metadata */}
          <Card>
            <CardHeader>
              <CardTitle className="text-base">Additional Information</CardTitle>
              <CardDescription>
                Optional metadata for organizing your credentials
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="space-y-2">
                <Label htmlFor="name">Display Name</Label>
                <Input
                  id="name"
                  value={metadata.name}
                  onChange={(e) => setMetadata(prev => ({ ...prev, name: e.target.value }))}
                  placeholder="e.g., Production API Key"
                />
              </div>
              
              <div className="space-y-2">
                <Label htmlFor="description">Description</Label>
                <Textarea
                  id="description"
                  value={metadata.description}
                  onChange={(e) => setMetadata(prev => ({ ...prev, description: e.target.value }))}
                  placeholder="Optional description of this credential's purpose"
                  rows={2}
                />
              </div>
              
              <div className="space-y-2">
                <Label htmlFor="expires_at" className="flex items-center gap-2">
                  <Calendar className="h-4 w-4" />
                  Expiration Date (Optional)
                </Label>
                <Input
                  id="expires_at"
                  type="date"
                  value={expiresAt}
                  onChange={(e) => setExpiresAt(e.target.value)}
                  min={new Date().toISOString().split('T')[0]}
                  className={errors.expires_at ? 'border-destructive' : ''}
                />
                {errors.expires_at && (
                  <p className="text-xs text-destructive">{errors.expires_at}</p>
                )}
              </div>
            </CardContent>
          </Card>

          {/* Test Result */}
          {testResult && (
            <Alert variant={testResult.success ? 'default' : 'destructive'}>
              {testResult.success ? (
                <CheckCircle className="h-4 w-4" />
              ) : (
                <AlertCircle className="h-4 w-4" />
              )}
              <AlertDescription>
                <strong>{testResult.success ? 'Test Successful:' : 'Test Failed:'}</strong> {testResult.message}
              </AlertDescription>
            </Alert>
          )}
        </div>

        <DialogFooter className="gap-2">
          <Button variant="outline" onClick={() => onOpenChange(false)}>
            Cancel
          </Button>
          
          {onTest && (
            <Button 
              variant="outline" 
              onClick={handleTest}
              disabled={isTestLoading || isLoading}
            >
              <TestTube className="mr-2 h-4 w-4" />
              {isTestLoading ? 'Testing...' : 'Test'}
            </Button>
          )}
          
          <Button onClick={handleSubmit} disabled={isLoading || isTestLoading}>
            {isLoading ? 'Adding...' : 'Add Credential'}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}