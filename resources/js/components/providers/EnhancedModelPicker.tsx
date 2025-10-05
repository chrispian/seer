import React, { useState, useEffect } from 'react'
import { Check, ChevronsUpDown, Bot, Filter, Shield, Zap, AlertCircle } from 'lucide-react'
import { cn } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import {
  Command,
  CommandEmpty,
  CommandGroup,
  CommandInput,
  CommandItem,
  CommandList,
} from '@/components/ui/command'
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from '@/components/ui/popover'
import { Switch } from '@/components/ui/switch'
import { Label } from '@/components/ui/label'
import { Separator } from '@/components/ui/separator'
import { providersApi, modelsApi } from '@/lib/api/providers'
import type { Provider, Model } from '@/types/provider'

interface ModelWithProvider extends Model {
  provider_id: string
  provider_name: string
  provider_status: 'healthy' | 'unhealthy' | 'unknown'
  provider_enabled: boolean
}

interface EnhancedModelPickerProps {
  value?: string
  onValueChange?: (value: string) => void
  disabled?: boolean
  placeholder?: string
  className?: string
  showProviderFilters?: boolean
  showHealthStatus?: boolean
  filterByCapability?: string[]
}

export function EnhancedModelPicker({
  value = '',
  onValueChange,
  disabled = false,
  placeholder = 'Select model...',
  className,
  showProviderFilters = true,
  showHealthStatus = true,
  filterByCapability = [],
}: EnhancedModelPickerProps) {
  const [open, setOpen] = useState(false)
  const [providers, setProviders] = useState<Provider[]>([])
  const [models, setModels] = useState<ModelWithProvider[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  
  // Filter states
  const [showEnabledOnly, setShowEnabledOnly] = useState(true)
  const [showHealthyOnly, setShowHealthyOnly] = useState(false)
  const [selectedProviders, setSelectedProviders] = useState<Set<string>>(new Set())

  // Fetch providers and models
  useEffect(() => {
    const fetchData = async () => {
      try {
        setLoading(true)
        setError(null)
        
        // Fetch providers and all models in parallel
        const [providersData, modelsData] = await Promise.all([
          providersApi.getProviders(),
          modelsApi.getAllModels()
        ])
        
        setProviders(providersData)
        
        // Combine models with provider information
        const enhancedModels: ModelWithProvider[] = []
        
        for (const provider of providersData) {
          for (const model of provider.models) {
            enhancedModels.push({
              ...model,
              provider_id: provider.id,
              provider_name: provider.name,
              provider_status: provider.status,
              provider_enabled: provider.enabled,
            })
          }
        }
        
        setModels(enhancedModels)
      } catch (err) {
        const errorMessage = err instanceof Error ? err.message : 'Failed to load models'
        setError(errorMessage)
        console.error('Error fetching models and providers:', err)
      } finally {
        setLoading(false)
      }
    }

    fetchData()
  }, [])

  // Filter models based on current filter settings
  const filteredModels = models.filter(model => {
    // Filter by enabled providers
    if (showEnabledOnly && !model.provider_enabled) {
      return false
    }
    
    // Filter by healthy providers
    if (showHealthyOnly && model.provider_status !== 'healthy') {
      return false
    }
    
    // Filter by selected providers (if any)
    if (selectedProviders.size > 0 && !selectedProviders.has(model.provider_id)) {
      return false
    }
    
    // Filter by capabilities
    if (filterByCapability.length > 0) {
      const hasRequiredCapabilities = filterByCapability.every(capability =>
        model.capabilities.includes(capability)
      )
      if (!hasRequiredCapabilities) {
        return false
      }
    }
    
    return true
  })

  // Group filtered models by provider
  const groupedModels = filteredModels.reduce((acc, model) => {
    const key = model.provider_id
    if (!acc[key]) {
      acc[key] = {
        provider: providers.find(p => p.id === key)!,
        models: []
      }
    }
    acc[key].models.push(model)
    return acc
  }, {} as Record<string, { provider: Provider; models: ModelWithProvider[] }>)

  // Find the selected model
  const selectedModel = models.find(model => model.id === value)

  const handleSelect = (selectedValue: string) => {
    onValueChange?.(selectedValue === value ? '' : selectedValue)
    setOpen(false)
  }

  const toggleProvider = (providerId: string) => {
    const newSelected = new Set(selectedProviders)
    if (newSelected.has(providerId)) {
      newSelected.delete(providerId)
    } else {
      newSelected.add(providerId)
    }
    setSelectedProviders(newSelected)
  }

  const clearProviderFilter = () => {
    setSelectedProviders(new Set())
  }

  const getStatusIcon = (status: 'healthy' | 'unhealthy' | 'unknown') => {
    switch (status) {
      case 'healthy':
        return <Shield className="h-3 w-3 text-green-500" />
      case 'unhealthy':
        return <AlertCircle className="h-3 w-3 text-red-500" />
      default:
        return <AlertCircle className="h-3 w-3 text-gray-400" />
    }
  }

  return (
    <Popover open={open} onOpenChange={setOpen}>
      <PopoverTrigger asChild>
        <Button
          variant="outline"
          role="combobox"
          aria-expanded={open}
          disabled={disabled}
          className={cn(
            'w-[250px] justify-between text-left font-normal',
            !selectedModel && 'text-muted-foreground',
            className
          )}
        >
          <div className="flex items-center gap-2 min-w-0">
            <Bot className="h-4 w-4 shrink-0" />
            <span className="truncate">
              {selectedModel ? (
                <div className="flex items-center gap-2">
                  <span>{selectedModel.name}</span>
                  {showHealthStatus && getStatusIcon(selectedModel.provider_status)}
                </div>
              ) : (
                placeholder
              )}
            </span>
          </div>
          <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
        </Button>
      </PopoverTrigger>
      <PopoverContent className="w-[400px] p-0" align="start">
        <div className="p-3 border-b">
          {showProviderFilters && (
            <div className="space-y-3">
              <div className="flex items-center justify-between">
                <h4 className="text-sm font-medium">Filters</h4>
                <Button 
                  variant="ghost" 
                  size="sm"
                  onClick={clearProviderFilter}
                  className="h-6 px-2 text-xs"
                >
                  Clear
                </Button>
              </div>
              
              <div className="flex items-center space-x-4">
                <div className="flex items-center space-x-2">
                  <Switch
                    id="enabled-only"
                    checked={showEnabledOnly}
                    onCheckedChange={setShowEnabledOnly}
                  />
                  <Label htmlFor="enabled-only" className="text-xs">Enabled only</Label>
                </div>
                
                {showHealthStatus && (
                  <div className="flex items-center space-x-2">
                    <Switch
                      id="healthy-only"
                      checked={showHealthyOnly}
                      onCheckedChange={setShowHealthyOnly}
                    />
                    <Label htmlFor="healthy-only" className="text-xs">Healthy only</Label>
                  </div>
                )}
              </div>
              
              {providers.length > 0 && (
                <div>
                  <Label className="text-xs text-muted-foreground">Providers:</Label>
                  <div className="flex flex-wrap gap-1 mt-1">
                    {providers.map(provider => (
                      <Badge
                        key={provider.id}
                        variant={selectedProviders.has(provider.id) ? "default" : "outline"}
                        className="text-xs cursor-pointer"
                        onClick={() => toggleProvider(provider.id)}
                      >
                        <div className="flex items-center gap-1">
                          {showHealthStatus && getStatusIcon(provider.status)}
                          {provider.name}
                          {!provider.enabled && <span className="text-muted-foreground">(disabled)</span>}
                        </div>
                      </Badge>
                    ))}
                  </div>
                </div>
              )}
            </div>
          )}
        </div>
        
        <Command>
          <CommandInput 
            placeholder="Search models..." 
            className="h-9" 
          />
          <CommandList>
            <CommandEmpty>
              {loading 
                ? 'Loading models...' 
                : error 
                  ? `Error: ${error}` 
                  : Object.keys(groupedModels).length === 0 
                    ? 'No models match the current filters.'
                    : 'No models found.'
              }
            </CommandEmpty>
            
            {!loading && !error && Object.entries(groupedModels).map(([providerId, { provider, models: providerModels }]) => (
              <CommandGroup 
                key={providerId} 
                heading={
                  <div className="flex items-center gap-2">
                    <span>{provider.name}</span>
                    {showHealthStatus && getStatusIcon(provider.status)}
                    {!provider.enabled && <Badge variant="secondary" className="text-xs">Disabled</Badge>}
                    <Badge variant="outline" className="text-xs">
                      {providerModels.length} model{providerModels.length !== 1 ? 's' : ''}
                    </Badge>
                  </div>
                }
              >
                {providerModels.map((model) => (
                  <CommandItem
                    key={model.id}
                    value={`${model.name} ${provider.name}`}
                    onSelect={() => handleSelect(model.id)}
                    className="flex items-center justify-between"
                  >
                    <div className="flex items-center gap-2 min-w-0">
                      <Check
                        className={cn(
                          'h-4 w-4',
                          value === model.id ? 'opacity-100' : 'opacity-0'
                        )}
                      />
                      <div className="min-w-0">
                        <div className="flex items-center gap-2">
                          <span className="truncate font-medium">{model.name}</span>
                          {model.capabilities.length > 0 && (
                            <div className="flex gap-1">
                              {model.capabilities.slice(0, 2).map(capability => (
                                <Badge key={capability} variant="secondary" className="text-xs">
                                  {capability}
                                </Badge>
                              ))}
                              {model.capabilities.length > 2 && (
                                <Badge variant="secondary" className="text-xs">
                                  +{model.capabilities.length - 2}
                                </Badge>
                              )}
                            </div>
                          )}
                        </div>
                        {model.context_length && (
                          <div className="text-xs text-muted-foreground">
                            {model.context_length.toLocaleString()} tokens
                          </div>
                        )}
                      </div>
                    </div>
                  </CommandItem>
                ))}
              </CommandGroup>
            ))}
          </CommandList>
        </Command>
      </PopoverContent>
    </Popover>
  )
}