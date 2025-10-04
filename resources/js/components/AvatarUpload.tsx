import React, { useState, useRef, useCallback } from 'react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Switch } from '@/components/ui/switch'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { Upload, Camera, User, AlertCircle } from 'lucide-react'
import { cn } from '@/lib/utils'
import { useUser, getUserAvatarUrl } from '@/hooks/useUser'

interface AvatarUploadProps {
  user: any
  useGravatar: boolean
  onUseGravatarChange: (useGravatar: boolean) => void
  onFileSelect: (file: File | null) => void
  className?: string
}

export function AvatarUpload({ 
  user, 
  useGravatar, 
  onUseGravatarChange, 
  onFileSelect,
  className 
}: AvatarUploadProps) {
  const [dragActive, setDragActive] = useState(false)
  const [selectedFile, setSelectedFile] = useState<File | null>(null)
  const [previewUrl, setPreviewUrl] = useState<string | null>(null)
  const [error, setError] = useState<string | null>(null)
  const fileInputRef = useRef<HTMLInputElement>(null)
  
  // Get fresh user data from the backend
  const { data: userData } = useUser()
  const currentUser = userData?.user || user

  // Validate file
  const validateFile = (file: File): string | null => {
    const maxSize = 5 * 1024 * 1024 // 5MB
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp']

    if (!allowedTypes.includes(file.type)) {
      return 'Please select a valid image file (JPEG, PNG, GIF, or WebP)'
    }

    if (file.size > maxSize) {
      return 'File size must be less than 5MB'
    }

    return null
  }

  // Handle file selection
  const handleFileSelect = (file: File) => {
    const validationError = validateFile(file)
    if (validationError) {
      setError(validationError)
      return
    }

    setError(null)
    setSelectedFile(file)
    onFileSelect(file)

    // Create preview URL
    const url = URL.createObjectURL(file)
    setPreviewUrl(url)

    // Automatically switch to custom avatar
    onUseGravatarChange(false)
  }

  // Handle file input change
  const handleInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0]
    if (file) {
      handleFileSelect(file)
    }
  }

  // Handle drag events
  const handleDrag = useCallback((e: React.DragEvent) => {
    e.preventDefault()
    e.stopPropagation()
  }, [])

  const handleDragIn = useCallback((e: React.DragEvent) => {
    e.preventDefault()
    e.stopPropagation()
    if (e.dataTransfer.items && e.dataTransfer.items.length > 0) {
      setDragActive(true)
    }
  }, [])

  const handleDragOut = useCallback((e: React.DragEvent) => {
    e.preventDefault()
    e.stopPropagation()
    setDragActive(false)
  }, [])

  const handleDrop = useCallback((e: React.DragEvent) => {
    e.preventDefault()
    e.stopPropagation()
    setDragActive(false)

    if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
      const file = e.dataTransfer.files[0]
      handleFileSelect(file)
    }
  }, [])

  // Clear selection
  const clearSelection = () => {
    setSelectedFile(null)
    setPreviewUrl(null)
    setError(null)
    onFileSelect(null)
    if (fileInputRef.current) {
      fileInputRef.current.value = ''
    }
  }

  // Get current avatar URL
  const getCurrentAvatarUrl = () => {
    if (previewUrl) return previewUrl
    if (useGravatar && currentUser) {
      // Use the backend-provided avatar URL which handles Gravatar properly
      return getUserAvatarUrl(currentUser)
    }
    if (currentUser?.avatar_path) return currentUser.avatar_url || `/storage/${currentUser.avatar_path}`
    return null
  }

  const currentAvatarUrl = getCurrentAvatarUrl()

  return (
    <div className={cn("space-y-6", className)}>
      {/* Gravatar Toggle */}
      <div className="flex items-center space-x-3">
        <Switch
          checked={useGravatar}
          onCheckedChange={(checked) => {
            onUseGravatarChange(checked)
            if (checked) {
              clearSelection()
            }
          }}
        />
        <div>
          <Label className="text-sm font-medium">Use Gravatar</Label>
          <p className="text-xs text-muted-foreground">
            Use your Gravatar image based on your email address
          </p>
        </div>
      </div>

      {/* Avatar Preview */}
      <div className="flex flex-col items-center space-y-4">
        <div className="relative">
          {currentAvatarUrl ? (
            <img
              src={currentAvatarUrl}
              alt="Avatar preview"
              className="w-32 h-32 rounded-full object-cover border-4 border-border shadow-lg"
              onError={(e) => {
                // Fallback to default avatar on error
                e.currentTarget.src = '/interface/avatars/default-avatar.png'
              }}
            />
          ) : (
            <div className="w-32 h-32 rounded-full bg-muted border-4 border-border shadow-lg flex items-center justify-center">
              <User className="w-12 h-12 text-muted-foreground" />
            </div>
          )}
          
          {!useGravatar && (
            <Button
              type="button"
              size="sm"
              variant="secondary"
              className="absolute -bottom-2 -right-2 h-8 w-8 rounded-full p-0"
              onClick={() => fileInputRef.current?.click()}
            >
              <Camera className="w-4 h-4" />
            </Button>
          )}
        </div>

        {useGravatar && currentUser?.email && (
          <div className="text-center">
            <p className="text-sm text-muted-foreground">
              Gravatar for <span className="font-medium">{currentUser.email}</span>
            </p>
            <p className="text-xs text-muted-foreground">
              Change your Gravatar at{' '}
              <a 
                href="https://gravatar.com" 
                target="_blank" 
                rel="noopener noreferrer"
                className="text-primary hover:underline"
              >
                gravatar.com
              </a>
            </p>
          </div>
        )}
      </div>

      {/* File Upload Area */}
      {!useGravatar && (
        <div
          className={cn(
            "relative border-2 border-dashed rounded-lg p-6 text-center transition-colors",
            dragActive 
              ? "border-primary bg-primary/5" 
              : "border-muted-foreground/25 hover:border-muted-foreground/50"
          )}
          onDragEnter={handleDragIn}
          onDragLeave={handleDragOut}
          onDragOver={handleDrag}
          onDrop={handleDrop}
        >
          <input
            ref={fileInputRef}
            type="file"
            className="sr-only"
            accept="image/*"
            onChange={handleInputChange}
          />

          <div className="space-y-3">
            <Upload className="w-10 h-10 text-muted-foreground mx-auto" />
            
            <div>
              <p className="text-sm font-medium">
                {selectedFile ? selectedFile.name : 'Upload a custom avatar'}
              </p>
              <p className="text-xs text-muted-foreground">
                Drag and drop or click to select • PNG, JPG, GIF up to 5MB
              </p>
            </div>

            <div className="flex gap-2 justify-center">
              <Button
                type="button"
                variant="outline"
                size="sm"
                onClick={() => fileInputRef.current?.click()}
              >
                Choose File
              </Button>
              
              {selectedFile && (
                <Button
                  type="button"
                  variant="outline"
                  size="sm"
                  onClick={clearSelection}
                >
                  Clear
                </Button>
              )}
            </div>
          </div>
        </div>
      )}

      {/* Error Display */}
      {error && (
        <Alert variant="destructive">
          <AlertCircle className="h-4 w-4" />
          <AlertDescription>{error}</AlertDescription>
        </Alert>
      )}

      {/* File Info */}
      {selectedFile && !error && (
        <div className="text-xs text-muted-foreground text-center">
          Selected: {selectedFile.name} ({(selectedFile.size / 1024 / 1024).toFixed(2)} MB)
        </div>
      )}
    </div>
  )
}