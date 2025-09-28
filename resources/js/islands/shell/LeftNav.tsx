import React from 'react'
import { Card, CardContent, CardHeader } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Plus, MessageSquare, Terminal, Pin, Trash2 } from 'lucide-react'

export function LeftNav() {
  const recentChats = [
    { id: 4, title: "#c4 Let's do a new...", count: 5, active: true },
    { id: 5, title: "#c5 New Chat", count: 0, active: false },
    { id: 2, title: "#c2 Test", count: 5, active: false },
    { id: 3, title: "#c3 Test", count: 5, active: false },
  ]

  return (
    <div className="w-72 bg-white border-r flex flex-col">
      {/* Vault Selection */}
      <Card className="m-4">
        <CardHeader className="pb-2">
          <h3 className="text-xs font-medium text-muted-foreground">Vault</h3>
        </CardHeader>
        <CardContent className="pt-0">
          <div className="flex space-x-1">
            <select className="flex-1 text-sm rounded-l p-2 border border-input bg-background">
              <option>work</option>
              <option>personal</option>
              <option>clients</option>
            </select>
            <Button variant="outline" size="icon" className="px-3">
              <Plus className="w-4 h-4" />
            </Button>
          </div>
        </CardContent>
      </Card>

      {/* Project Selection */}
      <Card className="mx-4 mb-4">
        <CardHeader className="pb-2">
          <h3 className="text-xs font-medium text-muted-foreground">Project</h3>
        </CardHeader>
        <CardContent className="pt-0">
          <div className="flex space-x-1">
            <select className="flex-1 text-sm rounded-l p-2 border border-input bg-background">
              <option>Engineering</option>
            </select>
            <Button variant="outline" size="icon" className="px-3">
              <Plus className="w-4 h-4" />
            </Button>
          </div>
        </CardContent>
      </Card>

      {/* Chat History */}
      <div className="flex-1 px-4 overflow-y-auto">
        <div className="flex items-center justify-between mb-3">
          <h3 className="text-xs font-medium text-muted-foreground flex items-center">
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
                  ? 'bg-accent border-l-2 border-l-primary'
                  : 'hover:bg-accent/50'
              }`}
            >
              <div className="flex items-center justify-between">
                <div className="flex-1 min-w-0 mr-2">
                  <span className="text-sm truncate block">
                    {chat.title}
                  </span>
                </div>
                <Badge variant="secondary">
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
      <div className="p-4 border-t space-y-2">
        <Button variant="outline" className="w-full">
          <Plus className="w-4 h-4 mr-1" />
          New Chat
        </Button>
        <Button className="w-full">
          <Terminal className="w-4 h-4 mr-1" />
          Commands
        </Button>
      </div>
    </div>
  )
}