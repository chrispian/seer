import React from 'react'
import { createRoot } from 'react-dom/client'
import { SetupWizard } from '@/components/SetupWizard'

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
        <SetupWizard
          step={window.setupData.step}
          user={window.setupData.user}
          routes={window.setupData.routes}
        />
      )
    }
  })
}

// Auto-mount when the module loads
mountSetupIsland()

export default function SetupIsland() {
  return null // This component just mounts other components
}