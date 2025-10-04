import React, { useState } from 'react'
import { Button } from '@/components/ui/button'
import { 
  DropdownMenu, 
  DropdownMenuContent, 
  DropdownMenuItem, 
  DropdownMenuTrigger 
} from '@/components/ui/dropdown-menu'
import { 
  Menubar,
  MenubarContent,
  MenubarItem,
  MenubarMenu,
  MenubarTrigger,
} from '@/components/ui/menubar'
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
import { useQueryClient } from '@tanstack/react-query'

interface MessageActionsProps {
  messageId: string // Client-side ID for React
  serverMessageId?: string // Server-side message ID from API
  serverFragmentId?: string // Server-side fragment ID if exists
  content: string
  isBookmarked?: boolean
  onDelete?: (messageId: string) => void
  onBookmarkToggle?: (messageId: string, bookmarked: boolean, fragmentId?: string) => void
  className?: string
}

function useCsrf() {
  return (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || ''
}

export function MessageActions({ 
  messageId,
  serverMessageId,
  serverFragmentId,
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
  const queryClient = useQueryClient()

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
        // If we already have a fragment ID, bookmark it directly
        if (serverFragmentId) {
          const bookmarkResponse = await fetch(`/api/fragments/${serverFragmentId}/bookmark`, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': csrf,
            },
          })
          
          if (!bookmarkResponse.ok) {
            throw new Error('Failed to bookmark existing fragment')
          }
          
          setBookmarked(true)
          onBookmarkToggle?.(messageId, true, serverFragmentId)
          
          // Invalidate bookmark queries to refresh the widget
          queryClient.invalidateQueries({ queryKey: ['widgets', 'bookmarks'] })
        } else {
          // Create a new fragment from the chat message
          const response = await fetch('/api/fragment', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': csrf,
            },
            body: JSON.stringify({
              message: content,
              type: 'chat',
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
          onBookmarkToggle?.(messageId, true, fragmentId)
          
          // Invalidate bookmark queries to refresh the widget
          queryClient.invalidateQueries({ queryKey: ['widgets', 'bookmarks'] })
        }
      } else {
        // Remove bookmark if we have a fragment ID
        if (serverFragmentId) {
          const bookmarkResponse = await fetch(`/api/fragments/${serverFragmentId}/bookmark`, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': csrf,
            },
          })
          
          if (!bookmarkResponse.ok) {
            throw new Error('Failed to unbookmark fragment')
          }
          
          setBookmarked(false)
          onBookmarkToggle?.(messageId, false)
          
          // Invalidate bookmark queries to refresh the widget
          queryClient.invalidateQueries({ queryKey: ['widgets', 'bookmarks'] })
        } else {
          console.log('Cannot remove bookmark: no fragment ID available')
          // Just update local state for now
          setBookmarked(false)
          onBookmarkToggle?.(messageId, false)
          
          // Still invalidate queries in case of local state changes
          queryClient.invalidateQueries({ queryKey: ['widgets', 'bookmarks'] })
        }
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
      // If we have a server fragment ID, try to delete it from the server
      if (serverFragmentId) {
        const response = await fetch(`/api/fragments/${serverFragmentId}`, {
          method: 'DELETE',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrf,
          },
        })
        
        if (!response.ok) {
          throw new Error('Failed to delete fragment from server')
        }
      }
      
      // Always remove from local state (even if server delete fails)
      onDelete?.(messageId)
      setShowDeleteDialog(false)
    } catch (error) {
      console.error('Failed to delete message:', error)
      // Still remove from local state even if server delete failed
      onDelete?.(messageId)
      setShowDeleteDialog(false)
    } finally {
      setIsDeleting(false)
    }
  }

  return (
    <>
      <div className={`${className}`} data-message-id={messageId}>
        <Menubar className="h-8 bg-gray-50 border-gray-300 rounded-md shadow-sm">
          {/* Copy Button */}
          <MenubarMenu>
            <MenubarTrigger 
              onClick={handleCopy}
              className="h-6 px-2 text-xs data-[state=open]:bg-primary data-[state=open]:text-primary-foreground hover:bg-gray-100"
              data-action="copy"
              title="Copy message"
            >
              <Copy className="w-3 h-3" />
            </MenubarTrigger>
          </MenubarMenu>

          {/* Bookmark Button */}
          <MenubarMenu>
            <MenubarTrigger
              onClick={handleBookmarkToggle}
              disabled={isTogglingBookmark}
              className={`h-6 px-2 text-xs data-[state=open]:bg-primary data-[state=open]:text-primary-foreground hover:bg-gray-100 ${bookmarked ? 'text-yellow-600' : ''}`}
              title={bookmarked ? "Remove bookmark" : "Bookmark message"}
            >
              <Bookmark className={`w-3 h-3 ${bookmarked ? 'fill-current' : ''}`} />
            </MenubarTrigger>
          </MenubarMenu>

          {/* More Actions Dropdown */}
          <MenubarMenu>
            <MenubarTrigger className="h-6 px-2 text-xs data-[state=open]:bg-primary data-[state=open]:text-primary-foreground hover:bg-gray-100" title="More actions">
              <MoreVertical className="w-3 h-3" />
            </MenubarTrigger>
            <MenubarContent align="end" className="rounded-sm">
              <MenubarItem 
                onClick={() => setShowDeleteDialog(true)}
                className="text-destructive focus:text-destructive rounded-sm"
              >
                <Trash2 className="w-3 h-3 mr-2" />
                Delete message
              </MenubarItem>
            </MenubarContent>
          </MenubarMenu>
        </Menubar>
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