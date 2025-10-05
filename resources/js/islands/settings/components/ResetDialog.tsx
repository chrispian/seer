import React, { useState } from 'react'
import { RotateCcw, AlertTriangle, CheckCircle, AlertCircle } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { useSettingsReset } from '@/hooks/useSettingsReset'

interface ResetDialogProps {
  open: boolean
  onOpenChange: (open: boolean) => void
  onSuccess?: () => void
}

interface SettingsSection {
  id: string
  label: string
  description: string
  items: string[]
}

const SETTINGS_SECTIONS: SettingsSection[] = [
  {
    id: 'preferences',
    label: 'General Preferences',
    description: 'Theme, language, timezone',
    items: ['Theme setting', 'Language preference', 'Timezone setting']
  },
  {
    id: 'notifications',
    label: 'Notification Settings',
    description: 'Email, desktop, and sound notifications',
    items: ['Email notifications', 'Desktop notifications', 'Sound notifications']
  },
  {
    id: 'ai',
    label: 'AI Configuration',
    description: 'AI provider, model, and behavior settings',
    items: ['Default provider', 'Default model', 'Temperature', 'Max tokens', 'Response streaming', 'Auto-title generation']
  },
  {
    id: 'layout',
    label: 'Layout Settings',
    description: 'Interface layout and display preferences',
    items: ['Sidebar state', 'Right panel width', 'Compact mode']
  }
]

export function ResetDialog({ open, onOpenChange, onSuccess }: ResetDialogProps) {
  const [step, setStep] = useState<'select' | 'confirm' | 'complete'>('select')
  
  const {
    state,
    selectedSections,
    confirmationToken,
    error,
    generateResetToken,
    resetSettings,
    toggleSection,
    isTokenValid,
    reset,
    isLoading
  } = useSettingsReset()

  const handleNext = async () => {
    if (selectedSections.length === 0) {
      return
    }
    
    const success = await generateResetToken()
    if (success) {
      setStep('confirm')
    }
  }

  const handleReset = async () => {
    const success = await resetSettings()
    if (success) {
      setStep('complete')
      setTimeout(() => {
        onSuccess?.()
        handleClose()
      }, 2000)
    }
  }

  const handleClose = () => {
    reset()
    setStep('select')
    onOpenChange(false)
  }

  const handleBack = () => {
    setStep('select')
  }

  const renderSectionSelection = () => (
    <div className="space-y-4">
      <Alert>
        <AlertTriangle className="h-4 w-4" />
        <AlertDescription>
          Resetting settings will restore them to their default values. This action cannot be undone.
        </AlertDescription>
      </Alert>

      <div className="space-y-3">
        <h4 className="font-medium text-sm">Select sections to reset:</h4>
        {SETTINGS_SECTIONS.map((section) => (
          <Card key={section.id} className="cursor-pointer" onClick={() => toggleSection(section.id)}>
            <CardContent className="p-4">
              <div className="flex items-start gap-3">
                <Checkbox
                  checked={selectedSections.includes(section.id)}
                  onChange={() => toggleSection(section.id)}
                  className="mt-1"
                />
                <div className="flex-1">
                  <h5 className="font-medium text-sm">{section.label}</h5>
                  <p className="text-xs text-muted-foreground mb-2">{section.description}</p>
                  <div className="text-xs text-muted-foreground">
                    Will reset: {section.items.join(', ')}
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        ))}
      </div>

      {selectedSections.length === 0 && (
        <p className="text-sm text-muted-foreground text-center py-4">
          Please select at least one section to reset
        </p>
      )}

      {error && (
        <Alert variant="destructive">
          <AlertCircle className="h-4 w-4" />
          <AlertDescription>{error}</AlertDescription>
        </Alert>
      )}
    </div>
  )

  const renderConfirmation = () => (
    <div className="space-y-4">
      <Alert variant="destructive">
        <AlertTriangle className="h-4 w-4" />
        <AlertDescription>
          <strong>Warning:</strong> You are about to reset the following settings sections. This action cannot be undone.
        </AlertDescription>
      </Alert>

      <Card>
        <CardHeader>
          <CardTitle className="text-sm">Sections to Reset</CardTitle>
          <CardDescription>
            The following settings will be restored to their default values:
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div className="space-y-3">
            {selectedSections.map((sectionId) => {
              const section = SETTINGS_SECTIONS.find(s => s.id === sectionId)
              return section ? (
                <div key={sectionId}>
                  <h5 className="font-medium text-sm">{section.label}</h5>
                  <p className="text-xs text-muted-foreground">
                    {section.items.join(', ')}
                  </p>
                </div>
              ) : null
            })}
          </div>
        </CardContent>
      </Card>

      {!isTokenValid() && (
        <Alert>
          <AlertCircle className="h-4 w-4" />
          <AlertDescription>
            Generating confirmation token...
          </AlertDescription>
        </Alert>
      )}

      {error && (
        <Alert variant="destructive">
          <AlertCircle className="h-4 w-4" />
          <AlertDescription>{error}</AlertDescription>
        </Alert>
      )}
    </div>
  )

  const renderComplete = () => (
    <div className="space-y-4 text-center">
      <div className="flex justify-center">
        <div className="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center">
          <CheckCircle className="w-8 h-8 text-green-600" />
        </div>
      </div>
      
      <div>
        <h3 className="font-medium mb-2">Settings Reset Complete</h3>
        <p className="text-sm text-muted-foreground">
          The selected settings have been restored to their default values.
          The page will reload shortly to reflect the changes.
        </p>
      </div>
    </div>
  )

  const getDialogTitle = () => {
    switch (step) {
      case 'select': return 'Reset Settings'
      case 'confirm': return 'Confirm Reset'
      case 'complete': return 'Reset Complete'
    }
  }

  const getDialogDescription = () => {
    switch (step) {
      case 'select': return 'Choose which settings sections to restore to default values'
      case 'confirm': return 'Please confirm that you want to reset the selected settings'
      case 'complete': return 'Your settings have been successfully reset'
    }
  }

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-md">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2">
            <RotateCcw className="w-5 h-5" />
            {getDialogTitle()}
          </DialogTitle>
          <DialogDescription>
            {getDialogDescription()}
          </DialogDescription>
        </DialogHeader>

        <div className="py-4">
          {step === 'select' && renderSectionSelection()}
          {step === 'confirm' && renderConfirmation()}
          {step === 'complete' && renderComplete()}
        </div>

        {step !== 'complete' && (
          <DialogFooter className="gap-2">
            {step === 'confirm' && (
              <Button variant="outline" onClick={handleBack} disabled={isLoading}>
                Back
              </Button>
            )}
            
            {step === 'select' && (
              <>
                <Button variant="outline" onClick={handleClose}>
                  Cancel
                </Button>
                <Button 
                  onClick={handleNext} 
                  disabled={selectedSections.length === 0 || isLoading}
                  variant="destructive"
                >
                  {isLoading ? 'Preparing...' : 'Continue'}
                </Button>
              </>
            )}
            
            {step === 'confirm' && (
              <Button 
                onClick={handleReset} 
                disabled={!isTokenValid() || isLoading}
                variant="destructive"
              >
                {isLoading ? 'Resetting...' : 'Reset Settings'}
              </Button>
            )}
          </DialogFooter>
        )}
      </DialogContent>
    </Dialog>
  )
}