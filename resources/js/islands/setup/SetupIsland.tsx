import React from 'react'
import { createRoot } from 'react-dom/client'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { SetupWizard } from '@/components/SetupWizard'

// Create a QueryClient for the setup flow
const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 1000 * 60 * 5, // 5 minutes
      gcTime: 1000 * 60 * 30, // 30 minutes
      retry: (failureCount, error) => {
        if (error instanceof Error && 'status' in error && 
            typeof error.status === 'number' && error.status >= 400 && error.status < 500) {
          return false;
        }
        return failureCount < 2;
      },
      refetchOnWindowFocus: false,
      refetchOnReconnect: true,
      retryOnMount: true,
    },
    mutations: {
      retry: 1,
      networkMode: 'online',
    },
  },
})

export function mountSetupIsland() {
  // Setup island mounts
  const setupContainers = [
    'setup-welcome',
    'setup-profile', 
    'setup-avatar',
    'setup-preferences',
    'setup-complete'
  ]

  setupContainers.forEach(containerId => {
    const container = document.getElementById(containerId)
    if (container && window.setupData) {
      console.log('Mounting setup wizard for:', containerId, window.setupData)
      const root = createRoot(container)
      root.render(
        <QueryClientProvider client={queryClient}>
          <SetupWizard
            step={window.setupData.step}
            user={window.setupData.user}
            routes={window.setupData.routes}
          />
        </QueryClientProvider>
      )
    }
  })
}

// Auto-mount when the module loads
mountSetupIsland()

export default function SetupIsland() {
  return null // This component just mounts other components
}