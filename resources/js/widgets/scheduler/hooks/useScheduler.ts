import { useQuery } from '@tanstack/react-query'

export interface ScheduleRun {
  id: number
  status: 'pending' | 'running' | 'completed' | 'failed'
  planned_run_at: string
  started_at?: string
  completed_at?: string
  duration_ms?: number
  error_message?: string
}

export interface Schedule {
  id: number
  name: string
  command_slug: string
  status: 'active' | 'inactive' | 'completed'
  recurrence_type: string
  recurrence_value: string
  timezone: string
  next_run_at?: string
  last_run_at?: string
  run_count: number
  max_runs?: number
  is_due: boolean
  is_locked: boolean
  recent_runs: ScheduleRun[]
  created_at: string
  updated_at: string
}

export interface SchedulerStats {
  total: number
  active: number
  due: number
  locked: number
  by_status: Record<string, number>
  recent_runs: {
    total_today: number
    completed_today: number
    failed_today: number
    running_now: number
  }
  next_runs: Array<{
    id: number
    name: string
    command_slug: string
    next_run_at: string
    time_until: string
  }>
}

export interface DetailedScheduleRun {
  id: number
  schedule_id: number
  schedule_name: string
  command_slug: string
  status: 'pending' | 'running' | 'completed' | 'failed'
  planned_run_at: string
  started_at?: string
  completed_at?: string
  duration_ms?: number
  error_message?: string
  created_at: string
}

async function fetchSchedules(status?: string, limit: number = 50): Promise<{ data: Schedule[], total: number }> {
  const params = new URLSearchParams({
    limit: limit.toString(),
    ...(status && { status })
  })
  
  const response = await fetch(`/api/schedules?${params}`)
  if (!response.ok) throw new Error('Failed to fetch schedules')
  return response.json()
}

async function fetchSchedulerStats(): Promise<SchedulerStats> {
  const response = await fetch('/api/schedules/stats')
  if (!response.ok) throw new Error('Failed to fetch scheduler stats')
  return response.json()
}

async function fetchScheduleRuns(status?: string, limit: number = 25): Promise<{ data: DetailedScheduleRun[], total: number }> {
  const params = new URLSearchParams({
    limit: limit.toString(),
    ...(status && { status })
  })
  
  const response = await fetch(`/api/schedules/runs?${params}`)
  if (!response.ok) throw new Error('Failed to fetch schedule runs')
  return response.json()
}

async function fetchScheduleDetails(id: number): Promise<Schedule> {
  const response = await fetch(`/api/schedules/${id}`)
  if (!response.ok) throw new Error(`Failed to fetch schedule ${id}`)
  return response.json()
}

export function useSchedules(status?: string, limit: number = 50) {
  return useQuery({
    queryKey: ['schedules', status, limit],
    queryFn: () => fetchSchedules(status, limit),
    staleTime: 1000 * 60 * 2, // 2 minutes
    refetchInterval: 30000, // Refresh every 30 seconds
  })
}

export function useSchedulerStats() {
  return useQuery({
    queryKey: ['schedules', 'stats'],
    queryFn: fetchSchedulerStats,
    staleTime: 1000 * 60 * 1, // 1 minute
    refetchInterval: 20000, // Refresh every 20 seconds
  })
}

export function useScheduleRuns(status?: string, limit: number = 25) {
  return useQuery({
    queryKey: ['schedules', 'runs', status, limit],
    queryFn: () => fetchScheduleRuns(status, limit),
    staleTime: 1000 * 60 * 1, // 1 minute
    refetchInterval: 15000, // Refresh every 15 seconds
  })
}

export function useScheduleDetails(id: number) {
  return useQuery({
    queryKey: ['schedules', id],
    queryFn: () => fetchScheduleDetails(id),
    enabled: !!id,
    staleTime: 1000 * 60 * 5, // 5 minutes
  })
}