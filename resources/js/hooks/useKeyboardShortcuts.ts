import { useEffect } from 'react'
import { useLayoutStore } from '@/stores/useLayoutStore'

interface KeyboardShortcuts {
  'ctrl+[': () => void // Toggle left sidebar
  'ctrl+]': () => void // Toggle right sidebar  
  'ctrl+\\': () => void // Toggle both sidebars
}

export function useKeyboardShortcuts() {
  const { setSidebarCollapsed, toggleRightRail, preferences } = useLayoutStore()
  
  const shortcuts: KeyboardShortcuts = {
    'ctrl+[': () => {
      // Toggle left sidebar (AppSidebar handles its own state, but we can access it through the layout store)
      setSidebarCollapsed(!preferences.layout.sidebarCollapsed)
    },
    'ctrl+]': () => {
      // Toggle right sidebar
      toggleRightRail()
    },
    'ctrl+\\': () => {
      // Toggle both sidebars - if either is open, close both; if both closed, open both
      const { sidebarCollapsed, rightRailCollapsed } = preferences.layout
      const shouldCollapse = !sidebarCollapsed || !rightRailCollapsed
      
      setSidebarCollapsed(shouldCollapse)
      if (rightRailCollapsed !== shouldCollapse) {
        toggleRightRail()
      }
    },
  }

  useEffect(() => {
    const handleKeyDown = (event: KeyboardEvent) => {
      // Only handle shortcuts if not in an input/textarea/contenteditable
      const target = event.target as HTMLElement
      if (
        target.tagName === 'INPUT' ||
        target.tagName === 'TEXTAREA' ||
        target.contentEditable === 'true'
      ) {
        return
      }

      const key = event.key.toLowerCase()
      const ctrl = event.ctrlKey || event.metaKey // Support both Ctrl and Cmd
      
      if (!ctrl) return

      let shortcutKey: keyof KeyboardShortcuts | null = null

      switch (key) {
        case '[':
          shortcutKey = 'ctrl+['
          break
        case ']':
          shortcutKey = 'ctrl+]'
          break
        case '\\':
          shortcutKey = 'ctrl+\\'
          break
      }

      if (shortcutKey && shortcuts[shortcutKey]) {
        event.preventDefault()
        event.stopPropagation()
        shortcuts[shortcutKey]()
      }
    }

    document.addEventListener('keydown', handleKeyDown)
    return () => document.removeEventListener('keydown', handleKeyDown)
  }, [preferences.layout, setSidebarCollapsed, toggleRightRail])

  return shortcuts
}