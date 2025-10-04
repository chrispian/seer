import React, { useState, useEffect } from 'react'
import { Card } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu'
import { Sheet, SheetContent, SheetHeader, SheetTitle } from '@/components/ui/sheet'
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group'
import { Plus, Search, Settings, Archive, Folder, MessageCircle, Loader2 } from 'lucide-react'
import { useChatSession } from '@/contexts/ChatSessionContext'
import { useCreateChatSession } from '@/hooks/useChatSessions'
import { VaultCreateDialog } from '@/components/VaultCreateDialog'
import { ProjectCreateDialog } from '@/components/ProjectCreateDialog'
import { ChatCreateDialog } from '@/components/ChatCreateDialog'

export function Ribbon() {
  const { createNewSession } = useChatSession()
  const createChatMutation = useCreateChatSession()
  const [settingsOpen, setSettingsOpen] = useState(false)
  const [vaultDialogOpen, setVaultDialogOpen] = useState(false)
  const [projectDialogOpen, setProjectDialogOpen] = useState(false)
  const [chatDialogOpen, setChatDialogOpen] = useState(false)
  const [isCreatingChat, setIsCreatingChat] = useState(false)
  const [toastVerbosity, setToastVerbosity] = useState<string>('normal')

  // Load toast verbosity from localStorage on mount
  useEffect(() => {
    const savedVerbosity = localStorage.getItem('toastVerbosity')
    if (savedVerbosity && ['minimal', 'normal', 'verbose'].includes(savedVerbosity)) {
      setToastVerbosity(savedVerbosity)
    }
  }, [])

  const handleNewVault = () => {
    setVaultDialogOpen(true)
  }

  const handleNewProject = () => {
    setProjectDialogOpen(true)
  }

  const handleNewChat = async () => {
    if (isCreatingChat) return
    console.log('Creating new chat from ribbon...')
    setIsCreatingChat(true)
    
    try {
      const result = await createNewSession()
      console.log('Chat created successfully from ribbon:', result)
    } catch (error) {
      console.error('Failed to create new chat from ribbon:', error)
    } finally {
      setIsCreatingChat(false)
    }
  }

  const handleSearch = () => {
    // Trigger global search by dispatching Ctrl+K event
    const event = new KeyboardEvent('keydown', {
      ctrlKey: true,
      key: 'k',
      code: 'KeyK',
      bubbles: true,
      cancelable: true
    })
    document.dispatchEvent(event)
    console.log('Triggered global search/recall palette')
  }

  const handleToastVerbosityChange = (value: string) => {
    setToastVerbosity(value)
    // Persist to localStorage
    localStorage.setItem('toastVerbosity', value)
    console.log('Toast verbosity changed to:', value)
    // TODO: Also persist to backend user preferences
  }

  return (
    <>
      <div className="w-16 h-full bg-gray-900 border-r border-gray-700 flex flex-col items-center py-4">
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
                className="w-8 h-8 rounded-xs shadow-none border-pink-400/30 !bg-gray-800 hover:!bg-pink-500/10 !text-pink-400 hover:!text-pink-300 data-[state=open]:!bg-pink-500/20 data-[state=open]:!text-pink-300 active:!bg-pink-500/20 active:!text-pink-300 focus:!bg-pink-500/20 focus:!text-pink-300 [&[data-state=open]]:!bg-pink-500/20 [&[data-state=open]]:!text-pink-300"
              >
                <Plus className="w-4 h-4" />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent side="right" className="rounded-xs bg-gray-800 border-pink-400/30 text-gray-300">
              <DropdownMenuItem 
                onClick={handleNewVault}
                className="text-gray-300 hover:bg-pink-500/20 hover:text-pink-300 focus:bg-pink-500/20 focus:text-pink-300 rounded-xs"
              >
                <div className="flex items-center space-x-2">
                  <Archive className="w-4 h-4 text-pink-400" />
                  <span>New Vault</span>
                </div>
              </DropdownMenuItem>
              <DropdownMenuItem 
                onClick={handleNewProject}
                className="text-gray-300 hover:bg-blue-400/20 hover:text-blue-300 focus:bg-blue-400/20 focus:text-blue-300 rounded-xs"
              >
                <div className="flex items-center space-x-2">
                  <Folder className="w-4 h-4 text-blue-400" />
                  <span>New Project</span>
                </div>
              </DropdownMenuItem>
              <DropdownMenuItem 
                onClick={handleNewChat}
                className="text-gray-300 hover:bg-cyan-400/20 hover:text-cyan-300 focus:bg-cyan-400/20 focus:text-cyan-300 rounded-xs"
                disabled={isCreatingChat}
              >
                <div className="flex items-center space-x-2">
                  {isCreatingChat ? (
                    <Loader2 className="w-4 h-4 text-cyan-400 animate-spin" />
                  ) : (
                    <MessageCircle className="w-4 h-4 text-cyan-400" />
                  )}
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
            className="w-8 h-8 rounded-xs shadow-none border-blue-400/30 !bg-gray-800 hover:!bg-blue-400/10 !text-blue-400 hover:!text-blue-300 active:!bg-blue-400/20 active:!text-blue-300 focus:!bg-blue-400/20 focus:!text-blue-300"
          >
            <Search className="w-4 h-4" />
          </Button>

          {/* Settings Button */}
          <Button 
            variant="outline" 
            size="icon" 
            onClick={() => window.location.href = '/settings'}
            className="w-8 h-8 rounded-xs shadow-none border-amber-500/30 !bg-gray-800 hover:!bg-amber-500/10 !text-amber-400 hover:!text-amber-300 active:!bg-amber-500/20 active:!text-amber-300 focus:!bg-amber-500/20 focus:!text-amber-300"
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

      {/* Creation Dialogs */}
      <VaultCreateDialog 
        open={vaultDialogOpen} 
        onOpenChange={setVaultDialogOpen} 
      />
      <ProjectCreateDialog 
        open={projectDialogOpen} 
        onOpenChange={setProjectDialogOpen} 
      />
      <ChatCreateDialog 
        open={chatDialogOpen} 
        onOpenChange={setChatDialogOpen} 
      />
    </>
  )
}
