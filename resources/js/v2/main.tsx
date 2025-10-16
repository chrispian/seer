import { createRoot } from 'react-dom/client'
import { V2ShellPage } from './V2ShellPage'
import {
  registerPrimitiveComponents,
  registerLayoutComponents,
  registerNavigationComponents,
  registerCompositeComponents,
  registerAdvancedComponents,
  registerFormComponents,
} from '@/components/v2/ComponentRegistry'
import { commandHandler } from '@/components/v2/CommandHandler'

const rootElement = document.getElementById('v2-root')
if (!rootElement) {
  throw new Error('Root element not found')
}

const pageKey = rootElement.dataset.pageKey || ''
const isAuthenticated = rootElement.dataset.isAuthenticated === 'true'
const hasUsers = rootElement.dataset.hasUsers === 'true'
const user = isAuthenticated && rootElement.dataset.user 
  ? JSON.parse(rootElement.dataset.user) 
  : null

// Initialize the command handler (it auto-registers listeners)
console.log('Command handler initialized:', commandHandler)

registerPrimitiveComponents()
registerLayoutComponents()
registerNavigationComponents()
registerCompositeComponents()
registerAdvancedComponents()
registerFormComponents()

const root = createRoot(rootElement)
root.render(
  <V2ShellPage 
    pageKey={pageKey}
    isAuthenticated={isAuthenticated}
    hasUsers={hasUsers}
    user={user}
  />
)
