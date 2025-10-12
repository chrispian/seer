import React from 'react'
import { DataManagementModal, ColumnDefinition } from '@/components/ui/DataManagementModal'
import { Badge } from '@/components/ui/badge'
import { Bookmark } from 'lucide-react'

interface BookmarkItem {
  id: string | number
  name: string
  fragment_title?: string
  fragment_id?: string | number
  last_viewed_human?: string
  updated_human?: string
  enabled?: boolean
}

interface BookmarkListModalProps {
  isOpen: boolean
  onClose: () => void
  bookmarks: BookmarkItem[]
  onRefresh?: () => void
}

export function BookmarkListModal({ isOpen, onClose, bookmarks, onRefresh }: BookmarkListModalProps) {
  const columns: ColumnDefinition<BookmarkItem>[] = [
    {
      key: 'name',
      label: 'Bookmark',
      render: (bookmark: BookmarkItem) => (
        <div className="flex items-center gap-2">
          <div className="flex-shrink-0 w-8 h-8 rounded bg-amber-500/10 flex items-center justify-center">
            <Bookmark className="h-4 w-4 text-amber-500" />
          </div>
          <div className="flex flex-col">
            <span className="font-medium text-sm">{bookmark.name}</span>
            {bookmark.fragment_title && (
              <span className="text-xs text-muted-foreground line-clamp-1">
                {bookmark.fragment_title}
              </span>
            )}
          </div>
        </div>
      )
    },
    {
      key: 'last_viewed_human',
      label: 'Last Viewed',
      width: 'w-32',
      render: (bookmark: BookmarkItem) => (
        <span className="text-xs text-muted-foreground">
          {bookmark.last_viewed_human || bookmark.updated_human || '—'}
        </span>
      )
    }
  ]

  return (
    <DataManagementModal
      isOpen={isOpen}
      onClose={onClose}
      title="Bookmarks"
      data={bookmarks}
      columns={columns}
      searchPlaceholder="Search bookmarks..."
      searchFields={['name', 'fragment_title']}
      onRefresh={onRefresh}
      emptyStateMessage="No bookmarks found"
      emptyStateIcon={<Bookmark className="h-8 w-8" />}
    />
  )
}
