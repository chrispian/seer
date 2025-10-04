import { useQuery, useMutation, useQueryClient, useInfiniteQuery } from '@tanstack/react-query'
import { useState } from 'react'

export interface InboxFragment {
  id: number
  title: string
  type: string
  tags: string[]
  category?: string
  vault?: string
  content?: string
  inbox_status: 'pending' | 'accepted' | 'archived' | 'skipped'
  inbox_reason?: string
  inbox_at: string
  reviewed_at?: string
  created_at: string
  updated_at: string
}

export interface InboxStats {
  pending: number
  accepted: number
  archived: number
  total: number
  by_type: Record<string, number>
}

export interface InboxFilters {
  type?: string[]
  tags?: string[]
  category?: string
  vault?: string
  from_date?: string
  to_date?: string
  inbox_reason?: string
}

export interface AcceptFragmentData {
  edits?: {
    title?: string
    type?: string
    tags?: string[]
    category?: string
    vault?: string
    edited_message?: string
  }
}

export interface BulkAcceptData {
  fragment_ids: number[]
  edits?: {
    type?: string
    tags?: string[]
    category?: string
    vault?: string
  }
}

export interface AcceptAllData {
  type?: string[]
  tags?: string[]
  category?: string
  vault?: string
  from_date?: string
  to_date?: string
  inbox_reason?: string
  edits?: {
    type?: string
    tags?: string[]
    category?: string
    vault?: string
  }
}

async function fetchInboxFragments(filters: InboxFilters = {}, perPage: number = 25, page: number = 1) {
  const params = new URLSearchParams({
    per_page: perPage.toString(),
    page: page.toString(),
    ...Object.fromEntries(
      Object.entries(filters).filter(([_, value]) => 
        value !== undefined && value !== null && value !== ''
      )
    )
  })

  const response = await fetch(`/api/inbox?${params}`)
  if (!response.ok) throw new Error('Failed to fetch inbox fragments')
  return response.json()
}

async function fetchInboxStats(): Promise<InboxStats> {
  const response = await fetch('/api/inbox/stats')
  if (!response.ok) throw new Error('Failed to fetch inbox stats')
  return response.json()
}

async function acceptFragment(fragmentId: number, data: AcceptFragmentData) {
  const response = await fetch(`/api/inbox/${fragmentId}/accept`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(data)
  })
  if (!response.ok) throw new Error('Failed to accept fragment')
  return response.json()
}

async function acceptMultiple(data: BulkAcceptData) {
  const response = await fetch('/api/inbox/accept-multiple', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(data)
  })
  if (!response.ok) throw new Error('Failed to accept fragments')
  return response.json()
}

async function acceptAll(data: AcceptAllData) {
  const response = await fetch('/api/inbox/accept-all', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(data)
  })
  if (!response.ok) throw new Error('Failed to accept all fragments')
  return response.json()
}

async function archiveFragment(fragmentId: number, reason?: string) {
  const response = await fetch(`/api/inbox/${fragmentId}/archive`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ reason })
  })
  if (!response.ok) throw new Error('Failed to archive fragment')
  return response.json()
}

async function skipFragment(fragmentId: number, reason?: string) {
  const response = await fetch(`/api/inbox/${fragmentId}/skip`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ reason })
  })
  if (!response.ok) throw new Error('Failed to skip fragment')
  return response.json()
}

async function reopenFragment(fragmentId: number) {
  const response = await fetch(`/api/inbox/${fragmentId}/reopen`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' }
  })
  if (!response.ok) throw new Error('Failed to reopen fragment')
  return response.json()
}

export function useInbox() {
  const [filters, setFilters] = useState<InboxFilters>({})
  const [selectedFragments, setSelectedFragments] = useState<Set<number>>(new Set())
  const queryClient = useQueryClient()

  // Fetch inbox fragments with infinite query for pagination
  const {
    data: fragmentsData,
    fetchNextPage,
    hasNextPage,
    isFetchingNextPage,
    isLoading: isLoadingFragments,
    error: fragmentsError
  } = useInfiniteQuery({
    queryKey: ['inbox', 'fragments', filters],
    queryFn: ({ pageParam = 1 }) => fetchInboxFragments(filters, 25, pageParam),
    getNextPageParam: (lastPage) => {
      if (lastPage.meta.current_page < lastPage.meta.last_page) {
        return lastPage.meta.current_page + 1
      }
      return undefined
    },
    initialPageParam: 1
  })

  // Fetch inbox stats
  const {
    data: stats,
    isLoading: isLoadingStats,
    error: statsError
  } = useQuery({
    queryKey: ['inbox', 'stats'],
    queryFn: fetchInboxStats,
    refetchInterval: 30000 // Refresh every 30 seconds
  })

  // Get flattened fragments array
  const fragments = fragmentsData?.pages.flatMap(page => page.data) || []

  // Mutations
  const acceptMutation = useMutation({
    mutationFn: ({ fragmentId, data }: { fragmentId: number; data: AcceptFragmentData }) =>
      acceptFragment(fragmentId, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['inbox'] })
      setSelectedFragments(new Set())
    }
  })

  const acceptMultipleMutation = useMutation({
    mutationFn: acceptMultiple,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['inbox'] })
      setSelectedFragments(new Set())
    }
  })

  const acceptAllMutation = useMutation({
    mutationFn: acceptAll,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['inbox'] })
      setSelectedFragments(new Set())
    }
  })

  const archiveMutation = useMutation({
    mutationFn: ({ fragmentId, reason }: { fragmentId: number; reason?: string }) =>
      archiveFragment(fragmentId, reason),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['inbox'] })
      setSelectedFragments(new Set())
    }
  })

  const skipMutation = useMutation({
    mutationFn: ({ fragmentId, reason }: { fragmentId: number; reason?: string }) =>
      skipFragment(fragmentId, reason),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['inbox'] })
      setSelectedFragments(new Set())
    }
  })

  const reopenMutation = useMutation({
    mutationFn: reopenFragment,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['inbox'] })
    }
  })

  // Selection helpers
  const toggleSelection = (fragmentId: number) => {
    const newSelection = new Set(selectedFragments)
    if (newSelection.has(fragmentId)) {
      newSelection.delete(fragmentId)
    } else {
      newSelection.add(fragmentId)
    }
    setSelectedFragments(newSelection)
  }

  const selectAll = () => {
    setSelectedFragments(new Set(fragments.map(f => f.id)))
  }

  const clearSelection = () => {
    setSelectedFragments(new Set())
  }

  const isSelected = (fragmentId: number) => selectedFragments.has(fragmentId)

  return {
    // Data
    fragments,
    stats,
    filters,
    selectedFragments: Array.from(selectedFragments),
    
    // Loading states
    isLoadingFragments,
    isLoadingStats,
    isFetchingNextPage,
    
    // Pagination
    fetchNextPage,
    hasNextPage,
    
    // Errors
    fragmentsError,
    statsError,
    
    // Filter management
    setFilters,
    
    // Selection management
    toggleSelection,
    selectAll,
    clearSelection,
    isSelected,
    selectedCount: selectedFragments.size,
    
    // Actions
    acceptFragment: acceptMutation.mutateAsync,
    acceptMultiple: acceptMultipleMutation.mutateAsync,
    acceptAll: acceptAllMutation.mutateAsync,
    archiveFragment: archiveMutation.mutateAsync,
    skipFragment: skipMutation.mutateAsync,
    reopenFragment: reopenMutation.mutateAsync,
    
    // Mutation states
    isAccepting: acceptMutation.isPending,
    isAcceptingMultiple: acceptMultipleMutation.isPending,
    isAcceptingAll: acceptAllMutation.isPending,
    isArchiving: archiveMutation.isPending,
    isSkipping: skipMutation.isPending,
    isReopening: reopenMutation.isPending
  }
}