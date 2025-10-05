import React, { useState, useEffect } from 'react'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Switch } from '@/components/ui/switch'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { Badge } from '@/components/ui/badge'
import { 
  Calendar,
  Edit,
  AlertCircle,
  CheckCircle,
  Info
} from 'lucide-react'
import type { Credential, UpdateCredentialRequest } from '@/types/provider'

interface EditCredentialDialogProps {
  open: boolean
  onOpenChange: (open: boolean) => void
  credential: Credential | null
  onSubmit: (credentialId: number, data: UpdateCredentialRequest) => Promise<void>
  onTest?: (credential: Credential) => Promise<{ success: boolean; message: string }>
  isLoading?: boolean
}

export function EditCredentialDialog({
  open,
  onOpenChange,
  credential,
  onSubmit,
  onTest,
  isLoading = false
}: EditCredentialDialogProps) {
  const [metadata, setMetadata] = useState({
    name: '',
    description: ''
  })
  const [expiresAt, setExpiresAt] = useState('')
  const [isActive, setIsActive] = useState(true)
  const [errors, setErrors] = useState<Record<string, string>>({})
  const [testResult, setTestResult] = useState<{ success: boolean; message: string } | null>(null)
  const [isTestLoading, setIsTestLoading] = useState(false)

  // Update form when credential changes
  useEffect(() => {
    if (credential) {
      setMetadata({
        name: credential.metadata?.name || '',
        description: credential.metadata?.description || ''
      })
      setExpiresAt(credential.expires_at ? credential.expires_at.split('T')[0] : '')
      setIsActive(credential.is_active)
      setErrors({})
      setTestResult(null)
    }
  }, [credential])

  const formatCredentialType = (type: string) => {
    return type.split('_').map(word => 
      word.charAt(0).toUpperCase() + word.slice(1)
    ).join(' ')
  }

  const formatDate = (timestamp: string) => {
    try {
      return new Date(timestamp).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      })
    } catch {
      return 'Invalid date'
    }
  }

  const getExpirationStatus = () => {
    if (!credential?.expires_at) return null
    
    const expiry = new Date(credential.expires_at)
    const now = new Date()
    const daysUntilExpiry = Math.ceil((expiry.getTime() - now.getTime()) / (1000 * 60 * 60 * 24))
    
    if (daysUntilExpiry < 0) {
      return { status: 'expired', text: 'Expired', variant: 'destructive' as const }
    } else if (daysUntilExpiry <= 7) {
      return { status: 'expiring', text: `Expires in ${daysUntilExpiry} day${daysUntilExpiry !== 1 ? 's' : ''}`, variant: 'destructive' as const }
    } else if (daysUntilExpiry <= 30) {
      return { status: 'warning', text: `Expires in ${daysUntilExpiry} days`, variant: 'secondary' as const }
    }
    
    return { status: 'valid', text: `Expires ${formatDate(credential.expires_at)}`, variant: 'secondary' as const }
  }

  const validateForm = () => {
    const newErrors: Record<string, string> = {}

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
    if (!credential || !onTest) return

    setIsTestLoading(true)
    setTestResult(null)

    try {
      const result = await onTest(credential)
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
    if (!credential || !validateForm()) return

    const submitData: UpdateCredentialRequest = {
      metadata: metadata.name || metadata.description ? { 
        name: metadata.name, 
        description: metadata.description 
      } : undefined,
      expires_at: expiresAt || undefined,
      is_active: isActive
    }

    try {
      await onSubmit(credential.id, submitData)
      onOpenChange(false)
    } catch (error) {
      // Error handling is managed by parent component
    }
  }

  const resetForm = () => {
    setErrors({})
    setTestResult(null)
  }

  if (!credential) return null

  const expirationStatus = getExpirationStatus()

  return (
    <Dialog open={open} onOpenChange={(open) => {
      if (!open) resetForm()
      onOpenChange(open)
    }}>
      <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2">
            <Edit className="h-5 w-5" />
            Edit Credential
          </DialogTitle>
          <DialogDescription>
            Update the metadata and settings for this credential.
          </DialogDescription>
        </DialogHeader>

        <div className="space-y-6">
          {/* Credential Overview */}
          <Card>
            <CardHeader>
              <CardTitle className="text-base">Credential Information</CardTitle>
              <CardDescription>
                Basic information about this credential
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="grid grid-cols-2 gap-4 text-sm">
                <div>
                  <span className="text-muted-foreground">Type:</span>
                  <div className="font-medium">{formatCredentialType(credential.credential_type)}</div>
                </div>
                <div>
                  <span className="text-muted-foreground">Created:</span>
                  <div className="font-medium">{formatDate(credential.created_at)}</div>
                </div>
                <div>
                  <span className="text-muted-foreground">Status:</span>
                  <div className="flex items-center gap-2">
                    <Badge variant={credential.is_active ? 'default' : 'secondary'}>
                      {credential.is_active ? 'Active' : 'Inactive'}
                    </Badge>
                  </div>
                </div>
                {expirationStatus && (
                  <div>
                    <span className="text-muted-foreground">Expiration:</span>
                    <div className="flex items-center gap-2">
                      <Badge variant={expirationStatus.variant}>
                        {expirationStatus.text}
                      </Badge>
                    </div>
                  </div>
                )}
              </div>
            </CardContent>
          </Card>

          {/* Status and Activation */}
          <Card>
            <CardHeader>
              <CardTitle className="text-base">Status Settings</CardTitle>
              <CardDescription>
                Control whether this credential is active and available for use
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div className="flex items-center justify-between">
                <div>
                  <Label htmlFor="is_active" className="text-sm font-medium">
                    Active Status
                  </Label>
                  <p className="text-xs text-muted-foreground">
                    Only active credentials can be used for authentication
                  </p>
                </div>
                <Switch
                  id="is_active"
                  checked={isActive}
                  onCheckedChange={setIsActive}
                />
              </div>
            </CardContent>
          </Card>

          {/* Metadata */}
          <Card>
            <CardHeader>
              <CardTitle className="text-base">Display Information</CardTitle>
              <CardDescription>
                Update the display name and description for easier identification
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
            </CardContent>
          </Card>

          {/* Expiration */}
          <Card>
            <CardHeader>
              <CardTitle className="text-base">Expiration Settings</CardTitle>
              <CardDescription>
                Set or update the expiration date for this credential
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="space-y-2">
                <Label htmlFor="expires_at" className="flex items-center gap-2">
                  <Calendar className="h-4 w-4" />
                  Expiration Date
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
                <p className="text-xs text-muted-foreground">
                  Leave empty if the credential doesn't expire
                </p>
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

          {/* Warning for inactive credentials */}
          {!isActive && (
            <Alert>
              <Info className="h-4 w-4" />
              <AlertDescription>
                This credential will be deactivated and cannot be used for authentication until re-enabled.
              </AlertDescription>
            </Alert>
          )}
        </div>

        <DialogFooter className="gap-2">
          <Button variant="outline" onClick={() => onOpenChange(false)}>
            Cancel
          </Button>
          
          {onTest && credential.is_active && (
            <Button 
              variant="outline" 
              onClick={handleTest}
              disabled={isTestLoading || isLoading}
            >
              {isTestLoading ? 'Testing...' : 'Test Connection'}
            </Button>
          )}
          
          <Button onClick={handleSubmit} disabled={isLoading || isTestLoading}>
            {isLoading ? 'Updating...' : 'Update Credential'}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}