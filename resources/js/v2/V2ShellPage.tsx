import { useEffect, useState } from 'react'
import { PageConfig } from '@/components/v2/types'
import { ComponentRenderer } from '@/v2/ComponentRenderer'
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { Sheet, SheetContent, SheetHeader, SheetTitle } from '@/components/ui/sheet'

interface V2ShellPageProps {
  pageKey: string
}

export function V2ShellPage({ pageKey }: V2ShellPageProps) {
  const [config, setConfig] = useState<PageConfig | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    async function fetchPageConfig() {
      try {
        const response = await fetch(`/api/v2/ui/pages/${pageKey}`)
        
        if (!response.ok) {
          throw new Error(`Failed to load page: ${response.statusText}`)
        }

        const data = await response.json()
        setConfig(data.config)
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
      <div className="flex items-center justify-center h-full">
        <div className="text-gray-500">Loading...</div>
      </div>
    )
  }

  if (error || !config) {
    return (
      <div className="flex items-center justify-center h-full">
        <div className="text-red-500">{error || 'Failed to load page'}</div>
      </div>
    )
  }

  const content = (
    <div className="flex flex-col h-full">
      {config.components.map((component) => (
        <ComponentRenderer key={component.id} config={component} />
      ))}
    </div>
  )

  if (config.overlay === 'modal') {
    return (
      <Dialog open={true} onOpenChange={() => window.history.back()}>
        <DialogContent className="max-w-4xl min-w-[56rem] min-h-[32rem] max-h-[80vh] overflow-y-auto">
          {config.title && (
            <DialogHeader>
              <DialogTitle>{config.title}</DialogTitle>
            </DialogHeader>
          )}
          {content}
        </DialogContent>
      </Dialog>
    )
  }

  if (config.overlay === 'sheet') {
    return (
      <Sheet open={true} onOpenChange={() => window.history.back()}>
        <SheetContent className="w-full sm:max-w-xl overflow-y-auto">
          {config.title && (
            <SheetHeader>
              <SheetTitle>{config.title}</SheetTitle>
            </SheetHeader>
          )}
          {content}
        </SheetContent>
      </Sheet>
    )
  }

  return (
    <div className="h-full p-6">
      {config.title && <h1 className="text-2xl font-bold mb-4">{config.title}</h1>}
      {content}
    </div>
  )
}
