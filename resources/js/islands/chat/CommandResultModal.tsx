import React, { useState, useEffect } from 'react'
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
import { ProjectListModal } from '@/components/projects/ProjectListModal'
import { VaultListModal } from '@/components/vaults/VaultListModal'
import { BookmarkListModal } from '@/components/bookmarks/BookmarkListModal'

/**
 * Central registry mapping component names to React components.
 * All components must be registered here to be rendered by the command system.
 */
const COMPONENT_MAP: Record<string, React.ComponentType<any>> = {
  'SprintListModal': SprintListModal,
  'TaskListModal': TaskListModal,
  'AgentProfileGridModal': AgentProfileGridModal,
  'BacklogListModal': BacklogListModal,
  'ProjectListModal': ProjectListModal,
  'VaultListModal': VaultListModal,
  'BookmarkListModal': BookmarkListModal,
  'FragmentListModal': FragmentListModal,
  'ChannelListModal': ChannelListModal,
  'SprintDetailModal': SprintDetailModal,
  'TaskDetailModal': TaskDetailModal,
  'UnifiedListModal': UnifiedListModal,
  'TodoManagementModal': TodoManagementModal,
  'TypeManagementModal': TypeManagementModal,
  'RoutingInfoModal': RoutingInfoModal,
  'AgentProfileDashboard': AgentProfileDashboard,
  'AgentDashboard': AgentDashboard,
}

interface CommandResult {
  success: boolean
  type?: string
  component?: string
  data?: any
  message?: string
  error?: string
  fragments?: any[]
  shouldResetChat?: boolean
  shouldShowSuccessToast?: boolean
  toastData?: any
  config?: {
    type?: {
      slug?: string
      display_name?: string
      plural_name?: string
      storage_type?: string
      default_card_component?: string
      default_detail_component?: string
    }
    ui?: {
      modal_container?: string
      layout_mode?: string
      card_component?: string
      detail_component?: string
      filters?: any
      default_sort?: any
      pagination_default?: number
    }
    command?: {
      command?: string
      name?: string
      description?: string
      category?: string
    }
  }
}

interface CommandResultModalProps {
  isOpen: boolean
  onClose: () => void
  result: CommandResult | null
  command: string
}

interface ComponentHandlers {
  onClose: () => void
  onRefresh?: () => void
  executeDetailCommand?: (cmd: string) => void
  onBackToList?: () => void
}

function transformCardToModal(cardName: string): string {
  if (cardName.endsWith('Card')) {
    return cardName.replace('Card', 'ListModal')
  }
  return cardName
}

function getComponentName(result: CommandResult): string {
  // Priority 1: Explicit component from respond() method
  if (result.component) {
    console.log('[CommandResultModal] Using explicit component:', result.component)
    return result.component
  }
  
  // Priority 2: Config-based resolution
  if (!result.config) {
    console.warn('[CommandResultModal] No config provided - using fallback')
    return 'UnifiedListModal'
  }
  
  if (result.config.ui?.modal_container) {
    console.log('[CommandResultModal] Using ui.modal_container:', result.config.ui.modal_container)
    return result.config.ui.modal_container
  }
  
  if (result.config.ui?.card_component) {
    const transformed = transformCardToModal(result.config.ui.card_component)
    console.log('[CommandResultModal] Transformed ui.card_component:', result.config.ui.card_component, '→', transformed)
    return transformed
  }
  
  if (result.config.type?.default_card_component) {
    const transformed = transformCardToModal(result.config.type.default_card_component)
    console.log('[CommandResultModal] Transformed type.default_card_component:', result.config.type.default_card_component, '→', transformed)
    return transformed
  }
  
  console.log('[CommandResultModal] No component specified - using UnifiedListModal')
  return 'UnifiedListModal'
}

function buildComponentProps(result: CommandResult, componentName: string, handlers: ComponentHandlers, isOpen: boolean = true): Record<string, any> {
  const props: Record<string, any> = {
    isOpen: isOpen,
    onClose: handlers.onClose,
    data: result.data,
    config: result.config,
  }
  
  // Handle Detail modals - they expect data spread as individual props
  if (componentName.includes('Detail')) {
    // Spread data object properties as individual props (sprint, tasks, stats, etc.)
    if (result.data && typeof result.data === 'object') {
      Object.assign(props, result.data)
    }
  } else {
    // Add legacy type-specific props for List modals
    if (componentName.includes('Sprint')) {
      // Handle new structure: {sprints, unassigned_tasks} or legacy array
      if (result.data && typeof result.data === 'object' && 'sprints' in result.data) {
        props.sprints = result.data.sprints
        props.unassigned_tasks = result.data.unassigned_tasks
      } else {
        props.sprints = result.data
      }
    } else if (componentName.includes('Task')) {
      props.tasks = result.data
    } else if (componentName.includes('Agent')) {
      props.agents = result.data
    } else if (componentName.includes('Project')) {
      props.projects = result.data
    } else if (componentName.includes('Vault')) {
      props.vaults = result.data
    } else if (componentName.includes('Bookmark')) {
      props.bookmarks = result.data
    } else if (componentName.includes('Fragment')) {
      props.fragments = result.data
    } else if (componentName.includes('Channel')) {
      props.channels = result.data
    }
  }
  
  if (handlers.onRefresh) {
    props.onRefresh = handlers.onRefresh
  }
  
  if (handlers.executeDetailCommand) {
    if (componentName.includes('Sprint')) {
      props.onItemSelect = (item: any) => handlers.executeDetailCommand!(`/sprint-detail ${item.code}`)
      props.onSprintSelect = (item: any) => handlers.executeDetailCommand!(`/sprint-detail ${item.code}`)
    } else if (componentName.includes('Task')) {
      props.onItemSelect = (item: any) => handlers.executeDetailCommand!(`/task-detail ${item.task_code}`)
      props.onTaskSelect = (item: any) => handlers.executeDetailCommand!(`/task-detail ${item.task_code}`)
    } else if (componentName.includes('Agent')) {
      props.onItemSelect = (item: any) => handlers.executeDetailCommand!(`/agent-profile-detail ${item.slug}`)
      props.onAgentSelect = (item: any) => handlers.executeDetailCommand!(`/agent-profile-detail ${item.slug}`)
    }
  }
  
  if (componentName.includes('Detail')) {
    // Detail modals get both onClose (for X/Close buttons) and onBack (for Back button/ESC)
    // onBack is set via onBackToList handler
    if (handlers.onBackToList) {
      props.onBack = handlers.onBackToList
    }
    
    // Add drill-down handlers for detail views
    if (componentName.includes('Sprint') && handlers.executeDetailCommand) {
      props.onTaskSelect = (item: any) => handlers.executeDetailCommand!(`/task-detail ${item.task_code}`)
    }
  }
  
  return props
}

function renderComponent(result: CommandResult, handlers: ComponentHandlers, isOpen: boolean = true): React.ReactNode {
  const componentName = getComponentName(result)
  let Component = COMPONENT_MAP[componentName]
  
  if (!Component) {
    console.warn(`[CommandResultModal] Component "${componentName}" not found in registry`)
    Component = COMPONENT_MAP['UnifiedListModal']
  }
  
  const props = buildComponentProps(result, componentName, handlers, isOpen)
  return <Component {...props} />
}

export function CommandResultModal({
  isOpen,
  onClose,
  result,
  command
}: CommandResultModalProps) {
  const [viewStack, setViewStack] = useState<CommandResult[]>([])
  const [isLoadingDetail, setIsLoadingDetail] = useState(false)

  // Reset stack when modal closes
  useEffect(() => {
    if (!isOpen) {
      setViewStack([])
    }
  }, [isOpen])

  if (!result || !isOpen) {
    return null
  }

  const handleBack = () => {
    console.log('CommandResultModal: Going back one level', 'stack length:', viewStack.length)
    
    // If stack is empty, we're on the root view - close modal
    if (viewStack.length === 0) {
      console.log('CommandResultModal: On root view - closing modal')
      onClose()
      return
    }
    
    // Otherwise, pop the stack to go back one level
    setViewStack(prev => prev.slice(0, -1))
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
        setViewStack(prev => [...prev, detailResult])
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
  const currentView = viewStack.length > 0 ? viewStack[viewStack.length - 1] : null
  console.log('[CommandResultModal v2.0 - NAVIGATION STACK] viewStack length:', viewStack.length, 'currentView:', currentView?.type, 'result.type:', result?.type)

  const handlers: ComponentHandlers = {
    onClose: onClose, // X/Close/ESC on root → close modal
    onRefresh: () => console.log('[CommandResultModal] Refresh requested'),
    executeDetailCommand,
    onBackToList: handleBack, // Back button on stack views
  }

  // If we have views in the stack, render the current view
  if (currentView && currentView.success && currentView.config) {
    // For views in the navigation stack:
    // - onClose (X/Close buttons) should close modal entirely
    // - onBack (Back button/ESC) should go back one level
    const stackHandlers: ComponentHandlers = {
      ...handlers,
      onClose: onClose, // X/Close buttons close modal entirely
      onBackToList: handleBack, // Back button/ESC go back
    }
    console.log('[CommandResultModal] Rendering from stack - component:', getComponentName(currentView), 'data keys:', Object.keys(currentView.data || {}))
    return renderComponent(currentView, stackHandlers, isOpen)
  }
  if (currentView && currentView.success) {
    // If view has no component (e.g., message response), show it in regular modal
    if (!currentView.component && currentView.message) {
      return (
        <Dialog open={isOpen} onOpenChange={onClose}>
          <DialogContent className="max-w-4xl max-h-[80vh] rounded-sm">
            <DialogHeader>
              <DialogTitle className="text-foreground">Details</DialogTitle>
            </DialogHeader>

            <ScrollArea className="max-h-[60vh] w-full rounded-sm border-0 bg-muted/20 p-3">
              <div className="prose prose-sm max-w-none text-foreground">
                <ReactMarkdown remarkPlugins={[remarkGfm]}>
                  {currentView.message}
                </ReactMarkdown>
              </div>
            </ScrollArea>

            <div className="flex justify-end gap-2">
              <Button variant="outline" onClick={handleBack} className="rounded-sm">
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

  // If this has config, use new rendering system
  if (result.success && result.config) {
    return renderComponent(result, handlers, isOpen)
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
