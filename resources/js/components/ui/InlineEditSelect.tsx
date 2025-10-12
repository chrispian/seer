import { useState, useEffect } from 'react'
import { Badge } from '@/components/ui/badge'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'

interface SelectOption {
  value: string
  label: string
  icon?: React.ReactNode
}

interface InlineEditSelectProps {
  value: string
  options: SelectOption[]
  onSave: (value: string) => Promise<void>
  placeholder?: string
  variant?: 'default' | 'outline' | 'secondary'
  className?: string
}

export function InlineEditSelect({
  value,
  options,
  onSave,
  placeholder = 'Select...',
  variant = 'default',
  className = '',
}: InlineEditSelectProps) {
  const [isEditing, setIsEditing] = useState(false)
  const [isLoading, setIsLoading] = useState(false)
  const [localValue, setLocalValue] = useState(value)

  useEffect(() => {
    setLocalValue(value)
  }, [value])

  const handleValueChange = async (newValue: string) => {
    if (newValue === localValue) {
      setIsEditing(false)
      return
    }

    setIsLoading(true)
    try {
      await onSave(newValue)
      setLocalValue(newValue)
      setIsEditing(false)
    } catch (error) {
      console.error('Failed to save:', error)
    } finally {
      setIsLoading(false)
    }
  }

  const handleOpenChange = (open: boolean) => {
    if (!open && !isLoading) {
      setIsEditing(false)
    } else if (open) {
      setIsEditing(true)
    }
  }

  const currentOption = options.find((opt) => opt.value === localValue)
  const displayLabel = currentOption?.label || localValue || placeholder

  if (!isEditing) {
    return (
      <Badge
        variant={variant}
        className={`cursor-pointer hover:opacity-80 transition-opacity ${className}`}
        onClick={() => setIsEditing(true)}
      >
        {currentOption?.icon && <span className="mr-1">{currentOption.icon}</span>}
        {displayLabel}
      </Badge>
    )
  }

  return (
    <Select
      value={localValue}
      onValueChange={handleValueChange}
      onOpenChange={handleOpenChange}
      open={isEditing}
      disabled={isLoading}
    >
      <SelectTrigger className={className}>
        <SelectValue placeholder={placeholder} />
      </SelectTrigger>
      <SelectContent>
        {options.map((option) => (
          <SelectItem key={option.value} value={option.value}>
            <div className="flex items-center">
              {option.icon && <span className="mr-2">{option.icon}</span>}
              {option.label}
            </div>
          </SelectItem>
        ))}
      </SelectContent>
    </Select>
  )
}
