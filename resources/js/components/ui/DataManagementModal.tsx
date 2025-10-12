import React, { useState, useEffect, useMemo, useRef } from 'react'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { ScrollArea } from '@/components/ui/scroll-area'
import { Badge } from '@/components/ui/badge'
import { Checkbox } from '@/components/ui/checkbox'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { LoadingSpinner } from '@/components/ui/loading-spinner'
import { Search, Filter, MoreHorizontal, Plus, Check, X, ChevronDown, ChevronRight, Info } from 'lucide-react'

export interface DataItem {
  id: string | number
  enabled?: boolean
  [key: string]: any
}

export interface ColumnDefinition<T extends DataItem> {
  key: keyof T | 'actions' | 'checkbox'
  label: string
  width?: string
  sortable?: boolean
  render?: (item: T, value: any) => React.ReactNode
}

export interface FilterDefinition {
  key: string
  label: string
  options: { value: string; label: string; count?: number }[]
}

interface DataRowProps<T extends DataItem> {
  item: T
  columns: ColumnDefinition<T>[]
  isExpanded: boolean
  expandedContent?: (item: T) => React.ReactNode
  onToggleExpanded: (itemId: string | number) => void
  onToggleCheckbox?: (itemId: string | number, checked: boolean) => void
  onAction?: (action: string, item: T) => void
  actionItems?: Array<{ key: string; label: string; className?: string }>
  clickableRows?: boolean
  expandedContentMaxWidth?: string
  onRowClick?: (item: T) => void
}

const DataRow = <T extends DataItem>({
  item,
  columns,
  isExpanded,
  expandedContent,
  onToggleExpanded,
  onToggleCheckbox,
  onAction,
  actionItems = [],
  clickableRows = false,
  expandedContentMaxWidth = '85%',
  onRowClick
}: DataRowProps<T>) => {
  
  const handleRowClick = (e: React.MouseEvent) => {
    // Don't trigger row click if clicking on buttons, checkboxes, or other interactive elements
    const target = e.target as HTMLElement
    if (target.closest('button') || target.closest('input') || target.closest('a') || target.closest('[role="button"]')) {
      return
    }
    
    if (clickableRows) {
      if (onRowClick) {
        onRowClick(item)
      } else if (expandedContent) {
        onToggleExpanded(item.id)
      }
    }
  }

  return (
    <TableRow 
      key={item.id} 
      className={`hover:bg-muted/50 ${clickableRows && (expandedContent || onRowClick) ? 'cursor-pointer' : ''}`}
      onClick={handleRowClick}
    >
      {columns.map((column) => {
      if (column.key === 'checkbox') {
        return (
          <TableCell key="checkbox" className="w-8">
            <div className="flex items-center justify-center p-1">
              <Checkbox
                checked={item.enabled ?? false}
                onCheckedChange={(checked) => onToggleCheckbox?.(item.id, checked as boolean)}
                className="rounded-none"
                aria-label={`Toggle ${item.id}`}
              />
            </div>
          </TableCell>
        )
      }
      
      if (column.key === 'actions') {
        return (
          <TableCell key="actions" className="w-12">
            <div className="flex items-center gap-1">
              {expandedContent && (
                <Button
                  variant="ghost"
                  size="icon"
                  className="h-6 w-6 hover:bg-gray-100 text-gray-600 hover:text-black"
                  onClick={() => onToggleExpanded(item.id)}
                  aria-label={`${isExpanded ? 'Collapse' : 'Expand'} details`}
                >
                  {isExpanded ? <ChevronDown className="h-3 w-3" /> : <ChevronRight className="h-3 w-3" />}
                </Button>
              )}
              {actionItems.length > 0 && (
                <DropdownMenu modal={false}>
                  <DropdownMenuTrigger asChild>
                    <Button 
                      variant="ghost" 
                      size="sm" 
                      className="h-8 w-8 p-0 focus:ring-2 focus:ring-ring"
                      aria-label="Item options"
                    >
                      <MoreHorizontal className="h-4 w-4" />
                    </Button>
                  </DropdownMenuTrigger>
                  <DropdownMenuContent align="end">
                    {actionItems.map((action) => (
                      <DropdownMenuItem
                        key={action.key}
                        className={action.className}
                        onClick={() => onAction?.(action.key, item)}
                      >
                        {action.label}
                      </DropdownMenuItem>
                    ))}
                  </DropdownMenuContent>
                </DropdownMenu>
              )}
            </div>
          </TableCell>
        )
      }
      
      const value = item[column.key]
      const content = column.render ? column.render(item, value) : value
      
      return (
        <TableCell key={String(column.key)} className={column.width}>
          {column.key === columns[1]?.key && expandedContent ? (
            <div className="flex flex-col space-y-1">
              <div>{content}</div>
              {isExpanded && (
                <div className="pt-2 space-y-2 border-t border-muted">
                  <div 
                    className="overflow-hidden"
                    style={{ maxWidth: expandedContentMaxWidth }}
                  >
                    <div className="overflow-x-auto max-h-96 overflow-y-auto border rounded p-3 bg-muted/10">
                      {expandedContent(item)}
                    </div>
                  </div>
                </div>
              )}
            </div>
          ) : (
            content
          )}
        </TableCell>
      )
    })}
    </TableRow>
  )
}

interface DataManagementModalProps<T extends DataItem> {
  isOpen: boolean
  onClose: () => void
  onBack?: () => void
  title: string
  data: T[]
  columns: ColumnDefinition<T>[]
  loading?: boolean
  error?: string
  filters?: FilterDefinition[]
  searchPlaceholder?: string
  searchFields?: (keyof T)[]
  expandedContent?: (item: T) => React.ReactNode
  onToggleItem?: (itemId: string | number, checked: boolean) => void
  onAction?: (action: string, item: T) => void
  actionItems?: Array<{ key: string; label: string; className?: string }>
  onRefresh?: () => void
  customHeader?: React.ReactNode
  emptyStateMessage?: string
  emptyStateIcon?: React.ReactNode
  defaultSort?: string
  defaultSortDirection?: 'asc' | 'desc'
  clickableRows?: boolean
  expandedContentMaxWidth?: string
  onRowClick?: (item: T) => void
}

export function DataManagementModal<T extends DataItem>({
  isOpen,
  onClose,
  onBack,
  title,
  data,
  columns,
  loading = false,
  error,
  filters = [],
  searchPlaceholder = "Search...",
  searchFields = [],
  expandedContent,
  onToggleItem,
  onAction,
  actionItems = [],
  onRefresh,
  customHeader,
  emptyStateMessage = "No items found",
  emptyStateIcon = <Info className="h-8 w-8" />,
  defaultSort,
  defaultSortDirection = 'desc',
  clickableRows = false,
  expandedContentMaxWidth = '85%',
  onRowClick
}: DataManagementModalProps<T>) {
  const [searchQuery, setSearchQuery] = useState('')
  const [activeFilters, setActiveFilters] = useState<Record<string, string>>({})
  const [expandedItems, setExpandedItems] = useState<Set<string | number>>(new Set())
  const searchInputRef = useRef<HTMLInputElement>(null)

  // Focus search input when modal opens
  useEffect(() => {
    if (isOpen) {
      setTimeout(() => {
        searchInputRef.current?.focus()
      }, 100)
    }
  }, [isOpen])

  // Use ref to avoid stale closure issues
  const onBackRef = useRef(onBack)
  useEffect(() => { onBackRef.current = onBack }, [onBack])

  // Keyboard shortcuts
  useEffect(() => {
    if (!isOpen) return

    const handleKeyDown = (e: KeyboardEvent) => {
      if (e.key === 'Escape') {
        console.log('[DataManagementModal] ESC pressed, onBack exists?', !!onBackRef.current)
        // If onBack is provided, use it for ESC (go back one level)
        // Otherwise use onClose (close modal)
        if (onBackRef.current) {
          e.preventDefault()
          e.stopPropagation()
          onBackRef.current()
        } else {
          onClose()
        }
      }
    }

    document.addEventListener('keydown', handleKeyDown, { capture: true })
    return () => document.removeEventListener('keydown', handleKeyDown, { capture: true })
  }, [isOpen, onClose])

  // ERROR TRAP: Catch non-array data before it causes .filter() error
  // This happens when handler returns wrong data structure or navigation_config.data_prop is incorrect
  if (!Array.isArray(data)) {
    console.error('[DataManagementModal] ❌ DATA IS NOT AN ARRAY ❌')
    console.error('Received type:', typeof data)
    console.error('Received value:', data)
    console.error('')
    console.error('🔍 COMMON CAUSES:')
    console.error('1. Handler returns old format: { type, component, data }')
    console.error('   FIX: Use $this->respond([\'items\' => $data]) instead')
    console.error('')
    console.error('2. navigation_config.data_prop mismatch')
    console.error('   Example: Handler returns [\'bookmarks\' => ...] but config says data_prop: \'items\'')
    console.error('   FIX: Match data_prop to response key')
    console.error('')
    console.error('3. Handler returns data directly instead of wrapped')
    console.error('   Example: return $data (wrong) vs return $this->respond([\'items\' => $data]) (right)')
    console.error('')
    console.error('💡 See: docs/NAVIGATION_SYSTEM_COMPLETE_GUIDE.md')
  }

  // Filter data based on search and filters
  const filteredData = useMemo(() => {
    // Guard against non-array data
    if (!Array.isArray(data)) {
      return []
    }
    
    return data.filter(item => {
      // Search filter
      if (searchQuery && searchFields.length > 0) {
        const searchLower = searchQuery.toLowerCase()
        const matches = searchFields.some(field => {
          const value = item[field]
          return value != null && String(value).toLowerCase().includes(searchLower)
        })
        if (!matches) return false
      }

      // Active filters
      for (const [filterKey, filterValue] of Object.entries(activeFilters)) {
        if (filterValue === 'all') continue
        
        const itemValue = item[filterKey as keyof T]
        if (itemValue == null || String(itemValue) !== filterValue) {
          return false
        }
      }

      return true
    })
  }, [data, searchQuery, searchFields, activeFilters])

  const toggleExpanded = (itemId: string | number) => {
    setExpandedItems(prev => {
      const newSet = new Set(prev)
      if (newSet.has(itemId)) {
        newSet.delete(itemId)
      } else {
        newSet.add(itemId)
      }
      return newSet
    })
  }

  if (!isOpen) return null

  // Show error UI if data is not an array
  if (!Array.isArray(data)) {
    return (
      <Dialog open={isOpen} onOpenChange={(open) => !open && (onBack ? onBack() : onClose())}>
        <DialogContent className="max-w-2xl">
          <DialogHeader>
            <DialogTitle className="text-destructive">Configuration Error</DialogTitle>
            <DialogDescription>
              DataManagementModal received invalid data
            </DialogDescription>
          </DialogHeader>
          <div className="space-y-4 p-4 bg-destructive/10 rounded border border-destructive/20">
            <div className="font-semibold">❌ Data is not an array</div>
            <div className="text-sm space-y-2">
              <div><strong>Received type:</strong> {typeof data}</div>
              <div className="text-xs"><strong>Value:</strong> {JSON.stringify(data, null, 2).slice(0, 200)}</div>
            </div>
            <div className="text-sm space-y-2">
              <div className="font-semibold">🔍 Common causes:</div>
              <ol className="list-decimal list-inside space-y-1 text-xs">
                <li>Handler uses old format instead of <code className="bg-muted px-1">$this-&gt;respond()</code></li>
                <li><code className="bg-muted px-1">navigation_config.data_prop</code> doesn't match response key</li>
                <li>Handler returns unwrapped data</li>
              </ol>
            </div>
            <div className="text-xs text-muted-foreground">
              💡 See: <code>docs/NAVIGATION_SYSTEM_COMPLETE_GUIDE.md</code>
            </div>
          </div>
        </DialogContent>
      </Dialog>
    )
  }

  return (
    <Dialog open={isOpen} onOpenChange={(open) => !open && (onBack ? onBack() : onClose())}>
      <DialogContent className="max-w-6xl w-[95vw] sm:w-[85vw] h-[85vh] min-h-[600px] rounded-sm flex flex-col">
        <DialogHeader>
          <DialogTitle className="text-foreground flex items-center gap-2">
            <span>{title}</span>
            <Badge variant="secondary" className="text-xs">
              {filteredData.length} of {Array.isArray(data) ? data.length : 0}
            </Badge>
          </DialogTitle>
          <DialogDescription className="sr-only">
            Data management interface for {title?.toLowerCase() || 'items'}
          </DialogDescription>
          {customHeader}
        </DialogHeader>

        {/* Search and Filters */}
        <div className="flex flex-col gap-3 p-3 sm:p-4 bg-muted/20 rounded-sm">
          <div className="flex items-center gap-2">
            <Search className="h-4 w-4 text-muted-foreground" />
            <Input
              ref={searchInputRef}
              placeholder={searchPlaceholder}
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              className="rounded-sm flex-1"
            />
            {onRefresh && (
              <Button variant="outline" size="sm" onClick={onRefresh}>
                Refresh
              </Button>
            )}
          </div>

          {/* Filter Chips */}
          {filters.length > 0 && (
            <div className="flex flex-wrap gap-2">
              {filters.map((filter) => (
                <div key={filter.key} className="flex gap-1">
                  {filter.options.map(option => (
                    <Button
                      key={option.value}
                      variant={activeFilters[filter.key] === option.value ? 'default' : 'outline'}
                      size="sm"
                      className="h-7 px-3 text-xs rounded-full"
                      onClick={() => setActiveFilters(prev => ({ ...prev, [filter.key]: option.value }))}
                    >
                      {option.label}
                      {option.count !== undefined && ` (${option.count})`}
                    </Button>
                  ))}
                </div>
              ))}

              {/* Clear Filters and Search */}
              {(Object.values(activeFilters).some(v => v !== 'all') || searchQuery) && (
                <Button
                  variant="ghost"
                  size="sm"
                  className="h-7 px-3 text-xs rounded-full"
                  onClick={() => {
                    setActiveFilters({})
                    setSearchQuery('')
                  }}
                >
                  <X className="h-3 w-3 mr-1" />
                  Clear All
                </Button>
              )}
            </div>
          )}
        </div>

        {/* Content */}
        <ScrollArea className="flex-1 pr-4">
          {loading ? (
            <div className="flex items-center justify-center py-8">
              <LoadingSpinner />
              <span className="ml-2 text-muted-foreground">Loading...</span>
            </div>
          ) : error ? (
            <div className="flex flex-col items-center justify-center py-8 text-destructive">
              <X className="h-8 w-8 mb-2" />
              <p className="text-center">{error}</p>
              {onRefresh && (
                <Button 
                  variant="outline" 
                  size="sm" 
                  className="mt-2"
                  onClick={onRefresh}
                >
                  Try Again
                </Button>
              )}
            </div>
          ) : filteredData.length === 0 ? (
            <div className="flex flex-col items-center justify-center py-8 text-muted-foreground">
              <div className="text-center">
                {emptyStateIcon}
                <p className="text-lg font-medium mb-1 mt-2">{emptyStateMessage}</p>
                {searchQuery && (
                  <p className="text-sm">Try adjusting your search or filters</p>
                )}
              </div>
            </div>
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  {columns.map((column) => (
                    <TableHead key={String(column.key)} className={column.width}>
                      {column.label}
                    </TableHead>
                  ))}
                </TableRow>
              </TableHeader>
              <TableBody>
                {filteredData.map((item) => (
                  <DataRow
                    key={item.id}
                    item={item}
                    columns={columns}
                    isExpanded={expandedItems.has(item.id)}
                    expandedContent={expandedContent}
                    onToggleExpanded={toggleExpanded}
                    onToggleCheckbox={onToggleItem}
                    onAction={onAction}
                    actionItems={actionItems}
                    clickableRows={clickableRows}
                    expandedContentMaxWidth={expandedContentMaxWidth}
                    onRowClick={onRowClick}
                  />
                ))}
              </TableBody>
            </Table>
          )}
        </ScrollArea>

        {/* Footer */}
        <div className="flex justify-between items-center">
          <span className="text-sm text-muted-foreground">
            Showing {filteredData.length} of {data.length} items
          </span>
          <Button variant="outline" onClick={onClose} className="rounded-sm">
            Close
          </Button>
        </div>
      </DialogContent>
    </Dialog>
  )
}