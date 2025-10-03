import React from 'react'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { ScrollArea } from '@/components/ui/scroll-area'
import { Inbox } from 'lucide-react'
import { InboxWidget } from '@/widgets'

interface InboxModalProps {
  isOpen: boolean
  onClose: () => void
}

export function InboxModal({ isOpen, onClose }: InboxModalProps) {
  return (
    <Dialog open={isOpen} onOpenChange={onClose}>
      <DialogContent className="max-w-6xl max-h-[90vh] rounded-sm">
        <DialogHeader>
          <DialogTitle className="text-foreground flex items-center gap-2">
            <Inbox className="w-5 h-5" />
            Inbox Management
          </DialogTitle>
          <DialogDescription className="text-muted-foreground">
            Review and process fragments in your inbox. Accept, archive, or skip items with bulk operations and filtering.
          </DialogDescription>
        </DialogHeader>
        
        <ScrollArea className="max-h-[75vh] w-full">
          <div className="p-1">
            <InboxWidget />
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