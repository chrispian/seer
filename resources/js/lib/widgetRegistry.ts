import React from 'react'
import {
  TodayActivityWidget,
  RecentBookmarksWidget,
  SessionInfoWidget,
  ToolCallsWidget,
  InboxWidget,
  TypeSystemWidget,
  SchedulerWidget,
} from '@/widgets'

// Widget registry for dynamic loading
export const widgetRegistry = {
  TodayActivityWidget,
  RecentBookmarksWidget,
  SessionInfoWidget,
  ToolCallsWidget,
  InboxWidget,
  TypeSystemWidget,
  SchedulerWidget,
} as const

export type WidgetComponentName = keyof typeof widgetRegistry

// Widget metadata for display and configuration
export interface WidgetMetadata {
  id: string
  name: string
  description: string
  component: WidgetComponentName
  category: 'activity' | 'content' | 'system' | 'productivity'
  defaultEnabled: boolean
  requiresAuth?: boolean
  minHeight?: number
  maxHeight?: number
}

export const widgetMetadata: Record<string, WidgetMetadata> = {
  'today-activity': {
    id: 'today-activity',
    name: 'Today\'s Activity',
    description: 'Shows recent activity metrics including messages, commands, tokens, and costs',
    component: 'TodayActivityWidget',
    category: 'activity',
    defaultEnabled: true,
    minHeight: 200,
    maxHeight: 400,
  },
  'recent-bookmarks': {
    id: 'recent-bookmarks',
    name: 'Recent Bookmarks',
    description: 'Displays recently bookmarked fragments and content',
    component: 'RecentBookmarksWidget',
    category: 'content',
    defaultEnabled: true,
    minHeight: 150,
    maxHeight: 300,
  },
  'tool-calls': {
    id: 'tool-calls',
    name: 'Tool Calls & Reasoning',
    description: 'Shows recent tool executions and AI reasoning steps',
    component: 'ToolCallsWidget',
    category: 'activity',
    defaultEnabled: true,
    minHeight: 200,
    maxHeight: 500,
  },
  'inbox': {
    id: 'inbox',
    name: 'Inbox',
    description: 'Fragment review queue and pending actions',
    component: 'InboxWidget',
    category: 'productivity',
    defaultEnabled: false,
    requiresAuth: true,
    minHeight: 250,
    maxHeight: 600,
  },
  'type-system': {
    id: 'type-system',
    name: 'Type System',
    description: 'Manages and displays type definitions and schemas',
    component: 'TypeSystemWidget',
    category: 'system',
    defaultEnabled: false,
    minHeight: 200,
    maxHeight: 400,
  },
  'scheduler': {
    id: 'scheduler',
    name: 'Scheduler',
    description: 'Task scheduling and automation management',
    component: 'SchedulerWidget',
    category: 'productivity',
    defaultEnabled: false,
    requiresAuth: true,
    minHeight: 180,
    maxHeight: 350,
  },
  'session-info': {
    id: 'session-info',
    name: 'Session Info',
    description: 'Current session context and metadata',
    component: 'SessionInfoWidget',
    category: 'system',
    defaultEnabled: true,
    minHeight: 100,
    maxHeight: 150,
  },
}

// Helper functions
export function getWidgetComponent(componentName: WidgetComponentName): React.ComponentType {
  return widgetRegistry[componentName]
}

export function getWidgetMetadata(widgetId: string): WidgetMetadata | null {
  return widgetMetadata[widgetId] || null
}

export function getWidgetsByCategory(category: WidgetMetadata['category']): WidgetMetadata[] {
  return Object.values(widgetMetadata).filter(widget => widget.category === category)
}

export function getAllWidgetIds(): string[] {
  return Object.keys(widgetMetadata)
}

export function isValidWidgetId(widgetId: string): boolean {
  return widgetId in widgetMetadata
}