import * as React from "react"
import { 
  Dialog, 
  DialogContent, 
  DialogDescription, 
  DialogFooter, 
  DialogHeader, 
  DialogTitle 
} from "./dialog"
import { Button } from "./button"

interface AlertDialogProps {
  open: boolean
  onOpenChange: (open: boolean) => void
  children: React.ReactNode
}

export function AlertDialog({ open, onOpenChange, children }: AlertDialogProps) {
  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      {children}
    </Dialog>
  )
}

export function AlertDialogContent({ children, ...props }: React.ComponentProps<typeof DialogContent>) {
  return <DialogContent {...props}>{children}</DialogContent>
}

export function AlertDialogHeader({ children, ...props }: React.ComponentProps<typeof DialogHeader>) {
  return <DialogHeader {...props}>{children}</DialogHeader>
}

export function AlertDialogTitle({ children, ...props }: React.ComponentProps<typeof DialogTitle>) {
  return <DialogTitle {...props}>{children}</DialogTitle>
}

export function AlertDialogDescription({ children, ...props }: React.ComponentProps<typeof DialogDescription>) {
  return <DialogDescription {...props}>{children}</DialogDescription>
}

export function AlertDialogFooter({ children, ...props }: React.ComponentProps<typeof DialogFooter>) {
  return <DialogFooter {...props}>{children}</DialogFooter>
}

interface AlertDialogActionProps extends React.ComponentProps<typeof Button> {
  onClick?: () => void
}

export function AlertDialogAction({ children, onClick, ...props }: AlertDialogActionProps) {
  return (
    <Button onClick={onClick} {...props}>
      {children}
    </Button>
  )
}

export function AlertDialogCancel({ children, onClick, ...props }: AlertDialogActionProps) {
  return (
    <Button variant="outline" onClick={onClick} {...props}>
      {children}
    </Button>
  )
}