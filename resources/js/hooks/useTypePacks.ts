import { useState, useCallback } from 'react'
import { typePacksApi, type TypePack, type CreateTypePackData, type UpdateTypePackData } from '@/lib/api/typePacks'

export function useTypePacks() {
  const [typePacks, setTypePacks] = useState<TypePack[]>([])
  const [isLoading, setIsLoading] = useState(false)
  const [error, setError] = useState<Error | null>(null)

  const fetchTypePacks = useCallback(async () => {
    setIsLoading(true)
    setError(null)
    try {
      const data = await typePacksApi.list()
      setTypePacks(data)
      return data
    } catch (err) {
      const error = err instanceof Error ? err : new Error('Failed to fetch type packs')
      setError(error)
      throw error
    } finally {
      setIsLoading(false)
    }
  }, [])

  const getTypePack = useCallback(async (slug: string) => {
    setIsLoading(true)
    setError(null)
    try {
      const data = await typePacksApi.get(slug)
      return data
    } catch (err) {
      const error = err instanceof Error ? err : new Error('Failed to fetch type pack')
      setError(error)
      throw error
    } finally {
      setIsLoading(false)
    }
  }, [])

  const createTypePack = useCallback(async (data: CreateTypePackData) => {
    setIsLoading(true)
    setError(null)
    try {
      const newTypePack = await typePacksApi.create(data)
      setTypePacks(prev => [...prev, newTypePack])
      return newTypePack
    } catch (err) {
      const error = err instanceof Error ? err : new Error('Failed to create type pack')
      setError(error)
      throw error
    } finally {
      setIsLoading(false)
    }
  }, [])

  const updateTypePack = useCallback(async (slug: string, data: UpdateTypePackData) => {
    setIsLoading(true)
    setError(null)
    try {
      const updatedTypePack = await typePacksApi.update(slug, data)
      setTypePacks(prev => prev.map(t => t.slug === slug ? updatedTypePack : t))
      return updatedTypePack
    } catch (err) {
      const error = err instanceof Error ? err : new Error('Failed to update type pack')
      setError(error)
      throw error
    } finally {
      setIsLoading(false)
    }
  }, [])

  const deleteTypePack = useCallback(async (slug: string) => {
    setIsLoading(true)
    setError(null)
    try {
      await typePacksApi.delete(slug)
      setTypePacks(prev => prev.filter(t => t.slug !== slug))
    } catch (err) {
      const error = err instanceof Error ? err : new Error('Failed to delete type pack')
      setError(error)
      throw error
    } finally {
      setIsLoading(false)
    }
  }, [])

  const getTemplates = useCallback(async () => {
    setIsLoading(true)
    setError(null)
    try {
      const templates = await typePacksApi.getTemplates()
      return templates
    } catch (err) {
      const error = err instanceof Error ? err : new Error('Failed to fetch templates')
      setError(error)
      throw error
    } finally {
      setIsLoading(false)
    }
  }, [])

  const createFromTemplate = useCallback(async (template: string, customName?: string) => {
    setIsLoading(true)
    setError(null)
    try {
      const newTypePack = await typePacksApi.createFromTemplate(template, customName)
      setTypePacks(prev => [...prev, newTypePack])
      return newTypePack
    } catch (err) {
      const error = err instanceof Error ? err : new Error('Failed to create type pack from template')
      setError(error)
      throw error
    } finally {
      setIsLoading(false)
    }
  }, [])

  const validateSchema = useCallback(async (slug: string, schema: Record<string, any>) => {
    setIsLoading(true)
    setError(null)
    try {
      const validation = await typePacksApi.validateSchema(slug, schema)
      return validation
    } catch (err) {
      const error = err instanceof Error ? err : new Error('Failed to validate schema')
      setError(error)
      throw error
    } finally {
      setIsLoading(false)
    }
  }, [])

  const refreshCache = useCallback(async (slug: string) => {
    setIsLoading(true)
    setError(null)
    try {
      await typePacksApi.refreshCache(slug)
    } catch (err) {
      const error = err instanceof Error ? err : new Error('Failed to refresh cache')
      setError(error)
      throw error
    } finally {
      setIsLoading(false)
    }
  }, [])

  const getFragments = useCallback(async (slug: string) => {
    setIsLoading(true)
    setError(null)
    try {
      const fragments = await typePacksApi.getFragments(slug)
      return fragments
    } catch (err) {
      const error = err instanceof Error ? err : new Error('Failed to fetch fragments')
      setError(error)
      throw error
    } finally {
      setIsLoading(false)
    }
  }, [])

  const getStats = useCallback(async () => {
    setIsLoading(true)
    setError(null)
    try {
      const stats = await typePacksApi.getStats()
      return stats
    } catch (err) {
      const error = err instanceof Error ? err : new Error('Failed to fetch stats')
      setError(error)
      throw error
    } finally {
      setIsLoading(false)
    }
  }, [])

  return {
    typePacks,
    isLoading,
    error,
    fetchTypePacks,
    getTypePack,
    createTypePack,
    updateTypePack,
    deleteTypePack,
    getTemplates,
    createFromTemplate,
    validateSchema,
    refreshCache,
    getFragments,
    getStats,
  }
}
