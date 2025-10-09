import type { AgentProfile } from '@/types/agent-profile'

const BASE_URL = '/api/agent-profiles'

function getCSRFToken(): string {
  return (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || ''
}

export interface AgentProfileFilters {
  status?: string[]
  type?: string[]
  mode?: string[]
  search?: string
  limit?: number
}

export const agentProfilesApi = {
  async list(filters?: AgentProfileFilters): Promise<AgentProfile[]> {
    const params = new URLSearchParams()
    
    if (filters?.status) {
      filters.status.forEach(s => params.append('status[]', s))
    }
    if (filters?.type) {
      filters.type.forEach(t => params.append('type[]', t))
    }
    if (filters?.mode) {
      filters.mode.forEach(m => params.append('mode[]', m))
    }
    if (filters?.search) {
      params.append('search', filters.search)
    }
    if (filters?.limit) {
      params.append('limit', filters.limit.toString())
    }

    const url = params.toString() ? `${BASE_URL}?${params}` : BASE_URL
    const response = await fetch(url)
    
    if (!response.ok) {
      throw new Error(`Failed to fetch agent profiles: ${response.statusText}`)
    }
    
    return response.json()
  },

  async get(id: string): Promise<AgentProfile> {
    const response = await fetch(`${BASE_URL}/${id}`)
    
    if (!response.ok) {
      throw new Error(`Failed to fetch agent profile: ${response.statusText}`)
    }
    
    return response.json()
  },

  async create(data: Omit<AgentProfile, 'id'>): Promise<AgentProfile> {
    const response = await fetch(BASE_URL, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': getCSRFToken(),
      },
      body: JSON.stringify(data),
    })
    
    if (!response.ok) {
      const error = await response.json()
      throw new Error(error.message || `Failed to create agent profile: ${response.statusText}`)
    }
    
    return response.json()
  },

  async update(id: string, data: Partial<AgentProfile>): Promise<AgentProfile> {
    const response = await fetch(`${BASE_URL}/${id}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': getCSRFToken(),
      },
      body: JSON.stringify(data),
    })
    
    if (!response.ok) {
      const error = await response.json()
      throw new Error(error.message || `Failed to update agent profile: ${response.statusText}`)
    }
    
    return response.json()
  },

  async delete(id: string): Promise<void> {
    const response = await fetch(`${BASE_URL}/${id}`, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': getCSRFToken(),
      },
    })
    
    if (!response.ok) {
      const error = await response.json()
      throw new Error(error.message || `Failed to delete agent profile: ${response.statusText}`)
    }
  },

  async duplicate(id: string): Promise<AgentProfile> {
    const original = await this.get(id)
    
    const duplicated: Omit<AgentProfile, 'id'> = {
      name: `${original.name} (Copy)`,
      slug: `${original.slug}-copy-${Date.now()}`,
      type: original.type,
      mode: original.mode,
      status: original.status,
      description: original.description,
      capabilities: original.capabilities,
      constraints: original.constraints,
      tools: original.tools,
      metadata: original.metadata,
    }
    
    return this.create(duplicated)
  },
}
