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
    <div className="w-80 bg-gray-900/95 border-l border-gray-700 flex flex-col">
      <div className="flex-1 p-4 overflow-y-auto space-y-4">
        {/* Today's Activity Stats */}
        <Card className="bg-gray-800 border border-pink-500/20">
          <CardHeader className="pb-2">
            <h4 className="text-xs font-medium text-pink-400">Today's Activity</h4>
          </CardHeader>
          <CardContent className="pt-0 space-y-2">
            <div className="flex justify-between items-center">
              <span className="text-xs text-gray-400">Messages</span>
              <Badge variant="secondary" className="bg-pink-500/20 text-pink-400">
                {todayStats.messages}
              </Badge>
            </div>
            <div className="flex justify-between items-center">
              <span className="text-xs text-gray-400">Commands</span>
              <Badge variant="secondary" className="bg-blue-500/20 text-blue-400">
                {todayStats.commands}
              </Badge>
            </div>
          </CardContent>
        </Card>

        {/* Recent Bookmarks Widget */}
        <Card className="bg-gray-800 border border-cyan-500/20">
          <CardHeader className="pb-2">
            <div className="flex items-center justify-between">
              <h4 className="text-xs font-medium text-cyan-400">Recent Bookmarks</h4>
              {!bookmarkSearchMode ? (
                <Button
                  variant="ghost"
                  size="icon"
                  className="h-6 w-6 text-gray-400 hover:text-cyan-400"
                  onClick={() => setBookmarkSearchMode(true)}
                >
                  <Search className="w-3 h-3" />
                </Button>
              ) : (
                <Button
                  variant="ghost"
                  size="icon"
                  className="h-6 w-6 text-gray-400 hover:text-cyan-400"
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
                  className="bg-gray-700 text-gray-300 text-xs border-cyan-500/40 focus:border-cyan-500"
                  autoFocus
                />
              </div>
            )}

            {/* Bookmarks List */}
            <div className="space-y-2 max-h-48 overflow-y-auto">
              {recentBookmarks.length === 0 ? (
                <div className="text-center text-gray-500 text-xs py-4">
                  {bookmarkSearchMode ? 'No results found' : 'No bookmarks yet'}
                </div>
              ) : (
                recentBookmarks.map((bookmark) => (
                  <div
                    key={bookmark.id}
                    className="flex items-center space-x-2 text-xs cursor-pointer hover:bg-cyan-500/10 p-2 rounded transition-colors"
                  >
                    <Bookmark className="w-3 h-3 text-pink-500 flex-shrink-0" />
                    <span className="text-gray-300 flex-1 truncate" title={bookmark.fragment_title}>
                      {bookmark.name}
                    </span>
                    <span className="text-gray-500 text-xs flex-shrink-0">{bookmark.updated_at}</span>
                  </div>
                ))
              )}
            </div>
          </CardContent>
        </Card>

        {/* Placeholder for additional widgets */}
        <Card className="bg-gray-800 border border-gray-600">
          <CardHeader className="pb-2">
            <h4 className="text-xs font-medium text-gray-400">Quick Actions</h4>
          </CardHeader>
          <CardContent className="pt-0">
            <div className="space-y-2">
              <Button variant="outline" size="sm" className="w-full text-xs justify-start bg-gray-700 hover:bg-gray-600">
                🔍 Search All
              </Button>
              <Button variant="outline" size="sm" className="w-full text-xs justify-start bg-gray-700 hover:bg-gray-600">
                📝 New Note
              </Button>
              <Button variant="outline" size="sm" className="w-full text-xs justify-start bg-gray-700 hover:bg-gray-600">
                ⚙️ Settings
              </Button>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  )
}