import React, { useState } from 'react'
import { DataManagementModal, ColumnDefinition } from '@/components/ui/DataManagementModal'
import { Badge } from '@/components/ui/badge'
import { MessageSquare, Calendar, Tag, ExternalLink } from 'lucide-react'

interface Fragment {
  id: string
  title?: string | null
  message: string
  type: string
  category?: string | null
  metadata?: Record<string, any>
  created_at: string
  updated_at: string
  created_human: string
  preview: string
}

interface FragmentListModalProps {
  isOpen: boolean
  onClose: () => void
  fragments: Fragment[]
  loading?: boolean
  error?: string | null
  onRefresh?: () => void
  onFragmentSelect?: (fragment: Fragment) => void
}

export function FragmentListModal({ 
  isOpen, 
  onClose, 
  fragments, 
  loading = false, 
  error = null,
  onRefresh,
  onFragmentSelect 
}: FragmentListModalProps) {

  const getTypeColor = (type: string) => {
    switch (type?.toLowerCase()) {
      case 'user':
        return 'bg-blue-100 text-blue-800'
      case 'assistant':
        return 'bg-purple-100 text-purple-800'
      case 'system':
        return 'bg-gray-100 text-gray-800'
      case 'note':
        return 'bg-green-100 text-green-800'
      case 'bookmark':
        return 'bg-yellow-100 text-yellow-800'
      default:
        return 'bg-gray-100 text-gray-800'
    }
  }

  const getTypeIcon = (type: string) => {
    switch (type?.toLowerCase()) {
      case 'bookmark':
        return <Tag className="h-3 w-3" />
      default:
        return <MessageSquare className="h-3 w-3" />
    }
  }

  const columns: ColumnDefinition<Fragment>[] = [
    {
      key: 'id',
      label: 'Fragment',
      render: (fragment) => (
        <div className="flex flex-col gap-1 max-w-[400px]">
          {fragment.title && (
            <span className="font-medium text-sm truncate">
              {fragment.title}
            </span>
          )}
          <span className="text-xs text-muted-foreground line-clamp-2">
            {fragment.preview}
          </span>
        </div>
      )
    },
    {
      key: 'type',
      label: 'Type',
      width: 'w-28',
      render: (fragment) => (
        <Badge variant="outline" className={`text-xs flex items-center gap-1 ${getTypeColor(fragment.type)}`}>
          {getTypeIcon(fragment.type)}
          {fragment.type}
        </Badge>
      )
    },
    {
      key: 'category',
      label: 'Category',
      width: 'w-24',
      render: (fragment) => fragment.category ? (
        <Badge variant="outline" className="text-xs">
          {fragment.category}
        </Badge>
      ) : (
        <span className="text-muted-foreground text-xs">-</span>
      )
    },
    {
      key: 'created_at',
      label: 'Created',
      width: 'w-32',
      render: (fragment) => (
        <div className="flex items-center gap-1 text-xs text-muted-foreground">
          <Calendar className="h-3 w-3" />
          <span>{fragment.created_human}</span>
        </div>
      )
    },
    {
      key: 'actions',
      label: '',
      width: 'w-12',
      render: () => (
        <div className="flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
          <ExternalLink className="h-4 w-4 text-muted-foreground" />
        </div>
      )
    }
  ]

  const filterOptions = [
    {
      key: 'type',
      label: 'Type',
      options: [
        { label: 'User', value: 'user' },
        { label: 'Assistant', value: 'assistant' },
        { label: 'System', value: 'system' },
        { label: 'Note', value: 'note' },
        { label: 'Bookmark', value: 'bookmark' }
      ]
    },
    {
      key: 'category',
      label: 'Category',
      options: Array.from(new Set(
        fragments
          .map(f => f.category)
          .filter((c): c is string => !!c)
      )).map(cat => ({ label: cat, value: cat }))
    }
  ]

  const sortOptions = [
    { label: 'Newest First', value: 'created_at_desc' },
    { label: 'Oldest First', value: 'created_at_asc' },
    { label: 'Type', value: 'type_asc' }
  ]

  const handleFragmentClick = (fragment: Fragment) => {
    console.log('Fragment clicked:', fragment.id)
    // TODO: Implement fragment navigation (T-FRAG-NAV-01)
    // This should navigate to the chat session with the fragment focused
    // and show ±5 surrounding fragments
    if (onFragmentSelect) {
      onFragmentSelect(fragment)
    } else {
      // Temporary alert until T-FRAG-NAV-01 is implemented
      alert(`Fragment Navigation\n\nClicked fragment: ${fragment.id}\n\nTask T-FRAG-NAV-01 needs to be implemented:\n- Navigate to chat session\n- Focus on this fragment\n- Show ±5 surrounding fragments\n- Enable bidirectional lazy loading`)
    }
  }

  return (
    <DataManagementModal
      isOpen={isOpen}
      onClose={onClose}
      title="Search Results"
      columns={columns}
      data={fragments}
      loading={loading}
      error={error ?? undefined}
      onRefresh={onRefresh}
      onRowClick={handleFragmentClick}
      searchPlaceholder="Search fragments..."
      searchFields={['title', 'message', 'preview', 'type', 'category']}
      filters={filterOptions}
      emptyStateMessage="No fragments found"
      clickableRows={true}
    />
  )
}
