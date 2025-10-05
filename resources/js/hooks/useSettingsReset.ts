import { useState } from 'react'
import { useToast } from './useToast'

interface ResetState {
  generating: boolean
  resetting: boolean
  completed: boolean
}

interface ResetResponse {
  success: boolean
  message: string
  reset_sections?: string[]
  error?: string
}

interface TokenResponse {
  success: boolean
  token: string
  expires_in: number
}

export function useSettingsReset() {
  const [state, setState] = useState<ResetState>({
    generating: false,
    resetting: false,
    completed: false
  })
  const [selectedSections, setSelectedSections] = useState<string[]>([])
  const [confirmationToken, setConfirmationToken] = useState<string | null>(null)
  const [tokenExpires, setTokenExpires] = useState<Date | null>(null)
  const [error, setError] = useState<string | null>(null)
  
  const { showToast } = useToast()
  
  const generateResetToken = async (): Promise<boolean> => {
    setState(prev => ({ ...prev, generating: true }))
    setError(null)
    
    try {
      // Get CSRF token
      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
      
      const response = await fetch('/settings/reset-token', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken || '',
        }
      })
      
      const result: TokenResponse = await response.json()
      
      if (result.success) {
        setConfirmationToken(result.token)
        setTokenExpires(new Date(Date.now() + result.expires_in * 1000))
        return true
      } else {
        setError('Failed to generate confirmation token')
        return false
      }
      
    } catch (err) {
      setError('Failed to generate confirmation token')
      return false
    } finally {
      setState(prev => ({ ...prev, generating: false }))
    }
  }
  
  const resetSettings = async (): Promise<boolean> => {
    if (!confirmationToken) {
      setError('No confirmation token available')
      return false
    }
    
    if (selectedSections.length === 0) {
      setError('Please select at least one section to reset')
      return false
    }
    
    setState(prev => ({ ...prev, resetting: true }))
    setError(null)
    
    try {
      // Get CSRF token
      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
      
      const response = await fetch('/settings/reset', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken || '',
        },
        body: JSON.stringify({
          sections: selectedSections,
          confirmation_token: confirmationToken
        })
      })
      
      const result: ResetResponse = await response.json()
      
      if (result.success) {
        setState(prev => ({ ...prev, resetting: false, completed: true }))
        showToast(`Reset ${result.reset_sections?.join(', ')} settings`, 'success')
        
        // Reload page to reflect changes
        setTimeout(() => {
          window.location.reload()
        }, 1500)
        
        return true
      } else {
        setError(result.error || 'Reset failed')
        showToast(result.error || 'Reset failed', 'error')
        return false
      }
      
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Reset failed')
      showToast('Reset failed', 'error')
      return false
    } finally {
      setState(prev => ({ ...prev, resetting: false }))
    }
  }
  
  const toggleSection = (section: string) => {
    setSelectedSections(prev => 
      prev.includes(section)
        ? prev.filter(s => s !== section)
        : [...prev, section]
    )
  }
  
  const isTokenValid = (): boolean => {
    return !!(confirmationToken && tokenExpires && new Date() < tokenExpires)
  }
  
  const reset = () => {
    setSelectedSections([])
    setConfirmationToken(null)
    setTokenExpires(null)
    setError(null)
    setState({ generating: false, resetting: false, completed: false })
  }
  
  return {
    state,
    selectedSections,
    confirmationToken,
    tokenExpires,
    error,
    generateResetToken,
    resetSettings,
    toggleSection,
    isTokenValid,
    reset,
    isLoading: state.generating || state.resetting
  }
}