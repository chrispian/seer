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
import { TaskListModal } from '@/components/orchestration/TaskListModal'
import { AgentListModal } from '@/components/orchestration/AgentListModal'
import { TodoManagementModal } from '@/islands/chat/TodoManagementModal'
import { FragmentListModal } from '@/components/fragments/FragmentListModal'
import { ChannelListModal } from '@/components/channels/ChannelListModal'


interface CommandResult {
  success: boolean
  type?: string
  component?: string  // New: direct component specification
  data?: any          // New: clean data object
  message?: string
  error?: string
  panelData?: {       // Legacy: for old YAML commands
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
    // New PHP command system - check for component field first
    if (result.component) {
      return true
    }
    // Legacy - check result.type for YAML commands
    return result.type === 'sprint' || result.type === 'task' || result.type === 'backlog' || result.type === 'agent' || result.type === 'ailogs'
  }

  const renderOrchestrationUI = () => {
    // New PHP command system - direct component routing
    if (result.component && result.data) {
      console.log('New PHP command - component:', result.component, 'data:', result.data)
      
      // Direct component routing based on component field
      switch (result.component) {
        case 'SprintListModal':
          return (
            <SprintListModal
              isOpen={isOpen}
              onClose={() => {
                console.log('SprintListModal onClose called')
                onClose()
              }}
              sprints={result.data}
              onSprintSelect={(sprint) => {
                console.log('Sprint selected:', sprint)
                // TODO: Implement sprint detail view
                alert(`Sprint ${sprint.code}: ${sprint.title}\n\nDetail view not implemented yet.`)
              }}
              onRefresh={() => {
                console.log('Refresh requested')
                // TODO: Implement refresh functionality
                alert('Refresh functionality not implemented yet.')
              }}
            />
          )
        case 'TaskListModal':
          return (
            <TaskListModal
              isOpen={isOpen}
              onClose={() => {
                console.log('TaskListModal onClose called')
                onClose()
              }}
              tasks={result.data}
              onTaskSelect={(task) => {
                console.log('Task selected:', task)
                // TODO: Implement task detail view
                alert(`Task ${task.task_code}: ${task.task_name}\n\nDetail view not implemented yet.`)
              }}
              onRefresh={() => {
                console.log('Task refresh requested')
                // TODO: Implement refresh functionality
                alert('Task refresh functionality not implemented yet.')
              }}
            />
          )
        case 'AgentListModal':
          return (
            <AgentListModal
              isOpen={isOpen}
              onClose={() => {
                console.log('AgentListModal onClose called')
                onClose()
              }}
              agents={result.data}
              onAgentSelect={(agent) => {
                console.log('Agent selected:', agent)
                // TODO: Implement agent detail view
                alert(`Agent ${agent.name} (${agent.slug})\n\nDetail view not implemented yet.`)
              }}
              onRefresh={() => {
                console.log('Agent refresh requested')
                // TODO: Implement refresh functionality
                alert('Agent refresh functionality not implemented yet.')
              }}
            />
          )
        case 'TodoManagementModal':
          return (
            <TodoManagementModal
              isOpen={isOpen}
              onClose={() => {
                console.log('TodoManagementModal onClose called')
                onClose()
              }}
            />
          )
        case 'FragmentListModal':
          return (
            <FragmentListModal
              isOpen={isOpen}
              onClose={() => {
                console.log('FragmentListModal onClose called')
                onClose()
              }}
              fragments={result.data}
              onFragmentSelect={(fragment) => {
                console.log('Fragment selected:', fragment)
                // TODO: Implement fragment navigation (T-FRAG-NAV-01)
                alert(`Fragment Navigation\n\nClicked: ${fragment.id}\n\nTask T-FRAG-NAV-01 required:\n- Navigate to chat session\n- Focus on fragment with ±5 context\n- Lazy loading`)
              }}
              onRefresh={() => {
                console.log('Fragment refresh requested')
                alert('Fragment refresh not implemented yet.')
              }}
            />
          )
        case 'ChannelListModal':
          return (
            <ChannelListModal
              isOpen={isOpen}
              onClose={() => {
                console.log('ChannelListModal onClose called')
                onClose()
              }}
              channels={result.data}
              onChannelSelect={(channel) => {
                console.log('Channel selected:', channel)
                alert(`Channel: ${channel.name}\n\nChannel interaction coming soon.\nThis will allow joining/viewing channel details.`)
              }}
              onRefresh={() => {
                console.log('Channel refresh requested')
                alert('Channel refresh not implemented yet.')
              }}
            />
          )
        case 'HelpModal':
          // For now, use the regular modal for help until we create HelpModal
          return null
        default:
          console.warn('Unknown component:', result.component)
          return null
      }
    }
    
    // Legacy YAML command system fallback
    if (result.type === 'sprint' && result.panelData?.sprints) {
      console.log('Legacy YAML command - using panelData')
      return (
        <SprintListModal
          isOpen={isOpen}
          onClose={onClose}
          sprints={result.panelData.sprints}
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

    // For new PHP help command
    if (result.component === 'HelpModal' && result.data?.commands) {
      const commands = result.data.commands
      const helpContent = commands.map(cmd => 
        `- **${cmd.usage}** – ${cmd.description}`
      ).join('\n')
      
      return `# Available Commands\n\n${helpContent}\n\n**Tip**: Most commands work with or without arguments. Try them out!`
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