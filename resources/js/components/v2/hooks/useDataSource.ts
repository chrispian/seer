import { useState, useEffect, useCallback } from 'react'
import type { DataSourceQuery, DataSourceResult } from '../types'

function getCSRFToken(): string {
  return (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || ''
}

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
      const response = await window.fetch(`/api/v2/ui/datasource/${query.dataSource}/query`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': getCSRFToken(),
        },
        body: JSON.stringify(query),
      })

      if (!response.ok) {
        throw new Error(`Failed to fetch data: ${response.statusText}`)
      }

      const result: DataSourceResult<T> = await response.json()
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
