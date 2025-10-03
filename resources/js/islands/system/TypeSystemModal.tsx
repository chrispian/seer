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
import { Package } from 'lucide-react'
import { TypeSystemWidget } from '@/widgets'

interface TypeSystemModalProps {
  isOpen: boolean
  onClose: () => void
}

export function TypeSystemModal({ isOpen, onClose }: TypeSystemModalProps) {
  return (
    <Dialog open={isOpen} onOpenChange={onClose}>
      <DialogContent className="max-w-4xl max-h-[90vh] rounded-sm">
        <DialogHeader>
          <DialogTitle className="text-foreground flex items-center gap-2">
            <Package className="w-5 h-5" />
            Type System
          </DialogTitle>
          <DialogDescription className="text-muted-foreground">
            Manage type packs, view validation status, and monitor fragment type distribution across your system.
          </DialogDescription>
        </DialogHeader>
        
        <ScrollArea className="max-h-[75vh] w-full">
          <div className="p-1">
            <TypeSystemWidget />
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