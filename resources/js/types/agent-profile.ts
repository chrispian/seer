export interface AgentProfile {
  id?: string
  name: string
  slug: string
  type: string
  mode: string
  status: string
  description?: string
  capabilities?: string[]
  constraints?: string[]
  tools?: string[]
  metadata?: Record<string, any>
  active_assignments?: number
  total_assignments?: number
  created_at?: string
  updated_at?: string
}
