import { useQuery } from '@tanstack/react-query'
import { TodayActivityData } from '../types'

const fetchTodayActivity = async (): Promise<TodayActivityData> => {
  const response = await fetch('/api/widgets/today-activity')
  if (!response.ok) {
    throw new Error('Failed to fetch today activity data')
  }
  return response.json()
}

export function useTodayActivity() {
  return useQuery({
    queryKey: ['widgets', 'today-activity'],
    queryFn: fetchTodayActivity,
    refetchInterval: 30000, // Refetch every 30 seconds
    staleTime: 10000, // Consider data stale after 10 seconds
  })
}

// TODO: Create API endpoint at /api/widgets/today-activity
// This should aggregate Fragment data for today where metadata.turn = 'response'
// and calculate token usage, costs, response times, etc.