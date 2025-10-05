export interface Provider {
  id: string
  name: string
  enabled: boolean
  status: 'healthy' | 'unhealthy' | 'unknown'
  capabilities: string[]
  models: Model[]
  model_counts?: {
    total: number
    enabled: number
    disabled: number
  }
  credentials_count: number
  last_health_check?: string
  usage_count: number
  ui_preferences?: {
    theme?: string
    display_options?: Record<string, any>
  }
  created_at: string
  updated_at: string
}

export interface Model {
  id: string
  name: string
  provider: string
  capabilities: string[]
  context_length?: number
  max_tokens?: number
  pricing?: {
    input_cost?: number
    output_cost?: number
  }
}

export interface Credential {
  id: number
  provider: string
  credential_type: string
  is_active: boolean
  created_at: string
  updated_at: string
  expires_at?: string
  metadata?: Record<string, any>
  ui_metadata?: Record<string, any>
  // Note: raw credentials never exposed
}

export interface ProviderStatistics {
  total_providers: number
  enabled_providers: number
  healthy_providers: number
  total_credentials: number
  providers_by_status: Record<string, number>
}

export interface HealthCheckResult {
  provider: string
  status: 'healthy' | 'unhealthy' | 'unknown'
  response_time?: number
  error_message?: string
  checked_at: string
}

export interface TestResult {
  success: boolean
  response_time?: number
  error_message?: string
  details?: Record<string, any>
}

export interface ApiResponse<T> {
  data: T
  meta?: {
    pagination?: {
      current_page: number
      per_page: number
      total: number
      last_page: number
    }
    counts?: Record<string, number>
  }
  status: 'success' | 'error'
  message: string
}

export interface CreateCredentialRequest {
  credential_type: string
  credentials: Record<string, string>
  metadata?: Record<string, any>
  expires_at?: string
}

export interface UpdateCredentialRequest {
  metadata?: Record<string, any>
  expires_at?: string
  is_active?: boolean
}

export interface UpdateProviderRequest {
  enabled?: boolean
  ui_preferences?: Record<string, any>
}

export type ProviderStatus = 'healthy' | 'unhealthy' | 'unknown'
export type CredentialType = 'api_key' | 'oauth' | 'basic_auth' | 'custom'