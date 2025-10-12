import { useState, KeyboardEvent } from 'react'
import { X } from 'lucide-react'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'
import { cn } from '@/lib/utils'

interface TagEditorProps {
  tags: string[]
  onSave: (tags: string[]) => Promise<void>
  placeholder?: string
  maxTags?: number
  readOnly?: boolean
  className?: string
}

export function TagEditor({
  tags,
  onSave,
  placeholder = 'Add tag...',
  maxTags,
  readOnly = false,
  className,
}: TagEditorProps) {
  const [inputValue, setInputValue] = useState('')
  const [isLoading, setIsLoading] = useState(false)

  const normalizedTags = tags.map(tag => tag.toLowerCase())

  const handleAddTag = async () => {
    const trimmedValue = inputValue.trim()
    
    if (!trimmedValue) return
    
    if (normalizedTags.includes(trimmedValue.toLowerCase())) {
      setInputValue('')
      return
    }
    
    if (maxTags && tags.length >= maxTags) {
      return
    }

    setIsLoading(true)
    try {
      await onSave([...tags, trimmedValue])
      setInputValue('')
    } finally {
      setIsLoading(false)
    }
  }

  const handleRemoveTag = async (tagToRemove: string) => {
    setIsLoading(true)
    try {
      await onSave(tags.filter(tag => tag !== tagToRemove))
    } finally {
      setIsLoading(false)
    }
  }

  const handleKeyDown = (e: KeyboardEvent<HTMLInputElement>) => {
    if (e.key === 'Enter') {
      e.preventDefault()
      handleAddTag()
    }
  }

  const isMaxTagsReached = maxTags ? tags.length >= maxTags : false

  return (
    <div className={cn('flex flex-wrap gap-2 items-center', className)}>
      {tags.map((tag) => (
        <Badge key={tag} variant="secondary" className="group">
          {tag}
          {!readOnly && (
            <X
              className="ml-1 h-3 w-3 cursor-pointer opacity-0 group-hover:opacity-100 transition-opacity"
              onClick={() => handleRemoveTag(tag)}
            />
          )}
        </Badge>
      ))}
      {!readOnly && (
        <Input
          value={inputValue}
          onChange={(e) => setInputValue(e.target.value)}
          onKeyDown={handleKeyDown}
          placeholder={placeholder}
          disabled={isLoading || isMaxTagsReached}
          className="w-32 h-6 text-sm inline-flex"
        />
      )}
    </div>
  )
}
