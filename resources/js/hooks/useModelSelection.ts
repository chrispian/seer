import { useState, useEffect } from 'react'

interface ModelSelectionState {
  selectedModel: string
  isLoading: boolean
  error: string | null
}

interface UseModelSelectionProps {
  sessionId?: number | null
  defaultModel?: string | number
  transformModelForApi?: (model: string) => { modelKey: string; providerSlug: string }
}

export function useModelSelection({ sessionId, defaultModel = '', transformModelForApi }: UseModelSelectionProps) {
  const [state, setState] = useState<ModelSelectionState>({
    selectedModel: String(defaultModel || ''),
    isLoading: false,
    error: null,
  })

  // Update selected model when sessionId or defaultModel changes
  useEffect(() => {
    const nextModel = String(defaultModel || '')

    setState(prev => {
      if (prev.selectedModel === nextModel) {
        return prev
      }

      return {
        ...prev,
        selectedModel: nextModel,
      }
    })
  }, [defaultModel, sessionId])

  const updateModel = async (aiModelId: number) => {
    if (!sessionId) {
      // Just update local state if no session
      setState(prev => ({
        ...prev,
        selectedModel: String(aiModelId),
      }))
      return
    }

    setState(prev => ({
      ...prev,
      isLoading: true,
      error: null,
      selectedModel: String(aiModelId),
    }))

    try {
      const response = await fetch(`/api/chat-sessions/${sessionId}/model`, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
        body: JSON.stringify({ ai_model_id: aiModelId }),
      })

      const result = await response.json()

      if (result.success) {
        setState(prev => ({
          ...prev,
          isLoading: false,
        }))
      } else {
        setState(prev => ({
          ...prev,
          error: result.error || 'Failed to update model',
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
