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
      console.log('Message copied to clipboard')
      
      // Show visual feedback (could be enhanced with toast)
      const button = document.querySelector(`[data-message-id="${messageId}"] [data-action="copy"]`) as HTMLElement
      if (button) {
        const originalHTML = button.innerHTML
        button.innerHTML = '✅'
        button.classList.add('text-green-400')
        
        setTimeout(() => {
          button.innerHTML = originalHTML
          button.classList.remove('text-green-400')
        }, 2000)
      }
    } catch (error) {
      console.error('Failed to copy message:', error)
      // Fallback for older browsers
      try {
        const textArea = document.createElement('textarea')
        textArea.value = content
        document.body.appendChild(textArea)
        textArea.select()
        document.execCommand('copy')
        document.body.removeChild(textArea)
        console.log('Message copied to clipboard (fallback)')
      } catch (fallbackError) {
        console.error('Fallback copy also failed:', fallbackError)
      }
    }
  }

  const handleBookmarkToggle = async () => {
    if (isTogglingBookmark) return
    
    setIsTogglingBookmark(true)
    try {
      if (!bookmarked) {
        // Create a new fragment from the chat message
        const response = await fetch('/api/fragment', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrf,
          },
          body: JSON.stringify({
            message: content,
            type: 'chat', // or some appropriate type for chat messages
          }),
        })
        
        if (!response.ok) {
          throw new Error('Failed to create fragment')
        }
        
        const fragmentData = await response.json()
        const fragmentId = fragmentData.id
        
        // Now bookmark the created fragment
        const bookmarkResponse = await fetch(`/api/fragments/${fragmentId}/bookmark`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrf,
          },
        })
        
        if (!bookmarkResponse.ok) {
          throw new Error('Failed to bookmark fragment')
        }
        
        setBookmarked(true)
        onBookmarkToggle?.(messageId, true)
      } else {
        // For now, we can't easily remove bookmarks from chat messages
        // since we don't store the fragment ID mapping
        // This could be enhanced by storing fragment IDs with chat messages
        console.log('Removing bookmark not implemented for chat messages')
        setBookmarked(false)
        onBookmarkToggle?.(messageId, false)
      }
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
      // For chat messages, we just remove them from local state
      // No API call needed since they're not stored as fragments
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
      <div className={`flex items-center space-x-0.5 opacity-0 group-hover:opacity-100 transition-opacity ${className}`} data-message-id={messageId}>
        {/* Copy Button */}
        <Button
          variant="ghost"
          size="icon"
          onClick={handleCopy}
          title="Copy message"
          className="h-6 w-6 rounded-sm"
          data-action="copy"
        >
          <Copy className="w-3 h-3" />
        </Button>

        {/* Bookmark Button */}
        <Button
          variant="ghost"
          size="icon"
          onClick={handleBookmarkToggle}
          disabled={isTogglingBookmark}
          title={bookmarked ? "Remove bookmark" : "Bookmark message"}
          className={`h-6 w-6 rounded-sm ${bookmarked ? 'text-yellow-600' : ''}`}
        >
          <Bookmark className={`w-3 h-3 ${bookmarked ? 'fill-current' : ''}`} />
        </Button>

        {/* More Actions Dropdown */}
        <DropdownMenu>
          <DropdownMenuTrigger asChild>
            <Button
              variant="ghost"
              size="icon"
              title="More actions"
              className="h-6 w-6 rounded-sm"
            >
              <MoreVertical className="w-3 h-3" />
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end" className="rounded-sm">
            <DropdownMenuItem 
              onClick={() => setShowDeleteDialog(true)}
              className="text-destructive focus:text-destructive rounded-sm"
            >
              <Trash2 className="w-3 h-3 mr-2" />
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