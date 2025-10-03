import { useState, useEffect } from 'react'

interface ModelSelectionState {
  selectedModel: string
  isLoading: boolean
  error: string | null
}

interface UseModelSelectionProps {
  sessionId?: number | null
  defaultModel?: string
}

export function useModelSelection({ sessionId, defaultModel = '' }: UseModelSelectionProps) {
  const [state, setState] = useState<ModelSelectionState>({
    selectedModel: defaultModel,
    isLoading: false,
    error: null,
  })

  // Update selected model when sessionId or defaultModel changes
  useEffect(() => {
    setState(prev => ({
      ...prev,
      selectedModel: defaultModel,
    }))
  }, [defaultModel, sessionId])

  const updateModel = async (modelValue: string) => {
    if (!sessionId) {
      // Just update local state if no session
      setState(prev => ({
        ...prev,
        selectedModel: modelValue,
      }))
      return
    }

    setState(prev => ({
      ...prev,
      isLoading: true,
      error: null,
    }))

    try {
      const response = await fetch(`/api/chat-sessions/${sessionId}/model`, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
        body: JSON.stringify({ model_value: modelValue }),
      })

      const result = await response.json()

      if (result.success) {
        setState(prev => ({
          ...prev,
          selectedModel: modelValue,
          isLoading: false,
        }))
      } else {
        setState(prev => ({
          ...prev,
          error: 'Failed to update model',
          isLoading: false,
        }))
      }
    } catch (error) {
      setState(prev => ({
        ...prev,
        error: 'Network error',
        isLoading: false,
      }))
      console.error('Error updating model:', error)
    }
  }

  return {
    selectedModel: state.selectedModel,
    isLoading: state.isLoading,
    error: state.error,
    updateModel,
  }
}