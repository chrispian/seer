import React, { useState } from 'react'
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
  Clock, 
  AlertCircle, 
  User, 
  Calendar, 
  FileText, 
  Settings,
  Eye,
  Users,
  ArrowLeft
} from 'lucide-react'
import ReactMarkdown from 'react-markdown'
import remarkGfm from 'remark-gfm'

interface Task {
  id: string
  task_code: string
  task_name: string
  description?: string
  status: string
  delegation_status: string
  priority?: string
  agent_recommendation?: string
  estimate_text?: string
  sprint_code?: string
  tags?: string[]
  created_at: string
  updated_at: string
  completed_at?: string
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

interface TaskDetailModalProps {
  isOpen: boolean
  onClose: () => void
  task: Task
  currentAssignment?: Assignment | null
  assignments?: Assignment[]
  content?: TaskContent
  loading?: boolean
  error?: string | null
  onBack?: () => void
}

export function TaskDetailModal({ 
  isOpen, 
  onClose, 
  task,
  currentAssignment,
  assignments = [],
  content = {},
  loading = false, 
  error = null,
  onBack
}: TaskDetailModalProps) {

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

  const contentTabs = [
    { key: 'agent', label: 'Agent Profile', icon: <User className="h-4 w-4" />, content: content.agent },
    { key: 'plan', label: 'Plan', icon: <FileText className="h-4 w-4" />, content: content.plan },
    { key: 'context', label: 'Context', icon: <Settings className="h-4 w-4" />, content: content.context },
    { key: 'todo', label: 'Todo', icon: <CheckCircle className="h-4 w-4" />, content: content.todo },
    { key: 'summary', label: 'Summary', icon: <Eye className="h-4 w-4" />, content: content.summary },
  ].filter(tab => tab.content && tab.content.trim())

  const hasAnyContent = contentTabs.length > 0

  return (
    <Dialog open={isOpen} onOpenChange={onClose}>
      <DialogContent className="max-w-6xl max-h-[90vh] rounded-sm">
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
        
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 h-[calc(90vh-120px)]">
          {/* Left Panel - Task Info */}
          <div className="lg:col-span-1 space-y-4">
            <div className="bg-muted/20 rounded-lg p-4">
              <h3 className="font-medium mb-3">Task Details</h3>
              
              <div className="space-y-3">
                <div>
                  <span className="text-sm font-medium">Name:</span>
                  <p className="text-sm text-muted-foreground mt-1">{task.task_name}</p>
                </div>

                {task.description && (
                  <div>
                    <span className="text-sm font-medium">Description:</span>
                    <p className="text-sm text-muted-foreground mt-1">{task.description}</p>
                  </div>
                )}

                <div className="flex flex-wrap gap-2">
                  <Badge variant="outline" className={`text-xs ${getStatusColor(task.status)}`}>
                    {task.status}
                  </Badge>
                  <Badge variant="outline" className={`text-xs ${getDelegationStatusColor(task.delegation_status)}`}>
                    {task.delegation_status}
                  </Badge>
                  {task.priority && (
                    <Badge variant="outline" className={`text-xs ${getPriorityColor(task.priority)}`}>
                      {task.priority}
                    </Badge>
                  )}
                </div>

                {task.sprint_code && (
                  <div>
                    <span className="text-sm font-medium">Sprint:</span>
                    <p className="text-sm text-muted-foreground mt-1">{task.sprint_code}</p>
                  </div>
                )}

                {task.estimate_text && (
                  <div>
                    <span className="text-sm font-medium">Estimate:</span>
                    <p className="text-sm text-muted-foreground mt-1">{task.estimate_text}</p>
                  </div>
                )}

                {task.agent_recommendation && (
                  <div>
                    <span className="text-sm font-medium">Recommended Agent:</span>
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
            {task.tags && task.tags.length > 0 && (
              <div className="bg-muted/20 rounded-lg p-4">
                <h3 className="font-medium mb-3">Tags</h3>
                <div className="flex flex-wrap gap-1">
                  {task.tags.map(tag => (
                    <Badge key={tag} variant="outline" className="text-xs">
                      {tag}
                    </Badge>
                  ))}
                </div>
              </div>
            )}
          </div>

          {/* Right Panel - Content */}
          <div className="lg:col-span-2">
            {hasAnyContent ? (
              <Tabs defaultValue={contentTabs[0]?.key} className="h-full">
                <TabsList className="grid w-full" style={{ gridTemplateColumns: `repeat(${contentTabs.length}, minmax(0, 1fr))` }}>
                  {contentTabs.map(tab => (
                    <TabsTrigger key={tab.key} value={tab.key} className="flex items-center gap-1">
                      {tab.icon}
                      <span className="hidden sm:inline">{tab.label}</span>
                    </TabsTrigger>
                  ))}
                </TabsList>
                
                {contentTabs.map(tab => (
                  <TabsContent key={tab.key} value={tab.key} className="h-[calc(100%-40px)]">
                    <ScrollArea className="h-full rounded-md border bg-muted/20 p-4">
                      <div className="prose prose-sm max-w-none text-foreground">
                        <ReactMarkdown remarkPlugins={[remarkGfm]}>
                          {tab.content || ''}
                        </ReactMarkdown>
                      </div>
                    </ScrollArea>
                  </TabsContent>
                ))}
              </Tabs>
            ) : (
              <div className="h-full flex items-center justify-center bg-muted/20 rounded-lg">
                <div className="text-center">
                  <FileText className="h-12 w-12 text-muted-foreground mx-auto mb-4" />
                  <h3 className="text-lg font-medium text-muted-foreground mb-2">
                    No Content Available
                  </h3>
                  <p className="text-sm text-muted-foreground">
                    This task doesn't have any delegation content (agent profiles, plans, etc.)
                  </p>
                </div>
              </div>
            )}
          </div>
        </div>

        <div className="flex justify-between items-center pt-4 border-t">
          <div className="text-sm text-muted-foreground">
            Task ID: {task.id}
          </div>
          <Button onClick={onClose}>
            Close
          </Button>
        </div>
      </DialogContent>
    </Dialog>
  )
}