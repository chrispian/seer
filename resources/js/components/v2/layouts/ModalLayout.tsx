import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import type { ComponentConfig } from '../types'

interface ModalLayoutProps {
  config: ComponentConfig
  title?: string
  open: boolean
  onOpenChange: (open: boolean) => void
  children: React.ReactNode
}

export function ModalLayout({ title, open, onOpenChange, children }: ModalLayoutProps) {
  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-4xl max-h-[90vh] overflow-y-auto">
        {title && (
          <DialogHeader>
            <DialogTitle>{title}</DialogTitle>
          </DialogHeader>
        )}
        <div className="space-y-4">{children}</div>
      </DialogContent>
    </Dialog>
  )
}
