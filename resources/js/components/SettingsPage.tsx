import React, { useState } from 'react'

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
import { ArrowLeft, User, Settings, Brain, Palette, Download, Upload, RotateCcw, CheckCircle, AlertCircle } from 'lucide-react'

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

  const [selectedFile, setSelectedFile] = useState<File | null>(null)

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

  const handleExportSettings = () => {
    window.location.href = routes.exportSettings
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
          <TabsList className="grid w-full grid-cols-4">
            <TabsTrigger value="profile" className="gap-2">
              <User className="w-4 h-4" />
              Profile
            </TabsTrigger>
            <TabsTrigger value="preferences" className="gap-2">
              <Settings className="w-4 h-4" />
              Preferences
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
                        onValueChange={(value) => 
                          setAIData(prev => ({ ...prev, default_provider: value }))
                        }
                      >
                        <SelectTrigger>
                          <SelectValue placeholder="Select provider" />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="openai">OpenAI</SelectItem>
                          <SelectItem value="anthropic">Anthropic</SelectItem>
                          <SelectItem value="ollama">Ollama</SelectItem>
                          <SelectItem value="openrouter">OpenRouter</SelectItem>
                        </SelectContent>
                      </Select>
                    </div>
                    
                    <div>
                      <Label htmlFor="default_model">Default Model</Label>
                      <Input
                        id="default_model"
                        value={aiData.default_model}
                        onChange={(e) => setAIData(prev => ({ ...prev, default_model: e.target.value }))}
                        placeholder="e.g., gpt-4, claude-3-sonnet"
                      />
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
                  
                  <Button variant="outline" className="gap-2">
                    <Upload className="w-4 h-4" />
                    Import Settings
                  </Button>
                  
                  <Button variant="outline" className="gap-2">
                    <RotateCcw className="w-4 h-4" />
                    Reset to Defaults
                  </Button>
                </div>
              </CardContent>
            </Card>
          </TabsContent>
        </Tabs>
      </div>
    </div>
  )
}