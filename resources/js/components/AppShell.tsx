import React, { useEffect } from 'react'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { ChatSessionProvider } from '@/contexts/ChatSessionContext'
import { Ribbon } from '@/islands/shell/Ribbon'
import { AppSidebar } from '@/components/AppSidebar'
import { SidebarProvider, SidebarInset, SidebarTrigger } from '@/components/ui/sidebar'
import { ChatHeader } from '@/islands/shell/ChatHeader'
import { RightRail } from '@/islands/shell/RightRail'
import ChatIsland from '@/islands/chat/ChatIsland'
import { useAppContext } from '@/hooks/useContext'
import { useReactiveQueries } from '@/hooks/useReactiveQueries'
import { useToast } from '@/hooks/useToast'
import { useKeyboardNavigation } from '@/hooks/useKeyboardNavigation'
import { useOfflineSupport } from '@/hooks/useOfflineSupport'
import { ToastContainer } from '@/components/ui/toast'
import { ErrorBoundary } from '@/components/ui/error-boundary'

// Create an optimized query client with performance-focused defaults
const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 1000 * 60 * 5, // 5 minutes - good default for most data
      gcTime: 1000 * 60 * 30, // 30 minutes garbage collection time  
      retry: (failureCount, error) => {
        // Don't retry on 4xx errors (client errors)
        if (error instanceof Error && 'status' in error && 
            typeof error.status === 'number' && error.status >= 400 && error.status < 500) {
          return false;
        }
        return failureCount < 2; // Retry up to 2 times for other errors
      },
      refetchOnWindowFocus: false,
      refetchOnReconnect: true, // Refetch when connection is restored
      retryOnMount: true,
    },
    mutations: {
      retry: 1,
      networkMode: 'online', // Only run mutations when online
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
  
  // Keyboard navigation and accessibility
  useKeyboardNavigation();
  
  // Offline support and optimistic updates
  const { isOnline, prefetchCriticalData } = useOfflineSupport();
  
  // Prefetch critical data when online
  useEffect(() => {
    if (isOnline) {
      prefetchCriticalData();
    }
  }, [isOnline, prefetchCriticalData]);
  
  return (
    <ErrorBoundary>
      <ChatSessionProvider>
        <div className="h-screen flex bg-background">
          {/* Far Left Ribbon */}
          <Ribbon />
          
          {/* Compact Sidebar */}
          <AppSidebar />
          
          {/* Main Content - Dashboard Layout */}
          <main className="flex-1 flex flex-col min-w-0 bg-muted/20">
            {/* Dashboard Header Section */}
            <header className="flex-shrink-0 bg-background border-b">
              <ChatHeader />
            </header>
            
            {/* Dashboard Content Grid */}
            <div className="flex-1 min-h-0 p-4 md:p-6 lg:p-8">
              <div className="h-full max-w-[1400px] mx-auto">
                {/* Dashboard Grid Container */}
                <div className="grid grid-cols-1 gap-4 md:gap-6 h-full">
                  {/* Main Content Area */}
                  <div className="min-h-0">
                    <div className="h-full rounded-lg border bg-card text-card-foreground shadow-sm overflow-hidden">
                      <ChatIsland />
                    </div>
                  </div>
                  
                  {/* Future: Could add dashboard widgets here if needed
                      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <!-- Dashboard widgets would go here -->
                      </div>
                  */}
                </div>
              </div>
            </div>
          </main>
          
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