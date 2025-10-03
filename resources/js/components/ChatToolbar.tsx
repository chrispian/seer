import React from 'react'
import { CompactModelPicker } from './CompactModelPicker'

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
        
        {/* Future: Add more compact controls here like custom actions, settings, etc. */}
      </div>
      
      {/* Future: Add right-aligned controls here */}
      <div className="flex items-center gap-2">
        {/* Placeholder for future features */}
      </div>
    </div>
  )
}