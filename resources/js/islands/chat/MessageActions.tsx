import React, { useState } from 'react'
import { Button } from '@/components/ui/button'
import { 
  DropdownMenu, 
  DropdownMenuContent, 
  DropdownMenuItem, 
  DropdownMenuTrigger 
} from '@/components/ui/dropdown-menu'
import { 
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from '@/components/ui/alert-dialog'
import { Copy, Bookmark, Trash2, MoreVertical } from 'lucide-react'

interface MessageActionsProps {
  messageId: string
  content: string
  isBookmarked?: boolean
  onDelete?: (messageId: string) => void
  onBookmarkToggle?: (messageId: string, bookmarked: boolean) => void
  className?: string
}

function useCsrf() {
  return (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || ''
}

export function MessageActions({ 
  messageId, 
  content, 
  isBookmarked = false,
  onDelete,
  onBookmarkToggle,
  className = ""
}: MessageActionsProps) {
  const [showDeleteDialog, setShowDeleteDialog] = useState(false)
  const [isDeleting, setIsDeleting] = useState(false)
  const [isTogglingBookmark, setIsTogglingBookmark] = useState(false)
  const [bookmarked, setBookmarked] = useState(isBookmarked)
  const csrf = useCsrf()

  const handleCopy = async () => {
    try {
      await navigator.clipboard.writeText(content)
      // Could show a toast notification here
      console.log('Message copied to clipboard')
    } catch (error) {
      console.error('Failed to copy message:', error)
      // Fallback for older browsers
      const textArea = document.createElement('textarea')
      textArea.value = content
      document.body.appendChild(textArea)
      textArea.select()
      document.execCommand('copy')
      document.body.removeChild(textArea)
    }
  }

  const handleBookmarkToggle = async () => {
    if (isTogglingBookmark) return
    
    setIsTogglingBookmark(true)
    try {
      const response = await fetch(`/api/fragments/${messageId}/bookmark`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrf,
        },
      })
      
      if (!response.ok) {
        throw new Error('Failed to toggle bookmark')
      }
      
      const data = await response.json()
      const newBookmarkState = data.bookmarked
      
      setBookmarked(newBookmarkState)
      onBookmarkToggle?.(messageId, newBookmarkState)
    } catch (error) {
      console.error('Failed to toggle bookmark:', error)
      // Could show error toast here
    } finally {
      setIsTogglingBookmark(false)
    }
  }

  const handleDelete = async () => {
    if (isDeleting) return
    
    setIsDeleting(true)
    try {
      const response = await fetch(`/api/fragments/${messageId}`, {
        method: 'DELETE',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrf,
        },
      })
      
      if (!response.ok) {
        throw new Error('Failed to delete message')
      }
      
      onDelete?.(messageId)
      setShowDeleteDialog(false)
    } catch (error) {
      console.error('Failed to delete message:', error)
      // Could show error toast here
    } finally {
      setIsDeleting(false)
    }
  }

  return (
    <>
      <div className={`flex items-center space-x-1 opacity-0 group-hover:opacity-100 transition-opacity ${className}`}>
        {/* Copy Button */}
        <Button
          variant="ghost"
          size="icon"
          onClick={handleCopy}
          title="Copy message"
          className="h-8 w-8"
        >
          <Copy className="w-4 h-4" />
        </Button>

        {/* Bookmark Button */}
        <Button
          variant="ghost"
          size="icon"
          onClick={handleBookmarkToggle}
          disabled={isTogglingBookmark}
          title={bookmarked ? "Remove bookmark" : "Bookmark message"}
          className={`h-8 w-8 ${bookmarked ? 'text-yellow-500' : ''}`}
        >
          <Bookmark className={`w-4 h-4 ${bookmarked ? 'fill-current' : ''}`} />
        </Button>

        {/* More Actions Dropdown */}
        <DropdownMenu>
          <DropdownMenuTrigger asChild>
            <Button
              variant="ghost"
              size="icon"
              title="More actions"
              className="h-8 w-8"
            >
              <MoreVertical className="w-4 h-4" />
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end">
            <DropdownMenuItem 
              onClick={() => setShowDeleteDialog(true)}
              className="text-destructive focus:text-destructive"
            >
              <Trash2 className="w-4 h-4 mr-2" />
              Delete message
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
      </div>

      {/* Delete Confirmation Dialog */}
      <AlertDialog open={showDeleteDialog} onOpenChange={setShowDeleteDialog}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Delete Message</AlertDialogTitle>
            <AlertDialogDescription>
              Are you sure you want to delete this message? This action cannot be undone.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>Cancel</AlertDialogCancel>
            <AlertDialogAction
              onClick={handleDelete}
              disabled={isDeleting}
              className="bg-destructive hover:bg-destructive/90"
            >
              {isDeleting ? 'Deleting...' : 'Delete'}
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </>
  )
}