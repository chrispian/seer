import { create } from 'zustand'
import { persist } from 'zustand/middleware'

// Widget configuration types
export interface WidgetConfig {
  id: string
  name: string
  component: string
  enabled: boolean
  position: number
  section: 'main' | 'footer'
  minimized: boolean
}

// Layout configuration
export interface LayoutConfig {
  rightRailWidth: 'narrow' | 'normal' | 'wide'
  sidebarCollapsed: boolean
  rightRailCollapsed: boolean
  widgetSpacing: 'compact' | 'normal' | 'comfortable'
  theme: 'light' | 'dark' | 'auto'
}

// Widget arrangements and customization preferences
export interface LayoutPreferences {
  widgets: WidgetConfig[]
  layout: LayoutConfig
  lastModified: string
}

interface LayoutStore {
  // State
  preferences: LayoutPreferences
  isCustomizing: boolean
  draggedWidget: string | null
  
  // Widget management actions
  toggleWidget: (widgetId: string) => void
  reorderWidgets: (widgetIds: string[]) => void
  moveWidgetToSection: (widgetId: string, section: 'main' | 'footer') => void
  toggleWidgetMinimized: (widgetId: string) => void
  resetWidgetLayout: () => void
  
  // Layout configuration actions
  setRightRailWidth: (width: 'narrow' | 'normal' | 'wide') => void
  setSidebarCollapsed: (collapsed: boolean) => void
  setRightRailCollapsed: (collapsed: boolean) => void
  toggleRightRail: () => void
  setWidgetSpacing: (spacing: 'compact' | 'normal' | 'comfortable') => void
  setTheme: (theme: 'light' | 'dark' | 'auto') => void
  
  // Customization mode actions
  setCustomizing: (customizing: boolean) => void
  setDraggedWidget: (widgetId: string | null) => void
  
  // Utility functions
  getWidgetsBySection: (section: 'main' | 'footer') => WidgetConfig[]
  getEnabledWidgets: () => WidgetConfig[]
  exportPreferences: () => string
  importPreferences: (preferencesJson: string) => void
}

// Default widget configuration
const defaultWidgets: WidgetConfig[] = [
  {
    id: 'today-activity',
    name: 'Today\'s Activity',
    component: 'TodayActivityWidget',
    enabled: true,
    position: 0,
    section: 'main',
    minimized: false,
  },
  {
    id: 'recent-bookmarks',
    name: 'Recent Bookmarks',
    component: 'RecentBookmarksWidget',
    enabled: true,
    position: 1,
    section: 'main',
    minimized: false,
  },
  {
    id: 'todos',
    name: 'Recent Todos',
    component: 'TodoWidget',
    enabled: true,
    position: 2,
    section: 'main',
    minimized: false,
  },
  {
    id: 'tool-calls',
    name: 'Tool Calls & Reasoning',
    component: 'ToolCallsWidget',
    enabled: true,
    position: 3,
    section: 'main',
    minimized: false,
  },
  {
    id: 'inbox',
    name: 'Inbox',
    component: 'InboxWidget',
    enabled: false,
    position: 4,
    section: 'main',
    minimized: false,
  },
  {
    id: 'type-system',
    name: 'Type System',
    component: 'TypeSystemWidget',
    enabled: false,
    position: 5,
    section: 'main',
    minimized: false,
  },
  {
    id: 'scheduler',
    name: 'Scheduler',
    component: 'SchedulerWidget',
    enabled: false,
    position: 6,
    section: 'main',
    minimized: false,
  },
  {
    id: 'session-info',
    name: 'Session Info',
    component: 'SessionInfoWidget',
    enabled: true,
    position: 0,
    section: 'footer',
    minimized: false,
  },
]

const defaultLayout: LayoutConfig = {
  rightRailWidth: 'normal',
  sidebarCollapsed: false,
  rightRailCollapsed: false,
  widgetSpacing: 'normal',
  theme: 'auto',
}

const defaultPreferences: LayoutPreferences = {
  widgets: defaultWidgets,
  layout: defaultLayout,
  lastModified: new Date().toISOString(),
}

export const useLayoutStore = create<LayoutStore>()(
  persist(
    (set, get) => ({
      // Initial state
      preferences: defaultPreferences,
      isCustomizing: false,
      draggedWidget: null,
      
      // Widget management actions
      toggleWidget: (widgetId: string) => {
        set((state) => {
          const existingWidget = state.preferences.widgets.find(w => w.id === widgetId)
          
          if (existingWidget) {
            // Widget exists, toggle its enabled state
            return {
              preferences: {
                ...state.preferences,
                widgets: state.preferences.widgets.map((widget) =>
                  widget.id === widgetId ? { ...widget, enabled: !widget.enabled } : widget
                ),
                lastModified: new Date().toISOString(),
              },
            }
          } else {
            // Widget doesn't exist, add it from defaults
            const defaultWidget = defaultWidgets.find(w => w.id === widgetId)
            if (defaultWidget) {
              return {
                preferences: {
                  ...state.preferences,
                  widgets: [...state.preferences.widgets, { ...defaultWidget, enabled: true }],
                  lastModified: new Date().toISOString(),
                },
              }
            }
            return state
          }
        })
      },
      
      reorderWidgets: (widgetIds: string[]) => {
        set((state) => {
          const widgetMap = new Map(state.preferences.widgets.map(w => [w.id, w]))
          const reorderedWidgets = widgetIds.map((id, index) => ({
            ...widgetMap.get(id)!,
            position: index,
          }))
          
          return {
            preferences: {
              ...state.preferences,
              widgets: reorderedWidgets,
              lastModified: new Date().toISOString(),
            },
          }
        })
      },
      
      moveWidgetToSection: (widgetId: string, section: 'main' | 'footer') => {
        set((state) => {
          const widgets = state.preferences.widgets.map((widget) => {
            if (widget.id === widgetId) {
              return { ...widget, section }
            }
            return widget
          })
          
          // Reposition widgets within their sections
          const mainWidgets = widgets.filter(w => w.section === 'main').sort((a, b) => a.position - b.position)
          const footerWidgets = widgets.filter(w => w.section === 'footer').sort((a, b) => a.position - b.position)
          
          const repositionedWidgets = [
            ...mainWidgets.map((w, i) => ({ ...w, position: i })),
            ...footerWidgets.map((w, i) => ({ ...w, position: i })),
          ]
          
          return {
            preferences: {
              ...state.preferences,
              widgets: repositionedWidgets,
              lastModified: new Date().toISOString(),
            },
          }
        })
      },
      
      toggleWidgetMinimized: (widgetId: string) => {
        set((state) => ({
          preferences: {
            ...state.preferences,
            widgets: state.preferences.widgets.map((widget) =>
              widget.id === widgetId ? { ...widget, minimized: !widget.minimized } : widget
            ),
            lastModified: new Date().toISOString(),
          },
        }))
      },
      
      resetWidgetLayout: () => {
        set({
          preferences: {
            ...defaultPreferences,
            layout: get().preferences.layout, // Keep layout settings
            lastModified: new Date().toISOString(),
          },
        })
      },
      
      // Layout configuration actions
      setRightRailWidth: (width: 'narrow' | 'normal' | 'wide') => {
        set((state) => ({
          preferences: {
            ...state.preferences,
            layout: { ...state.preferences.layout, rightRailWidth: width },
            lastModified: new Date().toISOString(),
          },
        }))
      },
      
      setSidebarCollapsed: (collapsed: boolean) => {
        set((state) => ({
          preferences: {
            ...state.preferences,
            layout: { ...state.preferences.layout, sidebarCollapsed: collapsed },
            lastModified: new Date().toISOString(),
          },
        }))
      },
      
      setRightRailCollapsed: (collapsed: boolean) => {
        set((state) => ({
          preferences: {
            ...state.preferences,
            layout: { ...state.preferences.layout, rightRailCollapsed: collapsed },
            lastModified: new Date().toISOString(),
          },
        }))
      },
      
      toggleRightRail: () => {
        set((state) => ({
          preferences: {
            ...state.preferences,
            layout: { 
              ...state.preferences.layout, 
              rightRailCollapsed: !state.preferences.layout.rightRailCollapsed 
            },
            lastModified: new Date().toISOString(),
          },
        }))
      },
      
      setWidgetSpacing: (spacing: 'compact' | 'normal' | 'comfortable') => {
        set((state) => ({
          preferences: {
            ...state.preferences,
            layout: { ...state.preferences.layout, widgetSpacing: spacing },
            lastModified: new Date().toISOString(),
          },
        }))
      },
      
      setTheme: (theme: 'light' | 'dark' | 'auto') => {
        set((state) => ({
          preferences: {
            ...state.preferences,
            layout: { ...state.preferences.layout, theme },
            lastModified: new Date().toISOString(),
          },
        }))
      },
      
      // Customization mode actions
      setCustomizing: (customizing: boolean) => {
        set({ isCustomizing: customizing })
      },
      
      setDraggedWidget: (widgetId: string | null) => {
        set({ draggedWidget: widgetId })
      },
      
      // Utility functions
      getWidgetsBySection: (section: 'main' | 'footer') => {
        const state = get()
        return state.preferences.widgets
          .filter((widget) => widget.section === section && widget.enabled)
          .sort((a, b) => a.position - b.position)
      },
      
      getEnabledWidgets: () => {
        const state = get()
        return state.preferences.widgets.filter((widget) => widget.enabled)
      },
      
      exportPreferences: () => {
        const state = get()
        return JSON.stringify(state.preferences, null, 2)
      },
      
      importPreferences: (preferencesJson: string) => {
        try {
          const preferences = JSON.parse(preferencesJson)
          set({
            preferences: {
              ...preferences,
              lastModified: new Date().toISOString(),
            },
          })
        } catch (error) {
          console.error('Failed to import preferences:', error)
        }
      },
    }),
    {
      name: 'seer-layout-store',
      // Persist all preferences but not the temporary customization state
      partialize: (state) => ({
        preferences: state.preferences,
      }),
    }
  )
)