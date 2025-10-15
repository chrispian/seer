import type { ResultConfig, DataSourceResult } from './types'

type SlotUpdateCallback = (data: DataSourceResult) => void

export class SlotBinder {
  private static instance: SlotBinder
  private slots: Map<string, SlotUpdateCallback[]> = new Map()

  static getInstance(): SlotBinder {
    if (!SlotBinder.instance) {
      SlotBinder.instance = new SlotBinder()
    }
    return SlotBinder.instance
  }

  subscribe(targetId: string, callback: SlotUpdateCallback): () => void {
    if (!this.slots.has(targetId)) {
      this.slots.set(targetId, [])
    }

    const callbacks = this.slots.get(targetId)!
    callbacks.push(callback)

    return () => {
      const index = callbacks.indexOf(callback)
      if (index > -1) {
        callbacks.splice(index, 1)
      }
    }
  }

  update(result: ResultConfig, data: DataSourceResult): void {
    const callbacks = this.slots.get(result.target)

    if (!callbacks || callbacks.length === 0) {
      console.warn(`No subscribers for slot: ${result.target}`)
      return
    }

    callbacks.forEach(callback => callback(data))
  }

  clear(targetId: string): void {
    this.slots.delete(targetId)
  }

  clearAll(): void {
    this.slots.clear()
  }
}

export const slotBinder = SlotBinder.getInstance()
