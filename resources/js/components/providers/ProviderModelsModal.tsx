import React, { useState, useEffect } from 'react'
import { DataManagementModal, ColumnDefinition } from '@/components/ui/DataManagementModal'
import { Badge } from '@/components/ui/badge'
import { Bot, DollarSign, Zap, Database, Eye } from 'lucide-react'
import { providersApi } from '@/lib/api/providers'
import type { Provider } from '@/types/provider'

interface AIModel {
  id: number
  model_id: string
  name: string
  description?: string
  capabilities: string[]
  pricing: {
    input_cost_per_million?: number
    output_cost_per_million?: number
    currency?: string
  }
  limits: {
    context_length?: number
    max_output?: number
  }
  enabled: boolean
  priority: number
  metadata?: {
    modalities?: {
      input: string[]
      output: string[]
    }
  }
}

interface ProviderModelsModalProps {
  isOpen: boolean
  onClose: () => void
  provider: Provider | null
}

export function ProviderModelsModal({ isOpen, onClose, provider }: ProviderModelsModalProps) {
  const [models, setModels] = useState<AIModel[]>([])
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)

  // Load models when provider changes
  useEffect(() => {
    if (isOpen && provider) {
      loadModels()
    }
  }, [isOpen, provider])

  const loadModels = async () => {
    if (!provider) return
    
    setLoading(true)
    setError(null)
    
    try {
      // Get models for this provider
      const providerData = await providersApi.getProvider(provider.id)
      setModels(providerData.models || [])
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to load models')
    } finally {
      setLoading(false)
    }
  }

  const handleToggleModel = async (modelId: string | number, enabled: boolean) => {
    if (!provider) return
    
    try {
      // Update model enabled status
      await providersApi.updateModel(provider.id, Number(modelId), { enabled })
      
      // Update local state
      setModels(prev => prev.map(model => 
        model.id === modelId ? { ...model, enabled } : model
      ))
    } catch (err) {
      console.error('Failed to toggle model:', err)
      // You might want to show a toast notification here
    }
  }

  const formatPrice = (price?: number, currency = 'USD') => {
    if (price === undefined || price === null) return 'N/A'
    return `$${price.toFixed(2)} / 1M tokens`
  }

  const formatCapabilities = (capabilities: string[]) => {
    const capabilityMap: Record<string, { label: string; color: string }> = {
      'text': { label: 'Text', color: 'bg-blue-100 text-blue-800' },
      'function_calling': { label: 'Functions', color: 'bg-green-100 text-green-800' },
      'vision': { label: 'Vision', color: 'bg-purple-100 text-purple-800' },
      'embedding': { label: 'Embeddings', color: 'bg-orange-100 text-orange-800' },
      'code': { label: 'Code', color: 'bg-gray-100 text-gray-800' },
    }

    return (
      <div className="flex flex-wrap gap-1">
        {capabilities.slice(0, 3).map(cap => {
          const config = capabilityMap[cap] || { label: cap, color: 'bg-gray-100 text-gray-800' }
          return (
            <Badge key={cap} variant="outline" className={`text-xs ${config.color}`}>
              {config.label}
            </Badge>
          )
        })}
        {capabilities.length > 3 && (
          <Badge variant="outline" className="text-xs">
            +{capabilities.length - 3}
          </Badge>
        )}
      </div>
    )
  }

  const expandedContent = (model: AIModel) => (
    <div className="space-y-3 text-sm">
      {model.description && (
        <div>
          <span className="font-medium">Description:</span>
          <p className="text-muted-foreground mt-1">{model.description}</p>
        </div>
      )}
      
      <div className="grid grid-cols-2 gap-4">
        <div>
          <span className="font-medium">Pricing:</span>
          <div className="text-muted-foreground mt-1">
            <div>Input: {formatPrice(model.pricing?.input_cost_per_million, model.pricing?.currency)}</div>
            <div>Output: {formatPrice(model.pricing?.output_cost_per_million, model.pricing?.currency)}</div>
          </div>
        </div>
        
        <div>
          <span className="font-medium">Limits:</span>
          <div className="text-muted-foreground mt-1">
            <div>Context: {model.limits?.context_length?.toLocaleString() || 'N/A'} tokens</div>
            <div>Max Output: {model.limits?.max_output?.toLocaleString() || 'N/A'} tokens</div>
          </div>
        </div>
      </div>

      {model.metadata?.modalities && (
        <div>
          <span className="font-medium">Modalities:</span>
          <div className="text-muted-foreground mt-1">
            <div>Input: {model.metadata.modalities.input?.join(', ') || 'N/A'}</div>
            <div>Output: {model.metadata.modalities.output?.join(', ') || 'N/A'}</div>
          </div>
        </div>
      )}

      <div>
        <span className="font-medium">All Capabilities:</span>
        <div className="mt-1">
          {formatCapabilities(model.capabilities)}
        </div>
      </div>
    </div>
  )

  const columns: ColumnDefinition<AIModel>[] = [
    {
      key: 'checkbox',
      label: '',
      width: 'w-8'
    },
    {
      key: 'name',
      label: 'Model',
      render: (model) => (
        <div className="flex flex-col">
          <span className="font-medium">{model.name}</span>
          <span className="text-xs text-muted-foreground">{model.model_id}</span>
        </div>
      )
    },
    {
      key: 'capabilities',
      label: 'Capabilities',
      render: (model) => formatCapabilities(model.capabilities)
    },
    {
      key: 'pricing',
      label: 'Pricing',
      width: 'w-32',
      render: (model) => (
        <div className="text-sm">
          <div>{formatPrice(model.pricing?.input_cost_per_million, model.pricing?.currency)}</div>
          <div className="text-xs text-muted-foreground">input</div>
        </div>
      )
    },
    {
      key: 'limits',
      label: 'Context',
      width: 'w-24',
      render: (model) => (
        <div className="text-sm">
          {model.limits?.context_length ? `${(model.limits.context_length / 1000).toFixed(0)}K` : 'N/A'}
        </div>
      )
    },
    {
      key: 'actions',
      label: '',
      width: 'w-12'
    }
  ]

  const filters = [
    {
      key: 'enabled',
      label: 'Status',
      options: [
        { value: 'all', label: 'All', count: models.length },
        { value: 'true', label: 'Enabled', count: models.filter(m => m.enabled).length },
        { value: 'false', label: 'Disabled', count: models.filter(m => !m.enabled).length }
      ]
    }
  ]

  const actionItems = [
    { key: 'details', label: 'View Details' }
  ]

  return (
    <DataManagementModal
      isOpen={isOpen}
      onClose={onClose}
      title={`${provider?.name || 'Provider'} Models`}
      data={models}
      columns={columns}
      loading={loading}
      error={error}
      filters={filters}
      searchPlaceholder="Search models..."
      searchFields={['name', 'model_id', 'description']}
      expandedContent={expandedContent}
      onToggleItem={handleToggleModel}
      onAction={(action, model) => {
        if (action === 'details') {
          // Could open a detailed modal or navigate to model details
          console.log('View details for model:', model.name)
        }
      }}
      actionItems={actionItems}
      onRefresh={loadModels}
      customHeader={
        <div className="text-sm text-muted-foreground">
          Configure which models are available for selection in AI tools
        </div>
      }
      emptyStateMessage="No models found for this provider"
      emptyStateIcon={<Bot className="h-8 w-8" />}
    />
  )
}