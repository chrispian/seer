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
import { AgentProfileGridModal } from '@/components/orchestration/AgentProfileGridModal'
import { BacklogListModal } from '@/components/orchestration/BacklogListModal'
import { TodoManagementModal } from '@/islands/chat/TodoManagementModal'
import { FragmentListModal } from '@/components/fragments/FragmentListModal'
import { ChannelListModal } from '@/components/channels/ChannelListModal'
import { RoutingInfoModal } from '@/components/routing/RoutingInfoModal'
import { AgentProfileDashboard } from '@/pages/AgentProfileDashboard'
import { AgentDashboard } from '@/pages/AgentDashboard'
import { TypeManagementModal } from '@/components/types/TypeManagementModal'
import { UnifiedListModal } from '@/components/unified/UnifiedListModal'


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
  const [detailView, setDetailView] = useState<CommandResult | null>(null)
  const [isLoadingDetail, setIsLoadingDetail] = useState(false)

  if (!result) {
    return null
  }

  const handleBackToList = () => {
    console.log('CommandResultModal: Going back to list view')
    setDetailView(null)
  }

  const executeDetailCommand = async (detailCommand: string) => {
    console.log('Executing detail command:', detailCommand)
    setIsLoadingDetail(true)
    
    try {
      const csrf = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || ''
      const response = await fetch('/api/commands/execute', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrf
        },
        body: JSON.stringify({ command: detailCommand })
      })

      const detailResult = await response.json()
      
      if (detailResult.success) {
        console.log('Detail command result:', detailResult)
        setDetailView(detailResult)
      } else {
        console.error('Detail command failed:', detailResult)
        alert(detailResult.error || 'Failed to load details')
      }
    } catch (error) {
      console.error('Detail command execution failed:', error)
      alert('Failed to load details')
    } finally {
      setIsLoadingDetail(false)
    }
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
    return result.type === 'sprint' || result.type === 'task' || result.type === 'backlog' || result.type === 'agent' || result.type === 'agent-profile' || result.type === 'ailogs'
  }

  const renderOrchestrationUI = (currentResult: CommandResult = result, isDetail = false) => {
    // New PHP command system - direct component routing
    if (currentResult.component) {
      console.log('New PHP command - component:', currentResult.component, 'data:', currentResult.data, 'isDetail:', isDetail)
      console.log('About to switch on component:', currentResult.component)
      console.log('Component type:', typeof currentResult.component)
      console.log('Component === "TypeManagementModal":', currentResult.component === 'TypeManagementModal')
      
      // Direct component routing based on component field
      switch (currentResult.component) {
        case 'SprintListModal':
          return (
            <SprintListModal
              isOpen={isOpen}
              onClose={() => {
                console.log('SprintListModal onClose called')
                handleBackToList()
                onClose()
              }}
              sprints={currentResult.data}
              onSprintSelect={(sprint) => {
                console.log('Sprint selected:', sprint)
                executeDetailCommand(`/sprint-detail ${sprint.code}`)
              }}
              onRefresh={() => {
                console.log('Refresh requested')
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
                handleBackToList()
                onClose()
              }}
              tasks={currentResult.data}
              onTaskSelect={(task) => {
                console.log('Task selected:', task)
                executeDetailCommand(`/task-detail ${task.task_code}`)
              }}
              onRefresh={() => {
                console.log('Task refresh requested')
                alert('Task refresh functionality not implemented yet.')
              }}
            />
          )
        case 'AgentProfileDashboard':
          return (
            <Dialog open={isOpen} onOpenChange={onClose}>
              <DialogContent className="max-w-[95vw] h-[90vh] p-0">
                <AgentProfileDashboard initialAgents={currentResult.data} />
              </DialogContent>
            </Dialog>
          )
        case 'AgentDashboard':
          return (
            <Dialog open={isOpen} onOpenChange={onClose}>
              <DialogContent className="max-w-[95vw] h-[90vh] p-0">
                <AgentDashboard 
                  initialAgents={currentResult.data.agents} 
                  agentProfiles={currentResult.data.agentProfiles}
                />
              </DialogContent>
            </Dialog>
          )
        
        case 'AgentProfileListModal':
          return (
            <AgentProfileGridModal
              isOpen={isOpen}
              onClose={() => {
                console.log('AgentProfileGridModal onClose called')
                handleBackToList()
                onClose()
              }}
              agents={currentResult.data}
              onAgentSelect={(agent: any) => {
                console.log('Agent profile selected:', agent)
                executeDetailCommand(`/agent-profile-detail ${agent.slug}`)
              }}
              onRefresh={() => {
                console.log('Agent profile refresh requested')
                alert('Agent profile refresh functionality not implemented yet.')
              }}
            />
          )
        case 'BacklogListModal':
          return (
            <BacklogListModal
              isOpen={isOpen}
              onClose={() => {
                console.log('BacklogListModal onClose called')
                handleBackToList()
                onClose()
              }}
              tasks={currentResult.data}
              onTaskSelect={(task) => {
                console.log('Backlog task selected:', task)
                executeDetailCommand(`/task-detail ${task.task_code}`)
              }}
              onRefresh={() => {
                console.log('Backlog refresh requested')
                alert('Backlog refresh functionality not implemented yet.')
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
        case 'TypeManagementModal':
          return (
            <TypeManagementModal
              isOpen={isOpen}
              onClose={() => {
                console.log('TypeManagementModal onClose called')
                onClose()
              }}
            />
          )
        case 'UnifiedListModal':
          return (
            <UnifiedListModal
              isOpen={isOpen}
              onClose={() => {
                console.log('UnifiedListModal onClose called')
                onClose()
              }}
              data={currentResult.data}
              onRefresh={() => {
                console.log('UnifiedListModal refresh requested')
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
              fragments={currentResult.data}
              onFragmentSelect={(fragment) => {
                console.log('Fragment selected:', fragment)
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
              channels={currentResult.data}
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
        case 'RoutingInfoModal':
          return (
            <RoutingInfoModal
              isOpen={isOpen}
              onClose={() => {
                console.log('RoutingInfoModal onClose called')
                onClose()
              }}
              routingData={currentResult.data}
            />
          )
        case 'SprintDetailModal':
          return (
            <SprintDetailModal
              isOpen={isOpen}
              onClose={onClose}
              sprint={currentResult.data?.sprint}
              tasks={currentResult.data?.tasks || []}
              stats={currentResult.data?.stats || { total: 0, completed: 0, in_progress: 0, todo: 0, backlog: 0 }}
              onBack={isDetail ? handleBackToList : onClose}
              onTaskSelect={(task) => {
                console.log('Task selected from sprint detail:', task)
                executeDetailCommand(`/task-detail ${task.task_code}`)
              }}
            />
          )
        case 'TaskDetailModal':
          if (!currentResult.data?.task) {
            console.error('TaskDetailModal: No task data', currentResult)
            return (
              <Dialog open={isOpen} onOpenChange={onClose}>
                <DialogContent className="max-w-4xl rounded-sm">
                  <DialogHeader>
                    <DialogTitle className="text-foreground">Error</DialogTitle>
                  </DialogHeader>
                  <div className="p-4 text-center text-muted-foreground">
                    Task data not available. Please try again.
                  </div>
                </DialogContent>
              </Dialog>
            )
          }
          return (
            <TaskDetailModal
              isOpen={isOpen}
              onClose={onClose}
              task={currentResult.data.task}
              currentAssignment={currentResult.data?.current_assignment}
              assignments={currentResult.data?.assignments || []}
              content={currentResult.data?.content || {}}
              activities={currentResult.data?.activities || []}
              activitiesLoading={false}
              activitiesError={null}
              onBack={isDetail ? handleBackToList : onClose}
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
    if (currentResult.type === 'sprint' && currentResult.panelData?.sprints) {
      console.log('Legacy YAML command - using panelData')
      return (
        <SprintListModal
          isOpen={isOpen}
          onClose={onClose}
          sprints={currentResult.panelData.sprints}
        />
      )
    }
    
    return null
  }

  // If we have a detail view, render it
  if (detailView && detailView.success) {
    const detailUI = renderOrchestrationUI(detailView, true)
    if (detailUI) {
      return detailUI
    }
    // If detail view has no component (e.g., message response), show it in regular modal
    if (!detailView.component && detailView.message) {
      // Use detailView as the result for the regular modal
      const currentResult = detailView
      return (
        <Dialog open={isOpen} onOpenChange={onClose}>
          <DialogContent className="max-w-4xl max-h-[80vh] rounded-sm">
            <DialogHeader>
              <DialogTitle className="text-foreground">Agent Details</DialogTitle>
            </DialogHeader>
            
            <ScrollArea className="max-h-[60vh] w-full rounded-sm border-0 bg-muted/20 p-3">
              <div className="prose prose-sm max-w-none text-foreground">
                <ReactMarkdown remarkPlugins={[remarkGfm]}>
                  {currentResult.message}
                </ReactMarkdown>
              </div>
            </ScrollArea>
            
            <div className="flex justify-end gap-2">
              <Button variant="outline" onClick={handleBackToList} className="rounded-sm">
                Back
              </Button>
              <Button variant="outline" onClick={onClose} className="rounded-sm">
                Close
              </Button>
            </div>
          </DialogContent>
        </Dialog>
      )
    }
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
      const helpContent = commands.map((cmd: any) => 
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