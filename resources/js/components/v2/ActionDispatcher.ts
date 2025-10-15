import type { ActionConfig, ActionRequest, ActionResult } from './types'

function getCSRFToken(): string {
  return (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || ''
}

function interpolateParams(params: Record<string, any>, context: Record<string, any>): Record<string, any> {
  const result: Record<string, any> = {}
  
  for (const [key, value] of Object.entries(params)) {
    if (typeof value === 'string' && value.startsWith('{{') && value.endsWith('}}')) {
      const path = value.slice(2, -2).trim()
      const keys = path.split('.')
      let current: any = context
      
      for (const k of keys) {
        current = current?.[k]
      }
      
      result[key] = current
    } else {
      result[key] = value
    }
  }
  
  return result
}

export class ActionDispatcher {
  private static instance: ActionDispatcher

  static getInstance(): ActionDispatcher {
    if (!ActionDispatcher.instance) {
      ActionDispatcher.instance = new ActionDispatcher()
    }
    return ActionDispatcher.instance
  }

  async execute(action: ActionConfig, context: Record<string, any> = {}): Promise<ActionResult> {
    const params = action.params ? interpolateParams(action.params, context) : {}

    if (action.type === 'navigate') {
      return this.handleNavigate(action.route!, params)
    }

    if (action.type === 'command') {
      return this.handleCommand(action.command!, params)
    }

    if (action.type === 'api') {
      return this.handleApi(action, params)
    }

    throw new Error(`Unknown action type: ${action.type}`)
  }

  private async handleCommand(command: string, params: Record<string, any>): Promise<ActionResult> {
    const request: ActionRequest = {
      type: 'command',
      command,
      params,
    }

    try {
      const response = await fetch('/api/v2/ui/action', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': getCSRFToken(),
        },
        body: JSON.stringify(request),
      })

      if (!response.ok) {
        throw new Error(`Action failed: ${response.statusText}`)
      }

      const result: ActionResult = await response.json()
      return result
    } catch (error) {
      return {
        success: false,
        message: error instanceof Error ? error.message : 'Action failed',
      }
    }
  }

  private async handleNavigate(route: string, params: Record<string, any>): Promise<ActionResult> {
    const request: ActionRequest = {
      type: 'navigate',
      route,
      params,
    }

    try {
      const response = await fetch('/api/v2/ui/action', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': getCSRFToken(),
        },
        body: JSON.stringify(request),
      })

      if (!response.ok) {
        throw new Error(`Navigation failed: ${response.statusText}`)
      }

      const result: ActionResult = await response.json()

      if (result.redirect) {
        window.location.href = result.redirect
      }

      return result
    } catch (error) {
      return {
        success: false,
        message: error instanceof Error ? error.message : 'Navigation failed',
      }
    }
  }

  private async handleApi(action: any, params: Record<string, any>): Promise<ActionResult> {
    try {
      const data = action.data ? { ...action.data, ...params } : params

      const response = await window.fetch(action.url, {
        method: action.method || 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': getCSRFToken(),
        },
        body: JSON.stringify(data),
      })

      if (!response.ok) {
        const errorData = await response.json().catch(() => ({}))
        throw new Error(errorData.message || `API call failed: ${response.statusText}`)
      }

      const result = await response.json()
      return result
    } catch (error) {
      return {
        success: false,
        message: error instanceof Error ? error.message : 'API call failed',
      }
    }
  }
}

export const actionDispatcher = ActionDispatcher.getInstance()
