import type { ComponentType } from 'react'
import type { ComponentConfig } from './types'

export interface RendererProps {
  config: ComponentConfig
  [key: string]: any
}

type ComponentRenderer = ComponentType<RendererProps>

export class ComponentRegistry {
  private static instance: ComponentRegistry
  private registry: Map<string, ComponentRenderer> = new Map()

  static getInstance(): ComponentRegistry {
    if (!ComponentRegistry.instance) {
      ComponentRegistry.instance = new ComponentRegistry()
    }
    return ComponentRegistry.instance
  }

  register(type: string, component: ComponentRenderer): void {
    this.registry.set(type, component)
  }

  get(type: string): ComponentRenderer | undefined {
    return this.registry.get(type)
  }

  has(type: string): boolean {
    return this.registry.has(type)
  }

  unregister(type: string): void {
    this.registry.delete(type)
  }

  clear(): void {
    this.registry.clear()
  }

  getAll(): Map<string, ComponentRenderer> {
    return new Map(this.registry)
  }
}

export const componentRegistry = ComponentRegistry.getInstance()
