import { useEffect, useState } from 'react'
import { Skeleton } from '@/components/ui/skeleton'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Badge } from '@/components/ui/badge'
import type { ComponentConfig } from '../types'

interface DetailComponentProps {
  config: ComponentConfig
  data?: any
}

export function DetailComponent({ config, data: initialData }: DetailComponentProps) {
  const [data, setData] = useState<any>(initialData)
  const [loading, setLoading] = useState(!initialData)
  const [error, setError] = useState<Error | null>(null)

  useEffect(() => {
    if (!initialData && config.dataSource && config.id) {
      fetchData()
    }
  }, [config.dataSource, config.id, initialData])

  const fetchData = async () => {
    if (!config.url) return

    setLoading(true)
    setError(null)

    try {
      const response = await window.fetch(config.url)
      
      if (!response.ok) {
        throw new Error(`Failed to load data: ${response.statusText}`)
      }

      const result = await response.json()
      setData(result.data || result)
    } catch (err) {
      setError(err instanceof Error ? err : new Error('Failed to load data'))
    } finally {
      setLoading(false)
    }
  }

  if (loading) {
    return (
      <div className="space-y-6">
        <div className="flex items-start gap-4">
          <Skeleton className="h-20 w-20 rounded-full" />
          <div className="flex-1 space-y-2">
            <Skeleton className="h-8 w-48" />
            <Skeleton className="h-4 w-32" />
          </div>
        </div>
        <div className="space-y-4">
          <Skeleton className="h-4 w-full" />
          <Skeleton className="h-4 w-full" />
          <Skeleton className="h-4 w-3/4" />
        </div>
      </div>
    )
  }

  if (error) {
    return (
      <div className="p-4 text-center text-destructive">
        Error: {error.message}
      </div>
    )
  }

  if (!data) {
    return (
      <div className="p-4 text-center text-muted-foreground">
        No data available
      </div>
    )
  }

  const fields = config.fields || []

  return (
    <div className="space-y-6">
      {/* Header with avatar and name */}
      <div className="flex items-start gap-4">
        <Avatar className="h-20 w-20">
          <AvatarImage src={data.avatar_url || data.avatar_path} alt={data.name} />
          <AvatarFallback>{data.name?.substring(0, 2).toUpperCase()}</AvatarFallback>
        </Avatar>
        <div className="flex-1">
          <h2 className="text-2xl font-bold">{data.name}</h2>
          <div className="flex items-center gap-2 mt-1">
            {data.designation && (
              <Badge variant="secondary">{data.designation}</Badge>
            )}
            {data.status && (
              <Badge variant={data.status === 'active' ? 'default' : 'outline'}>
                {data.status}
              </Badge>
            )}
          </div>
        </div>
      </div>

      {/* Detail fields */}
      <div className="space-y-4">
        {fields.map((field) => {
          const value = data[field.key]
          
          if (!value && field.key !== 'persona') return null

          return (
            <div key={field.key} className="space-y-1">
              <dt className="text-sm font-medium text-muted-foreground">{field.label}</dt>
              <dd className="text-sm">
                {field.type === 'date' && value
                  ? new Date(value).toLocaleDateString()
                  : field.type === 'badge' && value
                  ? <Badge>{value}</Badge>
                  : field.type === 'text' && value
                  ? <p className="whitespace-pre-wrap">{value}</p>
                  : value || '—'}
              </dd>
            </div>
          )
        })}
      </div>

      {/* Related data section */}
      {data.agent_profile && (
        <div className="pt-4 border-t">
          <h3 className="text-sm font-medium mb-2">Agent Profile</h3>
          <div className="space-y-1">
            <p className="text-sm font-medium">{data.agent_profile.name}</p>
            {data.agent_profile.description && (
              <p className="text-sm text-muted-foreground">{data.agent_profile.description}</p>
            )}
          </div>
        </div>
      )}
    </div>
  )
}
