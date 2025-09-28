import React from 'react'
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog"

export function AuthModal({ defaultTab }: { defaultTab: 'login'|'signup' }) {
  return (
    <Dialog defaultOpen>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>{defaultTab === 'signup' ? 'Create account' : 'Log in'}</DialogTitle>
        </DialogHeader>
        <div className="text-sm text-muted-foreground">
          (Auth form goes here)
        </div>
      </DialogContent>
    </Dialog>
  )
}

