import { useEffect, useState } from 'react'
import { ComponentRenderer } from './ComponentRenderer'
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { Sheet, SheetContent, SheetHeader, SheetTitle } from '@/components/ui/sheet'
import type { ComponentConfig } from '@/components/v2/types'

interface PageConfig {
  id: string
  title?: string
  overlay?: 'modal' | 'sheet' | 'page'
  layout?: ComponentConfig
  components?: ComponentConfig[]
  _meta?: {
    page_id: number
    hash: string
    version: number
    enabled: boolean
    module_key: string
    timestamp: string
  }
}

interface V2ShellPageProps {
  pageKey: string
  isAuthenticated: boolean
  hasUsers: boolean
  user: any
}

export function V2ShellPage({ pageKey }: V2ShellPageProps) {
  const [config, setConfig] = useState<PageConfig | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [modalOpen, setModalOpen] = useState(true)

  useEffect(() => {
    async function fetchPageConfig() {
      try {
        const response = await fetch(`/api/v2/ui/pages/${pageKey}`)
        
        if (!response.ok) {
          throw new Error(`Failed to load page: ${response.statusText}`)
        }

        const data = await response.json()
        console.log('Loaded page config:', data)
        setConfig(data)
      } catch (err) {
        console.error('Failed to load page:', err)
        setError(err instanceof Error ? err.message : 'Failed to load page')
      } finally {
        setLoading(false)
      }
    }

    fetchPageConfig()
  }, [pageKey])

  const renderComponent = (componentConfig: ComponentConfig): React.ReactNode => {
    const children = componentConfig.children?.map((child) => renderComponent(child))
    
    return (
      <ComponentRenderer key={componentConfig.id} config={componentConfig}>
        {children}
      </ComponentRenderer>
    )
  }

  if (loading) {
    return (
      <div className="flex items-center justify-center h-screen">
        <div className="text-gray-500">Loading...</div>
      </div>
    )
  }

  if (error || !config) {
    return (
      <div className="flex items-center justify-center h-screen">
        <div className="text-red-500">{error || 'Failed to load page'}</div>
      </div>
    )
  }

  const content = config.layout 
    ? renderComponent(config.layout)
    : (
        <div className="flex flex-col gap-4">
          {config.components?.map((component: ComponentConfig) => renderComponent(component))}
        </div>
      )

  const overlay = config.overlay || 'page'

  if (overlay === 'modal') {
    if (!modalOpen) {
      return null;
    }
    
    return (
      <Dialog open={modalOpen} onOpenChange={setModalOpen}>
        <DialogContent className="max-w-4xl min-w-[56rem] min-h-[32rem] max-h-[80vh] overflow-y-auto">
          {config.title && (
            <DialogHeader>
              <DialogTitle>{config.title}</DialogTitle>
            </DialogHeader>
          )}
          <div className="mt-4">{content}</div>
        </DialogContent>
      </Dialog>
    )
  }

  if (overlay === 'sheet') {
    if (!modalOpen) {
      return null;
    }
    
    return (
      <Sheet open={modalOpen} onOpenChange={setModalOpen}>
        <SheetContent className="w-full sm:max-w-xl overflow-y-auto">
          {config.title && (
            <SheetHeader>
              <SheetTitle>{config.title}</SheetTitle>
            </SheetHeader>
          )}
          <div className="mt-4">{content}</div>
        </SheetContent>
      </Sheet>
    )
  }

  return (
    <div className="min-h-screen p-6">
      {config.title && <h1 className="text-2xl font-bold mb-6">{config.title}</h1>}
      {content}
    </div>
  )
}
