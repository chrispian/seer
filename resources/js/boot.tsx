import React from 'react'
import { AuthModal } from '@/islands/AuthModal'
import { AppShell } from '@/components/AppShell'

export function bootIslands(mount: (id: string, node: React.ReactNode) => void, boot: any) {
  // Check if we're on a setup page
  const isSetupPage = window.location.pathname.startsWith('/setup')
  const isSettingsPage = window.location.pathname.startsWith('/settings')
  
  if (isSetupPage) {
    // Setup pages handle their own mounting via setupData
    import('@/islands/setup/SetupIsland').then(() => {
      console.log('Setup island loaded')
    }).catch(error => {
      console.error('Failed to load setup island:', error)
    })
    return
  }

  if (isSettingsPage) {
    // Settings pages handle their own mounting via settingsData
    import('@/islands/settings/SettingsIsland')
    return
  }

  // Mount the new integrated AppShell for main app
  mount('app-root', <AppShell />)

  if (!boot.isAuthenticated) {
    mount('overlays-root', <AuthModal defaultTab={boot.hasUsers ? 'login' : 'signup'} />)
  }
}

