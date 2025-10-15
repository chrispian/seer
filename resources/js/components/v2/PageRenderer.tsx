import { usePageConfig } from './hooks/usePageConfig'
import { componentRegistry } from './ComponentRegistry'
import { ModalLayout } from './layouts/ModalLayout'
import type { ComponentConfig } from './types'

interface PageRendererProps {
  pageKey: string
  open?: boolean
  onOpenChange?: (open: boolean) => void
}

export function PageRenderer({ pageKey, open = true, onOpenChange }: PageRendererProps) {
  const { config, loading, error } = usePageConfig(pageKey)

  if (loading) {
    return (
      <div className="flex items-center justify-center p-8">
        <div className="text-muted-foreground">Loading page...</div>
      </div>
    )
  }

  if (error) {
    return (
      <div className="flex items-center justify-center p-8">
        <div className="text-destructive">Error: {error.message}</div>
      </div>
    )
  }

  if (!config) {
    return (
      <div className="flex items-center justify-center p-8">
        <div className="text-muted-foreground">Page not found</div>
      </div>
    )
  }

  const renderComponent = (componentConfig: ComponentConfig) => {
    const Component = componentRegistry.get(componentConfig.type)

    if (!Component) {
      console.warn(`Component type not registered: ${componentConfig.type}`)
      return null
    }

    return <Component key={componentConfig.id} config={componentConfig} />
  }

  const content = (
    <>
      {config.components.map(component => renderComponent(component))}
    </>
  )

  if (config.overlay === 'modal') {
    return (
      <ModalLayout
        config={config as any}
        title={config.title}
        open={open}
        onOpenChange={onOpenChange || (() => {})}
      >
        {content}
      </ModalLayout>
    )
  }

  return <div className="space-y-4 p-4">{content}</div>
}
