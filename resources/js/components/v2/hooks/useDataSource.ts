import { useState, useEffect, useCallback } from 'react'
import { apiGet } from '@/lib/api'
import type { DataSourceQuery, DataSourceResult } from '../types'

interface UseDataSourceOptions {
  dataSource: string
  filters?: Record<string, any>
  search?: string
  sort?: { field: string; direction: 'asc' | 'desc' }
  pagination?: { page: number; perPage: number }
  autoFetch?: boolean
}

interface UseDataSourceResult<T = any> {
  data: T[]
  meta: DataSourceResult['meta']
  loading: boolean
  error: Error | null
  refetch: () => Promise<void>
  fetch: (options?: Partial<UseDataSourceOptions>) => Promise<void>
}

export function useDataSource<T = any>(options: UseDataSourceOptions): UseDataSourceResult<T> {
  const [data, setData] = useState<T[]>([])
  const [meta, setMeta] = useState<DataSourceResult['meta']>(undefined)
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<Error | null>(null)
  const [hasFetched, setHasFetched] = useState(false)

  const { dataSource, filters, search, sort, pagination, autoFetch } = options

  const fetchData = useCallback(async (overrides?: Partial<UseDataSourceOptions>) => {
    setLoading(true)
    setError(null)

    const query: DataSourceQuery = {
      dataSource: overrides?.dataSource ?? dataSource,
      filters: overrides?.filters ?? filters,
      search: overrides?.search ?? search,
      sort: overrides?.sort ?? sort,
      pagination: overrides?.pagination ?? pagination,
    }

    try {
      // Build query params for GET request
      const params = new URLSearchParams()
      if (query.search) params.append('search', query.search)
      if (query.filters) {
        Object.entries(query.filters).forEach(([key, value]) => {
          params.append(`filters[${key}]`, String(value))
        })
      }
      if (query.sort) {
        params.append('sort[field]', query.sort.field)
        params.append('sort[direction]', query.sort.direction)
      }
      if (query.pagination) {
        params.append('page', String(query.pagination.page))
        params.append('per_page', String(query.pagination.perPage))
      }
      
      const result: DataSourceResult<T> = await apiGet(
        `/api/v2/ui/datasources/${query.dataSource}?${params.toString()}`
      )
      
      setData(result.data)
      setMeta(result.meta)
    } catch (err) {
      setError(err instanceof Error ? err : new Error('Unknown error'))
    } finally {
      setLoading(false)
    }
  }, [dataSource, filters, search, sort, pagination])

  useEffect(() => {
    if (autoFetch !== false && !hasFetched) {
      fetchData()
      setHasFetched(true)
    }
  }, [autoFetch, hasFetched, fetchData])

  const refetch = async () => {
    await fetchData()
  }

  return {
    data,
    meta,
    loading,
    error,
    refetch,
    fetch: fetchData,
  }
}
