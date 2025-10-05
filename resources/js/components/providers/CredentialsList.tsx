import React, { useState } from 'react'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Badge } from '@/components/ui/badge'
import { LoadingSpinner } from '@/components/ui/loading-spinner'
import { CredentialCard } from './CredentialCard'
import { 
  Plus, 
  Search, 
  Filter,
  RefreshCw,
  Key
} from 'lucide-react'
import type { Credential } from '@/types/provider'

interface CredentialsListProps {
  credentials: Credential[]
  providerId: string
  providerName: string
  isLoading?: boolean
  onAdd: () => void
  onEdit: (credential: Credential) => void
  onDelete: (credential: Credential) => void
  onTest: (credential: Credential) => void
  onToggleActive: (credential: Credential) => void
  onRefresh: () => void
  testingCredentials?: Set<number>
}

type FilterBy = 'all' | 'active' | 'inactive' | 'expiring' | 'expired'

export function CredentialsList({
  credentials,
  providerId,
  providerName,
  isLoading = false,
  onAdd,
  onEdit,
  onDelete,
  onTest,
  onToggleActive,
  onRefresh,
  testingCredentials = new Set()
}: CredentialsListProps) {
  const [searchQuery, setSearchQuery] = useState('')
  const [filterBy, setFilterBy] = useState<FilterBy>('all')

  const getCredentialExpirationStatus = (credential: Credential) => {
    if (!credential.expires_at) return 'valid'
    
    const expiry = new Date(credential.expires_at)
    const now = new Date()
    const daysUntilExpiry = Math.ceil((expiry.getTime() - now.getTime()) / (1000 * 60 * 60 * 24))
    
    if (daysUntilExpiry < 0) return 'expired'
    if (daysUntilExpiry <= 7) return 'expiring'
    return 'valid'
  }

  const filteredCredentials = credentials.filter(credential => {
    // Apply search filter
    if (searchQuery) {
      const searchLower = searchQuery.toLowerCase()
      const matchesType = credential.credential_type.toLowerCase().includes(searchLower)
      const matchesMetadata = credential.metadata?.name?.toLowerCase().includes(searchLower)
      
      if (!matchesType && !matchesMetadata) {
        return false
      }
    }

    // Apply status filter
    switch (filterBy) {
      case 'active':
        return credential.is_active
      case 'inactive':
        return !credential.is_active
      case 'expiring':
        return getCredentialExpirationStatus(credential) === 'expiring'
      case 'expired':
        return getCredentialExpirationStatus(credential) === 'expired'
      default:
        return true
    }
  })

  const getFilterCounts = () => {
    return {
      all: credentials.length,
      active: credentials.filter(c => c.is_active).length,
      inactive: credentials.filter(c => !c.is_active).length,
      expiring: credentials.filter(c => getCredentialExpirationStatus(c) === 'expiring').length,
      expired: credentials.filter(c => getCredentialExpirationStatus(c) === 'expired').length,
    }
  }

  const filterCounts = getFilterCounts()

  if (isLoading) {
    return (
      <Card>
        <CardContent className="flex items-center justify-center py-8">
          <LoadingSpinner />
          <span className="ml-2">Loading credentials...</span>
        </CardContent>
      </Card>
    )
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <Card>
        <CardHeader>
          <div className="flex items-center justify-between">
            <div>
              <CardTitle className="flex items-center gap-2">
                <Key className="h-5 w-5" />
                {providerName} Credentials
              </CardTitle>
              <CardDescription>
                Manage authentication credentials for {providerName}
              </CardDescription>
            </div>
            <div className="flex gap-2">
              <Button variant="outline" size="sm" onClick={onRefresh}>
                <RefreshCw className="h-4 w-4" />
              </Button>
              <Button onClick={onAdd}>
                <Plus className="mr-2 h-4 w-4" />
                Add Credential
              </Button>
            </div>
          </div>
        </CardHeader>
      </Card>

      {/* Filters */}
      <Card>
        <CardContent className="py-4">
          <div className="flex flex-col sm:flex-row gap-4">
            {/* Search */}
            <div className="relative flex-1">
              <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground h-4 w-4" />
              <Input
                placeholder="Search credentials..."
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                className="pl-9"
              />
            </div>

            {/* Filter */}
            <Select value={filterBy} onValueChange={(value: FilterBy) => setFilterBy(value)}>
              <SelectTrigger className="w-48">
                <Filter className="mr-2 h-4 w-4" />
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All ({filterCounts.all})</SelectItem>
                <SelectItem value="active">Active ({filterCounts.active})</SelectItem>
                <SelectItem value="inactive">Inactive ({filterCounts.inactive})</SelectItem>
                {filterCounts.expiring > 0 && (
                  <SelectItem value="expiring">
                    <div className="flex items-center gap-2">
                      Expiring Soon ({filterCounts.expiring})
                      <Badge variant="destructive" className="text-xs">!</Badge>
                    </div>
                  </SelectItem>
                )}
                {filterCounts.expired > 0 && (
                  <SelectItem value="expired">
                    <div className="flex items-center gap-2">
                      Expired ({filterCounts.expired})
                      <Badge variant="destructive" className="text-xs">!</Badge>
                    </div>
                  </SelectItem>
                )}
              </SelectContent>
            </Select>
          </div>
        </CardContent>
      </Card>

      {/* Results summary */}
      <div className="flex items-center gap-2 text-sm text-muted-foreground">
        <span>
          Showing {filteredCredentials.length} of {credentials.length} credentials
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

      {/* Credentials grid */}
      {filteredCredentials.length === 0 ? (
        <Card>
          <CardContent className="flex flex-col items-center justify-center py-12 text-center">
            <div className="w-16 h-16 rounded-full bg-muted flex items-center justify-center mb-4">
              <Key className="h-8 w-8 text-muted-foreground" />
            </div>
            <h3 className="text-lg font-medium mb-2">
              {searchQuery || filterBy !== 'all' 
                ? 'No credentials match your filters' 
                : 'No credentials configured'
              }
            </h3>
            <p className="text-muted-foreground mb-4">
              {searchQuery || filterBy !== 'all' 
                ? 'Try adjusting your search or filter criteria'
                : `Add authentication credentials to enable ${providerName}`
              }
            </p>
            {!searchQuery && filterBy === 'all' && (
              <Button onClick={onAdd}>
                <Plus className="mr-2 h-4 w-4" />
                Add Your First Credential
              </Button>
            )}
          </CardContent>
        </Card>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {filteredCredentials.map((credential) => (
            <CredentialCard
              key={credential.id}
              credential={credential}
              onEdit={onEdit}
              onDelete={onDelete}
              onTest={onTest}
              onToggleActive={onToggleActive}
              isTestLoading={testingCredentials.has(credential.id)}
            />
          ))}
        </div>
      )}
    </div>
  )
}