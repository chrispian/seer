import React, { useState } from 'react'
import { Card } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Search } from 'lucide-react'

export function ChatHeader() {
  const [searchQuery, setSearchQuery] = useState('')
  const [searchOpen, setSearchOpen] = useState(false)

  const searchResults = [
    { id: 1, type: 'note', title: 'Sample Fragment', preview: 'This is a sample fragment...', created_at: '2024-01-01' },
    { id: 2, type: 'todo', title: 'Task Example', preview: 'Complete the feature...', created_at: '2024-01-02' },
  ]

  return (
    <div className="h-14 bg-white border-b flex items-center justify-between px-6">
      {/* Left: Contact Card Style Layout */}
      <div className="flex items-center space-x-4">
        <Avatar className="w-12 h-12">
          <AvatarImage src="/interface/avatars/default/avatar-1.png" alt="Agent Avatar" />
          <AvatarFallback>AI</AvatarFallback>
        </Avatar>
        
        <div className="flex items-center space-x-4">
          <div>
            <div className="flex items-center space-x-3">
              <h2 className="text-base font-medium">Agent ID:</h2>
              <span className="text-sm font-medium">C1-13</span>
              <Badge variant="secondary">v1.1.2</Badge>
            </div>
            <div className="text-xs font-medium text-muted-foreground">
              <span>Role: </span>
              <span>Chat Assistant</span>
            </div>
          </div>
        </div>
      </div>

      {/* Right: Search Input */}
      <div className="flex items-center space-x-4">
        <div className="relative">
          <div className="flex items-center">
            <Input
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              onFocus={() => setSearchOpen(true)}
              onBlur={() => setTimeout(() => setSearchOpen(false), 200)}
              placeholder="Search fragments..."
              className="text-sm rounded-l-md w-64"
            />
            <Button size="icon" className="rounded-l-none">
              <Search className="w-4 h-4" />
            </Button>
          </div>

          {/* Search Dropdown */}
          {searchOpen && searchQuery.length > 0 && (
            <Card className="absolute top-full left-0 right-0 mt-1 shadow-lg z-50 max-h-80 overflow-y-auto">
              {searchResults.length === 0 ? (
                <div className="p-3 text-xs text-muted-foreground text-center">No fragments found</div>
              ) : (
                searchResults.map((result) => (
                  <div
                    key={result.id}
                    className="p-3 hover:bg-accent cursor-pointer border-b last:border-b-0"
                  >
                    <div className="flex items-start space-x-2">
                      <Badge variant="secondary" className="mt-0.5">
                        {result.type}
                      </Badge>
                      <div className="flex-1 min-w-0">
                        <div className="text-sm font-medium truncate">{result.title}</div>
                        <div className="text-xs text-muted-foreground line-clamp-2 mt-1">{result.preview}</div>
                        <div className="text-xs text-muted-foreground mt-2">{result.created_at}</div>
                      </div>
                    </div>
                  </div>
                ))
              )}
            </Card>
          )}
        </div>
      </div>
    </div>
  )
}