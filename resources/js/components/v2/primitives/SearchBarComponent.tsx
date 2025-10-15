import { useState, useEffect } from 'react'
import { Input } from '@/components/ui/input'
import { Search } from 'lucide-react'
import { useDebounce } from '@/hooks/useDebounce'
import type { ComponentConfig } from '../types'
import { slotBinder } from '../SlotBinder'

interface SearchBarComponentProps {
  config: ComponentConfig
}

export function SearchBarComponent({ config }: SearchBarComponentProps) {
  const [searchTerm, setSearchTerm] = useState('')
  const debouncedSearch = useDebounce(searchTerm, 300)

  useEffect(() => {
    if (config.result) {
      slotBinder.update(config.result, { search: debouncedSearch })
    }
  }, [debouncedSearch, config.result])

  return (
    <div className="relative">
      <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
      <Input
        type="text"
        placeholder="Search..."
        value={searchTerm}
        onChange={e => setSearchTerm(e.target.value)}
        className="pl-10"
      />
    </div>
  )
}
