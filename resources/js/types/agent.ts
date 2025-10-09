import type { AgentProfile } from './agent-profile'

export interface Agent {
  id?: string
  name: string
  designation: string
  avatar_path?: string
  avatar_url?: string
  agent_profile_id: string
  agent_profile?: AgentProfile
  persona?: string
  tool_config?: Record<string, any>
  metadata?: Record<string, any>
  version: number
  status: string
  created_at?: string
  updated_at?: string
  deleted_at?: string
}
