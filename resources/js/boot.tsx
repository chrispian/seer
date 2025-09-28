import React from 'react'
import { AuthModal } from '@/islands/AuthModal'
import ChatIsland from '@/islands/chat/ChatIsland'
import { Ribbon } from '@/islands/shell/Ribbon'
import { LeftNav } from '@/islands/shell/LeftNav'
import { ChatHeader } from '@/islands/shell/ChatHeader'
import { RightRail } from '@/islands/shell/RightRail'

export function bootIslands(mount: (id: string, node: React.ReactNode) => void, boot: any) {
  mount('ribbon-root', <Ribbon />)
  mount('left-nav-root', <LeftNav />)
  mount('chat-header-root', <ChatHeader />)

  // ✅ real chat in the main content
  mount('chat-transcript-root', <ChatIsland />)

  mount('right-rail-root', <RightRail />)

  if (!boot.isAuthenticated) {
    mount('overlays-root', <AuthModal defaultTab={boot.hasUsers ? 'login' : 'signup'} />)
  }
}

