import React from 'react'
import { PanelLeftOpen, PanelLeftClose } from 'lucide-react'
import { BlackButton } from '@/components/ui/black-button'

interface SidebarHeaderProps {
  isCollapsed: boolean
  onToggle: () => void
}

export function SidebarHeader({ isCollapsed, onToggle }: SidebarHeaderProps) {
  return (
    <div className="p-1 md:p-2 border-b flex justify-end">
      <BlackButton
        size="icon-sm"
        onClick={onToggle}
        className="h-5 w-5 md:h-6 md:w-6"
      >
        {isCollapsed ? (
          <PanelLeftOpen className="h-3 w-3 md:h-4 md:w-4" />
        ) : (
          <PanelLeftClose className="h-3 w-3 md:h-4 md:w-4" />
        )}
      </BlackButton>
    </div>
  )
}
