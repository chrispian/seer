import React, { useState } from 'react'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Switch } from '@/components/ui/switch'
import { Label } from '@/components/ui/label'
import { Separator } from '@/components/ui/separator'
import { ScrollArea } from '@/components/ui/scroll-area'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog'
import {
  Sheet,
  SheetContent,
  SheetDescription,
  SheetHeader,
  SheetTitle,
} from '@/components/ui/sheet'
import {
  Settings,
  Download,
  Upload,
  RotateCcw,
  Layout,
  Palette,
  Space,
  Monitor,
  Eye,
  EyeOff,
  GripVertical,
  X,
  Plus,
} from 'lucide-react'
import { useLayoutStore } from '@/stores/useLayoutStore'
import { getWidgetsByCategory, widgetMetadata, getAllWidgetIds } from '@/lib/widgetRegistry'

interface CustomizationPanelProps {
  isOpen: boolean
  onClose: () => void
}

export function CustomizationPanel({ isOpen, onClose }: CustomizationPanelProps) {
  const {
    preferences,
    toggleWidget,
    resetWidgetLayout,
    setRightRailWidth,
    setWidgetSpacing,
    setTheme,
    exportPreferences,
    importPreferences,
    getWidgetsBySection,
    getEnabledWidgets,
  } = useLayoutStore()

  const [importText, setImportText] = useState('')
  const [importMethod, setImportMethod] = useState<'text' | 'file'>('file')

  const handleExport = () => {
    const exported = exportPreferences()
    
    // Create and download file (consistent with user settings)
    const blob = new Blob([exported], { type: 'application/json' })
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `fragments-layout-${new Date().toISOString().split('T')[0]}.json`
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
    URL.revokeObjectURL(url)
    
    // Also copy to clipboard as backup
    navigator.clipboard.writeText(exported)
  }

  const handleImport = () => {
    if (importText.trim()) {
      importPreferences(importText)
      setImportText('')
    }
  }

  const handleFileImport = async (event: React.ChangeEvent<HTMLInputElement>) => {
    const file = event.target.files?.[0]
    if (file) {
      try {
        const content = await file.text()
        importPreferences(content)
        // Reset the file input
        event.target.value = ''
      } catch (error) {
        console.error('Failed to import layout file:', error)
      }
    }
  }

  const enabledWidgets = getEnabledWidgets()
  const mainWidgets = getWidgetsBySection('main')
  const footerWidgets = getWidgetsBySection('footer')

  const categoryGroups = {
    activity: getWidgetsByCategory('activity'),
    content: getWidgetsByCategory('content'),
    system: getWidgetsByCategory('system'),
    productivity: getWidgetsByCategory('productivity'),
  }

  return (
    <Sheet open={isOpen} onOpenChange={onClose}>
      <SheetContent side="right" className="w-[480px] sm:w-[600px] p-0 flex flex-col">
        <SheetHeader className="p-6 pb-3">
          <div className="flex items-center gap-2">
            <Settings className="w-5 h-5" />
            <SheetTitle>Layout Customization</SheetTitle>
          </div>
          <SheetDescription>
            Customize your workspace layout, widgets, and preferences.
          </SheetDescription>
        </SheetHeader>
        
        <div className="flex-1 overflow-hidden px-6">
          <ScrollArea className="h-full pr-4">
            <div className="space-y-6">
              
              {/* Layout Settings */}
              <section>
                <h3 className="text-lg font-semibold mb-3 flex items-center gap-2">
                  <Layout className="w-4 h-4" />
                  Layout Settings
                </h3>
                
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <div className="space-y-2">
                    <Label htmlFor="rail-width">Right Rail Width</Label>
                    <Select
                      value={preferences.layout.rightRailWidth}
                      onValueChange={(value: 'narrow' | 'normal' | 'wide') => setRightRailWidth(value)}
                    >
                      <SelectTrigger>
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="narrow">Narrow (320px)</SelectItem>
                        <SelectItem value="normal">Normal (384px)</SelectItem>
                        <SelectItem value="wide">Wide (448px)</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                  
                  <div className="space-y-2">
                    <Label htmlFor="widget-spacing">Widget Spacing</Label>
                    <Select
                      value={preferences.layout.widgetSpacing}
                      onValueChange={(value: 'compact' | 'normal' | 'comfortable') => setWidgetSpacing(value)}
                    >
                      <SelectTrigger>
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="compact">Compact</SelectItem>
                        <SelectItem value="normal">Normal</SelectItem>
                        <SelectItem value="comfortable">Comfortable</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                  
                  <div className="space-y-2">
                    <Label htmlFor="theme">Theme</Label>
                    <Select
                      value={preferences.layout.theme}
                      onValueChange={(value: 'light' | 'dark' | 'auto') => setTheme(value)}
                    >
                      <SelectTrigger>
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="light">Light</SelectItem>
                        <SelectItem value="dark">Dark</SelectItem>
                        <SelectItem value="auto">Auto</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                </div>
              </section>

              <Separator />

              {/* Widget Management */}
              <section>
                <div className="flex items-center justify-between mb-3">
                  <h3 className="text-lg font-semibold flex items-center gap-2">
                    <Eye className="w-4 h-4" />
                    Widgets ({enabledWidgets.length} enabled)
                  </h3>
                  <Button variant="outline" size="sm" onClick={resetWidgetLayout}>
                    <RotateCcw className="w-4 h-4 mr-1" />
                    Reset Layout
                  </Button>
                </div>

                {Object.entries(categoryGroups).map(([category, categoryWidgets]) => (
                  <div key={category} className="mb-4">
                    <h4 className="text-sm font-medium text-muted-foreground mb-2 capitalize">
                      {category} Widgets
                    </h4>
                     <div className="space-y-2">
                       {categoryWidgets.map((widget) => {
                         const config = preferences.widgets.find(w => w.id === widget.id)
                         // If no config exists, create a default one based on widget metadata
                         const effectiveConfig = config || {
                           id: widget.id,
                           name: widget.name,
                           component: widget.component,
                           enabled: widget.defaultEnabled,
                           position: 999, // Will be properly positioned when enabled
                           section: 'main' as const,
                           minimized: false,
                         }
                         
                         return (
                           <div
                             key={widget.id}
                             className="flex items-center justify-between p-3 border rounded-lg"
                           >
                             <div className="flex items-center gap-3">
                               <GripVertical className="w-4 h-4 text-muted-foreground" />
                               <div>
                                 <div className="font-medium">{widget.name}</div>
                                 <div className="text-sm text-muted-foreground">
                                   {widget.description}
                                 </div>
                               </div>
                             </div>
                             <div className="flex items-center gap-2">
                               <Badge variant={effectiveConfig.section === 'main' ? 'default' : 'secondary'}>
                                 {effectiveConfig.section}
                               </Badge>
                               <Switch
                                 checked={effectiveConfig.enabled}
                                 onCheckedChange={() => toggleWidget(widget.id)}
                               />
                             </div>
                           </div>
                         )
                       })}
                    </div>
                  </div>
                ))}
              </section>

              <Separator />

              {/* Current Layout Preview */}
              <section>
                <h3 className="text-lg font-semibold mb-3 flex items-center gap-2">
                  <Monitor className="w-4 h-4" />
                  Current Layout
                </h3>
                
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <h4 className="text-sm font-medium mb-2">Main Section ({mainWidgets.length} widgets)</h4>
                    <div className="space-y-1">
                      {mainWidgets.map((widget, index) => {
                        const metadata = widgetMetadata[widget.id]
                        return (
                          <div key={widget.id} className="flex items-center gap-2 text-sm p-2 bg-muted rounded">
                            <span className="text-muted-foreground">{index + 1}.</span>
                            <span>{metadata?.name}</span>
                            {widget.minimized && <Badge variant="outline" className="text-xs">Minimized</Badge>}
                          </div>
                        )
                      })}
                      {mainWidgets.length === 0 && (
                        <div className="text-sm text-muted-foreground p-2">No widgets enabled</div>
                      )}
                    </div>
                  </div>
                  
                  <div>
                    <h4 className="text-sm font-medium mb-2">Footer Section ({footerWidgets.length} widgets)</h4>
                    <div className="space-y-1">
                      {footerWidgets.map((widget, index) => {
                        const metadata = widgetMetadata[widget.id]
                        return (
                          <div key={widget.id} className="flex items-center gap-2 text-sm p-2 bg-muted rounded">
                            <span className="text-muted-foreground">{index + 1}.</span>
                            <span>{metadata?.name}</span>
                            {widget.minimized && <Badge variant="outline" className="text-xs">Minimized</Badge>}
                          </div>
                        )
                      })}
                      {footerWidgets.length === 0 && (
                        <div className="text-sm text-muted-foreground p-2">No widgets enabled</div>
                      )}
                    </div>
                  </div>
                </div>
              </section>

              <Separator />

              {/* Import/Export */}
              <section>
                <h3 className="text-lg font-semibold mb-3 flex items-center gap-2">
                  <Download className="w-4 h-4" />
                  Backup & Restore
                </h3>
                
                <div className="flex gap-2">
                  <Button variant="outline" onClick={handleExport}>
                    <Download className="w-4 h-4 mr-1" />
                    Export Settings
                  </Button>
                  
                  <Dialog>
                    <DialogTrigger asChild>
                      <Button variant="outline">
                        <Upload className="w-4 h-4 mr-1" />
                        Import Settings
                      </Button>
                    </DialogTrigger>
                    <DialogContent>
                      <DialogHeader>
                        <DialogTitle>Import Layout Settings</DialogTitle>
                        <DialogDescription>
                          Import your layout configuration from a file or paste the JSON directly.
                        </DialogDescription>
                      </DialogHeader>
                      <div className="space-y-4">
                        <div className="flex gap-2 mb-4">
                          <Button
                            variant={importMethod === 'file' ? 'default' : 'outline'}
                            size="sm"
                            onClick={() => setImportMethod('file')}
                          >
                            Upload File
                          </Button>
                          <Button
                            variant={importMethod === 'text' ? 'default' : 'outline'}
                            size="sm"
                            onClick={() => setImportMethod('text')}
                          >
                            Paste JSON
                          </Button>
                        </div>

                        {importMethod === 'file' ? (
                          <div>
                            <input
                              type="file"
                              accept=".json"
                              onChange={handleFileImport}
                              className="w-full p-2 border rounded"
                            />
                            <p className="text-sm text-muted-foreground mt-2">
                              Select a JSON file exported from Fragments Engine
                            </p>
                          </div>
                        ) : (
                          <div>
                            <textarea
                              className="w-full h-32 p-2 border rounded text-sm font-mono"
                              placeholder="Paste exported settings here..."
                              value={importText}
                              onChange={(e) => setImportText(e.target.value)}
                            />
                            <div className="flex justify-end gap-2 mt-2">
                              <Button variant="outline" onClick={() => setImportText('')}>
                                Clear
                              </Button>
                              <Button onClick={handleImport} disabled={!importText.trim()}>
                                Import
                              </Button>
                            </div>
                          </div>
                        )}
                      </div>
                    </DialogContent>
                  </Dialog>
                </div>
              </section>
            </div>
          </ScrollArea>
        </div>
      </SheetContent>
    </Sheet>
  )
}