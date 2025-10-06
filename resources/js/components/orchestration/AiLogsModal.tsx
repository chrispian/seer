import React, { useState } from 'react'
import { DataManagementModal, ColumnDefinition } from '@/components/ui/DataManagementModal'
import { Badge } from '@/components/ui/badge'
import { Activity, Server, AlertCircle, CheckCircle, Clock, Code } from 'lucide-react'
import ReactMarkdown from 'react-markdown'

interface AiLog {
  id: string
  source_type: string
  source_file: string
  log_level: string
  log_timestamp: string
  service: string
  message: string
  session_id?: string
  provider?: string
  model?: string
  tool_calls?: any
  structured_data?: any
  file_modified_at: string
  created_at: string
  expanded_content: string
}

interface AiLogsModalProps {
  isOpen: boolean
  onClose: () => void
  logs: AiLog[]
  loading?: boolean
  error?: string | null
  onRefresh?: () => void
}

export function AiLogsModal({ 
  isOpen, 
  onClose, 
  logs, 
  loading = false, 
  error = null,
  onRefresh 
}: AiLogsModalProps) {

  const getSourceTypeColor = (sourceType: string) => {
    switch (sourceType?.toLowerCase()) {
      case 'codex':
        return 'bg-purple-100 text-purple-800'
      case 'opencode':
        return 'bg-blue-100 text-blue-800'
      case 'claude_desktop':
        return 'bg-green-100 text-green-800'
      case 'claude_mcp':
        return 'bg-emerald-100 text-emerald-800'
      case 'claude_projects':
        return 'bg-teal-100 text-teal-800'
      default:
        return 'bg-gray-100 text-gray-800'
    }
  }

  const getLogLevelColor = (level: string) => {
    switch (level?.toLowerCase()) {
      case 'error':
        return 'bg-red-100 text-red-800'
      case 'warn':
      case 'warning':
        return 'bg-yellow-100 text-yellow-800'
      case 'info':
        return 'bg-blue-100 text-blue-800'
      case 'debug':
        return 'bg-gray-100 text-gray-800'
      default:
        return 'bg-gray-100 text-gray-800'
    }
  }

  const getLogLevelIcon = (level: string) => {
    switch (level?.toLowerCase()) {
      case 'error':
        return <AlertCircle className="h-3 w-3" />
      case 'warn':
      case 'warning':
        return <Clock className="h-3 w-3" />
      case 'info':
        return <CheckCircle className="h-3 w-3" />
      case 'debug':
        return <Code className="h-3 w-3" />
      default:
        return <Activity className="h-3 w-3" />
    }
  }

  const expandedContent = (log: AiLog) => (
    <div className="space-y-3 text-sm max-w-none">
      <div className="prose prose-sm max-w-none">
        <ReactMarkdown>{log.expanded_content}</ReactMarkdown>
      </div>
    </div>
  )

  const columns: ColumnDefinition<AiLog>[] = [
    {
      key: 'log_timestamp',
      label: 'Time',
      width: 'w-32',
      render: (log) => (
        <div className="flex flex-col">
          <span className="text-xs text-muted-foreground">
            {new Date(log.log_timestamp).toLocaleDateString()}
          </span>
          <span className="text-xs font-mono">
            {new Date(log.log_timestamp).toLocaleTimeString()}
          </span>
        </div>
      )
    },
    {
      key: 'source_type',
      label: 'Source',
      width: 'w-24',
      render: (log) => (
        <Badge variant="outline" className={`text-xs ${getSourceTypeColor(log.source_type)}`}>
          {log.source_type}
        </Badge>
      )
    },
    {
      key: 'log_level',
      label: 'Level',
      width: 'w-20',
      render: (log) => (
        <Badge variant="outline" className={`text-xs flex items-center gap-1 ${getLogLevelColor(log.log_level)}`}>
          {getLogLevelIcon(log.log_level)}
          {log.log_level?.toUpperCase()}
        </Badge>
      )
    },
    {
      key: 'service',
      label: 'Service',
      width: 'w-24',
      render: (log) => (
        <span className="text-xs text-muted-foreground">
          {log.service || '-'}
        </span>
      )
    },
    {
      key: 'message',
      label: 'Message',
      render: (log) => (
        <div className="flex flex-col max-w-[400px]">
          <span className="text-sm truncate">
            {log.message || 'No message'}
          </span>
          {log.source_file && (
            <span className="text-xs text-muted-foreground truncate mt-0.5">
              {log.source_file.split('/').pop()} {/* Show only filename */}
            </span>
          )}
          <div className="flex gap-2 mt-1">
            {log.provider && (
              <Badge variant="secondary" className="text-xs">
                {log.provider}
              </Badge>
            )}
            {log.model && (
              <Badge variant="secondary" className="text-xs">
                {log.model}
              </Badge>
            )}
            {log.tool_calls && (
              <Badge variant="secondary" className="text-xs">
                <Code className="h-3 w-3 mr-1" />
                Tools
              </Badge>
            )}
          </div>
        </div>
      )
    },
    {
      key: 'session_id',
      label: 'Session',
      width: 'w-24',
      render: (log) => log.session_id ? (
        <span className="text-xs font-mono text-muted-foreground">
          {log.session_id.substring(0, 8)}...
        </span>
      ) : <span className="text-xs text-muted-foreground">-</span>
    },
    {
      key: 'actions',
      label: '',
      width: 'w-12'
    }
  ]

  const filters = [
    {
      key: 'source_type',
      label: 'Source',
      options: [
        { value: 'all', label: 'All', count: logs.length },
        { value: 'codex', label: 'Codex', count: logs.filter(l => l.source_type === 'codex').length },
        { value: 'opencode', label: 'OpenCode', count: logs.filter(l => l.source_type === 'opencode').length },
        { value: 'claude_desktop', label: 'Claude Desktop', count: logs.filter(l => l.source_type === 'claude_desktop').length },
        { value: 'claude_mcp', label: 'Claude MCP', count: logs.filter(l => l.source_type === 'claude_mcp').length },
        { value: 'claude_projects', label: 'Claude Projects', count: logs.filter(l => l.source_type === 'claude_projects').length }
      ]
    },
    {
      key: 'log_level',
      label: 'Level',
      options: [
        { value: 'all', label: 'All', count: logs.length },
        { value: 'error', label: 'Error', count: logs.filter(l => l.log_level === 'error').length },
        { value: 'warn', label: 'Warning', count: logs.filter(l => l.log_level === 'warn' || l.log_level === 'warning').length },
        { value: 'info', label: 'Info', count: logs.filter(l => l.log_level === 'info').length },
        { value: 'debug', label: 'Debug', count: logs.filter(l => l.log_level === 'debug').length }
      ]
    },
    {
      key: 'provider',
      label: 'Provider',
      options: [
        { value: 'all', label: 'All', count: logs.length },
        { value: 'anthropic', label: 'Anthropic', count: logs.filter(l => l.provider === 'anthropic').length },
        { value: 'openai', label: 'OpenAI', count: logs.filter(l => l.provider === 'openai').length },
        { value: 'ollama', label: 'Ollama', count: logs.filter(l => l.provider === 'ollama').length }
      ]
    }
  ]

  const actionItems = [
    { key: 'expand', label: 'View Details' }
  ]

  return (
    <DataManagementModal
      isOpen={isOpen}
      onClose={onClose}
      title="AI Logs"
      data={logs}
      columns={columns}
      loading={loading}
      error={error}
      filters={filters}
      searchPlaceholder="Search logs by message, session, or service..."
      searchFields={['message', 'session_id', 'service', 'provider', 'model']}
      expandedContent={expandedContent}
      onAction={(action, log) => {
        // Actions handled by expand button in DataManagementModal
      }}
      actionItems={actionItems}
      onRefresh={onRefresh}
      customHeader={
        <div className="text-sm text-muted-foreground">
          View and analyze AI interaction logs from Codex, OpenCode, and Claude. Click any row to expand details.
        </div>
      }
      emptyStateMessage="No AI logs found"
      emptyStateIcon={<Activity className="h-8 w-8" />}
      defaultSort="log_timestamp"
      defaultSortDirection="desc"
      clickableRows={true}
      expandedContentMaxWidth="90%"
    />
  )
}