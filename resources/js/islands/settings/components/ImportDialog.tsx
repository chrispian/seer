import React, { useRef, useState } from 'react'
import { Upload, FileText, CheckCircle, AlertCircle, X } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Progress } from '@/components/ui/progress'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { Badge } from '@/components/ui/badge'
import { useSettingsImport } from '@/hooks/useSettingsImport'

interface ImportDialogProps {
  open: boolean
  onOpenChange: (open: boolean) => void
  onSuccess?: () => void
}

export function ImportDialog({ open, onOpenChange, onSuccess }: ImportDialogProps) {
  const fileInputRef = useRef<HTMLInputElement>(null)
  const [dragActive, setDragActive] = useState(false)
  const [showPreview, setShowPreview] = useState(false)
  
  const {
    progress,
    selectedFile,
    previewData,
    error,
    previewSettings,
    importSettings,
    reset,
    isLoading
  } = useSettingsImport()

  const handleFileSelect = async (file: File) => {
    const success = await previewSettings(file)
    if (success) {
      setShowPreview(true)
    }
  }

  const handleFileInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0]
    if (file) {
      handleFileSelect(file)
    }
  }

  const handleDrop = (e: React.DragEvent) => {
    e.preventDefault()
    setDragActive(false)
    
    const file = e.dataTransfer.files[0]
    if (file) {
      handleFileSelect(file)
    }
  }

  const handleDragOver = (e: React.DragEvent) => {
    e.preventDefault()
    setDragActive(true)
  }

  const handleDragLeave = () => {
    setDragActive(false)
  }

  const handleImport = async () => {
    const success = await importSettings()
    if (success) {
      onSuccess?.()
      handleClose()
    }
  }

  const handleClose = () => {
    reset()
    setShowPreview(false)
    onOpenChange(false)
  }

  const handleBack = () => {
    setShowPreview(false)
    reset()
  }

  const renderFileUpload = () => (
    <div className="space-y-4">
      <div
        className={`
          border-2 border-dashed rounded-lg p-8 text-center transition-colors
          ${dragActive ? 'border-primary bg-primary/5' : 'border-border'}
          ${isLoading ? 'pointer-events-none opacity-50' : 'cursor-pointer hover:border-primary/50'}
        `}
        onDrop={handleDrop}
        onDragOver={handleDragOver}
        onDragLeave={handleDragLeave}
        onClick={() => fileInputRef.current?.click()}
      >
        <Upload className="w-12 h-12 mx-auto mb-4 text-muted-foreground" />
        <h3 className="text-lg font-medium mb-2">
          {isLoading ? 'Processing...' : 'Select settings file'}
        </h3>
        <p className="text-sm text-muted-foreground mb-4">
          Drop a JSON settings file here, or click to browse
        </p>
        <p className="text-xs text-muted-foreground">
          Supports files exported from Fragments Engine (.json, max 1MB)
        </p>
      </div>

      <input
        ref={fileInputRef}
        type="file"
        accept=".json,application/json"
        onChange={handleFileInputChange}
        className="hidden"
        disabled={isLoading}
      />

      {isLoading && (
        <div className="space-y-2">
          <Progress value={progress.uploading ? 30 : progress.processing ? 70 : 100} />
          <p className="text-sm text-center text-muted-foreground">
            {progress.uploading && 'Uploading file...'}
            {progress.processing && 'Processing settings...'}
            {progress.completed && 'Import completed!'}
          </p>
        </div>
      )}

      {error && (
        <Alert variant="destructive">
          <AlertCircle className="h-4 w-4" />
          <AlertDescription>{error}</AlertDescription>
        </Alert>
      )}
    </div>
  )

  const renderPreview = () => (
    <div className="space-y-4">
      <div className="flex items-center gap-2 mb-4">
        <FileText className="w-5 h-5" />
        <span className="font-medium">{selectedFile?.name}</span>
        <Badge variant="secondary">{(selectedFile?.size || 0) / 1024 < 1 ? '< 1' : Math.round((selectedFile?.size || 0) / 1024)} KB</Badge>
      </div>

      <Card>
        <CardHeader>
          <CardTitle className="text-sm">Settings Preview</CardTitle>
          <CardDescription>
            The following settings will be imported and merged with your current configuration
          </CardDescription>
        </CardHeader>
        <CardContent className="space-y-3">
          {previewData?.profile && (
            <div>
              <h4 className="font-medium text-sm mb-2">Profile Settings</h4>
              <div className="text-sm text-muted-foreground space-y-1">
                {previewData.profile.display_name && (
                  <div>• Display Name: {previewData.profile.display_name}</div>
                )}
                {typeof previewData.profile.use_gravatar === 'boolean' && (
                  <div>• Gravatar: {previewData.profile.use_gravatar ? 'Enabled' : 'Disabled'}</div>
                )}
              </div>
            </div>
          )}

          {previewData?.settings && (
            <div>
              <h4 className="font-medium text-sm mb-2">Application Settings</h4>
              <div className="text-sm text-muted-foreground space-y-1">
                {previewData.settings.theme && (
                  <div>• Theme: {previewData.settings.theme}</div>
                )}
                {previewData.settings.language && (
                  <div>• Language: {previewData.settings.language}</div>
                )}
                {previewData.settings.ai && (
                  <div>• AI Configuration: {previewData.settings.ai.default_provider || 'Default'}</div>
                )}
                {previewData.settings.notifications && (
                  <div>• Notification Preferences</div>
                )}
              </div>
            </div>
          )}

          {previewData?.exported_at && (
            <div className="text-xs text-muted-foreground pt-2 border-t">
              Exported: {new Date(previewData.exported_at).toLocaleString()}
            </div>
          )}
        </CardContent>
      </Card>

      <Alert>
        <AlertCircle className="h-4 w-4" />
        <AlertDescription>
          Importing will merge these settings with your current configuration. Existing settings will be overwritten where conflicts occur.
        </AlertDescription>
      </Alert>

      {error && (
        <Alert variant="destructive">
          <AlertCircle className="h-4 w-4" />
          <AlertDescription>{error}</AlertDescription>
        </Alert>
      )}
    </div>
  )

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-md">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2">
            <Upload className="w-5 h-5" />
            Import Settings
          </DialogTitle>
          <DialogDescription>
            {showPreview 
              ? 'Review the settings that will be imported'
              : 'Upload a settings file to restore your configuration'
            }
          </DialogDescription>
        </DialogHeader>

        <div className="py-4">
          {showPreview ? renderPreview() : renderFileUpload()}
        </div>

        <DialogFooter className="gap-2">
          {showPreview ? (
            <>
              <Button variant="outline" onClick={handleBack} disabled={isLoading}>
                Back
              </Button>
              <Button onClick={handleImport} disabled={isLoading}>
                {isLoading ? 'Importing...' : 'Import Settings'}
              </Button>
            </>
          ) : (
            <Button variant="outline" onClick={handleClose}>
              Cancel
            </Button>
          )}
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}