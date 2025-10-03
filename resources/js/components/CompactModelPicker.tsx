import React, { useState, useEffect } from 'react'
import { Check, ChevronsUpDown, Bot } from 'lucide-react'
import { cn } from '@/lib/utils'
import { Button } from '@/components/ui/button'
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

interface Model {
  value: string
  label: string
  model_key: string
  context_length?: number
}

interface Provider {
  provider: string
  provider_name: string
  models: Model[]
}

interface CompactModelPickerProps {
  value?: string
  onValueChange?: (value: string) => void
  disabled?: boolean
  className?: string
}

export function CompactModelPicker({
  value = '',
  onValueChange,
  disabled = false,
  className,
}: CompactModelPickerProps) {
  const [open, setOpen] = useState(false)
  const [providers, setProviders] = useState<Provider[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  // Fetch available models
  useEffect(() => {
    const fetchModels = async () => {
      try {
        setLoading(true)
        setError(null)
        
        const response = await fetch('/api/models/available')
        
        if (!response.ok) {
          throw new Error(`HTTP ${response.status}: ${response.statusText}`)
        }
        
        const result = await response.json()
        
        if (result.success && Array.isArray(result.data)) {
          setProviders(result.data)
        } else {
          setError('Invalid response format')
        }
      } catch (err) {
        const errorMessage = err instanceof Error ? err.message : 'Failed to connect to server'
        setError(errorMessage)
        console.error('Error fetching models:', err)
      } finally {
        setLoading(false)
      }
    }

    fetchModels()
  }, [])

  // Find the selected model across all providers
  const selectedModel = providers
    .flatMap(provider => provider.models)
    .find(model => model.value === value)

  const handleSelect = (selectedValue: string) => {
    onValueChange?.(selectedValue === value ? '' : selectedValue)
    setOpen(false)
  }

  // Get display text for the selected model
  const getDisplayText = () => {
    if (selectedModel) {
      const provider = providers.find(p => p.models.some(m => m.value === value))
      return `${provider?.provider_name}: ${selectedModel.label}`
    }
    return loading ? 'Loading...' : 'Select model'
  }

  return (
    <Popover open={open} onOpenChange={setOpen}>
      <PopoverTrigger asChild>
        <Button
          variant="ghost"
          role="combobox"
          aria-expanded={open}
          disabled={disabled}
          className={cn(
            'h-6 px-2 py-1 text-xs font-normal justify-start gap-1.5 text-gray-300 hover:text-white hover:bg-gray-800/50',
            !selectedModel && 'text-gray-400',
            className
          )}
        >
          <Bot className="h-3 w-3 shrink-0" />
          <span className="truncate max-w-[200px]">
            {getDisplayText()}
          </span>
          <ChevronsUpDown className="ml-auto h-3 w-3 shrink-0 opacity-50" />
        </Button>
      </PopoverTrigger>
      <PopoverContent className="w-[300px] p-0" align="start" side="top">
        <Command>
          <CommandInput 
            placeholder="Search models..." 
            className="h-8" 
          />
          <CommandList>
            <CommandEmpty>
              {loading 
                ? 'Loading models...' 
                : error 
                  ? `Error: ${error}` 
                  : providers.length === 0 
                    ? 'No models available. Please configure AI credentials first.'
                    : 'No models found.'
              }
            </CommandEmpty>
            
            {!loading && !error && providers.map((provider) => (
              <CommandGroup key={provider.provider} heading={provider.provider_name}>
                {provider.models.map((model) => (
                  <CommandItem
                    key={model.value}
                    value={`${model.label} ${provider.provider_name}`}
                    onSelect={() => handleSelect(model.value)}
                    className="flex items-center justify-between text-xs"
                  >
                    <div className="flex items-center gap-2 min-w-0">
                      <Check
                        className={cn(
                          'h-3 w-3',
                          value === model.value ? 'opacity-100' : 'opacity-0'
                        )}
                      />
                      <div className="min-w-0">
                        <div className="truncate font-medium">{model.label}</div>
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