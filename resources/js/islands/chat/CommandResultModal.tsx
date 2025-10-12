/**
 * CommandResultModal - Config-Driven Command Result Renderer
 * Hash: #PM-CACHE-2025-10-11
 * 
 * ═══════════════════════════════════════════════════════════════
 * CURRENT SYSTEM (All tables below are ACTIVE, none are legacy)
 * ═══════════════════════════════════════════════════════════════
 * 
 * DATABASE TABLES:
 *   1. commands - Slash command registry (/sprints, /tasks, etc.)
 *      Fields: ui_modal_container, navigation_config, type_slug
 *   
 *   2. types_registry - Model-backed types (sprints, tasks, agents)
 *      Fields: slug, model_class, default_card_component
 *   
 *   3. fragment_type_registry - Fragment-backed types (notes, bookmarks)
 *      Fields: slug, container_component, schema
 * 
 * COMPONENT RESOLUTION:
 *   config.ui.modal_container → commands.ui_modal_container
 *   This is 100% database-driven, no hardcoding.
 * 
 * CACHE WARNING:
 *   CommandRegistry caches for 1 hour. After database changes:
 *   php artisan cache:clear
 * 
 * See: docs/POST_MORTEM_SPRINT_NAVIGATION_CACHE_BUG.md
 * ═══════════════════════════════════════════════════════════════
 */

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
import { SprintFormModal } from '@/components/orchestration/SprintFormModal'
import { TaskListModal } from '@/components/orchestration/TaskListModal'
import { TaskDetailModal } from '@/components/orchestration/TaskDetailModal'
import { AgentProfileGridModal } from '@/components/orchestration/AgentProfileGridModal'
import { BacklogListModal } from '@/components/orchestration/BacklogListModal'
import { TodoManagementModal } from '@/islands/chat/TodoManagementModal'
import { FragmentListModal } from '@/components/fragments/FragmentListModal'
import { ChannelListModal } from '@/components/channels/ChannelListModal'
import { SecurityDashboardModal } from '@/components/security/SecurityDashboardModal'
import { RoutingInfoModal } from '@/components/routing/RoutingInfoModal'
import { AgentProfileDashboard } from '@/pages/AgentProfileDashboard'
import { AgentDashboard } from '@/pages/AgentDashboard'
import { TypeManagementModal } from '@/components/types/TypeManagementModal'
import { UnifiedListModal } from '@/components/unified/UnifiedListModal'
import { ProjectListModal } from '@/components/projects/ProjectListModal'
import { VaultListModal } from '@/components/vaults/VaultListModal'
import { BookmarkListModal } from '@/components/bookmarks/BookmarkListModal'
import { DataManagementModal } from '@/components/ui/DataManagementModal'

/**
 * Central registry mapping component names to React components.
 * All components must be registered here to be rendered by the command system.
 */
const COMPONENT_MAP: Record<string, React.ComponentType<any>> = {
  'DataManagementModal': DataManagementModal,
  'SprintListModal': SprintListModal,
  'SprintFormModal': SprintFormModal,
  'TaskListModal': TaskListModal,
  'AgentProfileGridModal': AgentProfileGridModal,
  'BacklogListModal': BacklogListModal,
  'ProjectListModal': ProjectListModal,
  'VaultListModal': VaultListModal,
  'BookmarkListModal': BookmarkListModal,
  'FragmentListModal': FragmentListModal,
  'ChannelListModal': ChannelListModal,
  'SecurityDashboardModal': SecurityDashboardModal,
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
  _command?: string
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
      navigation?: {
        data_prop?: string
        item_key?: string
        detail_command?: string
        parent_command?: string
        children?: Array<{
          type: string
          command: string
          item_key: string
        }>
      }
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
    return result.component
  }
  
  // Priority 2: Config-based resolution
  if (!result.config) {
    console.warn('[CommandResultModal] No config provided - using fallback')
    return 'UnifiedListModal'
  }
  
  if (result.config.ui?.modal_container) {
    return result.config.ui.modal_container
  }
  
  if (result.config.ui?.card_component) {
    const transformed = transformCardToModal(result.config.ui.card_component)
    return transformed
  }
  
  if (result.config.type?.default_card_component) {
    const transformed = transformCardToModal(result.config.type.default_card_component)
    return transformed
  }
  
  return 'UnifiedListModal'
}

function capitalize(str: string): string {
  return str.charAt(0).toUpperCase() + str.slice(1)
}

function buildComponentProps(result: CommandResult, componentName: string, handlers: ComponentHandlers, isOpen: boolean = true): Record<string, any> {
  
  const props: Record<string, any> = {
    isOpen: isOpen,
    onClose: handlers.onClose,
    data: result.data,
    config: result.config,
  }
  
  const navConfig = result.config?.ui?.navigation
  
  // Handle Detail modals - they expect data spread as individual props
  if (componentName.includes('Detail')) {
    // Spread data object properties as individual props (sprint, tasks, stats, etc.)
    if (result.data && typeof result.data === 'object') {
      Object.assign(props, result.data)
    }

  } else if (navConfig?.data_prop) {
    // CONFIG-DRIVEN: Use navigation config to set data props
    const dataProp = navConfig.data_prop
    
    // Handle new structure: {sprints, unassigned_tasks} or legacy array
    if (result.data && typeof result.data === 'object' && dataProp in result.data) {
      // For DataManagementModal, the 'data' prop must be an array
      if (componentName === 'DataManagementModal') {
        props.data = result.data[dataProp]
        // Store other properties separately (like unassigned_tasks)
        Object.keys(result.data).forEach(key => {
          if (key !== dataProp) {
            props[key] = result.data[key]
          }
        })
      } else {
        // For other components, use the configured data prop name
        props[dataProp] = result.data[dataProp]
        // Copy any additional data properties
        Object.keys(result.data).forEach(key => {
          if (key !== dataProp) {
            props[key] = result.data[key]
          }
        })
      }
    } else {
      // Fallback for simple array data
      if (componentName === 'DataManagementModal') {
        props.data = result.data
      } else {
        props[dataProp] = result.data
      }
    }
  } else {
    // LEGACY FALLBACK: Maintain backward compatibility for commands without navigation config
    if (componentName.includes('Sprint')) {
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
  
  // Configure DataManagementModal specific props
  if (componentName === 'DataManagementModal') {
    
    // Set up columns based on the type
    const typeSlug = (result.config as any)?.type_slug || result.config?.type?.slug
    
    if (typeSlug === 'sprint' || typeSlug === 'sprints') {
      // Sprint-specific columns
      props.columns = [
        { 
          key: 'code', 
          label: 'Sprint', 
          sortable: true,
        },
        { 
          key: 'title', 
          label: 'Title', 
          sortable: true,
          render: (_item: any, value: any) => value || 'No title'
        },
        { 
          key: 'status', 
          label: 'Status', 
          sortable: true,
          render: (_item: any, value: any) => value || 'Active'
        },
        { 
          key: 'progress_percentage', 
          label: 'Progress', 
          render: (_item: any, value: any) => `${value || 0}%` 
        },
        { key: 'total_tasks', label: 'Tasks' },
        { key: 'created_at', label: 'Created', sortable: true },
      ]
      props.title = 'Sprints'
      props.searchFields = ['code', 'title']
      props.searchPlaceholder = 'Search sprints...'
    } else if (typeSlug === 'task' || typeSlug === 'tasks') {
      // Task-specific columns
      props.columns = [
        { 
          key: 'task_code', 
          label: 'Code', 
          sortable: true,
          width: 'w-24'
        },
        { 
          key: 'task_name', 
          label: 'Task', 
          sortable: true,
          render: (item: any, value: any) => (
            <div className="flex flex-col">
              <span className="font-medium">{value || 'Untitled'}</span>
              {item.description && (
                <span className="text-xs text-muted-foreground truncate max-w-[300px]">
                  {item.description}
                </span>
              )}
            </div>
          )
        },
        { 
          key: 'sprint_code', 
          label: 'Sprint',
          sortable: true,
          width: 'w-24',
          render: (_item: any, value: any) => value || '-'
        },
        { 
          key: 'delegation_status', 
          label: 'Status', 
          sortable: true,
          width: 'w-28',
          render: (_item: any, value: any) => {
            const statusColors: Record<string, string> = {
              'unassigned': 'text-gray-500',
              'assigned': 'text-blue-600',
              'in_progress': 'text-yellow-600',
              'completed': 'text-green-600',
              'blocked': 'text-red-600',
            }
            return <span className={statusColors[value] || 'text-gray-500'}>{value || 'unknown'}</span>
          }
        },
        { 
          key: 'priority', 
          label: 'Priority',
          sortable: true,
          width: 'w-20',
          render: (_item: any, value: any) => {
            const priorityColors: Record<string, string> = {
              'high': 'text-red-600',
              'medium': 'text-yellow-600',
              'low': 'text-green-600',
            }
            return <span className={priorityColors[value] || 'text-gray-500'}>{value || 'medium'}</span>
          }
        },
        { 
          key: 'assigned_to', 
          label: 'Assigned To',
          width: 'w-32',
          render: (_item: any, value: any) => value || 'Unassigned'
        },
        { 
          key: 'updated_at', 
          label: 'Updated', 
          sortable: true,
          width: 'w-28',
          render: (_item: any, value: any) => {
            if (!value) return '-'
            return new Date(value).toLocaleDateString()
          }
        },
      ]
      props.searchFields = ['task_code', 'task_name', 'description']
      props.searchPlaceholder = 'Search tasks...'
      
      // Add action items for CRUD
      props.actionItems = [
        { key: 'view', label: 'View Details' },
        { key: 'edit', label: 'Edit Task' },
        { key: 'assign', label: 'Assign Agent' },
        { key: 'delete', label: 'Delete', className: 'text-red-600' },
      ]
      
      // Handle actions
      props.onAction = (action: string, item: any) => {
        switch(action) {
          case 'view':
            handlers.executeDetailCommand!(`/task-detail ${item.task_code}`)
            break
          case 'edit':
            // TODO: Implement edit command
            console.log('Edit task:', item.task_code)
            break
          case 'assign':
            // TODO: Implement assign command
            console.log('Assign task:', item.task_code)
            break
          case 'delete':
            if (confirm(`Delete task ${item.task_code}?`)) {
              // TODO: Implement delete command
              console.log('Delete task:', item.task_code)
            }
            break
        }
      }
    } else if (props.data && Array.isArray(props.data) && props.data.length > 0) {
      // Auto-generate columns from first data item (use props.data, not result.data!)
      
      const firstItem = props.data[0]
      
      const keys = Object.keys(firstItem).filter(key => 
        key !== 'id' && 
        key !== 'metadata' && 
        !key.startsWith('_')
      )
      
      props.columns = keys.slice(0, 6).map(key => ({
        key,
        label: key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()),
        sortable: true
      }))
    } else {
      // Fallback columns
      if (props.data) {
      }
      props.columns = []
    }
    
    // Enable clickable rows for navigation if we have navigation config
    if (navConfig) {
      props.clickableRows = true
      
      // Set up row click handler using navigation config
      if (navConfig.detail_command && navConfig.item_key && handlers.executeDetailCommand) {
        const itemKey = navConfig.item_key as string
        props.onRowClick = (item: any) => {
          handlers.executeDetailCommand!(`${navConfig.detail_command} ${item[itemKey]}`)
        }
      }
    }
  }
  
  if (handlers.executeDetailCommand && navConfig) {
    // CONFIG-DRIVEN: Use navigation config for item selection handlers
    if (navConfig.detail_command && navConfig.item_key) {
      const itemKey = navConfig.item_key
      props.onItemSelect = (item: any) => handlers.executeDetailCommand!(`${navConfig.detail_command} ${item[itemKey]}`)
      
      // Also set component-specific handlers based on component name
      if (componentName.includes('Task') || componentName.includes('Backlog')) {
        props.onTaskSelect = (item: any) => handlers.executeDetailCommand!(`${navConfig.detail_command} ${item[itemKey]}`)
      } else if (componentName.includes('Sprint')) {
        props.onSprintSelect = (item: any) => handlers.executeDetailCommand!(`${navConfig.detail_command} ${item[itemKey]}`)
        // Add create handler for Sprint list
        props.onCreate = () => handlers.executeDetailCommand!('/sprint-create')
      } else if (componentName.includes('Agent')) {
        props.onAgentSelect = (item: any) => handlers.executeDetailCommand!(`${navConfig.detail_command} ${item[itemKey]}`)
      }
    }
    
    // Add child handlers for drill-down navigation
    if (navConfig.children) {
      navConfig.children.forEach((child: any) => {
        const handlerName = `on${capitalize(child.type)}Select`
        props[handlerName] = (item: any) => handlers.executeDetailCommand!(`${child.command} ${item[child.item_key]}`)
      })
    }
  } else if (handlers.executeDetailCommand) {
    // LEGACY FALLBACK: Hardcoded handlers for backward compatibility
    if (componentName.includes('Sprint')) {
      props.onItemSelect = (item: any) => handlers.executeDetailCommand!(`/sprint-detail ${item.code}`)
      props.onSprintSelect = (item: any) => handlers.executeDetailCommand!(`/sprint-detail ${item.code}`)
      props.onCreate = () => handlers.executeDetailCommand!('/sprint-create')
    } else if (componentName.includes('Task')) {
      props.onItemSelect = (item: any) => handlers.executeDetailCommand!(`/task-detail ${item.task_code}`)
      props.onTaskSelect = (item: any) => handlers.executeDetailCommand!(`/task-detail ${item.task_code}`)
    } else if (componentName.includes('Agent')) {
      props.onItemSelect = (item: any) => handlers.executeDetailCommand!(`/agent-profile-detail ${item.slug}`)
      props.onAgentSelect = (item: any) => handlers.executeDetailCommand!(`/agent-profile-detail ${item.slug}`)
    }
  }
  
  if (componentName.includes('Detail') || componentName.includes('Form')) {
    // Detail and Form modals get both onClose (for X/Close buttons) and onBack (for Back button/ESC)
    if (handlers.onBackToList) {
      props.onBack = handlers.onBackToList
    }
    
    // Add edit handler for detail modals
    if (componentName.includes('Detail') && handlers.executeDetailCommand) {
      if (componentName.includes('Sprint')) {
        props.onEdit = (item: any) => handlers.executeDetailCommand!(`/sprint-edit ${item.code}`)
      } else if (componentName.includes('Task')) {
        props.onEdit = (item: any) => handlers.executeDetailCommand!(`/task-edit ${item.task_code}`)
      }
    }
    
    // Add drill-down handlers for detail views
    if (componentName.includes('Detail') && navConfig?.children && handlers.executeDetailCommand) {
      // CONFIG-DRIVEN: Use children config
      navConfig.children.forEach((child: any) => {
        const handlerName = `on${capitalize(child.type)}Select`
        props[handlerName] = (item: any) => handlers.executeDetailCommand!(`${child.command} ${item[child.item_key]}`)
      })
    } else if (componentName.includes('Sprint') && handlers.executeDetailCommand) {
      // LEGACY FALLBACK
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
  console.log('isOpen:', isOpen)
  console.log('result:', result)
  console.log('command:', command)
  
  const [viewStack, setViewStack] = useState<CommandResult[]>([])
  const [_isLoadingDetail, setIsLoadingDetail] = useState(false)

  // Reset stack when modal closes
  useEffect(() => {
    if (!isOpen) {
      setViewStack([])
    }
  }, [isOpen])

  if (!result || !isOpen) {
    return null
  }
  
  console.log('result.success:', result.success)
  console.log('result.config:', !!result.config)
  console.log('result.data keys:', Object.keys(result.data || {}))

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
        // Store the command that loaded this view
        detailResult._command = detailCommand
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

  const refreshCurrentView = async () => {
    
    // Determine which command to refresh
    const currentView = viewStack.length > 0 ? viewStack[viewStack.length - 1] : null
    const commandToRefresh = currentView?._command || command
    
    if (!commandToRefresh) {
      console.warn('[CommandResultModal] No command to refresh')
      return
    }
    
    setIsLoadingDetail(true)
    
    try {
      const csrf = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || ''
      const response = await fetch('/api/commands/execute', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrf
        },
        body: JSON.stringify({ command: commandToRefresh })
      })

      const refreshedResult = await response.json()

      if (refreshedResult.success) {
        refreshedResult._command = commandToRefresh
        
        if (viewStack.length > 0) {
          // Update the current view in the stack
          setViewStack(prev => [...prev.slice(0, -1), refreshedResult])
        }
        // Note: Root view refresh would need to be handled by parent component
      } else {
        console.error('[CommandResultModal] Refresh failed:', refreshedResult)
      }
    } catch (error) {
      console.error('[CommandResultModal] Refresh error:', error)
    } finally {
      setIsLoadingDetail(false)
    }
  }

  // Debug current state
  const currentView = viewStack.length > 0 ? viewStack[viewStack.length - 1] : null
  console.log('[CommandResultModal v2.0 - NAVIGATION STACK] viewStack length:', viewStack.length, 'currentView:', currentView?.type, 'result.type:', result?.type)

  const handlers: ComponentHandlers = {
    onClose: onClose, // X/Close/ESC on root → close modal
    onRefresh: refreshCurrentView,
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
