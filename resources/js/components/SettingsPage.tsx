import React, { useState } from 'react'

declare global {
  interface Window {
    settingsData?: {
      user: any
      profile_settings: any
      routes: Record<string, string>
    }
  }
}

import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Switch } from '@/components/ui/switch'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { Separator } from '@/components/ui/separator'
import { Badge } from '@/components/ui/badge'
import { AvatarUpload } from '@/components/AvatarUpload'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { ArrowLeft, User, Settings, Brain, Palette, Download, Upload, RotateCcw, CheckCircle, AlertCircle, Bot, KeySquare } from 'lucide-react'
import { ImportDialog } from '@/islands/Settings/components/ImportDialog'
import { ResetDialog } from '@/islands/Settings/components/ResetDialog'
import { ProvidersManagement } from '@/components/providers/ProvidersManagement'
import { providersApi } from '@/lib/api/providers'

interface SettingsPageProps {
  user: any
  profileSettings: any
  routes: Record<string, string>
}

export function SettingsPage({ user, profileSettings, routes }: SettingsPageProps) {
  console.log('SettingsPage rendering with:', { user, profileSettings, routes })
  
  const [activeTab, setActiveTab] = useState('profile')
  const [loading, setLoading] = useState(false)
  const [message, setMessage] = useState<{ type: 'success' | 'error', text: string } | null>(null)
  const [providers, setProviders] = useState<any[]>([])
  const [availableModels, setAvailableModels] = useState<any[]>([])
  
  const [profileData, setProfileData] = useState({
    name: user?.name || '',
    email: user?.email || '',
    display_name: user?.display_name || '',
  })
  
  const [avatarData, setAvatarData] = useState({
    use_gravatar: user?.use_gravatar ?? true,
  })
  
  const [preferencesData, setPreferencesData] = useState({
    theme: profileSettings?.theme || 'system',
    language: profileSettings?.language || 'en',
    timezone: profileSettings?.timezone || 'UTC',
    notifications: {
      email: profileSettings?.notifications?.email ?? true,
      desktop: profileSettings?.notifications?.desktop ?? true,
      sound: profileSettings?.notifications?.sound ?? false,
    },
    layout: {
      sidebar_collapsed: profileSettings?.layout?.sidebar_collapsed ?? false,
      right_rail_width: profileSettings?.layout?.right_rail_width || 320,
      compact_mode: profileSettings?.layout?.compact_mode ?? false,
    }
  })
  
  const [aiData, setAIData] = useState({
    default_provider: profileSettings?.ai?.default_provider || '',
    default_model: profileSettings?.ai?.default_model || '',
    temperature: profileSettings?.ai?.temperature || 0.7,
    max_tokens: profileSettings?.ai?.max_tokens || 2000,
    stream_responses: profileSettings?.ai?.stream_responses ?? true,
    auto_title: profileSettings?.ai?.auto_title ?? true,
    context_length: profileSettings?.ai?.context_length || 4000,
  })

  const readwiseIntegration = profileSettings?.integrations?.readwise ?? {}
  const obsidianIntegration = profileSettings?.integrations?.obsidian ?? {}
  const hardcoverIntegration = profileSettings?.integrations?.hardcover ?? {}
  const [integrationsData, setIntegrationsData] = useState({
    readwise: {
      tokenPresent: readwiseIntegration.token_present ?? false,
      syncEnabled: readwiseIntegration.sync_enabled ?? false,
      readerSyncEnabled: readwiseIntegration.reader_sync_enabled ?? false,
      lastSyncedAt: readwiseIntegration.last_synced_at ?? null,
    },
    obsidian: {
      vaultPath: obsidianIntegration.vault_path ?? '',
      syncEnabled: obsidianIntegration.sync_enabled ?? false,
      enrichEnabled: obsidianIntegration.enrich_enabled ?? false,
      lastSyncedAt: obsidianIntegration.last_synced_at ?? null,
      fileCount: obsidianIntegration.file_count ?? 0,
    },
    hardcover: {
      apiKeyPresent: hardcoverIntegration.api_key_present ?? false,
      syncEnabled: hardcoverIntegration.sync_enabled ?? false,
      lastSyncedAt: hardcoverIntegration.last_synced_at ?? null,
    },
  })

  const [readwiseTokenInput, setReadwiseTokenInput] = useState('')
  const [hardcoverApiKeyInput, setHardcoverApiKeyInput] = useState('')
  const [obsidianVaultPathInput, setObsidianVaultPathInput] = useState(obsidianIntegration.vault_path ?? '')
  const [testingVaultPath, setTestingVaultPath] = useState(false)
  const [vaultPathTestResult, setVaultPathTestResult] = useState<{ valid: boolean; message: string; fileCount?: number } | null>(null)

  const [selectedFile, setSelectedFile] = useState<File | null>(null)
  const [showImportDialog, setShowImportDialog] = useState(false)
  const [showResetDialog, setShowResetDialog] = useState(false)

  // Load providers and models when component mounts
  React.useEffect(() => {
    const loadProviders = async () => {
      try {
        const providersData = await providersApi.getProviders()
        setProviders(providersData)
        
        // Extract all models from all providers
        const allModels = providersData.flatMap(provider => 
          provider.models.map(model => ({
            id: `${provider.id}/${model.id}`,
            name: `${provider.name} - ${model.name}`,
            provider: provider.id,
            providerName: provider.name,
            modelId: model.id,
            modelName: model.name,
            capabilities: model.capabilities,
            enabled: model.enabled && provider.enabled
          }))
        ).filter(model => model.enabled)
        
        setAvailableModels(allModels)
      } catch (error) {
        console.error('Failed to load providers:', error)
      }
    }
    
    loadProviders()
  }, [])

  const showMessage = (type: 'success' | 'error', text: string) => {
    setMessage({ type, text })
    setTimeout(() => setMessage(null), 5000)
  }

  const handleSubmit = async (endpoint: string, data: any, useFormData = false) => {
    setLoading(true)
    
    try {
      let body: any = data
      let headers: any = {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      }

      if (useFormData) {
        const formData = new FormData()
        Object.entries(data).forEach(([key, value]) => {
          if (value !== null && value !== undefined) {
            formData.append(key, String(value))
          }
        })
        if (selectedFile) {
          formData.append('avatar', selectedFile)
        }
        body = formData
      } else {
        headers['Content-Type'] = 'application/json'
        body = JSON.stringify(data)
      }

      const response = await fetch(endpoint, {
        method: 'POST',
        headers,
        body
      })

      const result = await response.json()
      
      if (result.success) {
        showMessage('success', result.message)
        
        // Update avatar URL if returned
        if (result.avatar_url) {
          // Force reload avatar images
          const avatarImages = document.querySelectorAll('img[src*="avatar"]')
          avatarImages.forEach(img => {
            const src = (img as HTMLImageElement).src
            ;(img as HTMLImageElement).src = src + '?t=' + Date.now()
          })
        }
      } else {
        showMessage('error', result.error || 'Update failed')
      }
    } catch (error) {
      console.error('Settings update error:', error)
      showMessage('error', 'An unexpected error occurred')
    } finally {
      setLoading(false)
    }
  }

  const handleProfileSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    handleSubmit(routes.updateProfile, profileData)
  }

  const handleAvatarSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    handleSubmit(routes.updateAvatar, avatarData, true)
  }

  const handlePreferencesSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    handleSubmit(routes.updatePreferences, preferencesData)
  }

  const handleAISubmit = (e: React.FormEvent) => {
    e.preventDefault()
    handleSubmit(routes.updateAI, aiData)
  }

  const submitIntegrations = async (tokenOverride?: string | null, syncOverride?: boolean) => {
    setLoading(true)

    try {
      const payload: Record<string, any> = {
        readwise_sync_enabled: syncOverride ?? integrationsData.readwise.syncEnabled,
        readwise_reader_sync_enabled: integrationsData.readwise.readerSyncEnabled,
        obsidian_vault_path: obsidianVaultPathInput,
        obsidian_sync_enabled: integrationsData.obsidian.syncEnabled,
        obsidian_enrich_enabled: integrationsData.obsidian.enrichEnabled,
        hardcover_sync_enabled: integrationsData.hardcover.syncEnabled,
      }

      if (tokenOverride !== undefined) {
        payload.readwise_api_token = tokenOverride
      } else if (readwiseTokenInput !== '') {
        payload.readwise_api_token = readwiseTokenInput
      }

      if (hardcoverApiKeyInput !== '') {
        payload.hardcover_api_key = hardcoverApiKeyInput
      }

      const response = await fetch(routes.updateIntegrations, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
        body: JSON.stringify(payload),
      })

      const result = await response.json()

      if (! response.ok || ! result.success) {
        throw new Error(result.message || result.error || 'Failed to update integration settings')
      }

      const readwise = result.integrations?.readwise ?? {}
      const obsidian = result.integrations?.obsidian ?? {}
      const hardcover = result.integrations?.hardcover ?? {}
      
      setIntegrationsData(prev => ({
        ...prev,
        readwise: {
          tokenPresent: readwise.token_present ?? prev.readwise.tokenPresent,
          syncEnabled: readwise.sync_enabled ?? (syncOverride ?? prev.readwise.syncEnabled),
          readerSyncEnabled: readwise.reader_sync_enabled ?? prev.readwise.readerSyncEnabled,
          lastSyncedAt: readwise.last_synced_at ?? prev.readwise.lastSyncedAt,
        },
        obsidian: {
          vaultPath: obsidian.vault_path ?? prev.obsidian.vaultPath,
          syncEnabled: obsidian.sync_enabled ?? prev.obsidian.syncEnabled,
          enrichEnabled: obsidian.enrich_enabled ?? prev.obsidian.enrichEnabled,
          lastSyncedAt: obsidian.last_synced_at ?? prev.obsidian.lastSyncedAt,
          fileCount: obsidian.file_count ?? prev.obsidian.fileCount,
        },
        hardcover: {
          apiKeyPresent: hardcover.api_key_present ?? prev.hardcover.apiKeyPresent,
          syncEnabled: hardcover.sync_enabled ?? prev.hardcover.syncEnabled,
          lastSyncedAt: hardcover.last_synced_at ?? prev.hardcover.lastSyncedAt,
        },
      }))

      if (tokenOverride === undefined && readwiseTokenInput !== '') {
        setReadwiseTokenInput('')
      }

      showMessage('success', result.message || 'Integration settings saved')
      return true
    } catch (error: any) {
      console.error('Failed to update integrations:', error)
      showMessage('error', error.message || 'Failed to update integration settings')
      return false
    } finally {
      setLoading(false)
    }
  }

  const testVaultPath = async () => {
    if (!obsidianVaultPathInput) {
      setVaultPathTestResult({ valid: false, message: 'Please enter a vault path first' })
      return
    }

    setTestingVaultPath(true)
    setVaultPathTestResult(null)

    try {
      const response = await fetch('/settings/integrations/obsidian/test-path', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
        body: JSON.stringify({ vault_path: obsidianVaultPathInput }),
      })

      const result = await response.json()

      if (result.valid) {
        setVaultPathTestResult({
          valid: true,
          message: `Valid vault found with ${result.file_count} markdown files`,
          fileCount: result.file_count,
        })
      } else {
        setVaultPathTestResult({
          valid: false,
          message: result.error || 'Invalid vault path',
        })
      }
    } catch (error: any) {
      setVaultPathTestResult({
        valid: false,
        message: error.message || 'Failed to test vault path',
      })
    } finally {
      setTestingVaultPath(false)
    }
  }

  const handleIntegrationsSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    await submitIntegrations()
  }

  const handleExportSettings = () => {
    window.location.href = routes.exportSettings
  }

  const handleImportSuccess = () => {
    showMessage('success', 'Settings imported successfully')
    // Page will reload automatically from the hook
  }

  const handleResetSuccess = () => {
    showMessage('success', 'Settings reset successfully')
    // Page will reload automatically from the hook
  }

  return (
    <div className="min-h-screen bg-background">
      {/* Header */}
      <div className="border-b bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60">
        <div className="container mx-auto px-4 py-4">
          <div className="flex items-center gap-4">
            <Button
              variant="ghost"
              size="sm"
              onClick={() => window.location.href = routes.home}
              className="gap-2"
            >
              <ArrowLeft className="w-4 h-4" />
              Back to App
            </Button>
            <div>
              <h1 className="text-2xl font-bold">Settings</h1>
              <p className="text-muted-foreground">Manage your profile and preferences</p>
            </div>
          </div>
        </div>
      </div>

      {/* Message Display */}
      {message && (
        <div className="container mx-auto px-4 py-4">
          <Alert variant={message.type === 'error' ? 'destructive' : 'default'}>
            {message.type === 'success' ? (
              <CheckCircle className="h-4 w-4" />
            ) : (
              <AlertCircle className="h-4 w-4" />
            )}
            <AlertDescription>{message.text}</AlertDescription>
          </Alert>
        </div>
      )}

      {/* Settings Content */}
      <div className="container mx-auto px-4 py-8">
        <Tabs value={activeTab} onValueChange={setActiveTab} className="space-y-6">
          <TabsList className="grid w-full grid-cols-6">
            <TabsTrigger value="profile" className="gap-2">
              <User className="w-4 h-4" />
              Profile
            </TabsTrigger>
            <TabsTrigger value="preferences" className="gap-2">
              <Settings className="w-4 h-4" />
              Preferences
            </TabsTrigger>
            <TabsTrigger value="providers" className="gap-2">
              <Bot className="w-4 h-4" />
              Providers
            </TabsTrigger>
            <TabsTrigger value="integrations" className="gap-2">
              <KeySquare className="w-4 h-4" />
              Integrations
            </TabsTrigger>
            <TabsTrigger value="ai" className="gap-2">
              <Brain className="w-4 h-4" />
              AI Settings
            </TabsTrigger>
            <TabsTrigger value="appearance" className="gap-2">
              <Palette className="w-4 h-4" />
              Appearance
            </TabsTrigger>
          </TabsList>

          {/* Profile Tab */}
          <TabsContent value="profile" className="space-y-6">
            <div className="grid gap-6 md:grid-cols-2">
              {/* Basic Profile Info */}
              <Card>
                <CardHeader>
                  <CardTitle>Profile Information</CardTitle>
                  <CardDescription>
                    Update your basic profile information
                  </CardDescription>
                </CardHeader>
                <CardContent>
                  <form onSubmit={handleProfileSubmit} className="space-y-4">
                    <div>
                      <Label htmlFor="name">Full Name</Label>
                      <Input
                        id="name"
                        value={profileData.name}
                        onChange={(e) => setProfileData(prev => ({ ...prev, name: e.target.value }))}
                        required
                      />
                    </div>
                    
                    <div>
                      <Label htmlFor="display_name">Display Name</Label>
                      <Input
                        id="display_name"
                        value={profileData.display_name}
                        onChange={(e) => setProfileData(prev => ({ ...prev, display_name: e.target.value }))}
                        placeholder="How you'd like to be addressed"
                      />
                    </div>
                    
                    <div>
                      <Label htmlFor="email">Email Address</Label>
                      <Input
                        id="email"
                        type="email"
                        value={profileData.email}
                        onChange={(e) => setProfileData(prev => ({ ...prev, email: e.target.value }))}
                        required
                      />
                    </div>
                    
                    <Button type="submit" disabled={loading}>
                      {loading ? 'Updating...' : 'Update Profile'}
                    </Button>
                  </form>
                </CardContent>
              </Card>

              {/* Avatar Settings */}
              <Card>
                <CardHeader>
                  <CardTitle>Avatar</CardTitle>
                  <CardDescription>
                    Manage your profile picture
                  </CardDescription>
                </CardHeader>
                <CardContent>
                  <form onSubmit={handleAvatarSubmit}>
                    <AvatarUpload
                      user={user}
                      useGravatar={avatarData.use_gravatar}
                      onUseGravatarChange={(useGravatar) => 
                        setAvatarData(prev => ({ ...prev, use_gravatar: useGravatar }))
                      }
                      onFileSelect={setSelectedFile}
                      className="mb-4"
                    />
                    
                    <Button type="submit" disabled={loading}>
                      {loading ? 'Updating...' : 'Update Avatar'}
                    </Button>
                  </form>
                </CardContent>
              </Card>
            </div>
          </TabsContent>

          {/* Preferences Tab */}
          <TabsContent value="preferences" className="space-y-6">
            <Card>
              <CardHeader>
                <CardTitle>General Preferences</CardTitle>
                <CardDescription>
                  Customize your application experience
                </CardDescription>
              </CardHeader>
              <CardContent>
                <form onSubmit={handlePreferencesSubmit} className="space-y-6">
                  <div className="grid gap-4 md:grid-cols-2">
                    <div>
                      <Label htmlFor="language">Language</Label>
                      <Select 
                        value={preferencesData.language} 
                        onValueChange={(value) => 
                          setPreferencesData(prev => ({ ...prev, language: value }))
                        }
                      >
                        <SelectTrigger>
                          <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="en">English</SelectItem>
                          <SelectItem value="es">Español</SelectItem>
                          <SelectItem value="fr">Français</SelectItem>
                          <SelectItem value="de">Deutsch</SelectItem>
                        </SelectContent>
                      </Select>
                    </div>
                    
                    <div>
                      <Label htmlFor="timezone">Timezone</Label>
                      <Select 
                        value={preferencesData.timezone} 
                        onValueChange={(value) => 
                          setPreferencesData(prev => ({ ...prev, timezone: value }))
                        }
                      >
                        <SelectTrigger>
                          <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="UTC">UTC</SelectItem>
                          <SelectItem value="America/New_York">Eastern Time</SelectItem>
                          <SelectItem value="America/Chicago">Central Time</SelectItem>
                          <SelectItem value="America/Denver">Mountain Time</SelectItem>
                          <SelectItem value="America/Los_Angeles">Pacific Time</SelectItem>
                          <SelectItem value="Europe/London">London</SelectItem>
                          <SelectItem value="Europe/Paris">Paris</SelectItem>
                          <SelectItem value="Asia/Tokyo">Tokyo</SelectItem>
                        </SelectContent>
                      </Select>
                    </div>
                  </div>

                  <Separator />

                  <div>
                    <h4 className="text-sm font-medium mb-4">Notifications</h4>
                    <div className="space-y-3">
                      <div className="flex items-center justify-between">
                        <div>
                          <Label>Email Notifications</Label>
                          <p className="text-xs text-muted-foreground">
                            Receive email updates about important events
                          </p>
                        </div>
                        <Switch
                          checked={preferencesData.notifications.email}
                          onCheckedChange={(checked) =>
                            setPreferencesData(prev => ({
                              ...prev,
                              notifications: { ...prev.notifications, email: checked }
                            }))
                          }
                        />
                      </div>
                      
                      <div className="flex items-center justify-between">
                        <div>
                          <Label>Desktop Notifications</Label>
                          <p className="text-xs text-muted-foreground">
                            Show browser notifications for real-time updates
                          </p>
                        </div>
                        <Switch
                          checked={preferencesData.notifications.desktop}
                          onCheckedChange={(checked) =>
                            setPreferencesData(prev => ({
                              ...prev,
                              notifications: { ...prev.notifications, desktop: checked }
                            }))
                          }
                        />
                      </div>
                      
                      <div className="flex items-center justify-between">
                        <div>
                          <Label>Sound Notifications</Label>
                          <p className="text-xs text-muted-foreground">
                            Play sounds for notifications
                          </p>
                        </div>
                        <Switch
                          checked={preferencesData.notifications.sound}
                          onCheckedChange={(checked) =>
                            setPreferencesData(prev => ({
                              ...prev,
                              notifications: { ...prev.notifications, sound: checked }
                            }))
                          }
                        />
                      </div>
                    </div>
                  </div>

                  <Button type="submit" disabled={loading}>
                    {loading ? 'Updating...' : 'Update Preferences'}
                  </Button>
                </form>
              </CardContent>
            </Card>
          </TabsContent>

          {/* Providers Tab */}
          <TabsContent value="providers">
            <ProvidersManagement />
          </TabsContent>

          {/* Integrations Tab */}
          <TabsContent value="integrations" className="space-y-6">
            <Card>
              <CardHeader>
                <CardTitle>Readwise</CardTitle>
                <CardDescription>
                  Connect your Readwise account to sync highlights daily.
                </CardDescription>
              </CardHeader>
              <CardContent>
                <form onSubmit={handleIntegrationsSubmit} className="space-y-6">
                  <div className="grid gap-4 md:grid-cols-2">
                    <div className="space-y-2">
                      <Label htmlFor="readwise_token">API Token</Label>
                      <Input
                        id="readwise_token"
                        type="password"
                        placeholder="Enter new token"
                        value={readwiseTokenInput}
                        onChange={(event) => setReadwiseTokenInput(event.target.value)}
                        autoComplete="off"
                      />
                      <p className="text-xs text-muted-foreground">
                        {integrationsData.readwise.tokenPresent
                          ? 'Token already stored. Provide a new value to replace it or leave blank to keep the current token.'
                          : 'Paste your Readwise API token. You can generate one under Readwise → Account → Export → API Token.'}
                      </p>
                    </div>

                    <div className="space-y-2">
                      <Label>Automatic Sync</Label>
                      <div className="space-y-3">
                        <div className="flex items-center justify-between rounded-lg border p-3">
                          <div>
                            <p className="font-medium">Highlights import</p>
                            <p className="text-xs text-muted-foreground">Sync book/article highlights daily.</p>
                          </div>
                          <Switch
                            checked={integrationsData.readwise.syncEnabled}
                            onCheckedChange={(checked) =>
                              setIntegrationsData(prev => ({
                                ...prev,
                                readwise: { ...prev.readwise, syncEnabled: checked },
                              }))
                            }
                          />
                        </div>
                        <div className="flex items-center justify-between rounded-lg border p-3">
                          <div>
                            <p className="font-medium">Reader import</p>
                            <p className="text-xs text-muted-foreground">Sync saved articles, RSS, emails daily.</p>
                          </div>
                          <Switch
                            checked={integrationsData.readwise.readerSyncEnabled}
                            onCheckedChange={(checked) =>
                              setIntegrationsData(prev => ({
                                ...prev,
                                readwise: { ...prev.readwise, readerSyncEnabled: checked },
                              }))
                            }
                          />
                        </div>
                      </div>
                      {integrationsData.readwise.lastSyncedAt && (
                        <p className="text-xs text-muted-foreground">
                          Last synced: {new Date(integrationsData.readwise.lastSyncedAt).toLocaleString()}
                        </p>
                      )}
                    </div>
                  </div>

                  <div className="flex items-center gap-3">
                    <Button type="submit" disabled={loading}>
                      {loading ? 'Saving...' : 'Save Integration'}
                    </Button>
                    {integrationsData.readwise.tokenPresent && (
                      <Button
                        type="button"
                        variant="secondary"
                        onClick={async () => {
                          const success = await submitIntegrations('')
                          if (success) {
                            setIntegrationsData(prev => ({
                              ...prev,
                              readwise: { ...prev.readwise, tokenPresent: false },
                            }))
                          }
                        }}
                      >
                        Remove Token
                      </Button>
                    )}
                  </div>
                </form>
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle>Hardcover</CardTitle>
                <CardDescription>
                  Connect your Hardcover account to sync your book library.
                </CardDescription>
              </CardHeader>
              <CardContent>
                <form onSubmit={handleIntegrationsSubmit} className="space-y-6">
                  <div className="grid gap-4 md:grid-cols-2">
                    <div className="space-y-2">
                      <Label htmlFor="hardcover_bearer_token">Bearer Token</Label>
                      <textarea
                        id="hardcover_bearer_token"
                        className="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                        placeholder="Enter new bearer token"
                        value={hardcoverApiKeyInput}
                        onChange={(event) => setHardcoverApiKeyInput(event.target.value)}
                        autoComplete="off"
                      />
                      <p className="text-xs text-muted-foreground">
                        {integrationsData.hardcover.apiKeyPresent
                          ? 'Bearer token already stored. Provide a new value to replace it or leave blank to keep the current token.'
                          : 'Paste your Hardcover bearer token from your account settings. Used with Authorization: Bearer {token} header.'}
                      </p>
                    </div>

                    <div className="space-y-2">
                      <Label>Automatic Sync</Label>
                      <div className="flex items-center justify-between rounded-lg border p-3">
                        <div>
                          <p className="font-medium">Daily import</p>
                          <p className="text-xs text-muted-foreground">Sync your book library daily.</p>
                        </div>
                        <Switch
                          checked={integrationsData.hardcover.syncEnabled}
                          onCheckedChange={(checked) =>
                            setIntegrationsData(prev => ({
                              ...prev,
                              hardcover: { ...prev.hardcover, syncEnabled: checked },
                            }))
                          }
                        />
                      </div>
                      {integrationsData.hardcover.lastSyncedAt && (
                        <p className="text-xs text-muted-foreground">
                          Last synced: {new Date(integrationsData.hardcover.lastSyncedAt).toLocaleString()}
                        </p>
                      )}
                    </div>
                  </div>

                  <div className="flex items-center gap-3">
                    <Button type="submit" disabled={loading}>
                      {loading ? 'Saving...' : 'Save Integration'}
                    </Button>
                    {integrationsData.hardcover.apiKeyPresent && (
                      <Button
                        type="button"
                        variant="secondary"
                        onClick={async () => {
                          setHardcoverApiKeyInput('')
                          const success = await submitIntegrations()
                          if (success) {
                            setIntegrationsData(prev => ({
                              ...prev,
                              hardcover: { ...prev.hardcover, apiKeyPresent: false },
                            }))
                          }
                        }}
                      >
                        Remove API Key
                      </Button>
                    )}
                  </div>
                </form>
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle>Obsidian Vault Import</CardTitle>
                <CardDescription>
                  Import your Obsidian notes into the codex vault. Notes will be synced daily.
                </CardDescription>
              </CardHeader>
              <CardContent>
                <form onSubmit={handleIntegrationsSubmit} className="space-y-6">
                  <div className="space-y-4">
                    <div className="space-y-2">
                      <Label htmlFor="obsidian_vault_path">Vault Path</Label>
                      <div className="flex gap-2">
                        <Input
                          id="obsidian_vault_path"
                          type="text"
                          placeholder="/path/to/your/obsidian/vault"
                          value={obsidianVaultPathInput}
                          onChange={(event) => {
                            setObsidianVaultPathInput(event.target.value)
                            setVaultPathTestResult(null)
                          }}
                        />
                        <Button
                          type="button"
                          variant="outline"
                          onClick={testVaultPath}
                          disabled={testingVaultPath || !obsidianVaultPathInput}
                        >
                          {testingVaultPath ? 'Testing...' : 'Test'}
                        </Button>
                      </div>
                      <p className="text-xs text-muted-foreground">
                        Full path to your Obsidian vault directory
                      </p>
                      {vaultPathTestResult && (
                        <Alert variant={vaultPathTestResult.valid ? 'default' : 'destructive'}>
                          <AlertDescription className="flex items-center gap-2">
                            {vaultPathTestResult.valid ? (
                              <CheckCircle className="w-4 h-4" />
                            ) : (
                              <AlertCircle className="w-4 h-4" />
                            )}
                            {vaultPathTestResult.message}
                          </AlertDescription>
                        </Alert>
                      )}
                    </div>

                    <div className="space-y-2">
                      <Label>Automatic Sync</Label>
                      <div className="flex items-center justify-between rounded-lg border p-3">
                        <div>
                          <p className="font-medium">Daily import</p>
                          <p className="text-xs text-muted-foreground">Enable the scheduled Obsidian sync (runs at 03:00 UTC).</p>
                        </div>
                        <Switch
                          checked={integrationsData.obsidian.syncEnabled}
                          onCheckedChange={(checked) =>
                            setIntegrationsData(prev => ({
                              ...prev,
                              obsidian: { ...prev.obsidian, syncEnabled: checked },
                            }))
                          }
                        />
                      </div>
                      {integrationsData.obsidian.lastSyncedAt && (
                        <p className="text-xs text-muted-foreground">
                          Last synced: {new Date(integrationsData.obsidian.lastSyncedAt).toLocaleString()} 
                          {integrationsData.obsidian.fileCount > 0 && ` • ${integrationsData.obsidian.fileCount} files`}
                        </p>
                      )}
                    </div>

                    <div className="space-y-2">
                      <Label>AI Enrichment</Label>
                      <div className="flex items-center justify-between rounded-lg border p-3">
                        <div>
                          <p className="font-medium">AI type inference</p>
                          <p className="text-xs text-muted-foreground">Run AI enrichment during sync (type inference, entity extraction).</p>
                        </div>
                        <Switch
                          checked={integrationsData.obsidian.enrichEnabled}
                          onCheckedChange={(checked) =>
                            setIntegrationsData(prev => ({
                              ...prev,
                              obsidian: { ...prev.obsidian, enrichEnabled: checked },
                            }))
                          }
                        />
                      </div>
                      <p className="text-xs text-muted-foreground">
                        When enabled, notes are processed with AI to infer type (note, task, meeting, etc.) and extract entities. This is slower but provides richer metadata.
                      </p>
                    </div>
                  </div>

                  <div className="flex items-center gap-3">
                    <Button type="submit" disabled={loading}>
                      {loading ? 'Saving...' : 'Save Integration'}
                    </Button>
                  </div>
                </form>
              </CardContent>
            </Card>
          </TabsContent>

          {/* AI Settings Tab */}
          <TabsContent value="ai" className="space-y-6">
            <Card>
              <CardHeader>
                <CardTitle>AI Configuration</CardTitle>
                <CardDescription>
                  Configure AI provider settings and behavior
                </CardDescription>
              </CardHeader>
              <CardContent>
                <form onSubmit={handleAISubmit} className="space-y-6">
                  <div className="grid gap-4 md:grid-cols-2">
                    <div>
                      <Label htmlFor="default_provider">Default Provider</Label>
                      <Select 
                        value={aiData.default_provider} 
                        onValueChange={(value) => {
                          setAIData(prev => ({ 
                            ...prev, 
                            default_provider: value,
                            default_model: '' // Reset model when provider changes
                          }))
                        }}
                      >
                        <SelectTrigger>
                          <SelectValue placeholder="Select provider" />
                        </SelectTrigger>
                        <SelectContent>
                          {providers.filter(p => p.enabled && p.models.length > 0).map((provider) => (
                            <SelectItem key={provider.id} value={provider.id}>
                              <div className="flex items-center gap-2">
                                <span>{provider.name}</span>
                                <Badge variant="secondary" className="text-xs">
                                  {provider.models.length} models
                                </Badge>
                              </div>
                            </SelectItem>
                          ))}
                          {providers.filter(p => p.enabled && p.models.length > 0).length === 0 && (
                            <SelectItem value="" disabled>
                              No providers available
                            </SelectItem>
                          )}
                        </SelectContent>
                      </Select>
                      {providers.length > 0 && (
                        <p className="text-xs text-muted-foreground mt-1">
                          {providers.filter(p => p.enabled).length} of {providers.length} providers enabled
                        </p>
                      )}
                    </div>
                    
                    <div>
                      <Label htmlFor="default_model">Default Model</Label>
                      <Select 
                        value={aiData.default_model} 
                        onValueChange={(value) => 
                          setAIData(prev => ({ ...prev, default_model: value }))
                        }
                      >
                        <SelectTrigger>
                          <SelectValue placeholder="Select a model" />
                        </SelectTrigger>
                        <SelectContent className="max-h-[300px]">
                          {aiData.default_provider && (
                            <>
                              {/* Models from selected provider */}
                              {availableModels
                                .filter(model => model.provider === aiData.default_provider)
                                .map((model) => (
                                  <SelectItem key={model.id} value={model.id}>
                                    <div className="flex flex-col">
                                      <span>{model.modelName}</span>
                                      <span className="text-xs text-muted-foreground">
                                        {model.capabilities.join(', ')}
                                      </span>
                                    </div>
                                  </SelectItem>
                                ))
                              }
                              {availableModels.filter(model => model.provider === aiData.default_provider).length === 0 && (
                                <SelectItem value="" disabled>
                                  No models available for this provider
                                </SelectItem>
                              )}
                            </>
                          )}
                          {!aiData.default_provider && (
                            <SelectItem value="" disabled>
                              Please select a provider first
                            </SelectItem>
                          )}
                        </SelectContent>
                      </Select>
                      {aiData.default_provider && availableModels.filter(model => model.provider === aiData.default_provider).length > 0 && (
                        <p className="text-xs text-muted-foreground mt-1">
                          {availableModels.filter(model => model.provider === aiData.default_provider).length} models available
                        </p>
                      )}
                    </div>
                  </div>

                  <div className="grid gap-4 md:grid-cols-3">
                    <div>
                      <Label htmlFor="temperature">Temperature</Label>
                      <Input
                        id="temperature"
                        type="number"
                        min="0"
                        max="2"
                        step="0.1"
                        value={aiData.temperature}
                        onChange={(e) => setAIData(prev => ({ ...prev, temperature: parseFloat(e.target.value) }))}
                      />
                      <p className="text-xs text-muted-foreground mt-1">
                        Controls randomness (0-2)
                      </p>
                    </div>
                    
                    <div>
                      <Label htmlFor="max_tokens">Max Tokens</Label>
                      <Input
                        id="max_tokens"
                        type="number"
                        min="1"
                        max="32000"
                        value={aiData.max_tokens}
                        onChange={(e) => setAIData(prev => ({ ...prev, max_tokens: parseInt(e.target.value) }))}
                      />
                    </div>
                    
                    <div>
                      <Label htmlFor="context_length">Context Length</Label>
                      <Input
                        id="context_length"
                        type="number"
                        min="1000"
                        max="128000"
                        value={aiData.context_length}
                        onChange={(e) => setAIData(prev => ({ ...prev, context_length: parseInt(e.target.value) }))}
                      />
                    </div>
                  </div>

                  <Separator />

                  <div>
                    <h4 className="text-sm font-medium mb-4">Behavior Settings</h4>
                    <div className="space-y-3">
                      <div className="flex items-center justify-between">
                        <div>
                          <Label>Stream Responses</Label>
                          <p className="text-xs text-muted-foreground">
                            Show AI responses as they're being generated
                          </p>
                        </div>
                        <Switch
                          checked={aiData.stream_responses}
                          onCheckedChange={(checked) =>
                            setAIData(prev => ({ ...prev, stream_responses: checked }))
                          }
                        />
                      </div>
                      
                      <div className="flex items-center justify-between">
                        <div>
                          <Label>Auto-generate Titles</Label>
                          <p className="text-xs text-muted-foreground">
                            Automatically create titles for conversations
                          </p>
                        </div>
                        <Switch
                          checked={aiData.auto_title}
                          onCheckedChange={(checked) =>
                            setAIData(prev => ({ ...prev, auto_title: checked }))
                          }
                        />
                      </div>
                    </div>
                  </div>

                  <Button type="submit" disabled={loading}>
                    {loading ? 'Updating...' : 'Update AI Settings'}
                  </Button>
                </form>
              </CardContent>
            </Card>
          </TabsContent>

          {/* Appearance Tab */}
          <TabsContent value="appearance" className="space-y-6">
            <Card>
              <CardHeader>
                <CardTitle>Theme & Layout</CardTitle>
                <CardDescription>
                  Customize the visual appearance of the application
                </CardDescription>
              </CardHeader>
              <CardContent className="space-y-6">
                <div>
                  <Label htmlFor="theme">Theme</Label>
                  <Select 
                    value={preferencesData.theme} 
                    onValueChange={(value) => 
                      setPreferencesData(prev => ({ ...prev, theme: value }))
                    }
                  >
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="system">System Default</SelectItem>
                      <SelectItem value="light">Light</SelectItem>
                      <SelectItem value="dark">Dark</SelectItem>
                    </SelectContent>
                  </Select>
                </div>

                <Separator />

                <div>
                  <h4 className="text-sm font-medium mb-4">Layout Options</h4>
                  <div className="space-y-3">
                    <div className="flex items-center justify-between">
                      <div>
                        <Label>Compact Mode</Label>
                        <p className="text-xs text-muted-foreground">
                          Reduce spacing and padding for denser layout
                        </p>
                      </div>
                      <Switch
                        checked={preferencesData.layout.compact_mode}
                        onCheckedChange={(checked) =>
                          setPreferencesData(prev => ({
                            ...prev,
                            layout: { ...prev.layout, compact_mode: checked }
                          }))
                        }
                      />
                    </div>
                    
                    <div>
                      <Label htmlFor="right_rail_width">Right Panel Width</Label>
                      <Input
                        id="right_rail_width"
                        type="number"
                        min="200"
                        max="600"
                        value={preferencesData.layout.right_rail_width}
                        onChange={(e) =>
                          setPreferencesData(prev => ({
                            ...prev,
                            layout: { ...prev.layout, right_rail_width: parseInt(e.target.value) }
                          }))
                        }
                      />
                      <p className="text-xs text-muted-foreground mt-1">
                        Width in pixels (200-600)
                      </p>
                    </div>
                  </div>
                </div>

                <Button onClick={handlePreferencesSubmit} disabled={loading}>
                  {loading ? 'Updating...' : 'Update Appearance'}
                </Button>
              </CardContent>
            </Card>

            {/* Import/Export Settings */}
            <Card>
              <CardHeader>
                <CardTitle>Settings Management</CardTitle>
                <CardDescription>
                  Import, export, or reset your settings
                </CardDescription>
              </CardHeader>
              <CardContent>
                <div className="flex gap-3">
                  <Button variant="outline" onClick={handleExportSettings} className="gap-2">
                    <Download className="w-4 h-4" />
                    Export Settings
                  </Button>
                  
                  <Button variant="outline" className="gap-2" onClick={() => setShowImportDialog(true)}>
                    <Upload className="w-4 h-4" />
                    Import Settings
                  </Button>
                  
                  <Button variant="outline" className="gap-2" onClick={() => setShowResetDialog(true)}>
                    <RotateCcw className="w-4 h-4" />
                    Reset to Defaults
                  </Button>
                </div>
              </CardContent>
            </Card>
          </TabsContent>
        </Tabs>
      </div>

      {/* Import/Reset Dialogs */}
      <ImportDialog
        open={showImportDialog}
        onOpenChange={setShowImportDialog}
        onSuccess={handleImportSuccess}
      />
      
      <ResetDialog
        open={showResetDialog}
        onOpenChange={setShowResetDialog}
        onSuccess={handleResetSuccess}
      />
    </div>
  )
}
