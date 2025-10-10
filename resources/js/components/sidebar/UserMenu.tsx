import React from 'react'
import { ChevronUp, User, Terminal } from 'lucide-react'
import { Button } from '@/components/ui/button'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger
} from '@/components/ui/dropdown-menu'
import { UserAvatar } from '@/components/UserAvatar'

interface UserMenuProps {
  userDisplayName: string
  onSettingsClick: () => void
  onCommandsClick: () => void
}

export function UserMenu({
  userDisplayName,
  onSettingsClick,
  onCommandsClick
}: UserMenuProps) {
  return (
    <div className="p-2 md:p-3 border-t">
      <DropdownMenu>
        <DropdownMenuTrigger asChild>
          <Button
            variant="ghost"
            className="w-full justify-start h-12 px-3 data-[state=open]:bg-gray-100"
          >
            <UserAvatar className="rounded-lg mr-3" size="md" />
            <div className="grid flex-1 text-left text-sm leading-tight">
              <span className="truncate font-semibold">{userDisplayName}</span>
              <span className="truncate text-xs text-gray-500">Local User</span>
            </div>
            <ChevronUp className="ml-auto size-4" />
          </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent
          className="w-[--radix-dropdown-menu-trigger-width] min-w-56 rounded-lg"
          side="top"
          align="start"
          sideOffset={4}
        >
          <DropdownMenuItem onClick={onSettingsClick}>
            <User className="w-4 h-4 mr-2" />
            Settings
          </DropdownMenuItem>
          <DropdownMenuItem onClick={onCommandsClick}>
            <Terminal className="w-4 h-4 mr-2" />
            Commands
          </DropdownMenuItem>
        </DropdownMenuContent>
      </DropdownMenu>
    </div>
  )
}
