import type { 
  Provider, 
  Credential, 
  ProviderStatistics, 
  HealthCheckResult, 
  TestResult, 
  ApiResponse,
  CreateCredentialRequest,
  UpdateCredentialRequest,
  UpdateProviderRequest
} from '@/types/provider'

// Base API configuration
const API_BASE = '/api'
const PROVIDERS_BASE = `${API_BASE}/providers`

// Helper function to handle API responses
async function handleApiResponse<T>(response: Response): Promise<ApiResponse<T>> {
  if (!response.ok) {
    const errorData = await response.json().catch(() => ({ 
      message: 'Network error occurred',
      status: 'error' 
    }))
    throw new Error(errorData.message || `HTTP ${response.status}`)
  }
  return await response.json()
}

// Helper function to get CSRF token
function getCsrfToken(): string {
  const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
  if (!token) {
    throw new Error('CSRF token not found')
  }
  return token
}

// Helper function to create request headers
function createHeaders(includeContentType = true): HeadersInit {
  const headers: HeadersInit = {
    'X-CSRF-TOKEN': getCsrfToken(),
    'Accept': 'application/json',
  }
  
  if (includeContentType) {
    headers['Content-Type'] = 'application/json'
  }
  
  return headers
}

// Provider Management API
export const providersApi = {
  // Get all providers with status
  async getProviders(): Promise<Provider[]> {
    const response = await fetch(PROVIDERS_BASE, {
      method: 'GET',
      headers: createHeaders(false),
    })
    const result = await handleApiResponse<Provider[]>(response)
    return result.data
  },

  // Get provider statistics
  async getStatistics(): Promise<ProviderStatistics> {
    const response = await fetch(`${PROVIDERS_BASE}/statistics`, {
      method: 'GET',
      headers: createHeaders(false),
    })
    const result = await handleApiResponse<ProviderStatistics>(response)
    return result.data
  },

  // Get specific provider details
  async getProvider(providerId: string): Promise<Provider> {
    const response = await fetch(`${PROVIDERS_BASE}/${providerId}`, {
      method: 'GET',
      headers: createHeaders(false),
    })
    const result = await handleApiResponse<Provider>(response)
    return result.data
  },

  // Update provider configuration
  async updateProvider(providerId: string, data: UpdateProviderRequest): Promise<Provider> {
    const response = await fetch(`${PROVIDERS_BASE}/${providerId}`, {
      method: 'PUT',
      headers: createHeaders(),
      body: JSON.stringify(data),
    })
    const result = await handleApiResponse<Provider>(response)
    return result.data
  },

  // Toggle provider enabled/disabled
  async toggleProvider(providerId: string): Promise<Provider> {
    const response = await fetch(`${PROVIDERS_BASE}/${providerId}/toggle`, {
      method: 'POST',
      headers: createHeaders(),
    })
    const result = await handleApiResponse<Provider>(response)
    return result.data
  },

  // Test provider connectivity
  async testProvider(providerId: string): Promise<TestResult> {
    const response = await fetch(`${PROVIDERS_BASE}/${providerId}/test`, {
      method: 'POST',
      headers: createHeaders(),
    })
    const result = await handleApiResponse<TestResult>(response)
    return result.data
  },

  // Get provider health status
  async getProviderHealth(providerId: string): Promise<HealthCheckResult> {
    const response = await fetch(`${PROVIDERS_BASE}/${providerId}/health`, {
      method: 'GET',
      headers: createHeaders(false),
    })
    const result = await handleApiResponse<HealthCheckResult>(response)
    return result.data
  },

  // Bulk health check for all providers
  async bulkHealthCheck(): Promise<HealthCheckResult[]> {
    const response = await fetch(`${PROVIDERS_BASE}/health-check`, {
      method: 'POST',
      headers: createHeaders(),
    })
    const result = await handleApiResponse<HealthCheckResult[]>(response)
    return result.data
  },

  // Sync provider capabilities
  async syncCapabilities(): Promise<{ message: string }> {
    const response = await fetch(`${PROVIDERS_BASE}/sync-capabilities`, {
      method: 'POST',
      headers: createHeaders(),
    })
    const result = await handleApiResponse<{ message: string }>(response)
    return result.data
  }
}

// Credential Management API
export const credentialsApi = {
  // Get provider credentials
  async getCredentials(providerId: string): Promise<Credential[]> {
    const response = await fetch(`${PROVIDERS_BASE}/${providerId}/credentials`, {
      method: 'GET',
      headers: createHeaders(false),
    })
    const result = await handleApiResponse<Credential[]>(response)
    return result.data
  },

  // Add new credential
  async createCredential(providerId: string, data: CreateCredentialRequest): Promise<Credential> {
    const response = await fetch(`${PROVIDERS_BASE}/${providerId}/credentials`, {
      method: 'POST',
      headers: createHeaders(),
      body: JSON.stringify(data),
    })
    const result = await handleApiResponse<Credential>(response)
    return result.data
  },

  // Update credential
  async updateCredential(
    providerId: string, 
    credentialId: number, 
    data: UpdateCredentialRequest
  ): Promise<Credential> {
    const response = await fetch(`${PROVIDERS_BASE}/${providerId}/credentials/${credentialId}`, {
      method: 'PUT',
      headers: createHeaders(),
      body: JSON.stringify(data),
    })
    const result = await handleApiResponse<Credential>(response)
    return result.data
  },

  // Delete credential
  async deleteCredential(providerId: string, credentialId: number): Promise<{ message: string }> {
    const response = await fetch(`${PROVIDERS_BASE}/${providerId}/credentials/${credentialId}`, {
      method: 'DELETE',
      headers: createHeaders(),
    })
    const result = await handleApiResponse<{ message: string }>(response)
    return result.data
  },

  // Test credential
  async testCredential(providerId: string, credentialId: number): Promise<TestResult> {
    const response = await fetch(`${PROVIDERS_BASE}/${providerId}/credentials/${credentialId}/test`, {
      method: 'POST',
      headers: createHeaders(),
    })
    const result = await handleApiResponse<TestResult>(response)
    return result.data
  }
}

// Models API
export const modelsApi = {
  // Get provider models
  async getProviderModels(providerId: string) {
    const response = await fetch(`${PROVIDERS_BASE}/${providerId}/models`, {
      method: 'GET',
      headers: createHeaders(false),
    })
    const result = await handleApiResponse<any>(response)
    return result.data
  },

  // Get all models
  async getAllModels() {
    const response = await fetch(`${API_BASE}/models`, {
      method: 'GET',
      headers: createHeaders(false),
    })
    const result = await handleApiResponse<any>(response)
    return result.data
  }
}

// Add updateModel method to providersApi
providersApi.updateModel = async function(providerId: string, modelId: number, data: { enabled?: boolean; priority?: number }) {
  const response = await fetch(`${PROVIDERS_BASE}/${providerId}/models/${modelId}`, {
    method: 'PUT',
    headers: createHeaders(),
    body: JSON.stringify(data),
  })
  const result = await handleApiResponse<any>(response)
  return result.data
}