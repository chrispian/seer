import React, { useState } from 'react'
import { ScrollArea } from '@/components/ui/scroll-area'
import { Button } from '@/components/ui/button'
import { Settings, LayoutGrid, PanelRightClose, PanelRightOpen } from 'lucide-react'
import { Menubar, MenubarMenu, MenubarTrigger, MenubarSeparator } from '@/components/ui/menubar'
import { useLayoutStore } from '@/stores/useLayoutStore'
import { CustomizableWidget } from '@/components/CustomizableWidget'
import { CustomizationPanel } from '@/components/CustomizationPanel'

export function RightRail() {
  const [showCustomization, setShowCustomization] = useState(false)
  
  const {
    preferences,
    isCustomizing,
    setCustomizing,
    getWidgetsBySection,
    toggleRightRail,
  } = useLayoutStore()

  const mainWidgets = getWidgetsBySection('main')
  const footerWidgets = getWidgetsBySection('footer')
  
  const { rightRailWidth, widgetSpacing, rightRailCollapsed } = preferences.layout

  // Width classes based on user preference
  const widthClasses = {
    narrow: 'w-80',
    normal: 'w-80 xl:w-96',
    wide: 'w-96 xl:w-112',
  }

  const paddingClasses = {
    compact: 'p-2 sm:p-3',
    normal: 'p-3 sm:p-4 xl:p-6',
    comfortable: 'p-4 sm:p-6 xl:p-8',
  }

  return (
    <>
      <aside className={`${rightRailCollapsed ? 'w-12' : widthClasses[rightRailWidth]} h-full bg-background border-l border-border flex flex-col overflow-hidden transition-all duration-200`}>
        {/* Header with Customization Toggle */}
        <header className="flex items-center justify-between p-3 border-b border-border">
          {!rightRailCollapsed && <h2 className="text-sm font-medium text-muted-foreground">Widgets</h2>}
          
          {rightRailCollapsed ? (
            /* Collapsed state - single toggle button */
            <Button
              variant="ghost"
              size="sm"
              onClick={toggleRightRail}
              title="Expand widgets panel"
              className="w-full justify-center"
            >
              <PanelRightOpen className="w-4 h-4" />
            </Button>
          ) : (
            /* Expanded state - menubar with 3 icons */
            <Menubar className="h-7 bg-black border-0 rounded-sm p-0.5 space-x-0">
              <MenubarMenu>
                <MenubarTrigger
                  className="px-2 py-1 !text-white hover:!bg-gray-800 data-[state=open]:!bg-gray-800 active:!bg-gray-700 focus:!bg-gray-700 rounded-sm border-0"
                  onClick={toggleRightRail}
                  title="Collapse widgets panel"
                >
                  <PanelRightClose className="w-3.5 h-3.5" />
                </MenubarTrigger>
              </MenubarMenu>
              
              <MenubarSeparator className="bg-gray-600 w-px h-4 mx-0" />
              
              <MenubarMenu>
                <MenubarTrigger
                  className={`px-2 py-1 !text-white hover:!bg-gray-800 data-[state=open]:!bg-gray-800 active:!bg-gray-700 focus:!bg-gray-700 rounded-sm border-0 ${
                    isCustomizing ? '!bg-gray-700' : ''
                  }`}
                  onClick={() => setCustomizing(!isCustomizing)}
                  title="Customize layout"
                >
                  <Settings className="w-3.5 h-3.5" />
                </MenubarTrigger>
              </MenubarMenu>
              
              <MenubarSeparator className="bg-gray-600 w-px h-4 mx-0" />
              
              <MenubarMenu>
                <MenubarTrigger
                  className="px-2 py-1 !text-white hover:!bg-gray-800 data-[state=open]:!bg-gray-800 active:!bg-gray-700 focus:!bg-gray-700 rounded-sm border-0"
                  onClick={() => setShowCustomization(true)}
                  title="Widget settings"
                >
                  <LayoutGrid className="w-3.5 h-3.5" />
                </MenubarTrigger>
              </MenubarMenu>
            </Menubar>
          )}
        </header>

        {/* Main Widgets Area */}
        {!rightRailCollapsed && (
          <ScrollArea className="flex-1">
            <div className={paddingClasses[widgetSpacing]}>
              {/* Customization Mode Notice */}
              {isCustomizing && (
                <div className="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                  <p className="text-sm text-blue-700">
                    <strong>Customization Mode:</strong> Drag widgets to reorder, use controls to configure.
                  </p>
                  <div className="flex gap-2 mt-2">
                    <Button
                      size="sm"
                      onClick={() => setShowCustomization(true)}
                      className="text-xs"
                    >
                      Open Settings
                    </Button>
                    <Button
                      variant="outline"
                      size="sm"
                      onClick={() => setCustomizing(false)}
                      className="text-xs"
                    >
                      Done
                    </Button>
                  </div>
                </div>
              )}

              {/* Dynamic Widget Rendering */}
              <div className="space-y-1">
                {mainWidgets.length > 0 ? (
                  mainWidgets.map((widget) => (
                    <CustomizableWidget
                      key={widget.id}
                      config={widget}
                    />
                  ))
                ) : (
                  <div className="text-center py-8 text-muted-foreground">
                    <p className="text-sm">No widgets enabled</p>
                    <Button
                      variant="outline"
                      size="sm"
                      onClick={() => setShowCustomization(true)}
                      className="mt-2"
                    >
                      <LayoutGrid className="w-4 h-4 mr-1" />
                      Add Widgets
                    </Button>
                  </div>
                )}
              </div>
            </div>
          </ScrollArea>
        )}

        {/* Footer Widgets */}
        {!rightRailCollapsed && footerWidgets.length > 0 && (
          <footer className={`border-t border-border bg-muted/30 ${paddingClasses[widgetSpacing]}`}>
            <div className="space-y-1">
              {footerWidgets.map((widget) => (
                <CustomizableWidget
                  key={widget.id}
                  config={widget}
                />
              ))}
            </div>
          </footer>
        )}
      </aside>

      {/* Customization Panel */}
      <CustomizationPanel
        isOpen={showCustomization}
        onClose={() => setShowCustomization(false)}
      />
    </>
  )
}
