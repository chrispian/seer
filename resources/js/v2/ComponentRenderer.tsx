import { ComponentConfig } from '@/components/v2/types'
import { componentRegistry } from '@/components/v2/ComponentRegistry'

interface ComponentRendererProps {
  config: ComponentConfig
}

export function ComponentRenderer({ config }: ComponentRendererProps) {
  const Component = componentRegistry.get(config.type)

  if (!Component) {
    return (
      <div className="p-4 border border-yellow-500 bg-yellow-50 text-yellow-900 rounded">
        <strong>Unknown component type:</strong> {config.type}
        <br />
        <small>Component ID: {config.id}</small>
      </div>
    )
  }

  return <Component config={config} />
}
