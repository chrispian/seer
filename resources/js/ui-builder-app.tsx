import { createRoot } from 'react-dom/client'
import {
  ShellPage,
  registerCoreComponents,
  registerPrimitiveComponents,
  registerLayoutComponents,
  registerNavigationComponents,
  registerCompositeComponents,
  registerAdvancedComponents,
  registerFormComponents,
  commandHandler,
} from '@hollis-labs/ui-builder'

const rootElement = document.getElementById('root')
if (!rootElement) {
  throw new Error('Root element not found')
}

const pageKey = rootElement.dataset.pageKey || ''
const isAuthenticated = rootElement.dataset.isAuthenticated === 'true'
const hasUsers = rootElement.dataset.hasUsers === 'true'
const user = isAuthenticated && rootElement.dataset.user 
  ? JSON.parse(rootElement.dataset.user) 
  : null

// Initialize the command handler
console.log('Command handler initialized:', commandHandler)

// Register all components
registerCoreComponents()
registerPrimitiveComponents()
registerLayoutComponents()
registerNavigationComponents()
registerCompositeComponents()
registerAdvancedComponents()
registerFormComponents()

const root = createRoot(rootElement)
root.render(
  <ShellPage 
    pageKey={pageKey}
    isAuthenticated={isAuthenticated}
    hasUsers={hasUsers}
    user={user}
  />
)
