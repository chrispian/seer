import React from 'react'
import { Card, CardContent, CardHeader } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import { Separator } from '@/components/ui/separator'
import { Plus, MessageSquare, Terminal, Pin, Trash2 } from 'lucide-react'

export function LeftNav() {
  const recentChats = [
    { id: 4, title: "#c4 Let's do a new...", count: 5, active: true },
    { id: 5, title: "#c5 New Chat", count: 0, active: false },
    { id: 2, title: "#c2 Test", count: 5, active: false },
    { id: 3, title: "#c3 Test", count: 5, active: false },
  ]

  return (
    <div className="w-72 bg-gray-900/95 border-r border-gray-700 flex flex-col">
      {/* Vault Selection */}
      <Card className="m-4 bg-gray-800 border-pink-500/20">
        <CardHeader className="pb-2">
          <h3 className="text-xs font-medium text-pink-400">Vault</h3>
        </CardHeader>
        <CardContent className="pt-0">
          <div className="flex space-x-1">
            <select className="flex-1 bg-gray-700 text-sm text-pink-400 rounded-l p-2 border border-pink-500/20 focus:border-pink-500">
              <option>work</option>
              <option>personal</option>
              <option>clients</option>
            </select>
            <Button variant="outline" size="icon" className="px-3 bg-gray-700 border-pink-500/20 hover:bg-pink-500/20">
              <Plus className="w-4 h-4 text-pink-500" />
            </Button>
          </div>
        </CardContent>
      </Card>

      {/* Project Selection */}
      <Card className="mx-4 mb-4 bg-gray-800 border-blue-500/20">
        <CardHeader className="pb-2">
          <h3 className="text-xs font-medium text-blue-400">Project</h3>
        </CardHeader>
        <CardContent className="pt-0">
          <div className="flex space-x-1">
            <select className="flex-1 bg-gray-700 text-sm text-blue-400 rounded-l p-2 border border-blue-500/20 focus:border-blue-500">
              <option>Engineering</option>
            </select>
            <Button variant="outline" size="icon" className="px-3 bg-gray-700 border-blue-500/20 hover:bg-blue-500/20">
              <Plus className="w-4 h-4 text-blue-500" />
            </Button>
          </div>
        </CardContent>
      </Card>

      {/* Chat History */}
      <div className="flex-1 px-4 overflow-y-auto">
        <div className="flex items-center justify-between mb-3">
          <h3 className="text-xs font-medium text-blue-400 flex items-center">
            <MessageSquare className="w-3 h-3 mr-1" />
            Recent Chats
          </h3>
        </div>

        <div className="space-y-1">
          {recentChats.map((chat) => (
            <Card
              key={chat.id}
              className={`p-2 cursor-pointer transition-all ${
                chat.active
                  ? 'bg-pink-500/20 border-l-2 border-l-pink-500 border-r border-t border-b border-pink-500/20'
                  : 'bg-gray-800 hover:bg-blue-500/10 border border-gray-700'
              }`}
            >
              <div className="flex items-center justify-between">
                <div className="flex-1 min-w-0 mr-2">
                  <span className={`text-sm truncate block ${
                    chat.active ? 'text-pink-400' : 'text-gray-300'
                  }`}>
                    {chat.title}
                  </span>
                </div>
                <Badge 
                  variant="secondary" 
                  className={`${
                    chat.active 
                      ? 'bg-pink-500/30 text-pink-400' 
                      : 'bg-blue-500/20 text-blue-400'
                  }`}
                >
                  {chat.count}
                </Badge>
                <div className="flex items-center space-x-1 ml-2">
                  <Button variant="ghost" size="icon" className="h-6 w-6">
                    <Pin className="w-2.5 h-2.5" />
                  </Button>
                  <Button variant="ghost" size="icon" className="h-6 w-6">
                    <Trash2 className="w-2.5 h-2.5" />
                  </Button>
                </div>
              </div>
            </Card>
          ))}
        </div>
      </div>

      {/* New Chat & Commands */}
      <div className="p-4 border-t border-gray-700 space-y-2">
        <Button className="w-full bg-blue-500/20 hover:bg-blue-500/30 text-blue-400 border border-blue-500/40">
          <Plus className="w-4 h-4 mr-1" />
          New Chat
        </Button>
        <Button className="w-full bg-pink-600 hover:bg-pink-700 text-white">
          <Terminal className="w-4 h-4 mr-1" />
          Commands
        </Button>
      </div>
    </div>
  )
}