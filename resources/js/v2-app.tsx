import { createRoot } from 'react-dom/client'
import { V2ShellPage } from './v2/V2ShellPage'
import { registerComponents } from './components/v2/registerComponents'
import { Toaster } from 'sonner'

declare global {
  interface Window {
    __V2_BOOT__?: {
      isAuthenticated: boolean
      hasUsers: boolean
      user: { id: number; name: string; email: string } | null
      pageKey: string
    }
  }
}

registerComponents()

document.addEventListener('DOMContentLoaded', () => {
  const boot = window.__V2_BOOT__
  const rootEl = document.getElementById('v2-root')

  if (!boot || !rootEl) {
    console.warn('V2 boot data or root element not found')
    return
  }

  if (!boot.isAuthenticated) {
    console.warn('User not authenticated')
    rootEl.innerHTML = '<div class="p-4 text-red-500">Authentication required</div>'
    return
  }

  const pageKey = rootEl.dataset.pageKey || boot.pageKey

  if (!pageKey) {
    console.error('No page key provided')
    rootEl.innerHTML = '<div class="p-4 text-red-500">No page key specified</div>'
    return
  }

  createRoot(rootEl).render(
    <>
      <V2ShellPage pageKey={pageKey} />
      <Toaster />
    </>
  )
})
