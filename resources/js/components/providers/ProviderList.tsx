import React, { useState, useMemo } from 'react'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent } from '@/components/ui/card'
import { LoadingSpinner } from '@/components/ui/loading-spinner'
import { ProviderCard } from './ProviderCard'
import { 
  Search, 
  Filter, 
  Grid3X3, 
  List,
  SortAsc,
  SortDesc,
  Plus,
  RefreshCw,
  X
} from 'lucide-react'
import type { Provider } from '@/types/provider'

interface ProviderListProps {
  providers: Provider[]
  isLoading?: boolean
  onToggle: (providerId: string) => void
  onTest: (providerId: string) => void
  onConfigure: (providerId: string) => void
  onViewCredentials: (providerId: string) => void
  onViewDetails: (providerId: string) => void
  onRefresh: () => void
  onAddProvider?: () => void
  testingProviders?: Set<string>
}

type ViewMode = 'grid' | 'list'
type SortBy = 'name' | 'status' | 'models' | 'usage' | 'updated'
type SortOrder = 'asc' | 'desc'
type FilterBy = 'all' | 'enabled' | 'disabled' | 'healthy' | 'unhealthy'

export function ProviderList({
  providers,
  isLoading = false,
  onToggle,
  onTest,
  onConfigure,
  onViewCredentials,
  onViewDetails,
  onRefresh,
  onAddProvider,
  testingProviders = new Set()
}: ProviderListProps) {
  const [searchQuery, setSearchQuery] = useState('')
  const [viewMode, setViewMode] = useState<ViewMode>('grid')
  const [sortBy, setSortBy] = useState<SortBy>('name')
  const [sortOrder, setSortOrder] = useState<SortOrder>('asc')
  const [filterBy, setFilterBy] = useState<FilterBy>('enabled')

  const filteredAndSortedProviders = useMemo(() => {
    let filtered = providers || [] // Ensure providers is always an array

    // Apply search filter
    if (searchQuery) {
      filtered = filtered.filter(provider =>
        provider.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
        provider.capabilities.some(cap => 
          cap.toLowerCase().includes(searchQuery.toLowerCase())
        )
      )
    }

    // Apply status filter
    if (filterBy !== 'all') {
      filtered = filtered.filter(provider => {
        switch (filterBy) {
          case 'enabled':
            return provider.enabled
          case 'disabled':
            return !provider.enabled
          case 'healthy':
            return provider.status === 'healthy'
          case 'unhealthy':
            return provider.status === 'unhealthy'
          default:
            return true
        }
      })
    }

    // Apply sorting
    filtered.sort((a, b) => {
      let aValue: any, bValue: any

      switch (sortBy) {
        case 'name':
          aValue = a.name.toLowerCase()
          bValue = b.name.toLowerCase()
          break
        case 'status':
          const statusOrder = { healthy: 3, unknown: 2, unhealthy: 1 }
          aValue = statusOrder[a.status] || 0
          bValue = statusOrder[b.status] || 0
          break
        case 'models':
          aValue = a.models.length
          bValue = b.models.length
          break
        case 'usage':
          aValue = a.usage_count
          bValue = b.usage_count
          break
        case 'updated':
          aValue = new Date(a.updated_at).getTime()
          bValue = new Date(b.updated_at).getTime()
          break
        default:
          return 0
      }

      if (aValue < bValue) return sortOrder === 'asc' ? -1 : 1
      if (aValue > bValue) return sortOrder === 'asc' ? 1 : -1
      return 0
    })

    return filtered
  }, [providers, searchQuery, filterBy, sortBy, sortOrder])

  const getFilterCounts = () => {
    const safeProviders = providers || []
    return {
      all: safeProviders.length,
      enabled: safeProviders.filter(p => p.enabled).length,
      disabled: safeProviders.filter(p => !p.enabled).length,
      healthy: safeProviders.filter(p => p.status === 'healthy').length,
      unhealthy: safeProviders.filter(p => p.status === 'unhealthy').length,
    }
  }

  const filterCounts = getFilterCounts()

  const hasActiveFilters = searchQuery || filterBy !== 'all' || sortBy !== 'name' || sortOrder !== 'asc'
  const showClearButton = true // Always show the clear button
  
  const handleClearFilters = () => {
    setSearchQuery('')
    setFilterBy('all')
    setSortBy('name')
    setSortOrder('asc')
  }

  if (isLoading) {
    return (
      <Card>
        <CardContent className="flex items-center justify-center py-8">
          <LoadingSpinner />
          <span className="ml-2">Loading providers...</span>
        </CardContent>
      </Card>
    )
  }

  return (
    <div className="space-y-6">
      {/* Header with controls */}
      <div className="flex flex-col sm:flex-row gap-4 items-start sm:items-center justify-between">
        <div className="flex items-center gap-4 flex-1">
          {/* Search */}
          <div className="relative flex-1 max-w-sm">
            <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground h-4 w-4" />
            <Input
              placeholder="Search providers..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              className="pl-9"
            />
          </div>

          {/* Filter */}
          <Select value={filterBy} onValueChange={(value: FilterBy) => setFilterBy(value)}>
            <SelectTrigger className="w-40">
              <Filter className="mr-2 h-4 w-4" />
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">All ({filterCounts.all})</SelectItem>
              <SelectItem value="enabled">Enabled ({filterCounts.enabled})</SelectItem>
              <SelectItem value="disabled">Disabled ({filterCounts.disabled})</SelectItem>
              <SelectItem value="healthy">Healthy ({filterCounts.healthy})</SelectItem>
              <SelectItem value="unhealthy">Unhealthy ({filterCounts.unhealthy})</SelectItem>
            </SelectContent>
          </Select>

          {/* Sort */}
          <Select value={`${sortBy}-${sortOrder}`} onValueChange={(value) => {
            const [sort, order] = value.split('-')
            setSortBy(sort as SortBy)
            setSortOrder(order as SortOrder)
          }}>
            <SelectTrigger className="w-40">
              {sortOrder === 'asc' ? (
                <SortAsc className="mr-2 h-4 w-4" />
              ) : (
                <SortDesc className="mr-2 h-4 w-4" />
              )}
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="name-asc">Name A-Z</SelectItem>
              <SelectItem value="name-desc">Name Z-A</SelectItem>
              <SelectItem value="status-desc">Status</SelectItem>
              <SelectItem value="models-desc">Most Models</SelectItem>
              <SelectItem value="usage-desc">Most Used</SelectItem>
              <SelectItem value="updated-desc">Recently Updated</SelectItem>
            </SelectContent>
          </Select>
        </div>

        <div className="flex items-center gap-2">
          {/* Clear Filters */}
          {showClearButton && (
            <Button
              variant="outline"
              size="sm"
              onClick={handleClearFilters}
              className={hasActiveFilters ? "text-foreground" : "text-muted-foreground"}
            >
              <X className="mr-2 h-4 w-4" />
              Clear All
            </Button>
          )}

          {/* View Mode Toggle */}
          <div className="flex border rounded-md">
            <Button
              variant={viewMode === 'grid' ? 'default' : 'ghost'}
              size="sm"
              onClick={() => setViewMode('grid')}
              className="rounded-r-none"
            >
              <Grid3X3 className="h-4 w-4" />
            </Button>
            <Button
              variant={viewMode === 'list' ? 'default' : 'ghost'}
              size="sm"
              onClick={() => setViewMode('list')}
              className="rounded-l-none"
            >
              <List className="h-4 w-4" />
            </Button>
          </div>

          {/* Refresh */}
          <Button variant="outline" size="sm" onClick={onRefresh}>
            <RefreshCw className="h-4 w-4" />
          </Button>

          {/* Add Provider */}
          {onAddProvider && (
            <Button onClick={onAddProvider} size="sm">
              <Plus className="mr-2 h-4 w-4" />
              Add Provider
            </Button>
          )}
        </div>
      </div>

      {/* Results summary */}
      <div className="flex items-center gap-2 text-sm text-muted-foreground">
        <span>
          Showing {filteredAndSortedProviders.length} of {(providers || []).length} providers
        </span>
        {searchQuery && (
          <Badge variant="secondary" className="text-xs">
            Search: "{searchQuery}"
          </Badge>
        )}
        {filterBy !== 'all' && (
          <Badge variant="secondary" className="text-xs">
            Filter: {filterBy}
          </Badge>
        )}
      </div>

      {/* Provider grid/list */}
      {filteredAndSortedProviders.length === 0 ? (
        <Card>
          <CardContent className="flex flex-col items-center justify-center py-8 text-center">
            <div className="text-muted-foreground mb-2">
              {searchQuery || filterBy !== 'all' 
                ? 'No providers match your filters' 
                : 'No providers configured'
              }
            </div>
            {!searchQuery && filterBy === 'all' && onAddProvider && (
              <Button onClick={onAddProvider} variant="outline">
                <Plus className="mr-2 h-4 w-4" />
                Add Your First Provider
              </Button>
            )}
          </CardContent>
        </Card>
      ) : (
        <div className={
          viewMode === 'grid' 
            ? 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6'
            : 'space-y-4'
        }>
          {filteredAndSortedProviders.map((provider) => (
            <ProviderCard
              key={provider.id}
              provider={provider}
              onToggle={onToggle}
              onTest={onTest}
              onConfigure={onConfigure}
              onViewCredentials={onViewCredentials}
              onViewDetails={onViewDetails}
              isTestLoading={testingProviders.has(provider.id)}
            />
          ))}
        </div>
      )}
    </div>
  )
}