import React, { useState } from 'react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import { 
  Check, 
  CheckSquare, 
  Square, 
  X, 
  Archive,
  Loader2,
  Filter,
  Calendar,
  Tag,
  Folder,
  FileText
} from 'lucide-react'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
  DropdownMenuSeparator,
  DropdownMenuLabel,
  DropdownMenuCheckboxItem
} from '@/components/ui/dropdown-menu'
import { InboxFilters, BulkAcceptData, AcceptAllData } from '../hooks/useInbox'

interface BulkActionsToolbarProps {
  selectedCount: number
  totalCount: number
  filters: InboxFilters
  onFiltersChange: (filters: InboxFilters) => void
  onSelectAll: () => void
  onClearSelection: () => void
  onBulkAccept: (data: BulkAcceptData) => Promise<void>
  onAcceptAll: (data: AcceptAllData) => Promise<void>
  selectedFragmentIds: number[]
  isAcceptingMultiple?: boolean
  isAcceptingAll?: boolean
  availableTypes?: string[]
  availableCategories?: string[]
  availableVaults?: string[]
}

export function BulkActionsToolbar({
  selectedCount,
  totalCount,
  filters,
  onFiltersChange,
  onSelectAll,
  onClearSelection,
  onBulkAccept,
  onAcceptAll,
  selectedFragmentIds,
  isAcceptingMultiple = false,
  isAcceptingAll = false,
  availableTypes = [],
  availableCategories = [],
  availableVaults = []
}: BulkActionsToolbarProps) {
  const [bulkEdits, setBulkEdits] = useState({
    type: '',
    category: '',
    vault: '',
    tags: ''
  })
  const [showFilters, setShowFilters] = useState(false)

  const handleBulkAccept = async () => {
    const data: BulkAcceptData = {
      fragment_ids: selectedFragmentIds,
      edits: {
        type: bulkEdits.type || undefined,
        category: bulkEdits.category || undefined,
        vault: bulkEdits.vault || undefined,
        tags: bulkEdits.tags ? bulkEdits.tags.split(',').map(t => t.trim()).filter(t => t) : undefined
      }
    }

    await onBulkAccept(data)
    setBulkEdits({ type: '', category: '', vault: '', tags: '' })
  }

  const handleAcceptAll = async () => {
    const data: AcceptAllData = {
      ...filters,
      edits: {
        type: bulkEdits.type || undefined,
        category: bulkEdits.category || undefined,
        vault: bulkEdits.vault || undefined,
        tags: bulkEdits.tags ? bulkEdits.tags.split(',').map(t => t.trim()).filter(t => t) : undefined
      }
    }

    await onAcceptAll(data)
    setBulkEdits({ type: '', category: '', vault: '', tags: '' })
  }

  const updateFilter = (key: keyof InboxFilters, value: any) => {
    onFiltersChange({ ...filters, [key]: value })
  }

  const clearFilters = () => {
    onFiltersChange({})
  }

  const hasActiveFilters = Object.values(filters).some(value => 
    value !== undefined && value !== null && value !== '' && 
    (!Array.isArray(value) || value.length > 0)
  )

  const isAllSelected = selectedCount === totalCount && totalCount > 0

  return (
    <div className="space-y-3 p-4 bg-muted/50 border-b">
      {/* Selection and bulk actions */}
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-3">
          {/* Select all toggle */}
          <Button
            variant="ghost"
            size="sm"
            onClick={isAllSelected ? onClearSelection : onSelectAll}
            className="h-8 px-2"
          >
            {isAllSelected ? (
              <CheckSquare className="w-4 h-4" />
            ) : (
              <Square className="w-4 h-4" />
            )}
            <span className="ml-2 text-sm">
              {selectedCount > 0 ? `${selectedCount} selected` : 'Select all'}
            </span>
          </Button>

          {/* Bulk action buttons */}
          {selectedCount > 0 && (
            <div className="flex items-center gap-2">
              <Button
                size="sm"
                onClick={handleBulkAccept}
                disabled={isAcceptingMultiple}
                className="h-8"
              >
                {isAcceptingMultiple ? (
                  <Loader2 className="w-4 h-4 animate-spin mr-2" />
                ) : (
                  <Check className="w-4 h-4 mr-2" />
                )}
                Accept Selected
              </Button>
              
              <Button
                size="sm"
                variant="outline"
                onClick={onClearSelection}
                className="h-8"
              >
                <X className="w-4 h-4 mr-2" />
                Clear
              </Button>
            </div>
          )}
        </div>

        {/* Accept all and filters */}
        <div className="flex items-center gap-2">
          <Button
            size="sm"
            variant="outline"
            onClick={() => setShowFilters(!showFilters)}
            className="h-8"
          >
            <Filter className="w-4 h-4 mr-2" />
            Filters
            {hasActiveFilters && (
              <Badge variant="secondary" className="ml-2 text-xs">
                Active
              </Badge>
            )}
          </Button>

          <Button
            size="sm"
            variant="secondary"
            onClick={handleAcceptAll}
            disabled={isAcceptingAll}
            className="h-8"
          >
            {isAcceptingAll ? (
              <Loader2 className="w-4 h-4 animate-spin mr-2" />
            ) : (
              <Check className="w-4 h-4 mr-2" />
            )}
            Accept All{hasActiveFilters ? ' (Filtered)' : ''}
          </Button>
        </div>
      </div>

      {/* Bulk edits row */}
      {(selectedCount > 0 || showFilters) && (
        <div className="grid grid-cols-4 gap-2">
          <Input
            placeholder="Bulk edit type"
            value={bulkEdits.type}
            onChange={(e) => setBulkEdits(prev => ({ ...prev, type: e.target.value }))}
            className="text-xs h-8"
          />
          <Input
            placeholder="Bulk edit category"
            value={bulkEdits.category}
            onChange={(e) => setBulkEdits(prev => ({ ...prev, category: e.target.value }))}
            className="text-xs h-8"
          />
          <Input
            placeholder="Bulk edit vault"
            value={bulkEdits.vault}
            onChange={(e) => setBulkEdits(prev => ({ ...prev, vault: e.target.value }))}
            className="text-xs h-8"
          />
          <Input
            placeholder="Bulk edit tags (comma-separated)"
            value={bulkEdits.tags}
            onChange={(e) => setBulkEdits(prev => ({ ...prev, tags: e.target.value }))}
            className="text-xs h-8"
          />
        </div>
      )}

      {/* Filters */}
      {showFilters && (
        <div className="space-y-2">
          <div className="flex items-center justify-between">
            <h4 className="text-sm font-medium">Filters</h4>
            {hasActiveFilters && (
              <Button
                size="sm"
                variant="ghost"
                onClick={clearFilters}
                className="h-6 px-2 text-xs"
              >
                Clear all
              </Button>
            )}
          </div>
          
          <div className="grid grid-cols-2 md:grid-cols-4 gap-2">
            {/* Type filter */}
            <DropdownMenu>
              <DropdownMenuTrigger asChild>
                <Button variant="outline" size="sm" className="h-8 justify-start">
                  <FileText className="w-3 h-3 mr-2" />
                  Type
                  {filters.type && filters.type.length > 0 && (
                    <Badge variant="secondary" className="ml-2 text-xs">
                      {filters.type.length}
                    </Badge>
                  )}
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent>
                <DropdownMenuLabel>Fragment Types</DropdownMenuLabel>
                <DropdownMenuSeparator />
                {availableTypes.map(type => (
                  <DropdownMenuCheckboxItem
                    key={type}
                    checked={filters.type?.includes(type) || false}
                    onCheckedChange={(checked) => {
                      const current = filters.type || []
                      const updated = checked
                        ? [...current, type]
                        : current.filter(t => t !== type)
                      updateFilter('type', updated.length > 0 ? updated : undefined)
                    }}
                  >
                    {type}
                  </DropdownMenuCheckboxItem>
                ))}
              </DropdownMenuContent>
            </DropdownMenu>

            {/* Category filter */}
            <DropdownMenu>
              <DropdownMenuTrigger asChild>
                <Button variant="outline" size="sm" className="h-8 justify-start">
                  <Folder className="w-3 h-3 mr-2" />
                  Category
                  {filters.category && (
                    <Badge variant="secondary" className="ml-2 text-xs">
                      1
                    </Badge>
                  )}
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent>
                <DropdownMenuLabel>Categories</DropdownMenuLabel>
                <DropdownMenuSeparator />
                <DropdownMenuItem onClick={() => updateFilter('category', undefined)}>
                  <span className={!filters.category ? 'font-medium' : ''}>All categories</span>
                </DropdownMenuItem>
                {availableCategories.map(category => (
                  <DropdownMenuItem
                    key={category}
                    onClick={() => updateFilter('category', category)}
                  >
                    <span className={filters.category === category ? 'font-medium' : ''}>
                      {category}
                    </span>
                  </DropdownMenuItem>
                ))}
              </DropdownMenuContent>
            </DropdownMenu>

            {/* Date range filters */}
            <Input
              type="date"
              placeholder="From date"
              value={filters.from_date || ''}
              onChange={(e) => updateFilter('from_date', e.target.value || undefined)}
              className="text-xs h-8"
            />
            <Input
              type="date"
              placeholder="To date"
              value={filters.to_date || ''}
              onChange={(e) => updateFilter('to_date', e.target.value || undefined)}
              className="text-xs h-8"
            />
          </div>

          {/* Active filters display */}
          {hasActiveFilters && (
            <div className="flex flex-wrap gap-1 pt-2 border-t">
              {filters.type?.map(type => (
                <Badge key={type} variant="secondary" className="text-xs">
                  Type: {type}
                  <Button
                    variant="ghost"
                    size="sm"
                    className="h-auto p-0 ml-1"
                    onClick={() => {
                      const updated = filters.type?.filter(t => t !== type)
                      updateFilter('type', updated && updated.length > 0 ? updated : undefined)
                    }}
                  >
                    <X className="w-3 h-3" />
                  </Button>
                </Badge>
              ))}
              {filters.category && (
                <Badge variant="secondary" className="text-xs">
                  Category: {filters.category}
                  <Button
                    variant="ghost"
                    size="sm"
                    className="h-auto p-0 ml-1"
                    onClick={() => updateFilter('category', undefined)}
                  >
                    <X className="w-3 h-3" />
                  </Button>
                </Badge>
              )}
              {filters.from_date && (
                <Badge variant="secondary" className="text-xs">
                  From: {filters.from_date}
                  <Button
                    variant="ghost"
                    size="sm"
                    className="h-auto p-0 ml-1"
                    onClick={() => updateFilter('from_date', undefined)}
                  >
                    <X className="w-3 h-3" />
                  </Button>
                </Badge>
              )}
              {filters.to_date && (
                <Badge variant="secondary" className="text-xs">
                  To: {filters.to_date}
                  <Button
                    variant="ghost"
                    size="sm"
                    className="h-auto p-0 ml-1"
                    onClick={() => updateFilter('to_date', undefined)}
                  >
                    <X className="w-3 h-3" />
                  </Button>
                </Badge>
              )}
            </div>
          )}
        </div>
      )}
    </div>
  )
}