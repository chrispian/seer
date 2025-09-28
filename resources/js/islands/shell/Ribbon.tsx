import React from 'react'
import { Card } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu'
import { Plus, Search, Settings } from 'lucide-react'

export function Ribbon() {
  return (
    <div className="w-16 bg-white border-r flex flex-col items-center py-4 space-y-4">
      {/* App Logo/Icon */}
      <Card className="w-10 h-10 flex items-center justify-center">
        <span className="text-black font-bold text-xl font-mono leading-none">Fe</span>
      </Card>

      {/* Action Buttons */}
      <div className="space-y-3">
        {/* Create Flyout Menu */}
        <DropdownMenu>
          <DropdownMenuTrigger asChild>
            <Button variant="outline" size="icon" className="w-8 h-8">
              <Plus className="w-4 h-4" />
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent side="right">
            <DropdownMenuItem>
              <span className="flex items-center space-x-2">
                <span>📦</span>
                <span>New Vault</span>
              </span>
            </DropdownMenuItem>
            <DropdownMenuItem>
              <span className="flex items-center space-x-2">
                <span>📁</span>
                <span>New Project</span>
              </span>
            </DropdownMenuItem>
            <DropdownMenuItem>
              <span className="flex items-center space-x-2">
                <span>💬</span>
                <span>New Chat</span>
              </span>
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>

        {/* Search Button */}
        <Button variant="outline" size="icon" className="w-8 h-8">
          <Search className="w-4 h-4" />
        </Button>

        {/* Settings Button */}
        <Button variant="outline" size="icon" className="w-8 h-8">
          <Settings className="w-4 h-4" />
        </Button>
      </div>
    </div>
  )
}
