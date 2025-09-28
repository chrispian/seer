import React, { useState } from 'react'
import { Card } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu'
import { Sheet, SheetContent, SheetHeader, SheetTitle } from '@/components/ui/sheet'
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group'
import { Plus, Search, Settings, Archive, Folder, MessageCircle } from 'lucide-react'

export function Ribbon() {
  const [settingsOpen, setSettingsOpen] = useState(false)
  const [toastVerbosity, setToastVerbosity] = useState<string>('normal')

  const handleNewVault = () => {
    // TODO: Implement new vault creation
    console.log('Creating new vault...')
  }

  const handleNewProject = () => {
    // TODO: Implement new project creation
    console.log('Creating new project...')
  }

  const handleNewChat = () => {
    // TODO: Implement new chat creation
    console.log('Creating new chat...')
  }

  const handleSearch = () => {
    // TODO: Implement search functionality
    console.log('Opening search...')
  }

  const handleToastVerbosityChange = (value: string) => {
    setToastVerbosity(value)
    // TODO: Persist this setting to backend/localStorage
    console.log('Toast verbosity changed to:', value)
  }

  return (
    <>
      <div className="w-16 bg-gray-900 border-r border-gray-700 flex flex-col items-center py-4">
        {/* App Logo/Icon - Centered */}
        <div className="flex justify-center mb-8">
          <Card className="w-10 h-10 bg-pink-500 border-blue-400 border-2 rounded-xs shadow-none flex items-center justify-center">
            <span className="text-white font-bold text-xl font-mono leading-none">Fe</span>
          </Card>
        </div>

        {/* Action Buttons - Centered in column */}
        <div className="flex flex-col items-center space-y-3">
          {/* Create Flyout Menu */}
          <DropdownMenu>
            <DropdownMenuTrigger asChild>
              <Button 
                variant="outline" 
                size="icon" 
                className="w-8 h-8 rounded-xs shadow-none border-pink-400/30 bg-gray-800 hover:bg-pink-500/10 text-pink-400 hover:text-pink-300"
              >
                <Plus className="w-4 h-4" />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent side="right" className="rounded-xs bg-gray-800 border-pink-400/30">
              <DropdownMenuItem 
                onClick={handleNewVault}
                className="text-gray-300 hover:bg-pink-500/20 hover:text-pink-400 rounded-xs"
              >
                <div className="flex items-center space-x-2">
                  <Archive className="w-4 h-4 text-pink-400" />
                  <span>New Vault</span>
                </div>
              </DropdownMenuItem>
              <DropdownMenuItem 
                onClick={handleNewProject}
                className="text-gray-300 hover:bg-blue-400/20 hover:text-blue-400 rounded-xs"
              >
                <div className="flex items-center space-x-2">
                  <Folder className="w-4 h-4 text-blue-400" />
                  <span>New Project</span>
                </div>
              </DropdownMenuItem>
              <DropdownMenuItem 
                onClick={handleNewChat}
                className="text-gray-300 hover:bg-cyan-400/20 hover:text-cyan-400 rounded-xs"
              >
                <div className="flex items-center space-x-2">
                  <MessageCircle className="w-4 h-4 text-cyan-400" />
                  <span>New Chat</span>
                </div>
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>

          {/* Search Button */}
          <Button 
            variant="outline" 
            size="icon" 
            onClick={handleSearch}
            className="w-8 h-8 rounded-xs shadow-none border-blue-400/30 bg-gray-800 hover:bg-blue-400/10 text-blue-400 hover:text-blue-300"
          >
            <Search className="w-4 h-4" />
          </Button>

          {/* Settings Button */}
          <Button 
            variant="outline" 
            size="icon" 
            onClick={() => setSettingsOpen(true)}
            className="w-8 h-8 rounded-xs shadow-none border-amber-500/30 bg-gray-800 hover:bg-amber-500/10 text-amber-400 hover:text-amber-300"
          >
            <Settings className="w-4 h-4" />
          </Button>
        </div>
      </div>

      {/* Settings Sheet */}
      <Sheet open={settingsOpen} onOpenChange={setSettingsOpen}>
        <SheetContent side="left" className="w-80 bg-gray-900 border-gray-700">
          <SheetHeader>
            <SheetTitle className="text-amber-400 flex items-center">
              <Settings className="w-5 h-5 mr-2" />
              Settings
            </SheetTitle>
          </SheetHeader>
          
          <div className="mt-6 space-y-6">
            {/* Toast Notifications Section */}
            <div>
              <h3 className="text-sm font-medium text-gray-200 mb-3">Toast Notifications</h3>
              <p className="text-xs text-gray-400 mb-4">Choose how many notifications you want to see</p>
              
              <ToggleGroup 
                type="single" 
                value={toastVerbosity} 
                onValueChange={handleToastVerbosityChange}
                className="flex flex-col items-stretch space-y-2"
              >
                <ToggleGroupItem 
                  value="minimal" 
                  className="justify-start text-left p-3 rounded-xs border border-amber-500/30 data-[state=on]:bg-amber-500/20 data-[state=on]:text-amber-400"
                >
                  <div>
                    <div className="font-medium">Minimal</div>
                    <div className="text-xs text-gray-500">Only errors and warnings</div>
                  </div>
                </ToggleGroupItem>
                
                <ToggleGroupItem 
                  value="normal" 
                  className="justify-start text-left p-3 rounded-xs border border-amber-500/30 data-[state=on]:bg-amber-500/20 data-[state=on]:text-amber-400"
                >
                  <div>
                    <div className="font-medium">Normal</div>
                    <div className="text-xs text-gray-500">All notifications (recommended)</div>
                  </div>
                </ToggleGroupItem>
                
                <ToggleGroupItem 
                  value="verbose" 
                  className="justify-start text-left p-3 rounded-xs border border-amber-500/30 data-[state=on]:bg-amber-500/20 data-[state=on]:text-amber-400"
                >
                  <div>
                    <div className="font-medium">Verbose</div>
                    <div className="text-xs text-gray-500">All notifications with extra details</div>
                  </div>
                </ToggleGroupItem>
              </ToggleGroup>
            </div>
          </div>
        </SheetContent>
      </Sheet>
    </>
  )
}
