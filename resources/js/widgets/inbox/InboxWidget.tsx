import React from 'react'
import { Card, CardContent, CardHeader } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { ScrollArea } from '@/components/ui/scroll-area'
import { Badge } from '@/components/ui/badge'
import { 
  Inbox, 
  Loader2, 
  AlertCircle, 
  CheckCircle, 
  Archive,
  RefreshCw
} from 'lucide-react'
import { useInbox } from './hooks/useInbox'
import { FragmentReviewCard } from './components/FragmentReviewCard'
import { BulkActionsToolbar } from './components/BulkActionsToolbar'

export function InboxWidget() {
  const {
    fragments,
    stats,
    filters,
    selectedFragments,
    isLoadingFragments,
    isLoadingStats,
    isFetchingNextPage,
    fetchNextPage,
    hasNextPage,
    fragmentsError,
    statsError,
    setFilters,
    toggleSelection,
    selectAll,
    clearSelection,
    isSelected,
    selectedCount,
    acceptFragment,
    acceptMultiple,
    acceptAll,
    archiveFragment,
    skipFragment,
    reopenFragment,
    isAccepting,
    isAcceptingMultiple,
    isAcceptingAll,
    isArchiving,
    isSkipping,
    isReopening
  } = useInbox()

  // Get unique values for filter dropdowns
  const availableTypes = [...new Set(fragments.map(f => f.type))].sort()
  const availableCategories = [...new Set(fragments.map(f => f.category).filter(Boolean))].sort()
  const availableVaults = [...new Set(fragments.map(f => f.vault).filter(Boolean))].sort()

  const handleBulkAccept = async (data: any) => {
    await acceptMultiple(data)
  }

  const handleAcceptAll = async (data: any) => {
    await acceptAll(data)
  }

  if (fragmentsError || statsError) {
    return (
      <Card className="border-0 shadow-none border-b border-gray-200">
        <CardHeader className="pb-1">
          <h4 className="text-xs font-medium flex items-center gap-1">
            <Inbox className="w-3 h-3" />
            Inbox Management
          </h4>
        </CardHeader>
        <CardContent className="pt-0">
          <div className="flex items-center gap-2 text-red-500 text-xs py-4">
            <AlertCircle className="w-4 h-4" />
            <span>Failed to load inbox data</span>
          </div>
        </CardContent>
      </Card>
    )
  }

  return (
    <Card className="border-0 shadow-none border-b border-gray-200 flex flex-col h-full">
      <CardHeader className="pb-1 flex-shrink-0">
        <div className="flex items-center justify-between">
          <h4 className="text-xs font-medium flex items-center gap-1">
            <Inbox className="w-3 h-3" />
            Inbox Management
          </h4>
          <Button
            variant="ghost"
            size="sm"
            onClick={() => window.location.reload()}
            className="h-6 w-6 p-0"
          >
            <RefreshCw className="w-3 h-3" />
          </Button>
        </div>
        
        {/* Stats row */}
        {isLoadingStats ? (
          <div className="flex items-center gap-1">
            <Loader2 className="w-3 h-3 animate-spin" />
            <span className="text-xs text-muted-foreground">Loading stats...</span>
          </div>
        ) : stats ? (
          <div className="flex items-center gap-2 text-xs">
            <Badge variant="default" className="text-xs">
              <Inbox className="w-3 h-3 mr-1" />
              {stats.pending} pending
            </Badge>
            <Badge variant="secondary" className="text-xs">
              <CheckCircle className="w-3 h-3 mr-1" />
              {stats.accepted} accepted
            </Badge>
            <Badge variant="outline" className="text-xs">
              <Archive className="w-3 h-3 mr-1" />
              {stats.archived} archived
            </Badge>
          </div>
        ) : null}
      </CardHeader>

      <CardContent className="pt-0 flex-1 flex flex-col min-h-0 p-0">
        {/* Bulk actions toolbar */}
        <BulkActionsToolbar
          selectedCount={selectedCount}
          totalCount={fragments.length}
          filters={filters}
          onFiltersChange={setFilters}
          onSelectAll={selectAll}
          onClearSelection={clearSelection}
          onBulkAccept={handleBulkAccept}
          onAcceptAll={handleAcceptAll}
          selectedFragmentIds={selectedFragments}
          isAcceptingMultiple={isAcceptingMultiple}
          isAcceptingAll={isAcceptingAll}
          availableTypes={availableTypes}
          availableCategories={availableCategories}
          availableVaults={availableVaults}
        />

        {/* Fragments list */}
        <ScrollArea className="flex-1">
          <div className="p-4 space-y-3">
            {isLoadingFragments ? (
              // Loading skeleton
              [...Array(3)].map((_, i) => (
                <Card key={i} className="border animate-pulse">
                  <CardContent className="p-4">
                    <div className="space-y-2">
                      <div className="h-4 bg-muted rounded w-3/4"></div>
                      <div className="h-3 bg-muted rounded w-1/2"></div>
                      <div className="flex gap-2">
                        <div className="h-5 bg-muted rounded w-16"></div>
                        <div className="h-5 bg-muted rounded w-20"></div>
                      </div>
                    </div>
                  </CardContent>
                </Card>
              ))
            ) : fragments.length === 0 ? (
              <div className="text-center text-muted-foreground text-sm py-8">
                <Inbox className="w-12 h-12 mx-auto mb-4 opacity-50" />
                <p>No fragments in the inbox</p>
                <p className="text-xs mt-1">
                  Fragments will appear here when they need review
                </p>
              </div>
            ) : (
              <>
                {fragments.map((fragment) => (
                  <FragmentReviewCard
                    key={fragment.id}
                    fragment={fragment}
                    isSelected={isSelected(fragment.id)}
                    onToggleSelection={toggleSelection}
                    onAccept={acceptFragment}
                    onArchive={archiveFragment}
                    onSkip={skipFragment}
                    onReopen={reopenFragment}
                    isAccepting={isAccepting}
                    isArchiving={isArchiving}
                    isSkipping={isSkipping}
                    isReopening={isReopening}
                  />
                ))}
                
                {/* Load more button */}
                {hasNextPage && (
                  <div className="pt-4 text-center">
                    <Button
                      variant="outline"
                      onClick={() => fetchNextPage()}
                      disabled={isFetchingNextPage}
                      className="w-full"
                    >
                      {isFetchingNextPage ? (
                        <>
                          <Loader2 className="w-4 h-4 mr-2 animate-spin" />
                          Loading more...
                        </>
                      ) : (
                        'Load more fragments'
                      )}
                    </Button>
                  </div>
                )}
              </>
            )}
          </div>
        </ScrollArea>
      </CardContent>
    </Card>
  )
}