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
    <div className="h-14 bg-gray-900/50 border-b border-gray-700 flex items-center justify-between px-6 backdrop-blur-sm">
      {/* Left: Contact Card Style Layout */}
      <div className="flex items-center space-x-4">
        <Avatar className="w-12 h-12 border-2 border-blue-500/30">
          <AvatarImage src="/interface/avatars/default/avatar-1.png" alt="Agent Avatar" />
          <AvatarFallback className="bg-pink-500/20 text-pink-400">AI</AvatarFallback>
        </Avatar>
        
        <div className="flex items-center space-x-4">
          <div>
            <div className="flex items-center space-x-3">
              <h2 className="text-base font-medium text-gray-300">Agent ID:</h2>
              <span className="text-sm font-medium text-blue-400">C1-13</span>
              <Badge variant="secondary" className="bg-blue-500/20 text-blue-400 border border-blue-500/40">
                v1.1.2
              </Badge>
            </div>
            <div className="text-xs font-medium text-gray-300">
              <span>Role: </span>
              <span className="text-pink-400">Chat Assistant</span>
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
              className="text-sm bg-gray-800 border-gray-700 rounded-l-md px-3 py-2 text-gray-300 placeholder-gray-500 focus:border-pink-500 w-64"
            />
            <Card className="bg-pink-600 h-10 w-10 rounded-r-md border-0 flex items-center justify-center">
              <Search className="w-4 h-4 text-white" />
            </Card>
          </div>

          {/* Search Dropdown */}
          {searchOpen && searchQuery.length > 0 && (
            <Card className="absolute top-full left-0 right-0 mt-1 bg-gray-800 border border-pink-500/30 shadow-lg z-50 max-h-80 overflow-y-auto">
              {searchResults.length === 0 ? (
                <div className="p-3 text-xs text-gray-500 text-center">No fragments found</div>
              ) : (
                searchResults.map((result) => (
                  <div
                    key={result.id}
                    className="p-3 hover:bg-gray-700 cursor-pointer border-b border-gray-600 last:border-b-0"
                  >
                    <div className="flex items-start space-x-2">
                      <Badge 
                        variant="secondary" 
                        className={`mt-0.5 ${
                          result.type === 'todo' 
                            ? 'bg-green-900/20 text-green-400 border border-green-500/20'
                            : 'bg-gray-700 text-gray-300 border border-gray-500/20'
                        }`}
                      >
                        {result.type}
                      </Badge>
                      <div className="flex-1 min-w-0">
                        <div className="text-sm font-medium text-gray-200 truncate">{result.title}</div>
                        <div className="text-xs text-gray-400 line-clamp-2 mt-1">{result.preview}</div>
                        <div className="text-xs text-gray-400 mt-2">{result.created_at}</div>
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