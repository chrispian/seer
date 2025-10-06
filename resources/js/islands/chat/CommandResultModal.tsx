import React, { useState } from 'react'
import ReactMarkdown from 'react-markdown'
import remarkGfm from 'remark-gfm'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { ScrollArea } from '@/components/ui/scroll-area'
import { SprintListModal } from '@/components/orchestration/SprintListModal'
import { SprintDetailModal } from '@/components/orchestration/SprintDetailModal'
import { TaskListModal } from '@/components/orchestration/TaskListModal'
import { TaskDetailModal } from '@/components/orchestration/TaskDetailModal'
import { BacklogListModal } from '@/components/orchestration/BacklogListModal'
import { AgentListModal } from '@/components/orchestration/AgentListModal'
import { AiLogsModal } from '@/components/orchestration/AiLogsModal'

interface CommandResult {
  success: boolean
  type: string
  message?: string
  error?: string
  panelData?: {
    message?: string
    action?: string
    sprints?: any[]
    tasks?: any[]
    [key: string]: any
  }
  fragments?: any[]
  shouldResetChat?: boolean
  shouldShowSuccessToast?: boolean
  toastData?: any
}

interface CommandResultModalProps {
  isOpen: boolean
  onClose: () => void
  result: CommandResult | null
  command: string
}

export function CommandResultModal({ 
  isOpen, 
  onClose, 
  result, 
  command 
}: CommandResultModalProps) {
  const [detailView, setDetailView] = useState<{
    type: 'sprint' | 'task' | 'agent' | 'ailog' | null
    data: any
  }>({ type: null, data: null })

  if (!result) {
    return null
  }

  const handleBackToList = () => {
    console.log('CommandResultModal: Going back to list view')
    setDetailView({ type: null, data: null })
  }

  // Debug current state
  console.log('CommandResultModal render - detailView:', detailView, 'result.type:', result?.type)

  // Check if this is an orchestration command that should use rich UI
  const isOrchestrationCommand = () => {
    return result.type === 'sprint' || result.type === 'task' || result.type === 'backlog' || result.type === 'agent' || result.type === 'ailogs'
  }

  const renderOrchestrationUI = () => {
    if (!result.success || !result.panelData) {
      return null
    }

    const { panelData } = result

    // Show detail view if we have one
    if (detailView.type && detailView.data) {
      if (detailView.type === 'sprint') {
        return (
          <SprintDetailModal
            isOpen={isOpen}
            onClose={onClose}
            sprint={detailView.data}
            tasks={detailView.data.tasks || []}
            stats={detailView.data.stats || {}}
            loading={false}
            error={null}
            onBack={handleBackToList}
            onTaskSelect={(task) => {
              setDetailView({ type: 'task', data: task })
            }}
          />
        )
      }

      if (detailView.type === 'task') {
        return (
          <TaskDetailModal
            isOpen={isOpen}
            onClose={onClose}
            task={detailView.data}
            currentAssignment={detailView.data.current_assignment}
            assignments={detailView.data.assignments || []}
            content={detailView.data.content || {}}
            loading={false}
            error={null}
            onBack={handleBackToList}
          />
        )
      }
    }

    if (result.type === 'sprint' && panelData.action === 'list') {
      return (
        <SprintListModal
          isOpen={isOpen}
          onClose={onClose}
          sprints={panelData.sprints || []}
          loading={false}
          error={null}
          onSprintSelect={(sprint) => {
            console.log('CommandResultModal: Sprint selected from list:', sprint)
            setDetailView({ type: 'sprint', data: sprint })
          }}
        />
      )
    }

    if (result.type === 'sprint' && panelData.action === 'detail') {
      return (
        <SprintDetailModal
          isOpen={isOpen}
          onClose={onClose}
          sprint={panelData.sprint || {}}
          tasks={panelData.tasks || []}
          stats={panelData.stats || {}}
          loading={false}
          error={null}
          onTaskSelect={(task) => {
            console.log('Task selected:', task)
            // Could open task detail modal
          }}
        />
      )
    }

    if (result.type === 'task' && panelData.action === 'list') {
      return (
        <TaskListModal
          isOpen={isOpen}
          onClose={onClose}
          tasks={panelData.tasks || []}
          loading={false}
          error={null}
          title={panelData.sprint_filter ? `Tasks - ${panelData.sprint_filter}` : "Task List"}
          currentSprintOnly={!panelData.sprint_filter}
          onTaskSelect={(task) => {
            console.log('CommandResultModal: Task selected from list:', task)
            setDetailView({ type: 'task', data: task })
          }}
        />
      )
    }

    if (result.type === 'task' && panelData.action === 'detail') {
      return (
        <TaskDetailModal
          isOpen={isOpen}
          onClose={onClose}
          task={panelData.task || {}}
          currentAssignment={panelData.current_assignment}
          assignments={panelData.assignments || []}
          content={panelData.content || {}}
          loading={false}
          error={null}
        />
      )
    }

    if (result.type === 'backlog' && panelData.action === 'backlog_list') {
      return (
        <BacklogListModal
          isOpen={isOpen}
          onClose={onClose}
          backlogItems={panelData.tasks || []}
          loading={false}
          error={null}
          onBacklogItemSelect={(item) => {
            console.log('Backlog item selected:', item)
          }}
        />
      )
    }

    if (result.type === 'agent' && panelData.action === 'list') {
      return (
        <AgentListModal
          isOpen={isOpen}
          onClose={onClose}
          agents={panelData.agents || []}
          loading={false}
          error={null}
          onAgentSelect={(agent) => {
            console.log('Agent selected:', agent)
            // Could open agent detail modal or assignment interface
          }}
        />
      )
    }

    if (result.type === 'ailogs' && panelData.action === 'list') {
      return (
        <AiLogsModal
          isOpen={isOpen}
          onClose={onClose}
          logs={panelData.logs || []}
          loading={false}
          error={null}
          onRefresh={() => {
            console.log('AI logs refresh requested')
            // Could trigger a re-fetch of logs
          }}
        />
      )
    }

    return null
  }

  // If this is an orchestration command, render the rich UI instead of the modal
  if (isOrchestrationCommand() && result.success) {
    const orchestrationUI = renderOrchestrationUI()
    if (orchestrationUI) {
      return orchestrationUI
    }
  }

  const getTitle = () => {
    if (result.success) {
      return `Command: /${command}`
    } else {
      return `Command Failed: /${command}`
    }
  }

  const getContent = () => {
    if (!result.success) {
      return result.error || result.message || 'Command execution failed'
    }

    // For help command and other commands with panel data
    if (result.panelData?.message) {
      return result.panelData.message
    }

    // For commands with direct messages
    if (result.message) {
      return result.message
    }

    // For commands with fragments (like search results)
    if (result.fragments && result.fragments.length > 0) {
      const fragmentList = result.fragments.map((fragment, index) => 
        `**Fragment ${index + 1}:** ${fragment.message || fragment.content || 'No content'}`
      ).join('\n\n')
      
      return `Found ${result.fragments.length} result(s):\n\n${fragmentList}`
    }

    // Fallback for successful commands without specific content
    if (result.type === 'clear') {
      return 'Chat cleared successfully. The conversation history has been reset.'
    }
    
    return result.type === 'success' ? 'Command executed successfully' : `Command executed (type: ${result.type})`
  }

  return (
    <Dialog open={isOpen} onOpenChange={onClose}>
      <DialogContent className="max-w-4xl max-h-[80vh] rounded-sm">
        <DialogHeader>
          <DialogTitle className="text-foreground">{getTitle()}</DialogTitle>
          {!result.success && (
            <DialogDescription className="text-muted-foreground">
              An error occurred while executing this command.
            </DialogDescription>
          )}
        </DialogHeader>
        
        <ScrollArea className="max-h-[60vh] w-full rounded-sm border-0 bg-muted/20 p-3">
          <div className="prose prose-sm max-w-none text-foreground">
            <ReactMarkdown 
              remarkPlugins={[remarkGfm]}
              components={{
                // Custom styling for help content
                h1: ({ children, ...props }) => (
                  <h1 className="text-lg font-bold mb-3 text-foreground" {...props}>
                    {children}
                  </h1>
                ),
                h2: ({ children, ...props }) => (
                  <h2 className="text-base font-semibold mb-2 mt-4 text-foreground" {...props}>
                    {children}
                  </h2>
                ),
                code: ({ children, ...props }) => (
                  <code className="bg-muted px-1 py-0.5 rounded-sm text-sm text-foreground" {...props}>
                    {children}
                  </code>
                ),
                pre: ({ children, ...props }) => (
                  <pre className="bg-muted p-2 rounded-sm overflow-x-auto text-foreground" {...props}>
                    {children}
                  </pre>
                ),
                ul: ({ children, ...props }) => (
                  <ul className="list-disc pl-4 mb-3 text-foreground" {...props}>
                    {children}
                  </ul>
                ),
                li: ({ children, ...props }) => (
                  <li className="mb-0.5 text-foreground" {...props}>
                    {children}
                  </li>
                ),
                p: ({ children, ...props }) => (
                  <p className="text-foreground mb-2" {...props}>
                    {children}
                  </p>
                ),
                strong: ({ children, ...props }) => (
                  <strong className="text-foreground font-semibold" {...props}>
                    {children}
                  </strong>
                ),
              }}
            >
              {getContent()}
            </ReactMarkdown>
          </div>
        </ScrollArea>
        
        <div className="flex justify-end gap-2">
          <Button variant="outline" onClick={onClose} className="rounded-sm">
            Close
          </Button>
        </div>
      </DialogContent>
    </Dialog>
  )
}