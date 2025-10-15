import { useEffect, useState } from 'react'
import { ComponentRenderer } from './ComponentRenderer'
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { Sheet, SheetContent, SheetHeader, SheetTitle } from '@/components/ui/sheet'

interface PageConfig {
  id: string
  key: string
  title?: string
  overlay?: 'modal' | 'sheet' | 'page'
  layout_tree_json: {
    overlay?: 'modal' | 'sheet' | 'page'
    components: any[]
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
        setConfig(data)
      } catch (err) {
        setError(err instanceof Error ? err.message : 'Failed to load page')
      } finally {
        setLoading(false)
      }
    }

    fetchPageConfig()
  }, [pageKey])

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

  const components = config.layout_tree_json?.components || []

  const content = (
    <div className="flex flex-col gap-4">
      {components.map((component: any, idx: number) => (
        <ComponentRenderer key={component.id || idx} config={component} />
      ))}
    </div>
  )

  // Check for overlay type in layout_tree_json
  const overlay = config.layout_tree_json?.overlay || 'page'

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
