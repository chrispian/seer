import { useState, useRef, useEffect } from 'react'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'

interface InlineEditTextProps {
  value: string
  onSave: (value: string) => Promise<void>
  placeholder?: string
  multiline?: boolean
  className?: string
}

export function InlineEditText({
  value,
  onSave,
  placeholder = 'Click to edit',
  multiline = false,
  className = '',
}: InlineEditTextProps) {
  const [isEditing, setIsEditing] = useState(false)
  const [currentValue, setCurrentValue] = useState(value)
  const [isSaving, setIsSaving] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const inputRef = useRef<HTMLInputElement>(null)
  const textareaRef = useRef<HTMLTextAreaElement>(null)

  useEffect(() => {
    setCurrentValue(value)
  }, [value])

  useEffect(() => {
    if (isEditing) {
      if (multiline) {
        textareaRef.current?.focus()
        textareaRef.current?.select()
      } else {
        inputRef.current?.focus()
        inputRef.current?.select()
      }
    }
  }, [isEditing, multiline])

  const handleSave = async () => {
    if (currentValue === value) {
      setIsEditing(false)
      return
    }

    setIsSaving(true)
    setError(null)

    try {
      await onSave(currentValue)
      setIsEditing(false)
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to save')
      setCurrentValue(value)
    } finally {
      setIsSaving(false)
    }
  }

  const handleCancel = () => {
    setCurrentValue(value)
    setIsEditing(false)
    setError(null)
  }

  const handleKeyDown = (e: React.KeyboardEvent) => {
    if (e.key === 'Escape') {
      handleCancel()
    } else if (e.key === 'Enter' && !multiline && !e.shiftKey) {
      e.preventDefault()
      handleSave()
    }
  }

  if (!isEditing) {
    return (
      <div
        onClick={() => setIsEditing(true)}
        className={`cursor-pointer hover:bg-muted/50 rounded px-2 py-1 transition-colors ${multiline ? 'whitespace-pre-wrap' : ''} ${className}`}
      >
        {value || <span className="text-muted-foreground italic">{placeholder}</span>}
      </div>
    )
  }

  if (multiline) {
    return (
      <div className={className}>
        <Textarea
          ref={textareaRef}
          value={currentValue}
          onChange={(e) => setCurrentValue(e.target.value)}
          onBlur={handleSave}
          onKeyDown={handleKeyDown}
          disabled={isSaving}
          placeholder={placeholder}
          className="min-h-[80px]"
        />
        {error && <p className="text-sm text-destructive mt-1">{error}</p>}
      </div>
    )
  }

  return (
    <div className={className}>
      <Input
        ref={inputRef}
        value={currentValue}
        onChange={(e) => setCurrentValue(e.target.value)}
        onBlur={handleSave}
        onKeyDown={handleKeyDown}
        disabled={isSaving}
        placeholder={placeholder}
      />
      {error && <p className="text-sm text-destructive mt-1">{error}</p>}
    </div>
  )
}
