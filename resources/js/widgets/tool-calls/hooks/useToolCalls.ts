import { useQuery } from '@tanstack/react-query'
import { useAppStore } from '@/stores/useAppStore'
import { ToolCallData, ToolCallFilters } from '../types'

const fetchToolCalls = async (filters: ToolCallFilters): Promise<ToolCallData[]> => {
  const params = new URLSearchParams({
    limit: filters.limit.toString(),
    offset: filters.offset.toString(),
  })
  
  if (filters.sessionId) params.append('session_id', filters.sessionId.toString())
  if (filters.type) params.append('type', filters.type)
  if (filters.provider) params.append('provider', filters.provider)

  const response = await fetch(`/api/widgets/tool-calls?${params}`)
  if (!response.ok) {
    throw new Error('Failed to fetch tool calls')
  }
  return response.json()
}

export function useToolCalls() {
  const { currentSessionId } = useAppStore()

  return useQuery({
    queryKey: ['widgets', 'tool-calls', currentSessionId],
    queryFn: () => fetchToolCalls({
      sessionId: currentSessionId || undefined,
      limit: 20,
      offset: 0,
    }),
    enabled: !!currentSessionId,
    refetchInterval: 10000, // Refetch every 10 seconds for active session
    staleTime: 5000, // Consider data stale after 5 seconds
  })
}

// TODO: Create API endpoint at /api/widgets/tool-calls
// This should query Fragment metadata for tool call information
// Filter by current session and extract relevant metadata