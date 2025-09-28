import React from 'react'
import { ChatSessionProvider } from '@/contexts/ChatSessionContext'
import { Ribbon } from '@/islands/shell/Ribbon'
import { LeftNav } from '@/islands/shell/LeftNav'
import { ChatHeader } from '@/islands/shell/ChatHeader'
import { RightRail } from '@/islands/shell/RightRail'
import ChatIsland from '@/islands/chat/ChatIsland'

export function AppShell() {
  return (
    <ChatSessionProvider>
      <div className="h-screen flex bg-background">
        {/* Far Left Ribbon */}
        <Ribbon />
        
        {/* Left Navigation */}
        <LeftNav />
        
        {/* Main Content */}
        <div className="flex-1 flex flex-col min-w-0">
          <ChatHeader />
          <ChatIsland />
        </div>
        
        {/* Right Sidebar */}
        <RightRail />
      </div>
    </ChatSessionProvider>
  )
}