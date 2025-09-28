import React from 'react'
import { Hello } from '@/islands/Hello'
import { AuthModal } from '@/islands/AuthModal'
import ChatIsland from '@/islands/chat/ChatIsland'

export function bootIslands(mount: (id: string, node: React.ReactNode) => void, boot: any) {
  mount('ribbon-root', <Hello where="Ribbon" />)
  mount('left-nav-root', <Hello where="Left Nav" />)
  mount('chat-header-root', <Hello where="Header" />)

  // ✅ real chat in the main content
  mount('chat-transcript-root', <ChatIsland />)

  mount('right-rail-root', <Hello where="Right Rail" />)

  if (!boot.isAuthenticated) {
    mount('overlays-root', <AuthModal defaultTab={boot.hasUsers ? 'login' : 'signup'} />)
  }
}

