import { useState, useCallback } from 'react'
import { agentProfilesApi, type AgentProfileFilters } from '@/lib/api/agent-profiles'
import type { AgentProfile } from '@/types/agent-profile'

export function useAgentProfiles() {
  const [agents, setAgents] = useState<AgentProfile[]>([])
  const [isLoading, setIsLoading] = useState(false)
  const [error, setError] = useState<Error | null>(null)

  const fetchAgents = useCallback(async (filters?: AgentProfileFilters) => {
    setIsLoading(true)
    setError(null)
    try {
      const data = await agentProfilesApi.list(filters)
      setAgents(data)
      return data
    } catch (err) {
      const error = err instanceof Error ? err : new Error('Failed to fetch agent profiles')
      setError(error)
      throw error
    } finally {
      setIsLoading(false)
    }
  }, [])

  const createAgent = useCallback(async (data: Omit<AgentProfile, 'id'>) => {
    setIsLoading(true)
    setError(null)
    try {
      const newAgent = await agentProfilesApi.create(data)
      setAgents(prev => [...prev, newAgent])
      return newAgent
    } catch (err) {
      const error = err instanceof Error ? err : new Error('Failed to create agent profile')
      setError(error)
      throw error
    } finally {
      setIsLoading(false)
    }
  }, [])

  const updateAgent = useCallback(async (id: string, data: Partial<AgentProfile>) => {
    setIsLoading(true)
    setError(null)
    try {
      const updatedAgent = await agentProfilesApi.update(id, data)
      setAgents(prev => prev.map(a => a.id === id ? updatedAgent : a))
      return updatedAgent
    } catch (err) {
      const error = err instanceof Error ? err : new Error('Failed to update agent profile')
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
      await agentProfilesApi.delete(id)
      setAgents(prev => prev.filter(a => a.id !== id))
    } catch (err) {
      const error = err instanceof Error ? err : new Error('Failed to delete agent profile')
      setError(error)
      throw error
    } finally {
      setIsLoading(false)
    }
  }, [])

  const duplicateAgent = useCallback(async (id: string) => {
    setIsLoading(true)
    setError(null)
    try {
      const duplicated = await agentProfilesApi.duplicate(id)
      setAgents(prev => [...prev, duplicated])
      return duplicated
    } catch (err) {
      const error = err instanceof Error ? err : new Error('Failed to duplicate agent profile')
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
    duplicateAgent,
  }
}
