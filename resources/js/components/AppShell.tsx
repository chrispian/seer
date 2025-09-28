import React, { useEffect } from 'react'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { ChatSessionProvider } from '@/contexts/ChatSessionContext'
import { Ribbon } from '@/islands/shell/Ribbon'
import { LeftNav } from '@/islands/shell/LeftNav'
import { ChatHeader } from '@/islands/shell/ChatHeader'
import { RightRail } from '@/islands/shell/RightRail'
import ChatIsland from '@/islands/chat/ChatIsland'
import { useAppContext } from '@/hooks/useContext'
import { useReactiveQueries } from '@/hooks/useReactiveQueries'
import { useToast } from '@/hooks/useToast'
import { ToastContainer } from '@/components/ui/toast'
import { ErrorBoundary } from '@/components/ui/error-boundary'

// Create a client
const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 1000 * 60 * 5, // 5 minutes
      retry: 1,
      refetchOnWindowFocus: false,
    },
    mutations: {
      retry: 1,
    },
  },
})

function AppContent() {
  // Initialize app context on mount - this loads the context and initializes Zustand
  useAppContext();
  
  // Enable reactive query invalidation based on context changes
  useReactiveQueries();
  
  // Toast notification system
  const { toasts, removeToast } = useToast();
  
  return (
    <ErrorBoundary>
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
        
        {/* Toast Notifications */}
        <ToastContainer toasts={toasts} onRemove={removeToast} />
      </ChatSessionProvider>
    </ErrorBoundary>
  );
}

export function AppShell() {
  return (
    <QueryClientProvider client={queryClient}>
      <AppContent />
    </QueryClientProvider>
  )
}