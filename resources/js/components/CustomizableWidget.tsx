import React, { useState } from 'react'
import { Card } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { 
  MoreVertical, 
  Minimize2, 
  Maximize2, 
  Eye, 
  EyeOff, 
  GripVertical,
  X 
} from 'lucide-react'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { useLayoutStore } from '@/stores/useLayoutStore'
import { getWidgetComponent, getWidgetMetadata } from '@/lib/widgetRegistry'
import type { WidgetConfig } from '@/stores/useLayoutStore'

interface CustomizableWidgetProps {
  config: WidgetConfig
  className?: string
}

export function CustomizableWidget({ config, className = '' }: CustomizableWidgetProps) {
  const { 
    isCustomizing, 
    toggleWidgetMinimized, 
    toggleWidget,
    draggedWidget,
    setDraggedWidget 
  } = useLayoutStore()
  
  const [isDragOver, setIsDragOver] = useState(false)
  
  const metadata = getWidgetMetadata(config.id)
  const WidgetComponent = getWidgetComponent(config.component as any)
  
  if (!metadata || !WidgetComponent) {
    console.warn(`Widget not found: ${config.id}`)
    return null
  }

  const handleDragStart = (e: React.DragEvent) => {
    if (!isCustomizing) return
    e.dataTransfer.setData('text/plain', config.id)
    setDraggedWidget(config.id)
  }

  const handleDragEnd = () => {
    setDraggedWidget(null)
    setIsDragOver(false)
  }

  const handleDragOver = (e: React.DragEvent) => {
    if (!isCustomizing) return
    e.preventDefault()
    setIsDragOver(true)
  }

  const handleDragLeave = () => {
    setIsDragOver(false)
  }

  const handleDrop = (e: React.DragEvent) => {
    if (!isCustomizing) return
    e.preventDefault()
    setIsDragOver(false)
    
    const draggedId = e.dataTransfer.getData('text/plain')
    if (draggedId && draggedId !== config.id) {
      // Handle reordering logic - would need to be implemented in store
      console.log(`Reorder: ${draggedId} -> ${config.id}`)
    }
  }

  const spacingClasses = {
    compact: 'mb-2',
    normal: 'mb-3 sm:mb-4',
    comfortable: 'mb-4 sm:mb-6',
  }

  const { widgetSpacing } = useLayoutStore(state => state.preferences.layout)

  return (
    <div
      className={`widget-container ${spacingClasses[widgetSpacing]} ${className}`}
      draggable={isCustomizing}
      onDragStart={handleDragStart}
      onDragEnd={handleDragEnd}
      onDragOver={handleDragOver}
      onDragLeave={handleDragLeave}
      onDrop={handleDrop}
    >
      <Card className={`
        relative transition-all duration-200
        ${isCustomizing ? 'ring-2 ring-blue-200 hover:ring-blue-300' : ''}
        ${isDragOver ? 'ring-blue-400 ring-4' : ''}
        ${draggedWidget === config.id ? 'opacity-50' : ''}
        ${config.minimized ? 'opacity-75' : ''}
      `}>
        {/* Customization Header */}
        {isCustomizing && (
          <div className="absolute -top-2 -right-2 z-10 flex gap-1">
            <Button
              variant="outline"
              size="sm"
              className="h-6 w-6 p-0 bg-white border-blue-300 hover:bg-blue-50"
              onClick={() => toggleWidgetMinimized(config.id)}
              title={config.minimized ? 'Maximize widget' : 'Minimize widget'}
            >
              {config.minimized ? <Maximize2 className="h-3 w-3" /> : <Minimize2 className="h-3 w-3" />}
            </Button>
            
            <DropdownMenu>
              <DropdownMenuTrigger asChild>
                <Button
                  variant="outline"
                  size="sm"
                  className="h-6 w-6 p-0 bg-white border-blue-300 hover:bg-blue-50"
                >
                  <MoreVertical className="h-3 w-3" />
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="end" className="w-48">
                <DropdownMenuItem onClick={() => toggleWidgetMinimized(config.id)}>
                  {config.minimized ? (
                    <>
                      <Maximize2 className="mr-2 h-4 w-4" />
                      Maximize
                    </>
                  ) : (
                    <>
                      <Minimize2 className="mr-2 h-4 w-4" />
                      Minimize
                    </>
                  )}
                </DropdownMenuItem>
                <DropdownMenuSeparator />
                <DropdownMenuItem onClick={() => toggleWidget(config.id)}>
                  <EyeOff className="mr-2 h-4 w-4" />
                  Hide Widget
                </DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>
          </div>
        )}

        {/* Drag Handle */}
        {isCustomizing && (
          <div className="absolute top-2 left-2 z-10 cursor-grab active:cursor-grabbing opacity-50 hover:opacity-100">
            <GripVertical className="h-4 w-4 text-gray-400" />
          </div>
        )}

        {/* Widget Content */}
        <div className={`
          ${isCustomizing ? 'pointer-events-none' : ''}
          ${config.minimized ? 'hidden' : ''}
        `}>
          <WidgetComponent />
        </div>

        {/* Minimized State */}
        {config.minimized && (
          <div className="p-3 text-center">
            <p className="text-sm text-muted-foreground">{metadata.name}</p>
            {isCustomizing && (
              <Button
                variant="ghost"
                size="sm"
                onClick={() => toggleWidgetMinimized(config.id)}
                className="mt-1"
              >
                <Maximize2 className="h-3 w-3 mr-1" />
                Expand
              </Button>
            )}
          </div>
        )}
      </Card>
    </div>
  )
}