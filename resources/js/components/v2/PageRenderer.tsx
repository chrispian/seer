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

  const renderComponent = (componentConfig: ComponentConfig): React.ReactNode => {
    const Component = componentRegistry.get(componentConfig.type)

    if (!Component) {
      console.warn(`Component type not registered: ${componentConfig.type}`)
      return null
    }

    // Recursively render children if they exist
    const children = componentConfig.children?.map((child) => renderComponent(child))

    // Type assertion to allow children prop
    const ComponentWithChildren = Component as React.ComponentType<{
      config: ComponentConfig
      children?: React.ReactNode
    }>

    return (
      <ComponentWithChildren key={componentConfig.id} config={componentConfig}>
        {children}
      </ComponentWithChildren>
    )
  }

  // Support both old (components array) and new (layout object) schemas
  const content = config.layout 
    ? renderComponent(config.layout)
    : (
        <div className="space-y-4">
          {config.components?.map((component: ComponentConfig) => renderComponent(component))}
        </div>
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
