import React, { useState } from 'react'
import { Card, CardContent, CardHeader } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import { Search, X, Bookmark } from 'lucide-react'

export function RightRail() {
  const [bookmarkSearchMode, setBookmarkSearchMode] = useState(false)
  const [bookmarkSearchQuery, setBookmarkSearchQuery] = useState('')

  const todayStats = {
    messages: 5,
    commands: 0,
  }

  const recentBookmarks = [
    { id: 1, name: 'Important Note', updated_at: '2h ago', fragment_title: 'Meeting notes from client call' },
    { id: 2, name: 'Todo Item', updated_at: '1d ago', fragment_title: 'Fix the login authentication issue' },
    { id: 3, name: 'Code Snippet', updated_at: '2d ago', fragment_title: 'React component for data visualization' },
  ]

  const handleBookmarkSearch = (query: string) => {
    setBookmarkSearchQuery(query)
    // In real implementation, this would trigger search
  }

  const clearBookmarkSearch = () => {
    setBookmarkSearchMode(false)
    setBookmarkSearchQuery('')
  }

  return (
    <div className="w-80 bg-white border-l flex flex-col">
      <div className="flex-1 px-1 py-1 overflow-y-auto space-y-2">
        {/* Today's Activity Stats */}
        <Card className="border-0 shadow-none border-b-1 border-gray-200">
          <CardHeader className="pb-1">
            <h4 className="text-xs font-medium">Today's Activity</h4>
          </CardHeader>
          <CardContent className="pt-0 space-y-1">
            <div className="flex justify-between items-center">
              <span className="text-xs text-muted-foreground">Messages</span>
              <Badge variant="secondary">
                {todayStats.messages}
              </Badge>
            </div>
            <div className="flex justify-between items-center">
              <span className="text-xs text-muted-foreground">Commands</span>
              <Badge variant="secondary">
                {todayStats.commands}
              </Badge>
            </div>
          </CardContent>
        </Card>

        {/* Recent Bookmarks Widget */}
        <Card className="border-0 shadow-none border-b-1 border-gray-200">
          <CardHeader className="pb-1">
            <div className="flex items-center justify-between">
              <h4 className="text-xs font-medium">Recent Bookmarks</h4>
              {!bookmarkSearchMode ? (
                <Button
                  variant="ghost"
                  size="icon"
                  className="h-6 w-6"
                  onClick={() => setBookmarkSearchMode(true)}
                >
                  <Search className="w-3 h-3" />
                </Button>
              ) : (
                <Button
                  variant="ghost"
                  size="icon"
                  className="h-6 w-6"
                  onClick={clearBookmarkSearch}
                >
                  <X className="w-3 h-3" />
                </Button>
              )}
            </div>
          </CardHeader>
          <CardContent className="pt-0">
            {/* Search Input */}
            {bookmarkSearchMode && (
              <div className="mb-3">
                <Input
                  value={bookmarkSearchQuery}
                  onChange={(e) => handleBookmarkSearch(e.target.value)}
                  placeholder="Search bookmarks..."
                  className="text-xs"
                  autoFocus
                />
              </div>
            )}

            {/* Bookmarks List */}
            <div className="space-y-1 max-h-48 overflow-y-auto">
              {recentBookmarks.length === 0 ? (
                <div className="text-center text-muted-foreground text-xs py-4">
                  {bookmarkSearchMode ? 'No results found' : 'No bookmarks yet'}
                </div>
              ) : (
                recentBookmarks.map((bookmark) => (
                  <div
                    key={bookmark.id}
                    className="flex items-center space-x-2 text-xs cursor-pointer hover:bg-accent p-0 transition-colors"
                  >
                    <Bookmark className="w-3 h-3 flex-shrink-0" />
                    <span className="flex-1 truncate" title={bookmark.fragment_title}>
                      {bookmark.name}
                    </span>
                    <span className="text-muted-foreground text-xs flex-shrink-0">{bookmark.updated_at}</span>
                  </div>
                ))
              )}
            </div>
          </CardContent>
        </Card>

      </div>
    </div>
  )
}
