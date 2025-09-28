import React, { useState, useEffect } from 'react'
import { Card } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu'
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetFooter } from '@/components/ui/sheet'
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group'
import { Plus, Search, Settings, Archive, Folder, MessageCircle } from 'lucide-react'
import { useChatSession } from '@/contexts/ChatSessionContext'

export function Ribbon() {
  const { createNewSession, appContext, loadContext } = useChatSession()
  const [settingsOpen, setSettingsOpen] = useState(false)
  const [vaultModalOpen, setVaultModalOpen] = useState(false)
  const [projectModalOpen, setProjectModalOpen] = useState(false)
  const [toastVerbosity, setToastVerbosity] = useState<string>('normal')
  
  // Form state
  const [vaultForm, setVaultForm] = useState({ name: '', description: '' })
  const [projectForm, setProjectForm] = useState({ name: '', description: '' })
  const [isCreating, setIsCreating] = useState(false)

  // Load toast verbosity from localStorage on mount
  useEffect(() => {
    const savedVerbosity = localStorage.getItem('toastVerbosity')
    if (savedVerbosity && ['minimal', 'normal', 'verbose'].includes(savedVerbosity)) {
      setToastVerbosity(savedVerbosity)
    }
  }, [])

  const handleNewVault = () => {
    setVaultModalOpen(true)
  }

  const handleNewProject = () => {
    setProjectModalOpen(true)
  }

  const handleCreateVault = async () => {
    if (!vaultForm.name.trim()) return

    setIsCreating(true)
    try {
      // TODO: Replace with actual API call when endpoint is created
      const response = await fetch('/api/vaults', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
        },
        body: JSON.stringify({
          name: vaultForm.name,
          description: vaultForm.description,
        }),
      })

      if (response.ok) {
        await loadContext()
        setVaultModalOpen(false)
        setVaultForm({ name: '', description: '' })
        console.log('Vault created successfully')
      } else {
        throw new Error('Failed to create vault')
      }
    } catch (error) {
      console.error('Error creating vault:', error)
      // For now, just show a console message until we have proper error handling
      alert('Vault creation is not yet implemented. This will be available in the next update.')
    } finally {
      setIsCreating(false)
    }
  }

  const handleCreateProject = async () => {
    if (!projectForm.name.trim() || !appContext?.current_vault_id) return

    setIsCreating(true)
    try {
      // TODO: Replace with actual API call when endpoint is created  
      const response = await fetch('/api/projects', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
        },
        body: JSON.stringify({
          name: projectForm.name,
          description: projectForm.description,
          vault_id: appContext.current_vault_id,
        }),
      })

      if (response.ok) {
        await loadContext()
        setProjectModalOpen(false)
        setProjectForm({ name: '', description: '' })
        console.log('Project created successfully')
      } else {
        throw new Error('Failed to create project')
      }
    } catch (error) {
      console.error('Error creating project:', error)
      // For now, just show a console message until we have proper error handling
      alert('Project creation is not yet implemented. This will be available in the next update.')
    } finally {
      setIsCreating(false)
    }
  }

  const handleNewChat = async () => {
    try {
      const newSession = await createNewSession()
      console.log('Created new chat session:', newSession)
    } catch (error) {
      console.error('Failed to create new chat:', error)
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

      {/* Vault Creation Sheet */}
      <Sheet open={vaultModalOpen} onOpenChange={setVaultModalOpen}>
        <SheetContent side="left" className="w-80 bg-gray-900 border-gray-700">
          <SheetHeader>
            <SheetTitle className="text-pink-400 flex items-center">
              <Archive className="w-5 h-5 mr-2" />
              Create New Vault
            </SheetTitle>
          </SheetHeader>
          
          <div className="mt-6 space-y-4">
            <div>
              <Label htmlFor="vault-name" className="text-sm font-medium text-gray-200">
                Vault Name *
              </Label>
              <Input
                id="vault-name"
                value={vaultForm.name}
                onChange={(e) => setVaultForm(prev => ({ ...prev, name: e.target.value }))}
                placeholder="Enter vault name..."
                className="mt-1 bg-gray-800 border-pink-400/30 text-gray-200 rounded-xs"
                disabled={isCreating}
              />
            </div>
            
            <div>
              <Label htmlFor="vault-description" className="text-sm font-medium text-gray-200">
                Description
              </Label>
              <Textarea
                id="vault-description"
                value={vaultForm.description}
                onChange={(e) => setVaultForm(prev => ({ ...prev, description: e.target.value }))}
                placeholder="Brief description (optional)..."
                className="mt-1 bg-gray-800 border-pink-400/30 text-gray-200 rounded-xs resize-none"
                rows={3}
                disabled={isCreating}
              />
            </div>
          </div>

          <SheetFooter className="mt-6">
            <div className="flex space-x-3 w-full">
              <Button
                onClick={handleCreateVault}
                disabled={!vaultForm.name.trim() || isCreating}
                className="flex-1 bg-pink-500 hover:bg-pink-600 text-white rounded-xs"
              >
                {isCreating ? 'Creating...' : 'Create Vault'}
              </Button>
              <Button
                variant="outline"
                onClick={() => {
                  setVaultModalOpen(false)
                  setVaultForm({ name: '', description: '' })
                }}
                disabled={isCreating}
                className="flex-1 border-gray-600 text-gray-400 hover:bg-gray-800 rounded-xs"
              >
                Cancel
              </Button>
            </div>
          </SheetFooter>
        </SheetContent>
      </Sheet>

      {/* Project Creation Sheet */}
      <Sheet open={projectModalOpen} onOpenChange={setProjectModalOpen}>
        <SheetContent side="left" className="w-80 bg-gray-900 border-gray-700">
          <SheetHeader>
            <SheetTitle className="text-blue-400 flex items-center">
              <Folder className="w-5 h-5 mr-2" />
              Create New Project
            </SheetTitle>
          </SheetHeader>
          
          <div className="mt-6 space-y-4">
            <div>
              <Label htmlFor="project-name" className="text-sm font-medium text-gray-200">
                Project Name *
              </Label>
              <Input
                id="project-name"
                value={projectForm.name}
                onChange={(e) => setProjectForm(prev => ({ ...prev, name: e.target.value }))}
                placeholder="Enter project name..."
                className="mt-1 bg-gray-800 border-blue-400/30 text-gray-200 rounded-xs"
                disabled={isCreating}
              />
            </div>
            
            <div>
              <Label htmlFor="project-description" className="text-sm font-medium text-gray-200">
                Description
              </Label>
              <Textarea
                id="project-description"
                value={projectForm.description}
                onChange={(e) => setProjectForm(prev => ({ ...prev, description: e.target.value }))}
                placeholder="Brief description (optional)..."
                className="mt-1 bg-gray-800 border-blue-400/30 text-gray-200 rounded-xs resize-none"
                rows={3}
                disabled={isCreating}
              />
            </div>

            <div className="text-xs text-gray-400">
              Will be created in: <span className="text-blue-400">{appContext?.vaults.find(v => v.id === appContext.current_vault_id)?.name || 'Default Vault'}</span>
            </div>
          </div>

          <SheetFooter className="mt-6">
            <div className="flex space-x-3 w-full">
              <Button
                onClick={handleCreateProject}
                disabled={!projectForm.name.trim() || isCreating || !appContext?.current_vault_id}
                className="flex-1 bg-blue-500 hover:bg-blue-600 text-white rounded-xs"
              >
                {isCreating ? 'Creating...' : 'Create Project'}
              </Button>
              <Button
                variant="outline"
                onClick={() => {
                  setProjectModalOpen(false)
                  setProjectForm({ name: '', description: '' })
                }}
                disabled={isCreating}
                className="flex-1 border-gray-600 text-gray-400 hover:bg-gray-800 rounded-xs"
              >
                Cancel
              </Button>
            </div>
          </SheetFooter>
        </SheetContent>
      </Sheet>
    </>
  )
}
