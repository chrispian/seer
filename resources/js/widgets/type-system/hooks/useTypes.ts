import { useQuery } from '@tanstack/react-query'

export interface TypeInfo {
  slug: string
  name: string
  description: string
  version: string
  capabilities: string[]
  ui: {
    icon: string
    color: string
    display_name: string
    plural_name: string
  }
  hot_fields: Record<string, any>
  schema_hash: string
  updated_at: string
}

export interface TypeStats {
  slug: string
  fragments_count: number
  pending_count: number
  capabilities: string[]
  version: string
  updated_at: string
}

export interface TypeSystemStats {
  data: TypeStats[]
  total_types: number
  total_fragments: number
  total_pending: number
}

async function fetchTypes(): Promise<{ data: TypeInfo[], total: number }> {
  const response = await fetch('/api/types')
  if (!response.ok) throw new Error('Failed to fetch types')
  return response.json()
}

async function fetchTypeStats(): Promise<TypeSystemStats> {
  const response = await fetch('/api/types/stats')
  if (!response.ok) throw new Error('Failed to fetch type stats')
  return response.json()
}

async function fetchTypeDetails(slug: string) {
  const response = await fetch(`/api/types/${slug}`)
  if (!response.ok) throw new Error(`Failed to fetch type details for ${slug}`)
  return response.json()
}

export function useTypes() {
  return useQuery({
    queryKey: ['types'],
    queryFn: fetchTypes,
    staleTime: 1000 * 60 * 5, // 5 minutes
  })
}

export function useTypeStats() {
  return useQuery({
    queryKey: ['types', 'stats'],
    queryFn: fetchTypeStats,
    staleTime: 1000 * 60 * 2, // 2 minutes
    refetchInterval: 30000, // Refresh every 30 seconds
  })
}

export function useTypeDetails(slug: string) {
  return useQuery({
    queryKey: ['types', slug],
    queryFn: () => fetchTypeDetails(slug),
    enabled: !!slug,
    staleTime: 1000 * 60 * 10, // 10 minutes
  })
}