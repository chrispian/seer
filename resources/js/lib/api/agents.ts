import type { Agent } from '@/types/agent'

const BASE_URL = '/api/agents'

function getCSRFToken(): string {
  return (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || ''
}

export interface AgentFilters {
  status?: string[]
  agent_profile_id?: string
  search?: string
  limit?: number
}

export const agentsApi = {
  async list(filters?: AgentFilters): Promise<Agent[]> {
    const params = new URLSearchParams()
    
    if (filters?.status) {
      filters.status.forEach(s => params.append('status[]', s))
    }
    if (filters?.agent_profile_id) {
      params.append('agent_profile_id', filters.agent_profile_id)
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
      throw new Error(`Failed to fetch agents: ${response.statusText}`)
    }
    
    return response.json()
  },

  async get(id: string): Promise<Agent> {
    const response = await fetch(`${BASE_URL}/${id}`)
    
    if (!response.ok) {
      throw new Error(`Failed to fetch agent: ${response.statusText}`)
    }
    
    return response.json()
  },

  async create(data: Omit<Agent, 'id' | 'designation' | 'version'>): Promise<Agent> {
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
      throw new Error(error.message || `Failed to create agent: ${response.statusText}`)
    }
    
    return response.json()
  },

  async update(id: string, data: Partial<Agent>): Promise<Agent> {
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
      throw new Error(error.message || `Failed to update agent: ${response.statusText}`)
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
      throw new Error(error.message || `Failed to delete agent: ${response.statusText}`)
    }
  },

  async generateDesignation(): Promise<string> {
    const response = await fetch(`${BASE_URL}/generate-designation`)
    
    if (!response.ok) {
      throw new Error(`Failed to generate designation: ${response.statusText}`)
    }
    
    const data = await response.json()
    return data.designation
  },

  async uploadAvatar(id: string, file: File): Promise<Agent> {
    const formData = new FormData()
    formData.append('avatar', file)

    const response = await fetch(`${BASE_URL}/${id}/avatar`, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': getCSRFToken(),
      },
      body: formData,
    })

    if (!response.ok) {
      const error = await response.json()
      throw new Error(error.message || `Failed to upload avatar: ${response.statusText}`)
    }

    return response.json()
  },
}
