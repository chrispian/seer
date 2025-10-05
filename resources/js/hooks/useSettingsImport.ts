import { useState } from 'react'
import { useToast } from './useToast'

interface ImportProgress {
  uploading: boolean
  processing: boolean
  completed: boolean
}

interface ImportResponse {
  success: boolean
  message: string
  changes?: Record<string, any>
  error?: string
}

export function useSettingsImport() {
  const [progress, setProgress] = useState<ImportProgress>({
    uploading: false,
    processing: false,
    completed: false
  })
  const [selectedFile, setSelectedFile] = useState<File | null>(null)
  const [previewData, setPreviewData] = useState<any>(null)
  const [error, setError] = useState<string | null>(null)
  
  const { showToast } = useToast()
  
  const validateFile = (file: File): boolean => {
    setError(null)
    
    // Check file type
    if (!file.type.includes('json') && !file.name.endsWith('.json')) {
      setError('Only JSON files are supported')
      return false
    }
    
    // Check file size (1MB limit)
    if (file.size > 1024 * 1024) {
      setError('File must be smaller than 1MB')
      return false
    }
    
    return true
  }
  
  const previewSettings = async (file: File): Promise<boolean> => {
    if (!validateFile(file)) {
      return false
    }
    
    try {
      const content = await file.text()
      const data = JSON.parse(content)
      
      // Basic validation of settings structure
      if (!data.settings && !data.profile) {
        setError('Invalid settings file format')
        return false
      }
      
      setPreviewData(data)
      setSelectedFile(file)
      return true
      
    } catch (err) {
      setError('Invalid JSON format')
      return false
    }
  }
  
  const importSettings = async (): Promise<boolean> => {
    if (!selectedFile) {
      setError('No file selected')
      return false
    }
    
    setProgress({ uploading: true, processing: false, completed: false })
    setError(null)
    
    try {
      const formData = new FormData()
      formData.append('file', selectedFile)
      
      // Get CSRF token
      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
      
      const response = await fetch('/settings/import', {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': csrfToken || '',
        },
        body: formData
      })
      
      setProgress({ uploading: false, processing: true, completed: false })
      
      const result: ImportResponse = await response.json()
      
      if (result.success) {
        setProgress({ uploading: false, processing: false, completed: true })
        showToast(result.message, 'success')
        
        // Reload page to reflect changes
        setTimeout(() => {
          window.location.reload()
        }, 1500)
        
        return true
      } else {
        setError(result.error || 'Import failed')
        showToast(result.error || 'Import failed', 'error')
        return false
      }
      
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Import failed')
      showToast('Import failed', 'error')
      return false
    } finally {
      setProgress({ uploading: false, processing: false, completed: false })
    }
  }
  
  const reset = () => {
    setSelectedFile(null)
    setPreviewData(null)
    setError(null)
    setProgress({ uploading: false, processing: false, completed: false })
  }
  
  return {
    progress,
    selectedFile,
    previewData,
    error,
    validateFile,
    previewSettings,
    importSettings,
    reset,
    isLoading: progress.uploading || progress.processing
  }
}