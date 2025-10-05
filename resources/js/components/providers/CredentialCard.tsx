import React, { useState } from 'react'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Switch } from '@/components/ui/switch'
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu'
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip'
import { 
  MoreVertical, 
  Key, 
  TestTube, 
  Edit, 
  Trash2, 
  Calendar,
  Clock,
  CheckCircle,
  AlertTriangle,
  Eye,
  EyeOff
} from 'lucide-react'
import type { Credential } from '@/types/provider'

interface CredentialCardProps {
  credential: Credential
  onEdit: (credential: Credential) => void
  onDelete: (credential: Credential) => void
  onTest: (credential: Credential) => void
  onToggleActive: (credential: Credential) => void
  isLoading?: boolean
  isTestLoading?: boolean
}

export function CredentialCard({
  credential,
  onEdit,
  onDelete,
  onTest,
  onToggleActive,
  isLoading = false,
  isTestLoading = false
}: CredentialCardProps) {
  const [showMasked, setShowMasked] = useState(true)

  const formatCredentialType = (type: string) => {
    return type.split('_').map(word => 
      word.charAt(0).toUpperCase() + word.slice(1)
    ).join(' ')
  }

  const formatDate = (timestamp: string) => {
    try {
      return new Date(timestamp).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      })
    } catch {
      return 'Invalid date'
    }
  }

  const getExpirationStatus = () => {
    if (!credential.expires_at) return null
    
    const expiry = new Date(credential.expires_at)
    const now = new Date()
    const daysUntilExpiry = Math.ceil((expiry.getTime() - now.getTime()) / (1000 * 60 * 60 * 24))
    
    if (daysUntilExpiry < 0) {
      return { status: 'expired', text: 'Expired', variant: 'destructive' as const }
    } else if (daysUntilExpiry <= 7) {
      return { status: 'expiring', text: `Expires in ${daysUntilExpiry}d`, variant: 'destructive' as const }
    } else if (daysUntilExpiry <= 30) {
      return { status: 'warning', text: `Expires in ${daysUntilExpiry}d`, variant: 'secondary' as const }
    }
    
    return { status: 'valid', text: `Expires ${formatDate(credential.expires_at)}`, variant: 'secondary' as const }
  }

  const expirationStatus = getExpirationStatus()

  const getMaskedValue = (value: string) => {
    if (!value) return 'Not set'
    if (value.length <= 8) return '•'.repeat(value.length)
    return value.substring(0, 4) + '•'.repeat(Math.max(4, value.length - 8)) + value.substring(value.length - 4)
  }

  return (
    <Card className={`transition-all duration-200 hover:shadow-md ${
      credential.is_active ? 'border-primary/20' : 'border-muted-foreground/20 opacity-75'
    }`}>
      <CardHeader className="pb-3">
        <div className="flex items-start justify-between">
          <div className="flex items-center gap-3">
            <div className="p-2 rounded-md bg-muted">
              <Key className="h-4 w-4" />
            </div>
            <div>
              <CardTitle className="text-base">
                {formatCredentialType(credential.credential_type)}
              </CardTitle>
              <div className="flex items-center gap-2 text-sm text-muted-foreground">
                <span>Created {formatDate(credential.created_at)}</span>
                {credential.metadata?.name && (
                  <>
                    <span>•</span>
                    <span>{credential.metadata.name}</span>
                  </>
                )}
              </div>
            </div>
          </div>
          
          <div className="flex items-center gap-2">
            <Badge 
              variant={credential.is_active ? 'default' : 'secondary'}
              className={credential.is_active ? 'bg-green-100 text-green-800' : ''}
            >
              {credential.is_active ? 'Active' : 'Inactive'}
            </Badge>
            
            <DropdownMenu>
              <DropdownMenuTrigger asChild>
                <Button variant="ghost" size="sm" className="h-8 w-8 p-0">
                  <MoreVertical className="h-4 w-4" />
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="end">
                <DropdownMenuItem onClick={() => onEdit(credential)}>
                  <Edit className="mr-2 h-4 w-4" />
                  Edit
                </DropdownMenuItem>
                <DropdownMenuItem onClick={() => onTest(credential)} disabled={isTestLoading}>
                  <TestTube className="mr-2 h-4 w-4" />
                  Test Credential
                </DropdownMenuItem>
                <DropdownMenuSeparator />
                <DropdownMenuItem 
                  onClick={() => onDelete(credential)}
                  className="text-destructive focus:text-destructive"
                >
                  <Trash2 className="mr-2 h-4 w-4" />
                  Delete
                </DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>
          </div>
        </div>
      </CardHeader>
      
      <CardContent className="pt-0">
        <div className="space-y-4">
          {/* Status and Toggle */}
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-2">
              {credential.is_active ? (
                <CheckCircle className="h-4 w-4 text-green-600" />
              ) : (
                <Clock className="h-4 w-4 text-muted-foreground" />
              )}
              <span className="text-sm font-medium">
                {credential.is_active ? 'Active' : 'Inactive'}
              </span>
            </div>
            
            <TooltipProvider>
              <Tooltip>
                <TooltipTrigger asChild>
                  <div>
                    <Switch
                      checked={credential.is_active}
                      onCheckedChange={() => onToggleActive(credential)}
                      disabled={isLoading}
                    />
                  </div>
                </TooltipTrigger>
                <TooltipContent>
                  {credential.is_active ? 'Deactivate credential' : 'Activate credential'}
                </TooltipContent>
              </Tooltip>
            </TooltipProvider>
          </div>

          {/* Credential preview */}
          <div className="space-y-2">
            <div className="flex items-center justify-between">
              <span className="text-xs font-medium text-muted-foreground">Credential Value</span>
              <Button
                variant="ghost"
                size="sm"
                onClick={() => setShowMasked(!showMasked)}
                className="h-6 px-2"
              >
                {showMasked ? (
                  <Eye className="h-3 w-3" />
                ) : (
                  <EyeOff className="h-3 w-3" />
                )}
              </Button>
            </div>
            <div className="font-mono text-sm bg-muted p-2 rounded border">
              {showMasked ? getMaskedValue('sample_credential_value') : 'sk-••••••••••••••••••••••••••••••••••••••••••••••••••••'}
            </div>
          </div>

          {/* Expiration */}
          {expirationStatus && (
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-2">
                <Calendar className="h-4 w-4 text-muted-foreground" />
                <span className="text-sm">Expiration</span>
              </div>
              <Badge variant={expirationStatus.variant} className="text-xs">
                {expirationStatus.status === 'expired' && (
                  <AlertTriangle className="mr-1 h-3 w-3" />
                )}
                {expirationStatus.text}
              </Badge>
            </div>
          )}

          {/* Metadata */}
          {credential.metadata && Object.keys(credential.metadata).length > 0 && (
            <div className="space-y-2">
              <span className="text-xs font-medium text-muted-foreground">Metadata</span>
              <div className="space-y-1">
                {Object.entries(credential.metadata).map(([key, value]) => (
                  <div key={key} className="flex justify-between text-sm">
                    <span className="text-muted-foreground capitalize">
                      {key.replace(/_/g, ' ')}:
                    </span>
                    <span className="font-medium">{String(value)}</span>
                  </div>
                ))}
              </div>
            </div>
          )}

          {/* Actions */}
          <div className="flex gap-2 pt-2">
            <Button 
              size="sm" 
              variant="outline" 
              onClick={() => onTest(credential)}
              disabled={isTestLoading || !credential.is_active}
              className="flex-1"
            >
              <TestTube className="mr-2 h-3 w-3" />
              {isTestLoading ? 'Testing...' : 'Test'}
            </Button>
            
            <Button 
              size="sm" 
              variant="outline" 
              onClick={() => onEdit(credential)}
              className="flex-1"
            >
              <Edit className="mr-2 h-3 w-3" />
              Edit
            </Button>
          </div>
        </div>
      </CardContent>
    </Card>
  )
}