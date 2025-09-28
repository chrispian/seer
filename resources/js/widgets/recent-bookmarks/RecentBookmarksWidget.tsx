import React, { useState } from 'react'
import { Card, CardContent, CardHeader } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { ScrollArea } from '@/components/ui/scroll-area'
import { Search, X, Bookmark, ExternalLink } from 'lucide-react'
import { useBookmarks } from './hooks/useBookmarks'

export function RecentBookmarksWidget() {
  const [isSearchMode, setIsSearchMode] = useState(false)
  const { 
    bookmarks, 
    searchQuery, 
    setSearchQuery, 
    fetchNextPage, 
    hasNextPage, 
    isFetchingNextPage,
    isLoading,
    error 
  } = useBookmarks()

  const handleSearchToggle = () => {
    if (isSearchMode) {
      setSearchQuery('')
      setIsSearchMode(false)
    } else {
      setIsSearchMode(true)
    }
  }

  const handleBookmarkClick = (bookmark: any) => {
    // TODO: Navigate to bookmark fragment or handle bookmark click
    console.log('Bookmark clicked:', bookmark)
  }

  const formatTimeAgo = (dateString: string) => {
    const date = new Date(dateString)
    const now = new Date()
    const diffInHours = Math.floor((now.getTime() - date.getTime()) / (1000 * 60 * 60))
    
    if (diffInHours < 1) return 'Just now'
    if (diffInHours < 24) return `${diffInHours}h ago`
    const diffInDays = Math.floor(diffInHours / 24)
    if (diffInDays < 7) return `${diffInDays}d ago`
    return date.toLocaleDateString()
  }

  if (error) {
    return (
      <Card className="border-0 shadow-none border-b border-gray-200">
        <CardHeader className="pb-1">
          <div className="flex items-center justify-between">
            <h4 className="text-xs font-medium">Recent Bookmarks</h4>
            <Button variant="ghost" size="icon" className="h-6 w-6" disabled>
              <Search className="w-3 h-3" />
            </Button>
          </div>
        </CardHeader>
        <CardContent className="pt-0">
          <div className="text-center text-muted-foreground text-xs py-4">
            Failed to load bookmarks
          </div>
        </CardContent>
      </Card>
    )
  }

  return (
    <Card className="border-0 shadow-none border-b border-gray-200">
      <CardHeader className="pb-1">
        <div className="flex items-center justify-between">
          <h4 className="text-xs font-medium">Recent Bookmarks</h4>
          <Button
            variant="ghost"
            size="icon"
            className="h-6 w-6"
            onClick={handleSearchToggle}
          >
            {isSearchMode ? <X className="w-3 h-3" /> : <Search className="w-3 h-3" />}
          </Button>
        </div>
      </CardHeader>
      <CardContent className="pt-0">
        {/* Search Input */}
        {isSearchMode && (
          <div className="mb-3">
            <Input
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              placeholder="Search bookmarks..."
              className="text-xs"
              autoFocus
            />
          </div>
        )}

        {/* Bookmarks List */}
        <ScrollArea className="h-48">
          <div className="space-y-1">
            {isLoading ? (
              // Loading skeleton
              [...Array(3)].map((_, i) => (
                <div key={i} className="flex items-center space-x-2 p-1">
                  <div className="w-3 h-3 bg-muted rounded animate-pulse"></div>
                  <div className="flex-1">
                    <div className="h-3 bg-muted rounded w-full mb-1 animate-pulse"></div>
                    <div className="h-2 bg-muted rounded w-16 animate-pulse"></div>
                  </div>
                </div>
              ))
            ) : bookmarks.length === 0 ? (
              <div className="text-center text-muted-foreground text-xs py-4">
                {isSearchMode && searchQuery ? 'No results found' : 'No bookmarks yet'}
              </div>
            ) : (
              <>
                {bookmarks.map((bookmark) => (
                  <div
                    key={bookmark.id}
                    className="flex items-center space-x-2 text-xs cursor-pointer hover:bg-accent p-1 rounded transition-colors group"
                    onClick={() => handleBookmarkClick(bookmark)}
                  >
                    <Bookmark className="w-3 h-3 flex-shrink-0 text-muted-foreground" />
                    <div className="flex-1 min-w-0">
                      <div className="truncate font-medium" title={bookmark.fragment_title || bookmark.name}>
                        {bookmark.name}
                      </div>
                      {bookmark.fragment_preview && (
                        <div className="truncate text-muted-foreground text-xs" title={bookmark.fragment_preview}>
                          {bookmark.fragment_preview}
                        </div>
                      )}
                    </div>
                    <div className="flex items-center gap-1 flex-shrink-0">
                      <span className="text-muted-foreground text-xs">
                        {formatTimeAgo(bookmark.updated_at)}
                      </span>
                      <ExternalLink className="w-3 h-3 opacity-0 group-hover:opacity-100 transition-opacity" />
                    </div>
                  </div>
                ))}
                
                {/* Load More Button */}
                {hasNextPage && (
                  <div className="pt-2">
                    <Button
                      variant="ghost"
                      size="sm"
                      onClick={() => fetchNextPage()}
                      disabled={isFetchingNextPage}
                      className="w-full text-xs"
                    >
                      {isFetchingNextPage ? 'Loading...' : 'Load more'}
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