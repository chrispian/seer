import React from 'react'
import { useState, useEffect } from 'react'
import { Button } from '@/components/ui/button'
import { Plus, Bot } from 'lucide-react'
import { AgentMiniCard } from '@/components/agents/AgentMiniCard'
import { AgentEditor } from '@/components/agents/AgentEditor'
import type { Agent } from '@/types/agent'
import type { AgentProfile } from '@/types/agent-profile'
import { useAgents } from '@/hooks/useAgents'
import { toast } from 'sonner'
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from '@/components/ui/alert-dialog'

interface AgentDashboardProps {
  initialAgents?: Agent[]
  agentProfiles: AgentProfile[]
}

export function AgentDashboard({ initialAgents, agentProfiles }: AgentDashboardProps) {
  const { agents: hookAgents, isLoading: hookLoading, fetchAgents, createAgent, updateAgent, deleteAgent, generateDesignation, uploadAvatar } = useAgents()
  const [selectedAgent, setSelectedAgent] = useState<Agent | null>(null)
  const [isCreating, setIsCreating] = useState(false)
  const [deleteConfirm, setDeleteConfirm] = useState<Agent | null>(null)
  const [localAgents, setLocalAgents] = useState<Agent[]>(initialAgents || [])

  const agents = hookAgents.length > 0 ? hookAgents : localAgents
  const isLoading = hookLoading

  useEffect(() => {
    if (initialAgents) {
      setLocalAgents(initialAgents)
    }
  }, [initialAgents])

  useEffect(() => {
    fetchAgents().catch(err => {
      toast.error('Failed to load agents', { description: err.message })
    })
  }, [])

  const handleCreateClick = () => {
    setIsCreating(true)
  }

  const handleAgentClick = (agent: Agent) => {
    setSelectedAgent(agent)
  }

  const handleEdit = (agent: Agent) => {
    setSelectedAgent(agent)
  }

  const handleDelete = (agent: Agent) => {
    setDeleteConfirm(agent)
  }

  const confirmDelete = async () => {
    if (!deleteConfirm?.id) {
      return
    }

    try {
      await deleteAgent(deleteConfirm.id)
      toast.success('Agent deleted', { description: `"${deleteConfirm.name}" has been deleted` })
      setSelectedAgent(null)
      setDeleteConfirm(null)
      await fetchAgents()
    } catch (err) {
      toast.error('Failed to delete agent', { 
        description: err instanceof Error ? err.message : 'Unknown error' 
      })
    }
  }

  const handleSave = async (agentData: Partial<Agent>) => {
    try {
      if (agentData.id) {
        await updateAgent(agentData.id, agentData)
        toast.success('Agent updated', { description: `"${agentData.name}" has been updated` })
      } else {
        const created = await createAgent(agentData as Omit<Agent, 'id' | 'designation' | 'version'>)
        toast.success('Agent created', { 
          description: `"${created.name}" (${created.designation}) has been created` 
        })
      }
      setSelectedAgent(null)
      setIsCreating(false)
      await fetchAgents()
    } catch (err) {
      toast.error(
        agentData.id ? 'Failed to update agent' : 'Failed to create agent',
        { description: err instanceof Error ? err.message : 'Unknown error' }
      )
    }
  }

  const handleAvatarUpload = async (file: File) => {
    if (!selectedAgent?.id) {
      return
    }

    try {
      await uploadAvatar(selectedAgent.id, file)
      toast.success('Avatar uploaded', { description: 'Agent avatar has been updated' })
      await fetchAgents()
    } catch (err) {
      toast.error('Failed to upload avatar', { 
        description: err instanceof Error ? err.message : 'Unknown error' 
      })
      throw err
    }
  }

  return (
    <>
      <div className="flex flex-col h-full overflow-hidden">
        <div className="flex-shrink-0 px-6 pt-6 pb-4 border-b">
          <div className="flex justify-between items-start">
            <div>
              <h1 className="text-3xl font-bold tracking-tight">Agent Management</h1>
              <p className="text-muted-foreground mt-1">
                Manage AI agent instances and configurations
              </p>
            </div>
            <Button onClick={handleCreateClick} size="lg" disabled={agentProfiles.length === 0}>
              <Plus className="mr-2 h-4 w-4" />
              Create New Agent
            </Button>
          </div>
        </div>

        <div className="flex-1 overflow-y-auto px-6 py-4">
          {isLoading ? (
            <SkeletonGrid />
          ) : agents.length === 0 ? (
            <EmptyState onCreateClick={handleCreateClick} hasProfiles={agentProfiles.length > 0} />
          ) : (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
              {agents.map(agent => (
                <AgentMiniCard
                  key={agent.id || agent.designation}
                  agent={agent}
                  onClick={handleAgentClick}
                  onEdit={handleEdit}
                  onDelete={handleDelete}
                />
              ))}
            </div>
          )}
        </div>
      </div>

      {selectedAgent && (
        <AgentEditor
          agent={selectedAgent}
          agentProfiles={agentProfiles}
          isOpen={!!selectedAgent}
          onClose={() => setSelectedAgent(null)}
          onSave={handleSave}
          onDelete={handleDelete}
          onGenerateDesignation={generateDesignation}
          onAvatarUpload={handleAvatarUpload}
        />
      )}

      {isCreating && (
        <AgentEditor
          agentProfiles={agentProfiles}
          isOpen={isCreating}
          onClose={() => setIsCreating(false)}
          onSave={handleSave}
          onGenerateDesignation={generateDesignation}
        />
      )}
      
      <AlertDialog open={!!deleteConfirm} onOpenChange={() => setDeleteConfirm(null)}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Delete Agent?</AlertDialogTitle>
            <AlertDialogDescription>
              Are you sure you want to delete "{deleteConfirm?.name}" ({deleteConfirm?.designation})? This action cannot be undone.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>Cancel</AlertDialogCancel>
            <AlertDialogAction onClick={confirmDelete} className="bg-destructive text-destructive-foreground hover:bg-destructive/90">
              Delete
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
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
        </div>
      ))}
    </div>
  )
}

function EmptyState({ onCreateClick, hasProfiles }: { onCreateClick: () => void; hasProfiles: boolean }) {
  return (
    <div className="flex flex-col items-center justify-center py-16 text-center">
      <Bot className="h-16 w-16 text-muted-foreground mb-4" />
      <h3 className="text-lg font-semibold mb-2">No agents found</h3>
      <p className="text-muted-foreground mb-6 max-w-sm">
        {hasProfiles 
          ? 'Create your first agent instance to get started.'
          : 'You need to create an Agent Profile first before creating agents.'}
      </p>
      {hasProfiles && (
        <Button onClick={onCreateClick} size="lg">
          <Plus className="mr-2 h-4 w-4" />
          Create Agent
        </Button>
      )}
    </div>
  )
}
