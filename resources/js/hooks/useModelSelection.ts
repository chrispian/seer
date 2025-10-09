import { useState, useEffect } from 'react'

interface ModelSelectionState {
  selectedModel: string
  isLoading: boolean
  error: string | null
}

interface UseModelSelectionProps {
  sessionId?: number | null
  defaultModel?: string
  transformModelForApi?: (value: string) => string
}

const toUiValue = (value: string): string => {
  if (!value) return ''
  if (value.includes('/')) return value

  const [provider, ...rest] = value.split(':')
  if (!provider || rest.length === 0) {
    return value
  }

  return `${provider}/${rest.join(':')}`
}

const toApiValue = (value: string): string => {
  if (!value) return ''
  if (value.includes(':')) return value

  const [provider, ...rest] = value.split('/')
  if (!provider || rest.length === 0) {
    return value
  }

  return `${provider}:${rest.join('/')}`
}

export function useModelSelection({ sessionId, defaultModel = '', transformModelForApi }: UseModelSelectionProps) {
  const [state, setState] = useState<ModelSelectionState>({
    selectedModel: toUiValue(defaultModel),
    isLoading: false,
    error: null,
  })

  // Update selected model when sessionId or defaultModel changes
  useEffect(() => {
    const nextModel = toUiValue(defaultModel)

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

  const updateModel = async (modelValue: string) => {
    const uiValue = toUiValue(modelValue)

    if (!sessionId) {
      // Just update local state if no session
      setState(prev => ({
        ...prev,
        selectedModel: uiValue,
      }))
      return
    }

    const transformedValue = transformModelForApi ? transformModelForApi(modelValue) : modelValue
    const apiValue = toApiValue(transformedValue)

    if (!apiValue) {
      setState(prev => ({
        ...prev,
        selectedModel: uiValue,
        error: 'Invalid model selection',
      }))
      return
    }

    setState(prev => ({
      ...prev,
      isLoading: true,
      error: null,
      selectedModel: uiValue,
    }))

    try {
      const response = await fetch(`/api/chat-sessions/${sessionId}/model`, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
        body: JSON.stringify({ model_value: apiValue }),
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
