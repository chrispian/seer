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
import { Calendar } from 'lucide-react'
import { SchedulerWidget } from '@/widgets'

interface SchedulerModalProps {
  isOpen: boolean
  onClose: () => void
}

export function SchedulerModal({ isOpen, onClose }: SchedulerModalProps) {
  return (
    <Dialog open={isOpen} onOpenChange={onClose}>
      <DialogContent className="max-w-5xl max-h-[90vh] rounded-sm">
        <DialogHeader>
          <DialogTitle className="text-foreground flex items-center gap-2">
            <Calendar className="w-5 h-5" />
            Scheduler
          </DialogTitle>
          <DialogDescription className="text-muted-foreground">
            Monitor scheduled commands, view execution history, and track the health of your automation system.
          </DialogDescription>
        </DialogHeader>
        
        <ScrollArea className="max-h-[75vh] w-full">
          <div className="p-1">
            <SchedulerWidget />
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