import React from 'react'
import { GripVertical, MoreVertical, Pin, PinOff, Trash2 } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger
} from '@/components/ui/dropdown-menu'

export interface ChatSessionItemProps {
  session: {
    id: string
    channel_display: string
    message_count: number
    is_pinned?: boolean
  }
  isActive: boolean
  showPinHandle?: boolean
  index?: number
  keyPrefix?: string
  isDragOver?: boolean
  isDeleting?: boolean
  onSessionClick: (sessionId: string) => void
  onTogglePin: (sessionId: string, e: React.MouseEvent) => void
  onDelete: (sessionId: string, e: React.MouseEvent) => void
  onDragStart?: (e: React.DragEvent, session: any) => void
  onDragEnd?: (e: React.DragEvent) => void
  onDragOver?: (e: React.DragEvent, index: number) => void
  onDragLeave?: (e: React.DragEvent) => void
  onDrop?: (e: React.DragEvent, index: number) => void
}

export function ChatSessionItem({
  session,
  isActive,
  showPinHandle = false,
  index,
  keyPrefix = '',
  isDragOver = false,
  isDeleting = false,
  onSessionClick,
  onTogglePin,
  onDelete,
  onDragStart,
  onDragEnd,
  onDragOver,
  onDragLeave,
  onDrop
}: ChatSessionItemProps) {
  return (
    <div
      key={`${keyPrefix}${session.id}`}
      className={`flex items-center justify-between p-2 rounded-md cursor-pointer transition-all w-full ${
        isActive
          ? 'bg-gray-100 border-l-2 border-l-black'
          : 'hover:bg-gray-50'
      } ${isDragOver ? 'border-t-2 border-t-blue-500' : ''}`}
      onClick={() => onSessionClick(session.id)}
      draggable={showPinHandle}
      onDragStart={showPinHandle && onDragStart ? (e) => onDragStart(e, session) : undefined}
      onDragEnd={showPinHandle && onDragEnd ? onDragEnd : undefined}
      onDragOver={showPinHandle && typeof index === 'number' && onDragOver ? (e) => onDragOver(e, index) : undefined}
      onDragLeave={showPinHandle && onDragLeave ? onDragLeave : undefined}
      onDrop={showPinHandle && typeof index === 'number' && onDrop ? (e) => onDrop(e, index) : undefined}
    >
      <div className="flex items-center min-w-0 flex-1 max-w-[180px]">
        {showPinHandle && (
          <GripVertical className="w-3 h-3 text-gray-400 mr-2 cursor-grab flex-shrink-0" />
        )}
        <span className="text-sm truncate block">{session.channel_display}</span>
      </div>
      <div className="flex items-center space-x-1 ml-2 flex-shrink-0">
        <Badge variant="secondary" className="text-xs">
          {session.message_count}
        </Badge>
        <DropdownMenu>
          <DropdownMenuTrigger asChild>
            <Button
              variant="ghost"
              size="icon"
              className="h-6 w-6"
              onClick={(e) => e.stopPropagation()}
            >
              <MoreVertical className="w-3 h-3" />
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent key={`${keyPrefix}dropdown-${session.id}`}>
            <DropdownMenuItem
              key={`${keyPrefix}pin-${session.id}`}
              onClick={(e) => onTogglePin(session.id, e)}
            >
              {session.is_pinned ? (
                <>
                  <PinOff className="w-3 h-3 mr-2" />
                  Unpin
                </>
              ) : (
                <>
                  <Pin className="w-3 h-3 mr-2" />
                  Pin
                </>
              )}
            </DropdownMenuItem>
            <DropdownMenuItem
              key={`${keyPrefix}delete-${session.id}`}
              onClick={(e) => onDelete(session.id, e)}
              disabled={isDeleting}
            >
              <Trash2 className="w-3 h-3 mr-2" />
              Delete
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
      </div>
    </div>
  )
}
