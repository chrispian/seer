import React from 'react'
import { Card } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu'
import { Plus, Search, Settings } from 'lucide-react'

export function Ribbon() {
  return (
    <div className="w-16 bg-gray-900 border-r border-gray-700 flex flex-col items-center py-4 space-y-4">
      {/* Fe Periodic Element */}
      <div className="relative">
        <Card className="w-10 h-10 bg-pink-600 rounded-md flex items-center justify-center relative z-10 border border-pink-500">
          <span className="text-white font-bold text-xl font-mono leading-none">Fe</span>
        </Card>
        <div className="absolute -top-0.5 -left-0.5 w-11 h-11 border border-blue-500 rounded-md"></div>
      </div>

      {/* Action Buttons */}
      <div className="space-y-3">
        {/* Create Flyout Menu */}
        <DropdownMenu>
          <DropdownMenuTrigger asChild>
            <Button
              variant="outline"
              size="icon"
              className="w-8 h-8 bg-gray-800 border-pink-500/30 hover:bg-pink-500/10"
            >
              <Plus className="w-4 h-4 text-pink-500" />
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent side="right" className="bg-gray-900 border-pink-500/20">
            <DropdownMenuItem className="text-gray-300 hover:bg-pink-500/20 hover:text-pink-500">
              <span className="flex items-center space-x-2">
                <span>📦</span>
                <span>New Vault</span>
              </span>
            </DropdownMenuItem>
            <DropdownMenuItem className="text-gray-300 hover:bg-blue-500/20 hover:text-blue-500">
              <span className="flex items-center space-x-2">
                <span>📁</span>
                <span>New Project</span>
              </span>
            </DropdownMenuItem>
            <DropdownMenuItem className="text-gray-300 hover:bg-cyan-500/20 hover:text-cyan-500">
              <span className="flex items-center space-x-2">
                <span>💬</span>
                <span>New Chat</span>
              </span>
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>

        {/* Search Button */}
        <Button
          variant="outline"
          size="icon"
          className="w-8 h-8 bg-gray-800 border-blue-500/30 hover:bg-blue-500/10"
        >
          <Search className="w-4 h-4 text-blue-500" />
        </Button>

        {/* Toast Settings Button */}
        <Button
          variant="outline"
          size="icon"
          className="w-8 h-8 bg-gray-800 border-amber-500/30 hover:bg-amber-500/10"
        >
          <Settings className="w-4 h-4 text-amber-400" />
        </Button>
      </div>
    </div>
  )
}