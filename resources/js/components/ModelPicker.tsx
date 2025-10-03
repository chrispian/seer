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

interface ModelPickerProps {
  value?: string
  onValueChange?: (value: string) => void
  disabled?: boolean
  placeholder?: string
  className?: string
}

export function ModelPicker({
  value = '',
  onValueChange,
  disabled = false,
  placeholder = 'Select model...',
  className,
}: ModelPickerProps) {
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

  return (
    <Popover open={open} onOpenChange={setOpen}>
      <PopoverTrigger asChild>
        <Button
          variant="outline"
          role="combobox"
          aria-expanded={open}
          disabled={disabled}
          className={cn(
            'w-[200px] justify-between text-left font-normal',
            !selectedModel && 'text-muted-foreground',
            className
          )}
        >
          <div className="flex items-center gap-2 min-w-0">
            <Bot className="h-4 w-4 shrink-0" />
            <span className="truncate">
              {selectedModel ? selectedModel.label : placeholder}
            </span>
          </div>
          <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
        </Button>
      </PopoverTrigger>
      <PopoverContent className="w-[300px] p-0" align="start">
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
                    className="flex items-center justify-between"
                  >
                    <div className="flex items-center gap-2 min-w-0">
                      <Check
                        className={cn(
                          'h-4 w-4',
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