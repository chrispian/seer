import React from 'react'
import { AuthModal } from '@/islands/AuthModal'
import { AppShell } from '@/components/AppShell'

export function bootIslands(mount: (id: string, node: React.ReactNode) => void, boot: any) {
  // Mount the new integrated AppShell
  mount('app-root', <AppShell />)

  if (!boot.isAuthenticated) {
    mount('overlays-root', <AuthModal defaultTab={boot.hasUsers ? 'login' : 'signup'} />)
  }
}

