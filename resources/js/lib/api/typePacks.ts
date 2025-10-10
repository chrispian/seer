const BASE_URL = '/api/types'

function getCSRFToken(): string {
  return (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || ''
}

export interface TypePack {
  slug: string
  name: string
  description: string
  version: string
  capabilities: string[]
  ui: {
    icon?: string
    color?: string
    display_name?: string
    plural_name?: string
  }
  schema?: object
  indexes?: object
  source_path?: string
}

export interface CreateTypePackData {
  slug: string
  name: string
  description?: string
  version?: string
  schema?: object
  capabilities?: string[]
  ui?: {
    icon?: string
    color?: string
    display_name?: string
    plural_name?: string
  }
  indexes?: object
}

export interface UpdateTypePackData {
  name?: string
  description?: string
  version?: string
  schema?: object
  capabilities?: string[]
  ui?: {
    icon?: string
    color?: string
    display_name?: string
    plural_name?: string
  }
  indexes?: object
}

export interface TypePackTemplate {
  name: string
  description: string
  schema: object
  capabilities?: string[]
  ui?: object
}

export interface ValidationResult {
  valid: boolean
  errors?: Record<string, string[]>
}

export const typePacksApi = {
  async list(): Promise<{ data: TypePack[]; total: number }> {
    const response = await fetch(BASE_URL, {
      headers: {
        'Accept': 'application/json',
      },
    })
    if (!response.ok) throw new Error('Failed to fetch type packs')
    return response.json()
  },

  async get(slug: string): Promise<{ data: TypePack }> {
    const response = await fetch(`${BASE_URL}/${slug}`, {
      headers: {
        'Accept': 'application/json',
      },
    })
    if (!response.ok) throw new Error(`Failed to fetch type pack: ${slug}`)
    return response.json()
  },

  async create(data: CreateTypePackData): Promise<{ data: TypePack; message: string }> {
    const response = await fetch(BASE_URL, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': getCSRFToken(),
      },
      body: JSON.stringify(data),
    })
    if (!response.ok) {
      const error = await response.json()
      throw new Error(error.message || 'Failed to create type pack')
    }
    return response.json()
  },

  async update(slug: string, data: UpdateTypePackData): Promise<{ data: TypePack; message: string }> {
    const response = await fetch(`${BASE_URL}/${slug}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': getCSRFToken(),
      },
      body: JSON.stringify(data),
    })
    if (!response.ok) {
      const error = await response.json()
      throw new Error(error.message || 'Failed to update type pack')
    }
    return response.json()
  },

  async delete(slug: string, deleteFragments = false): Promise<{ message: string; deleted_fragments: number }> {
    const url = new URL(`${window.location.origin}${BASE_URL}/${slug}`)
    if (deleteFragments) {
      url.searchParams.append('delete_fragments', 'true')
    }

    const response = await fetch(url.toString(), {
      method: 'DELETE',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': getCSRFToken(),
      },
    })
    if (!response.ok) {
      const error = await response.json()
      throw new Error(error.message || 'Failed to delete type pack')
    }
    return response.json()
  },

  async templates(): Promise<{ data: Record<string, TypePackTemplate> }> {
    const response = await fetch(`${BASE_URL}/templates`, {
      headers: {
        'Accept': 'application/json',
      },
    })
    if (!response.ok) throw new Error('Failed to fetch templates')
    return response.json()
  },

  async createFromTemplate(data: {
    template: string
    slug: string
    name: string
  }): Promise<{ data: TypePack; message: string }> {
    const response = await fetch(`${BASE_URL}/from-template`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': getCSRFToken(),
      },
      body: JSON.stringify(data),
    })
    if (!response.ok) {
      const error = await response.json()
      throw new Error(error.message || 'Failed to create from template')
    }
    return response.json()
  },

  async validateSchema(slug: string, sampleData: object): Promise<ValidationResult> {
    const response = await fetch(`${BASE_URL}/${slug}/validate-schema`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': getCSRFToken(),
      },
      body: JSON.stringify({ sample_data: sampleData }),
    })
    if (!response.ok) throw new Error('Failed to validate schema')
    return response.json()
  },

  async refreshCache(slug: string): Promise<{ message: string }> {
    const response = await fetch(`${BASE_URL}/${slug}/refresh-cache`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': getCSRFToken(),
      },
    })
    if (!response.ok) throw new Error('Failed to refresh cache')
    return response.json()
  },

  async getFragments(slug: string): Promise<{ total: number; fragments: any[] }> {
    const response = await fetch(`${BASE_URL}/${slug}/fragments`, {
      headers: {
        'Accept': 'application/json',
      },
    })
    if (!response.ok) throw new Error('Failed to fetch fragments')
    return response.json()
  },

  async stats(): Promise<{
    data: any[]
    total_types: number
    total_fragments: number
    total_pending: number
  }> {
    const response = await fetch(`${BASE_URL}/stats`, {
      headers: {
        'Accept': 'application/json',
      },
    })
    if (!response.ok) throw new Error('Failed to fetch stats')
    return response.json()
  },
}
