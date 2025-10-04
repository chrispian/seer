import React from 'react'
import { CompactModelPicker } from './CompactModelPicker'
import { Menubar, MenubarMenu, MenubarTrigger, MenubarSeparator } from '@/components/ui/menubar'
import { Bot, Settings } from 'lucide-react'

interface ChatToolbarProps {
  selectedModel?: string
  onModelChange?: (model: string) => void
  disabled?: boolean
}

export function ChatToolbar({
  selectedModel = '',
  onModelChange,
  disabled = false,
}: ChatToolbarProps) {
  return (
    <div className="flex items-center justify-between px-3 py-1.5 bg-gray-900/95 border-t border-dashed border-gray-600/60 rounded-b-sm">
      <div className="flex items-center gap-3">
        {/* Model Picker */}
        <CompactModelPicker
          value={selectedModel}
          onValueChange={onModelChange}
          disabled={disabled}
        />
        
        {/* Agent and Mode Selectors */}
        <div className="flex items-center gap-2">
          {/* Agent Selector */}
          <Menubar className="h-6 bg-black border-0 rounded-sm p-0.5">
            <MenubarMenu>
              <MenubarTrigger
                className="px-2 py-1 text-white hover:bg-gray-800 data-[state=open]:bg-gray-800 rounded-sm border-0 text-xs"
                disabled={disabled}
                title="Agent Selector"
              >
                <Bot className="w-3 h-3 mr-1" />
                Agent
              </MenubarTrigger>
            </MenubarMenu>
          </Menubar>
          
          {/* Mode Selector */}
          <Menubar className="h-6 bg-black border-0 rounded-sm p-0.5">
            <MenubarMenu>
              <MenubarTrigger
                className="px-2 py-1 text-white hover:bg-gray-800 data-[state=open]:bg-gray-800 rounded-sm border-0 text-xs"
                disabled={disabled}
                title="Mode Selector"
              >
                <Settings className="w-3 h-3 mr-1" />
                Chat
              </MenubarTrigger>
            </MenubarMenu>
          </Menubar>
        </div>
      </div>
      
      {/* Future: Add right-aligned controls here */}
      <div className="flex items-center gap-2">
        {/* Placeholder for future features */}
      </div>
    </div>
  )
}