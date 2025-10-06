import React from 'react'
import { TaskListModal } from './TaskListModal'
import { Archive } from 'lucide-react'

interface BacklogItem {
  id: string
  task_code: string
  task_name: string
  description?: string
  status: string
  delegation_status: string
  priority?: string
  agent_recommendation?: string
  estimate_text?: string
  tags?: string[]
  created_at: string
  updated_at: string
  has_content?: {
    agent: boolean
    plan: boolean
    context: boolean
    todo: boolean
    summary: boolean
  }
}

interface BacklogListModalProps {
  isOpen: boolean
  onClose: () => void
  backlogItems: BacklogItem[]
  loading?: boolean
  error?: string | null
  onRefresh?: () => void
  onBacklogItemSelect?: (item: BacklogItem) => void
}

export function BacklogListModal({ 
  isOpen, 
  onClose, 
  backlogItems,
  loading = false, 
  error = null,
  onRefresh,
  onBacklogItemSelect
}: BacklogListModalProps) {

  return (
    <TaskListModal
      isOpen={isOpen}
      onClose={onClose}
      tasks={backlogItems}
      loading={loading}
      error={error}
      onRefresh={onRefresh}
      onTaskSelect={onBacklogItemSelect}
      title="Backlog Management"
      showBacklogOnly={true}
    />
  )
}