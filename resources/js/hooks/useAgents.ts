import { useState, useCallback } from 'react'
import { agentsApi, type AgentFilters } from '@/lib/api/agents'
import type { Agent } from '@/types/agent'

export function useAgents() {
  const [agents, setAgents] = useState<Agent[]>([])
  const [isLoading, setIsLoading] = useState(false)
  const [error, setError] = useState<Error | null>(null)

  const fetchAgents = useCallback(async (filters?: AgentFilters) => {
    setIsLoading(true)
    setError(null)
    try {
      const data = await agentsApi.list(filters)
      setAgents(data)
      return data
    } catch (err) {
      const error = err instanceof Error ? err : new Error('Failed to fetch agents')
      setError(error)
      throw error
    } finally {
      setIsLoading(false)
    }
  }, [])

  const createAgent = useCallback(async (data: Omit<Agent, 'id' | 'designation' | 'version'>) => {
    setIsLoading(true)
    setError(null)
    try {
      const newAgent = await agentsApi.create(data)
      setAgents(prev => [...prev, newAgent])
      return newAgent
    } catch (err) {
      const error = err instanceof Error ? err : new Error('Failed to create agent')
      setError(error)
      throw error
    } finally {
      setIsLoading(false)
    }
  }, [])

  const updateAgent = useCallback(async (id: string, data: Partial<Agent>) => {
    setIsLoading(true)
    setError(null)
    try {
      const updatedAgent = await agentsApi.update(id, data)
      setAgents(prev => prev.map(a => a.id === id ? updatedAgent : a))
      return updatedAgent
    } catch (err) {
      const error = err instanceof Error ? err : new Error('Failed to update agent')
      setError(error)
      throw error
    } finally {
      setIsLoading(false)
    }
  }, [])

  const deleteAgent = useCallback(async (id: string) => {
    setIsLoading(true)
    setError(null)
    try {
      await agentsApi.delete(id)
      setAgents(prev => prev.filter(a => a.id !== id))
    } catch (err) {
      const error = err instanceof Error ? err : new Error('Failed to delete agent')
      setError(error)
      throw error
    } finally {
      setIsLoading(false)
    }
  }, [])

  const generateDesignation = useCallback(async () => {
    try {
      return await agentsApi.generateDesignation()
    } catch (err) {
      const error = err instanceof Error ? err : new Error('Failed to generate designation')
      setError(error)
      throw error
    }
  }, [])

  const uploadAvatar = useCallback(async (id: string, file: File) => {
    setIsLoading(true)
    setError(null)
    try {
      const updatedAgent = await agentsApi.uploadAvatar(id, file)
      setAgents(prev => prev.map(a => a.id === id ? updatedAgent : a))
      return updatedAgent
    } catch (err) {
      const error = err instanceof Error ? err : new Error('Failed to upload avatar')
      setError(error)
      throw error
    } finally {
      setIsLoading(false)
    }
  }, [])

  return {
    agents,
    isLoading,
    error,
    fetchAgents,
    createAgent,
    updateAgent,
    deleteAgent,
    generateDesignation,
    uploadAvatar,
  }
}
