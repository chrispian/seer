import { useState } from 'react'
import type { ActionConfig, ActionResult } from '../types'
import { actionDispatcher } from '../ActionDispatcher'
import { useToast } from '@/hooks/useToast'

interface UseActionResult {
  execute: (action: ActionConfig, context?: Record<string, any>) => Promise<ActionResult>
  loading: boolean
  error: Error | null
}

export function useAction(): UseActionResult {
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<Error | null>(null)
  const toast = useToast()

  const execute = async (
    action: ActionConfig,
    context: Record<string, any> = {}
  ): Promise<ActionResult> => {
    setLoading(true)
    setError(null)

    try {
      const result = await actionDispatcher.execute(action, context)

      if (result.success && result.message) {
        toast.success('Success', result.message)
      } else if (!result.success && result.message) {
        toast.error('Error', result.message)
      }

      return result
    } catch (err) {
      const error = err instanceof Error ? err : new Error('Action failed')
      setError(error)
      toast.error('Error', error.message)
      return {
        success: false,
        message: error.message,
      }
    } finally {
      setLoading(false)
    }
  }

  return { execute, loading, error }
}
