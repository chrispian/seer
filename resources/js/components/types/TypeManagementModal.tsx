import { useState, useEffect } from 'react'
import { DataManagementModal, ColumnDefinition } from '@/components/ui/DataManagementModal'
import { Badge } from '@/components/ui/badge'
import { Switch } from '@/components/ui/switch'
import { Shield, Lock, HelpCircle } from 'lucide-react'
import { toast } from 'sonner'
import { TypeDetailModal } from './TypeDetailModal'
import { renderIcon } from '@/lib/icons'

interface FragmentType {
  id: string
  slug: string
  display_name: string
  plural_name: string
  description: string | null
  icon: string | null
  color: string | null
  is_enabled: boolean
  is_system: boolean
  hide_from_admin: boolean
  can_disable: boolean
  can_delete: boolean
  fragments_count: number
  version: string
  pagination_default: number
  list_columns: any
  filters: any
  actions: any
  default_sort: any
  container_component: string
  row_display_mode: string
}

interface TypeManagementModalProps {
  isOpen: boolean
  onClose: () => void
}

export function TypeManagementModal({ isOpen, onClose }: TypeManagementModalProps) {
  console.log('TypeManagementModal rendered - isOpen:', isOpen)
  
  const [types, setTypes] = useState<FragmentType[]>([])
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [selectedType, setSelectedType] = useState<FragmentType | null>(null)

  const fetchTypes = async () => {
    setLoading(true)
    setError(null)
    
    try {
      console.log('Fetching types from /api/types/admin...')
      const response = await fetch('/api/types/admin')
      console.log('Response status:', response.status)
      
      if (!response.ok) throw new Error('Failed to fetch types')
      
      const data = await response.json()
      console.log('Types data received:', data)
      
      const typesWithId = data.data.map((type: any) => ({ ...type, id: type.slug }))
      console.log('Types with ID:', typesWithId)
      
      setTypes(typesWithId)
    } catch (err) {
      console.error('Error fetching types:', err)
      setError(err instanceof Error ? err.message : 'Failed to load types')
      toast.error('Failed to load types')
    } finally {
      setLoading(false)
    }
  }

  const toggleType = async (slug: string) => {
    try {
      const response = await fetch(`/api/types/${slug}/toggle`, { method: 'POST' })
      if (!response.ok) throw new Error('Failed to toggle type')
      
      const data = await response.json()
      toast.success(data.message)
      
      setTypes(prev => prev.map(type => 
        type.slug === slug 
          ? { ...type, is_enabled: data.is_enabled }
          : type
      ))
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Failed to toggle type')
    }
  }

  useEffect(() => {
    console.log('TypeManagementModal useEffect - isOpen:', isOpen)
    if (isOpen) {
      console.log('Modal opened, calling fetchTypes()')
      fetchTypes()
    }
  }, [isOpen])
  
  console.log('TypeManagementModal render - types:', types, 'loading:', loading, 'error:', error)

  const columns: ColumnDefinition<FragmentType>[] = [
    {
      key: 'display_name',
      label: 'Type',
      render: (type) => (
        <div className="flex items-center gap-2">
          <div 
            className="flex-shrink-0 w-8 h-8 rounded flex items-center justify-center"
            style={{ backgroundColor: type.color || '#94a3b8' }}
          >
            {type.icon ? (
              renderIcon(type.icon, { className: 'h-4 w-4 text-white' })
            ) : (
              <HelpCircle className="h-4 w-4 text-white" />
            )}
          </div>
          <div className="flex flex-col">
            <div className="flex items-center gap-1">
              <span className="font-medium text-sm">{type.display_name}</span>
              {type.is_system && (
                <Shield className="h-3 w-3 text-muted-foreground" />
              )}
            </div>
            <span className="text-xs text-muted-foreground">{type.slug}</span>
          </div>
        </div>
      )
    },
    {
      key: 'description',
      label: 'Description',
      render: (type) => (
        <span className="text-xs text-muted-foreground line-clamp-2">
          {type.description || 'No description'}
        </span>
      )
    },
    {
      key: 'fragments_count',
      label: 'Fragments',
      width: 'w-24',
      render: (type) => (
        <Badge variant="outline" className="text-xs">
          {type.fragments_count}
        </Badge>
      )
    },
    {
      key: 'is_enabled',
      label: 'Enabled',
      width: 'w-24',
      render: (type) => (
        <div className="flex items-center gap-2">
          {type.can_disable ? (
            <Switch
              checked={type.is_enabled}
              onCheckedChange={() => toggleType(type.slug)}
            />
          ) : (
            <div className="flex items-center gap-1 text-xs text-muted-foreground">
              <Lock className="h-3 w-3" />
              <span>System</span>
            </div>
          )}
        </div>
      )
    }
  ]

  console.log('Rendering DataManagementModal with:', { 
    isOpen, 
    typesCount: types.length, 
    loading, 
    error,
    types: types.slice(0, 2) // Show first 2 for debugging
  })
  
  if (selectedType) {
    return (
      <TypeDetailModal
        isOpen={isOpen}
        onClose={onClose}
        onBack={() => setSelectedType(null)}
        type={selectedType}
        onUpdate={(updatedType: FragmentType) => {
          setTypes(prev => prev.map(t => t.slug === updatedType.slug ? updatedType : t))
          setSelectedType(updatedType)
        }}
      />
    )
  }
  
  return (
    <DataManagementModal
      isOpen={isOpen}
      onClose={onClose}
      title="Manage Fragment Types"
      data={types}
      columns={columns}
      loading={loading}
      error={error ?? undefined}
      onRefresh={fetchTypes}
      onRowClick={(type) => setSelectedType(type)}
      clickableRows={true}
    />
  )
}
