import { useQuery, useInfiniteQuery } from '@tanstack/react-query'
import { useState, useMemo } from 'react'
import { useDebounce } from '@/hooks/useDebounce'
import { useAppStore } from '@/stores/useAppStore'
import { BookmarkData, BookmarkFilters, BookmarkSearchResult } from '../types'

const fetchBookmarks = async (filters: BookmarkFilters): Promise<BookmarkSearchResult> => {
  const params = new URLSearchParams({
    query: filters.query,
    limit: filters.limit.toString(),
    offset: filters.offset.toString(),
  })
  
  if (filters.vault_id) params.append('vault_id', filters.vault_id.toString())
  if (filters.project_id) params.append('project_id', filters.project_id.toString())

  const response = await fetch(`/api/widgets/bookmarks?${params}`)
  if (!response.ok) {
    throw new Error('Failed to fetch bookmarks')
  }
  return response.json()
}

export function useBookmarks() {
  const [searchQuery, setSearchQuery] = useState('')
  const debouncedQuery = useDebounce(searchQuery, 300)
  
  // Get current session context for scoping
  const { currentVaultId, currentProjectId } = useAppStore()

  const filters = useMemo(() => ({
    query: debouncedQuery,
    vault_id: currentVaultId || undefined,
    project_id: currentProjectId || undefined,
    limit: 5,
    offset: 0,
  }), [debouncedQuery, currentVaultId, currentProjectId])

  const {
    data,
    fetchNextPage,
    hasNextPage,
    isFetchingNextPage,
    isLoading,
    error
  } = useInfiniteQuery({
    queryKey: ['widgets', 'bookmarks', filters],
    queryFn: ({ pageParam = 0 }) => 
      fetchBookmarks({ ...filters, offset: pageParam }),
    getNextPageParam: (lastPage, pages) => {
      const totalFetched = pages.reduce((sum, page) => sum + page.bookmarks.length, 0)
      return lastPage.hasMore ? totalFetched : undefined
    },
    staleTime: 30000, // 30 seconds
  })

  const bookmarks = useMemo(() => {
    return data?.pages.flatMap(page => page.bookmarks) || []
  }, [data])

  return {
    bookmarks,
    searchQuery,
    setSearchQuery,
    fetchNextPage,
    hasNextPage,
    isFetchingNextPage,
    isLoading,
    error,
  }
}

// TODO: Create API endpoint at /api/widgets/bookmarks
// This should query Bookmark model with session scoping and search capabilities