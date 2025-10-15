import { useState, useEffect, useRef } from 'react'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { ScrollArea } from '@/components/ui/scroll-area'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { 
  CheckCircle, 
  User, 
  FileText, 
  Settings,
  Eye,
  Users,
  ArrowLeft,
  Clock,
  RefreshCw,
  Calendar,
  UserPlus
} from 'lucide-react'
import ReactMarkdown from 'react-markdown'
import remarkGfm from 'remark-gfm'
import { TaskActivityTimeline } from './TaskActivityTimeline'
import { InlineEditText } from '@/components/ui/InlineEditText'
import { InlineEditSelect } from '@/components/ui/InlineEditSelect'
import { TagEditor } from '@/components/ui/TagEditor'
import { MarkdownEditor } from '@/components/ui/MarkdownEditor'
import { CopyToClipboard } from '@/components/ui/CopyToClipboard'
import { AssignSprintModal } from './AssignSprintModal'
import { AssignAgentModal } from './AssignAgentModal'
import { Edit } from 'lucide-react'

interface Task {
  id: string
  task_code: string
  task_name?: string | null
  description?: string
  status: string
  delegation_status: string
  priority?: string
  agent_recommendation?: string
  estimate_text?: string
  sprint_code?: string
  assignee_id?: string | null
  assignee_name?: string | null
  assignee_type?: string | null
  tags?: string[]
  created_at: string
  updated_at: string
  completed_at?: string | null
  metadata?: Record<string, any>
}

interface Assignment {
  id: string
  agent_name?: string
  assigned_at: string
  status: string
}

interface TaskContent {
  agent?: string
  plan?: string
  context?: string
  todo?: string
  summary?: string
}

interface TaskActivity {
  id: string
  task_id: string
  agent_id?: string | null
  user_id?: number | null
  activity_type: 'status_change' | 'content_update' | 'assignment' | 'note' | 'error' | 'artifact_attached'
  action: string
  description: string
  changes?: Record<string, any> | null
  metadata?: Record<string, any> | null
  created_at: string
  agent?: {
    id: string
    name: string
    slug?: string
  }
  user?: {
    id: number
    name: string
  }
}

interface TaskDetailModalProps {
  isOpen: boolean
  onClose: () => void
  task: Task
  currentAssignment?: Assignment | null
  assignments?: Assignment[]
  content?: TaskContent
  contentLoading?: boolean
  contentError?: string | null
  onRefreshContent?: () => void
  onSaveContent?: (field: string, value: string) => Promise<void>
  activities?: TaskActivity[]
  activitiesLoading?: boolean
  activitiesError?: string | null
  onRefreshActivities?: () => void
  onAddNote?: (note: string) => Promise<void>
  loading?: boolean
  error?: string | null
  onRefresh?: () => void
  onBack?: () => void
  editMode?: boolean
}

export function TaskDetailModal({ 
  isOpen, 
  onClose, 
  task: initialTask,
  currentAssignment,
  assignments = [],
  content: initialContent = {},
  contentLoading = false,
  contentError = null,
  onRefreshContent,
  onSaveContent,
  activities = [],
  activitiesLoading = false,
  activitiesError = null,
  onRefreshActivities,
  onAddNote,
  onRefresh,
  onBack,
  editMode = false
}: TaskDetailModalProps) {
  const [task, setTask] = useState<Task>(initialTask)
  const [content, setContent] = useState<TaskContent>(initialContent)
  const [availableSprints, setAvailableSprints] = useState<Array<{ value: string; label: string }>>([])
  const [availableAgents, setAvailableAgents] = useState<Array<{ value: string; label: string }>>([])
  const [editingContent, setEditingContent] = useState<string | null>(null)
  const [showAssignSprintModal, setShowAssignSprintModal] = useState(false)
  const [showAssignAgentModal, setShowAssignAgentModal] = useState(false)
  const isOpeningChildModal = useRef(false)

  // Update local state when props change
  useEffect(() => {
    setTask(initialTask)
  }, [initialTask])

  useEffect(() => {
    setContent(initialContent)
  }, [initialContent])

  useEffect(() => {
    if (!isOpen) return

    const loadOptions = async () => {
      try {
        const [sprintsRes, agentsRes] = await Promise.all([
          fetch('/api/orchestration/tasks/sprints/available'),
          fetch('/api/agents'),
        ])

        if (sprintsRes.ok) {
          const sprintsData = await sprintsRes.json()
          setAvailableSprints(sprintsData.sprints || [])
        }

        if (agentsRes.ok) {
          const agentsData = await agentsRes.json()
          const agents = (agentsData.data || agentsData.agents || []).map((agent: any) => ({
            value: agent.id,
            label: agent.name || agent.designation,
          }))
          setAvailableAgents(agents)
        }
      } catch (error) {
        console.error('Failed to load options:', error)
      }
    }

    loadOptions()
  }, [isOpen])

  if (!task) {
    return (
      <Dialog open={isOpen} onOpenChange={onClose}>
        <DialogContent className="max-w-4xl rounded-sm">
          <DialogHeader>
            <DialogTitle>Error</DialogTitle>
          </DialogHeader>
          <div className="p-4 text-center text-muted-foreground">
            Task data not available
          </div>
        </DialogContent>
      </Dialog>
    )
  }

  const getStatusColor = (status: string) => {
    switch (status?.toLowerCase()) {
      case 'done':
      case 'completed':
        return 'bg-green-100 text-green-800'
      case 'in-progress':
      case 'in_progress':
        return 'bg-blue-100 text-blue-800'
      case 'todo':
      case 'ready':
        return 'bg-yellow-100 text-yellow-800'
      case 'blocked':
        return 'bg-red-100 text-red-800'
      case 'backlog':
        return 'bg-gray-100 text-gray-800'
      case 'review':
        return 'bg-purple-100 text-purple-800'
      default:
        return 'bg-gray-100 text-gray-800'
    }
  }

  const getDelegationStatusColor = (status: string) => {
    switch (status?.toLowerCase()) {
      case 'completed':
        return 'bg-green-100 text-green-800'
      case 'assigned':
      case 'in_progress':
        return 'bg-blue-100 text-blue-800'
      case 'unassigned':
        return 'bg-gray-100 text-gray-800'
      case 'blocked':
        return 'bg-red-100 text-red-800'
      default:
        return 'bg-gray-100 text-gray-800'
    }
  }

  const getPriorityColor = (priority?: string) => {
    switch (priority?.toLowerCase()) {
      case 'high':
        return 'bg-red-100 text-red-800'
      case 'medium':
        return 'bg-yellow-100 text-yellow-800'
      case 'low':
        return 'bg-green-100 text-green-800'
      default:
        return 'bg-gray-100 text-gray-800'
    }
  }

  const handleSaveField = async (field: string, value: string) => {
    const csrf = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || ''
    
    const response = await fetch(`/api/orchestration/tasks/${task.id}/field`, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrf,
      },
      body: JSON.stringify({ field, value }),
    })

    if (!response.ok) {
      throw new Error('Failed to save field')
    }

    // Get updated task from response
    const data = await response.json()
    
    // Update local task state with the response (which includes sprint_code, assignee_name, etc.)
    if (data.success && data.task) {
      // Map backend fields to frontend Task interface
      setTask(prev => ({
        ...prev,
        ...data.task,
        task_name: data.task.title || prev.task_name,
        task_code: data.task.task_code || prev.task_code,
        updated_at: data.task.updated_at || new Date().toISOString(),
      }))
      
      // Optionally refresh activities
      if (onRefreshActivities) {
        onRefreshActivities()
      }
    }
  }

  const handleSaveTags = async (tags: string[]) => {
    const csrf = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || ''
    
    const response = await fetch(`/api/orchestration/tasks/${task.id}/tags`, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrf,
      },
      body: JSON.stringify({ tags }),
    })

    if (!response.ok) {
      throw new Error('Failed to save tags')
    }

    onRefresh?.()
  }

  const handleSaveContent = async (contentKey: string, contentValue: string) => {
    const fieldMap: Record<string, string> = {
      'agent': 'agent_content',
      'plan': 'plan_content',
      'context': 'context_content',
      'todo': 'todo_content',
      'summary': 'summary_content',
    }

    const field = fieldMap[contentKey]
    if (!field) return

    // Update local content state immediately (optimistic update)
    setContent(prev => ({
      ...prev,
      [contentKey]: contentValue,
    }))

    // Save to backend
    await handleSaveField(field, contentValue)
    setEditingContent(null)
  }

  const safeContent = content || {}
  const safeActivities = activities || []
  
  const contentTabs = [
    { key: 'activity', label: 'Activity', icon: <Clock className="h-3.5 w-3.5" />, content: null, isActivity: true },
    { key: 'agent', label: 'Agent', icon: <User className="h-3.5 w-3.5" />, content: safeContent.agent },
    { key: 'plan', label: 'Plan', icon: <FileText className="h-3.5 w-3.5" />, content: safeContent.plan },
    { key: 'context', label: 'Context', icon: <Settings className="h-3.5 w-3.5" />, content: safeContent.context },
    { key: 'todo', label: 'Todo', icon: <CheckCircle className="h-3.5 w-3.5" />, content: safeContent.todo },
    { key: 'summary', label: 'Summary', icon: <Eye className="h-3.5 w-3.5" />, content: safeContent.summary },
  ]

  return (
    <Dialog modal={false} open={isOpen} onOpenChange={(open) => {
      console.log('[TaskDetailModal] Dialog onOpenChange, open:', open, 'onBack exists?', !!onBack)
      // Don't close if child modals are open or being opened
      if (!open && !showAssignSprintModal && !showAssignAgentModal && !isOpeningChildModal.current) {
        if (onBack) {
          onBack()
        } else {
          onClose()
        }
      }
    }}>
      <DialogContent className="max-w-6xl w-[95vw] sm:w-[85vw] h-[85vh] min-h-[600px] rounded-sm flex flex-col overflow-hidden">
        <DialogHeader>
          <DialogTitle className="text-foreground flex items-center gap-2">
            {onBack && (
              <Button 
                variant="ghost" 
                size="sm" 
                onClick={onBack}
                className="flex items-center gap-1 text-muted-foreground hover:text-foreground -ml-2"
              >
                <ArrowLeft className="h-4 w-4" />
                Back
              </Button>
            )}
            <FileText className="h-5 w-5" />
            Task: {task.task_code}
          </DialogTitle>
        </DialogHeader>
        
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 flex-1 overflow-hidden">
          {/* Left Panel - Task Info */}
          <div className="lg:col-span-1 space-y-4 overflow-y-auto">
            <div className="bg-muted/20 rounded-lg p-4">
              <h3 className="font-medium mb-3">Task Details</h3>
              
              <div className="space-y-3">
                <div>
                  <span className="text-sm font-medium">Name:</span>
                  <InlineEditText 
                    value={task.task_name || task.metadata?.title || task.metadata?.task_name || ''}
                    onSave={(value) => handleSaveField('task_name', value)}
                    placeholder="Untitled Task"
                    className="text-sm text-muted-foreground mt-1"
                  />
                </div>

                <div>
                  <span className="text-sm font-medium">Description:</span>
                  <InlineEditText 
                    value={task.description || ''}
                    onSave={(value) => handleSaveField('description', value)}
                    placeholder="Add description..."
                    multiline
                    className="text-sm text-muted-foreground mt-1"
                  />
                </div>

                <div className="flex flex-wrap gap-2">
                  <InlineEditSelect
                    value={task.status}
                    options={[
                      { value: 'backlog', label: 'Backlog' },
                      { value: 'todo', label: 'Todo' },
                      { value: 'in_progress', label: 'In Progress' },
                      { value: 'review', label: 'Review' },
                      { value: 'done', label: 'Done' },
                      { value: 'blocked', label: 'Blocked' },
                    ]}
                    onSave={(value) => handleSaveField('status', value)}
                    className={getStatusColor(task.status)}
                  />
                  
                  <Badge variant="outline" className={`text-xs ${getDelegationStatusColor(task.delegation_status)}`}>
                    {task.delegation_status}
                  </Badge>
                  
                  <InlineEditSelect
                    value={task.priority || 'medium'}
                    options={[
                      { value: 'low', label: 'Low' },
                      { value: 'medium', label: 'Medium' },
                      { value: 'high', label: 'High' },
                    ]}
                    onSave={(value) => handleSaveField('priority', value)}
                    className={getPriorityColor(task.priority)}
                  />
                </div>

                <div className="flex gap-2 pt-2 border-t border-border/50">
                  <Button
                    size="sm"
                    variant={task.status === 'backlog' ? 'secondary' : 'outline'}
                    onClick={() => handleSaveField('status', 'backlog')}
                    disabled={task.status === 'backlog'}
                    className="flex-1"
                  >
                    {task.status === 'backlog' ? 'In Backlog' : 'Send to Backlog'}
                  </Button>
                </div>

                <div>
                  <div className="flex items-center justify-between">
                    <span className="text-sm font-medium">Sprint:</span>
                    <Button
                      size="sm"
                      variant="ghost"
                      onClick={(e) => { 
                        e.stopPropagation(); 
                        e.preventDefault();
                        console.log('[TaskDetailModal] Assign Sprint clicked');
                        isOpeningChildModal.current = true;
                        setShowAssignSprintModal(true);
                        setTimeout(() => { isOpeningChildModal.current = false; }, 100);
                      }}
                      className="h-6 px-2 text-xs"
                    >
                      <Calendar className="h-3 w-3 mr-1" />
                      Assign
                    </Button>
                  </div>
                  <p className="text-sm text-muted-foreground mt-1">
                    {task.sprint_code || 'No sprint assigned'}
                  </p>
                </div>

                <div>
                  <span className="text-sm font-medium">Estimate:</span>
                  <InlineEditText 
                    value={task.estimate_text || ''}
                    onSave={(value) => handleSaveField('estimate_text', value)}
                    placeholder="Add estimate..."
                    className="text-sm text-muted-foreground mt-1"
                  />
                </div>

                <div>
                  <div className="flex items-center justify-between">
                    <span className="text-sm font-medium">Assigned Agent:</span>
                    <Button
                      size="sm"
                      variant="ghost"
                      onClick={(e) => { 
                        e.stopPropagation(); 
                        e.preventDefault();
                        console.log('[TaskDetailModal] Assign Agent clicked');
                        isOpeningChildModal.current = true;
                        setShowAssignAgentModal(true);
                        setTimeout(() => { isOpeningChildModal.current = false; }, 100);
                      }}
                      className="h-6 px-2 text-xs"
                    >
                      <UserPlus className="h-3 w-3 mr-1" />
                      Assign
                    </Button>
                  </div>
                  <p className="text-sm text-muted-foreground mt-1">
                    {task.assignee_name || 'Unassigned'}
                  </p>
                </div>

                {task.agent_recommendation && (
                  <div>
                    <span className="text-sm font-medium">Recommended:</span>
                    <p className="text-sm text-muted-foreground mt-1">{task.agent_recommendation}</p>
                  </div>
                )}

                <div className="text-xs text-muted-foreground space-y-1">
                  <div>Created: {new Date(task.created_at).toLocaleDateString()}</div>
                  <div>Updated: {new Date(task.updated_at).toLocaleDateString()}</div>
                  {task.completed_at && (
                    <div>Completed: {new Date(task.completed_at).toLocaleDateString()}</div>
                  )}
                </div>
              </div>
            </div>

            {/* Assignment Info */}
            {(currentAssignment || assignments.length > 0) && (
              <div className="bg-muted/20 rounded-lg p-4">
                <h3 className="font-medium mb-3 flex items-center gap-2">
                  <Users className="h-4 w-4" />
                  Assignments
                </h3>
                
                {currentAssignment && (
                  <div className="mb-3 p-2 bg-blue-50 rounded">
                    <div className="text-sm font-medium">Current Assignment</div>
                    <div className="text-sm text-muted-foreground">
                      {currentAssignment.agent_name || 'Unassigned'}
                    </div>
                    <div className="text-xs text-muted-foreground">
                      {new Date(currentAssignment.assigned_at).toLocaleDateString()}
                    </div>
                  </div>
                )}

                {assignments.length > 0 && (
                  <div>
                    <div className="text-sm font-medium mb-2">Assignment History</div>
                    <div className="space-y-2 max-h-32 overflow-y-auto">
                      {assignments.map((assignment, index) => (
                        <div key={assignment.id || index} className="text-sm p-2 bg-gray-50 rounded">
                          <div className="font-medium">{assignment.agent_name || 'Unassigned'}</div>
                          <div className="text-xs text-muted-foreground">
                            {assignment.status} • {new Date(assignment.assigned_at).toLocaleDateString()}
                          </div>
                        </div>
                      ))}
                    </div>
                  </div>
                )}
              </div>
            )}

            {/* Tags */}
            <div className="bg-muted/20 rounded-lg p-4">
              <h3 className="font-medium mb-3">Tags</h3>
              <TagEditor
                tags={task.tags || []}
                onSave={handleSaveTags}
              />
            </div>
          </div>

          {/* Right Panel - Content */}
          <div className="lg:col-span-2 overflow-hidden flex flex-col">
            <Tabs defaultValue={contentTabs[0]?.key} className="h-full flex flex-col">
                <TabsList className="flex items-center justify-start border-b bg-muted/30 px-1 py-1 h-auto rounded-none w-full">
                  {contentTabs.map(tab => (
                    <TabsTrigger 
                      key={tab.key} 
                      value={tab.key} 
                      className="flex items-center gap-1.5 px-3 py-1.5 text-xs data-[state=active]:bg-background data-[state=active]:shadow-sm rounded-sm"
                    >
                      {tab.icon}
                      <span>{tab.label}</span>
                      {tab.isActivity && safeActivities.length > 0 && (
                        <Badge variant="secondary" className="ml-1 px-1.5 py-0 text-[10px] h-4">
                          {safeActivities.length}
                        </Badge>
                      )}
                    </TabsTrigger>
                  ))}
                  {onRefreshActivities && (
                    <Button 
                      onClick={onRefreshActivities} 
                      variant="ghost" 
                      size="sm"
                      disabled={activitiesLoading}
                      className="ml-auto h-7 px-2"
                    >
                      <RefreshCw className={`h-3 w-3 ${activitiesLoading ? 'animate-spin' : ''}`} />
                    </Button>
                  )}
                </TabsList>
                
                {contentTabs.map(tab => (
                  <TabsContent 
                    key={tab.key} 
                    value={tab.key} 
                    className="flex-1 mt-0 overflow-hidden"
                    onFocusCapture={(e) => {
                      // Prevent TabsContent from stealing focus from editor
                      if (editingContent === tab.key) {
                        e.stopPropagation()
                      }
                    }}
                  >
                    {tab.isActivity ? (
                      <div className="h-full rounded-md border bg-muted/20 p-4">
                        <TaskActivityTimeline
                          taskId={task.id}
                          activities={safeActivities}
                          loading={activitiesLoading}
                          error={activitiesError}
                          onRefresh={onRefreshActivities}
                          onAddNote={onAddNote}
                        />
                      </div>
                    ) : editingContent === tab.key ? (
                      <div 
                        className="h-full rounded-md border bg-muted/20 p-4"
                        onMouseDown={(e) => e.stopPropagation()}
                        onClick={(e) => e.stopPropagation()}
                      >
                        <MarkdownEditor
                          content={tab.content || ''}
                          onSave={(content) => handleSaveContent(tab.key, content)}
                          onCancel={() => setEditingContent(null)}
                          placeholder={`Add ${tab.label.toLowerCase()} content...`}
                        />
                      </div>
                    ) : tab.content ? (
                      <div className="h-full rounded-md border bg-muted/20">
                        <div className="flex justify-between items-center p-2 border-b">
                          <span className="text-xs font-medium text-muted-foreground">
                            {tab.label} Content
                          </span>
                          <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => setEditingContent(tab.key)}
                          >
                            <Edit className="h-3 w-3 mr-1" />
                            Edit
                          </Button>
                        </div>
                        <ScrollArea className="h-[calc(100%-40px)] p-4">
                          <div className="prose prose-sm max-w-none text-foreground">
                            <ReactMarkdown remarkPlugins={[remarkGfm]}>
                              {tab.content}
                            </ReactMarkdown>
                          </div>
                        </ScrollArea>
                      </div>
                    ) : (
                      <div className="h-full rounded-md border bg-muted/20 p-4 flex items-center justify-center">
                        <div className="text-center text-muted-foreground">
                          <FileText className="h-8 w-8 mx-auto mb-2 opacity-50" />
                          <p className="text-sm">No {tab.label.toLowerCase()} content yet</p>
                          <Button
                            variant="outline"
                            size="sm"
                            onClick={() => setEditingContent(tab.key)}
                            className="mt-2"
                          >
                            <Edit className="h-3 w-3 mr-1" />
                            Add Content
                          </Button>
                        </div>
                      </div>
                    )}
                  </TabsContent>
                ))}
              </Tabs>
          </div>
        </div>

        <div className="flex justify-between items-center pt-4 border-t">
          <div className="text-sm text-muted-foreground flex items-center gap-2">
            <span>Task ID: {task.id}</span>
            <CopyToClipboard text={task.id} />
            <span className="ml-4">Code: {task.task_code}</span>
            <CopyToClipboard text={task.task_code} />
          </div>
          <Button onClick={onClose}>
            Close
          </Button>
        </div>
      </DialogContent>

      <AssignSprintModal
        isOpen={showAssignSprintModal}
        onClose={() => setShowAssignSprintModal(false)}
        taskCode={task.task_code}
        currentSprintCode={task.sprint_code}
        onAssign={async (sprintCode) => {
          await handleSaveField('sprint_code', sprintCode)
        }}
      />

      <AssignAgentModal
        isOpen={showAssignAgentModal}
        onClose={() => setShowAssignAgentModal(false)}
        taskCode={task.task_code}
        currentAgentId={task.assignee_id}
        onAssign={async (agentId) => {
          await handleSaveField('assignee_id', agentId)
        }}
      />
    </Dialog>
  )
}