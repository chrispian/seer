import { useState, useEffect } from 'react'
import type { PageConfig } from '../types'

interface UsePageConfigResult {
  config: PageConfig | null
  loading: boolean
  error: Error | null
  refetch: () => void
}

export function usePageConfig(key: string): UsePageConfigResult {
  const [config, setConfig] = useState<PageConfig | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<Error | null>(null)
  const [refetchTrigger, setRefetchTrigger] = useState(0)

  useEffect(() => {
    let isMounted = true

    async function fetchConfig() {
      setLoading(true)
      setError(null)

      try {
        const response = await fetch(`/api/v2/ui/pages/${key}`)

        if (!response.ok) {
          throw new Error(`Failed to fetch page config: ${response.statusText}`)
        }

        const data: PageConfig = await response.json()

        if (isMounted) {
          setConfig(data)
        }
      } catch (err) {
        if (isMounted) {
          setError(err instanceof Error ? err : new Error('Unknown error'))
        }
      } finally {
        if (isMounted) {
          setLoading(false)
        }
      }
    }

    fetchConfig()

    return () => {
      isMounted = false
    }
  }, [key, refetchTrigger])

  const refetch = () => {
    setRefetchTrigger(prev => prev + 1)
  }

  return { config, loading, error, refetch }
}
