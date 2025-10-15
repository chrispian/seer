import { componentRegistry } from '@/components/v2/ComponentRegistry'

interface ComponentRendererProps {
  config: any
}

export function ComponentRenderer({ config }: ComponentRendererProps) {
  if (!config || !config.type) {
    return null
  }

  const Component = componentRegistry.get(config.type)

  if (!Component) {
    console.warn('Unknown component type:', config.type)
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
