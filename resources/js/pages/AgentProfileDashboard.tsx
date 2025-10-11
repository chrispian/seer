import React from 'react'
import { useState, useEffect } from 'react'
import { Button } from '@/components/ui/button'
import { Plus, Bot } from 'lucide-react'
import { AgentProfileMiniCard } from '@/components/agents/AgentProfileMiniCard'
import { AgentProfileEditor } from '@/components/agents/AgentProfileEditor'
import type { AgentProfile } from '@/types/agent-profile'
import { useAgentProfiles } from '@/hooks/useAgentProfiles'
import { useToast } from '@/hooks/useToast'
import { ToastContainer } from '@/components/ui/ToastContainer'

interface AgentProfileDashboardProps {
  initialAgents?: AgentProfile[]
}

export function AgentProfileDashboard({ initialAgents }: AgentProfileDashboardProps) {
  const { agents: hookAgents, isLoading: hookLoading, fetchAgents, createAgent, updateAgent, deleteAgent, duplicateAgent } = useAgentProfiles()
  const { success, error: showError, toasts, removeToast } = useToast()
  const [selectedAgent, setSelectedAgent] = useState<AgentProfile | null>(null)
  const [isCreating, setIsCreating] = useState(false)

  const agents = initialAgents || hookAgents
  const isLoading = initialAgents ? false : hookLoading

  useEffect(() => {
    if (!initialAgents) {
      fetchAgents().catch(err => {
        showError('Failed to load agent profiles', err.message)
      })
    }
  }, [initialAgents, fetchAgents, showError])

  const handleCreateClick = () => {
    setIsCreating(true)
  }

  const handleAgentClick = (agent: AgentProfile) => {
    setSelectedAgent(agent)
  }

  const handleEdit = (agent: AgentProfile) => {
    setSelectedAgent(agent)
  }

  const handleDelete = async (agent: AgentProfile) => {
    if (!agent.id) {
      return
    }

    if (!confirm(`Are you sure you want to delete "${agent.name}"?`)) {
      return
    }

    try {
      await deleteAgent(agent.id)
      success('Agent profile deleted', `"${agent.name}" has been deleted`)
      setSelectedAgent(null)
    } catch (err) {
      showError('Failed to delete agent profile', err instanceof Error ? err.message : 'Unknown error')
    }
  }

  const handleDuplicate = async (agent: AgentProfile) => {
    if (!agent.id) {
      return
    }

    try {
      const duplicated = await duplicateAgent(agent.id)
      success('Agent profile duplicated', `Created "${duplicated.name}"`)
    } catch (err) {
      showError('Failed to duplicate agent profile', err instanceof Error ? err.message : 'Unknown error')
    }
  }

  const handleSave = async (agentData: Partial<AgentProfile>) => {
    try {
      if (agentData.id) {
        await updateAgent(agentData.id, agentData)
        success('Agent profile updated', `"${agentData.name}" has been updated`)
      } else {
        const created = await createAgent(agentData as Omit<AgentProfile, 'id'>)
        success('Agent profile created', `"${created.name}" has been created`)
      }
      setSelectedAgent(null)
      setIsCreating(false)
    } catch (err) {
      showError(
        agentData.id ? 'Failed to update agent profile' : 'Failed to create agent profile',
        err instanceof Error ? err.message : 'Unknown error'
      )
    }
  }

  return (
    <>
      <div className="flex flex-col h-full overflow-hidden">
        <div className="flex-shrink-0 px-6 pt-6 pb-4 border-b">
          <div className="flex justify-between items-start">
            <div>
              <h1 className="text-3xl font-bold tracking-tight">Agent Profile Management</h1>
              <p className="text-muted-foreground mt-1">
                Manage AI agent profile configurations and capabilities
              </p>
            </div>
            <Button onClick={handleCreateClick} size="lg">
              <Plus className="mr-2 h-4 w-4" />
              Create New Profile
            </Button>
          </div>
        </div>

        <div className="flex-1 overflow-y-auto px-6 py-4">
          {isLoading ? (
            <SkeletonGrid />
          ) : agents.length === 0 ? (
            <EmptyState onCreateClick={handleCreateClick} />
          ) : (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
              {agents.map(agent => (
                <AgentProfileMiniCard
                  key={agent.id || agent.slug}
                  agent={agent}
                  onClick={handleAgentClick}
                  onEdit={handleEdit}
                  onDelete={handleDelete}
                  onDuplicate={handleDuplicate}
                />
              ))}
            </div>
          )}
        </div>
      </div>

      {selectedAgent && (
        <AgentProfileEditor
          agent={selectedAgent}
          isOpen={!!selectedAgent}
          onClose={() => setSelectedAgent(null)}
          onSave={handleSave}
          onDelete={handleDelete}
          onDuplicate={handleDuplicate}
        />
      )}

      {isCreating && (
        <AgentProfileEditor
          isOpen={isCreating}
          onClose={() => setIsCreating(false)}
          onSave={handleSave}
        />
      )}
      
      <ToastContainer toasts={toasts} onRemove={removeToast} />
    </>
  )
}

function SkeletonGrid() {
  return (
    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      {[1, 2, 3, 4, 5, 6, 7, 8].map(i => (
        <div key={i} className="border rounded-lg p-4 space-y-3 animate-pulse">
          <div className="h-4 bg-gray-200 rounded w-3/4" />
          <div className="h-3 bg-gray-200 rounded w-1/2" />
          <div className="flex gap-2">
            <div className="h-6 bg-gray-200 rounded w-16" />
            <div className="h-6 bg-gray-200 rounded w-16" />
          </div>
          <div className="h-16 bg-gray-200 rounded" />
          <div className="h-4 bg-gray-200 rounded w-24" />
        </div>
      ))}
    </div>
  )
}

function EmptyState({ onCreateClick }: { onCreateClick: () => void }) {
  return (
    <div className="flex flex-col items-center justify-center py-16 text-center">
      <Bot className="h-16 w-16 text-muted-foreground mb-4" />
      <h3 className="text-lg font-semibold mb-2">No agent profiles found</h3>
      <p className="text-muted-foreground mb-6 max-w-sm">
        Create your first agent profile to get started managing AI agent configurations.
      </p>
      <Button onClick={onCreateClick} size="lg">
        <Plus className="mr-2 h-4 w-4" />
        Create Agent Profile
      </Button>
    </div>
  )
}
