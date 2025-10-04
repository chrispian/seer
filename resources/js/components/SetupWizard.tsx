import React, { useState } from 'react'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Switch } from '@/components/ui/switch'
import { Progress } from '@/components/ui/progress'
import { AvatarUpload } from '@/components/AvatarUpload'
import { CheckCircle, User, Image, Settings, Sparkles } from 'lucide-react'

declare global {
  interface Window {
    setupData?: {
      step: string
      user: any
      routes: Record<string, string>
    }
  }
}

interface SetupWizardProps {
  step: 'welcome' | 'profile' | 'avatar' | 'preferences' | 'complete'
  user: any
  routes: Record<string, string>
}

const steps = [
  { id: 'welcome', title: 'Welcome', icon: Sparkles },
  { id: 'profile', title: 'Profile', icon: User },
  { id: 'avatar', title: 'Avatar', icon: Image },
  { id: 'preferences', title: 'Preferences', icon: Settings },
  { id: 'complete', title: 'Complete', icon: CheckCircle },
]

export function SetupWizard({ step, user, routes }: SetupWizardProps) {
  const [formData, setFormData] = useState({
    display_name: user?.display_name || user?.name || '',
    use_gravatar: user?.use_gravatar ?? true,
    theme: 'system',
    language: 'en',
    timezone: 'UTC',
  })
  
  const [loading, setLoading] = useState(false)
  const [selectedFile, setSelectedFile] = useState<File | null>(null)
  const currentStepIndex = steps.findIndex(s => s.id === step)
  const progress = ((currentStepIndex + 1) / steps.length) * 100

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setLoading(true)

    try {
      const endpoint = routes.store || routes.next
      if (!endpoint) return

      let body: any = formData

      // Handle file upload for avatar step
      if (step === 'avatar') {
        const formDataUpload = new FormData()
        
        if (selectedFile) {
          formDataUpload.append('avatar', selectedFile)
        }
        formDataUpload.append('use_gravatar', String(body.use_gravatar))
        
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        console.log('Submitting avatar data:', { 
          use_gravatar: body.use_gravatar, 
          hasFile: !!selectedFile,
          endpoint: endpoint,
          csrfToken: csrfToken ? csrfToken.substring(0, 10) + '...' : 'missing'
        })
        
        // Add CSRF token to FormData as well for Laravel's handling
        if (csrfToken) {
          formDataUpload.append('_token', csrfToken)
        }
        
        const response = await fetch(endpoint, {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': csrfToken || '',
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
          },
          body: formDataUpload,
          credentials: 'same-origin'
        })
        
        console.log('Avatar response status:', response.status)
        console.log('Avatar response headers:', Object.fromEntries(response.headers.entries()))
        
        if (!response.ok) {
          console.error('Avatar request failed:', response.status, response.statusText)
          const text = await response.text()
          console.error('Response text:', text.substring(0, 500))
          return
        }
        
        const contentType = response.headers.get('content-type')
        if (!contentType || !contentType.includes('application/json')) {
          const text = await response.text()
          console.error('Non-JSON response received:', text.substring(0, 500))
          return
        }
        
        const result = await response.json()
        console.log('Avatar response:', result)
        
        if (result.success) {
          if (result.next_step) {
            console.log('Redirecting to:', result.next_step)
            window.location.href = result.next_step
          } else if (result.redirect) {
            console.log('Redirecting to:', result.redirect)
            window.location.href = result.redirect
          }
        } else if (result.error) {
          console.error('Avatar upload error:', result.error)
        } else {
          console.error('Unexpected response format:', result)
        }
        
        return
      }

      // Regular JSON submission for other steps
      const response = await fetch(endpoint, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        },
        body: JSON.stringify(body),
        credentials: 'same-origin'
      })

      const result = await response.json()
      
      if (result.success) {
        if (result.next_step) {
          window.location.href = result.next_step
        } else if (result.redirect) {
          window.location.href = result.redirect
        }
      }
    } catch (error) {
      console.error('Setup submission error:', error)
    } finally {
      setLoading(false)
    }
  }

  const updateFormData = (key: string, value: any) => {
    setFormData(prev => ({ ...prev, [key]: value }))
  }

  return (
    <div className="min-h-screen bg-background flex items-center justify-center p-4">
      <div className="w-full max-w-2xl">
        {/* Progress Header */}
        <div className="mb-8">
          <div className="flex items-center justify-between mb-4">
            {steps.map((s, index) => {
              const Icon = s.icon
              const isActive = index <= currentStepIndex
              const isCurrent = s.id === step
              
              return (
                <div key={s.id} className="flex items-center">
                  <div className={`
                    flex items-center justify-center w-10 h-10 rounded-full border-2 transition-colors
                    ${isActive ? 'border-primary bg-primary text-primary-foreground' : 'border-muted-foreground bg-background'}
                    ${isCurrent ? 'ring-2 ring-primary ring-offset-2' : ''}
                  `}>
                    <Icon className="w-5 h-5" />
                  </div>
                  {index < steps.length - 1 && (
                    <div className={`w-12 h-0.5 mx-2 ${isActive ? 'bg-primary' : 'bg-muted'}`} />
                  )}
                </div>
              )
            })}
          </div>
          <Progress value={progress} className="w-full" />
        </div>

        {/* Step Content */}
        <Card>
          <CardHeader>
            <CardTitle className="text-2xl">
              {step === 'welcome' && 'Welcome to Fragments Engine'}
              {step === 'profile' && 'Set Up Your Profile'}
              {step === 'avatar' && 'Choose Your Avatar'}
              {step === 'preferences' && 'Customize Your Experience'}
              {step === 'complete' && 'Setup Complete!'}
            </CardTitle>
            <CardDescription>
              {step === 'welcome' && 'Let\'s get you set up with a personalized experience'}
              {step === 'profile' && 'Tell us a bit about yourself'}
              {step === 'avatar' && 'Add a personal touch with your avatar'}
              {step === 'preferences' && 'Configure your preferences'}
              {step === 'complete' && 'You\'re all set to start using Fragments Engine'}
            </CardDescription>
          </CardHeader>
          
          <CardContent>
            <form onSubmit={handleSubmit} className="space-y-6">
              {step === 'welcome' && (
                <div className="text-center py-8">
                  <p className="text-lg text-muted-foreground mb-6">
                    We'll help you set up your profile and preferences to get the most out of your AI-powered workspace.
                  </p>
                  <Button type="button" onClick={() => window.location.href = routes.profile}>
                    Get Started
                  </Button>
                </div>
              )}

              {step === 'profile' && (
                <div className="space-y-4">
                  <div>
                    <Label htmlFor="display_name">Display Name</Label>
                    <Input
                      id="display_name"
                      value={formData.display_name}
                      onChange={(e) => updateFormData('display_name', e.target.value)}
                      placeholder="How would you like to be addressed?"
                      required
                    />
                  </div>
                </div>
              )}

              {step === 'avatar' && (
                <AvatarUpload
                  user={user}
                  useGravatar={formData.use_gravatar}
                  onUseGravatarChange={(useGravatar) => updateFormData('use_gravatar', useGravatar)}
                  onFileSelect={setSelectedFile}
                />
              )}

              {step === 'preferences' && (
                <div className="space-y-4">
                  <div>
                    <Label htmlFor="theme">Theme Preference</Label>
                    <Select value={formData.theme} onValueChange={(value) => updateFormData('theme', value)}>
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
                  
                  <div>
                    <Label htmlFor="language">Language</Label>
                    <Select value={formData.language} onValueChange={(value) => updateFormData('language', value)}>
                      <SelectTrigger>
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="en">English</SelectItem>
                        <SelectItem value="es">Español</SelectItem>
                        <SelectItem value="fr">Français</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                </div>
              )}

              {step === 'complete' && (
                <div className="text-center py-8">
                  <CheckCircle className="w-16 h-16 text-green-500 mx-auto mb-4" />
                  <p className="text-lg mb-6">
                    Your profile has been set up successfully! You can now start using all the features of Fragments Engine.
                  </p>
                  <Button onClick={() => window.location.href = routes.home || '/'}>
                    Enter Application
                  </Button>
                </div>
              )}

              {step !== 'welcome' && step !== 'complete' && (
                <div className="flex justify-between pt-4">
                  <Button type="button" variant="outline" disabled>
                    Previous
                  </Button>
                  <Button type="submit" disabled={loading}>
                    {loading ? 'Saving...' : 'Continue'}
                  </Button>
                </div>
              )}
            </form>
          </CardContent>
        </Card>
      </div>
    </div>
  )
}