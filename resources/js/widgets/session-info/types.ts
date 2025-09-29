export interface SessionInfoData {
  sessionId: number | null
  shortCode: string
  title: string
  modelProvider: string | null
  modelName: string | null
  vault: {
    id: number
    name: string
  } | null
  project: {
    id: number
    name: string
  } | null
  messageCount: number
  lastActivity: string | null
  isActive: boolean
  metadata: Record<string, any>
}