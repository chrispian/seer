import React from 'react'
import { createRoot } from 'react-dom/client'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { SettingsPage } from '@/components/SettingsPage'

// Create query client for settings page
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

// Settings island mount
const container = document.getElementById('settings-app')
if (container && window.settingsData) {
  try {
    const root = createRoot(container)
    root.render(
      <QueryClientProvider client={queryClient}>
        <SettingsPage
          user={window.settingsData.user}
          profileSettings={window.settingsData.profile_settings}
          routes={window.settingsData.routes}
        />
      </QueryClientProvider>
    )
  } catch (error) {
    console.error('Error rendering settings page:', error)
    container.innerHTML = `<div class="p-8">Error loading settings. Please check browser console.</div>`
  }
} else if (container) {
  container.innerHTML = '<div class="p-8">Settings data not available. Please refresh the page.</div>'
}

export default function SettingsIsland() {
  return null // This component just mounts other components
}