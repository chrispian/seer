import React from 'react'
import { useState, useEffect } from 'react'
import { DataManagementModal, ColumnDefinition } from '@/components/ui/DataManagementModal'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Switch } from '@/components/ui/switch'
import { Package, Shield, Lock, FileCode, Database } from 'lucide-react'
import { toast } from 'sonner'
import { useTypePacks } from '@/hooks/useTypePacks'
import { renderIcon } from '@/lib/icons'
import type { TypePack } from '@/lib/api/typePacks'

interface TypePackListProps {
  isOpen: boolean
  onClose: () => void
  onSelectTypePack?: (typePack: TypePack) => void
  onCreateNew?: () => void
}

export function TypePackList({ 
  isOpen, 
  onClose, 
  onSelectTypePack,
  onCreateNew 
}: TypePackListProps) {
  const { 
    typePacks, 
    isLoading, 
    error, 
    fetchTypePacks, 
    deleteTypePack 
  } = useTypePacks()

  useEffect(() => {
    if (isOpen) {
      fetchTypePacks()
    }
  }, [isOpen, fetchTypePacks])

  const handleDelete = async (slug: string) => {
    if (!confirm('Are you sure you want to delete this type pack? This cannot be undone.')) {
      return
    }

    try {
      await deleteTypePack(slug)
      toast.success('Type pack deleted successfully')
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Failed to delete type pack')
    }
  }

  const getStatusBadge = (typePack: TypePack) => {
    if (typePack.is_enabled) {
      return <Badge variant="outline" className="text-xs bg-green-100 text-green-800">Active</Badge>
    }
    return <Badge variant="outline" className="text-xs bg-gray-100 text-gray-800">Disabled</Badge>
  }

  const columns: ColumnDefinition<TypePack>[] = [
    {
      key: 'display_name',
      label: 'Type Pack',
      render: (typePack) => (
        <div className="flex items-center gap-2">
          <div 
            className="flex-shrink-0 w-8 h-8 rounded flex items-center justify-center"
            style={{ backgroundColor: typePack.color || '#94a3b8' }}
          >
            {typePack.icon ? (
              renderIcon(typePack.icon, { className: 'h-4 w-4 text-white' })
            ) : (
              <Package className="h-4 w-4 text-white" />
            )}
          </div>
          <div className="flex flex-col">
            <div className="flex items-center gap-1">
              <span className="font-medium text-sm">{typePack.display_name}</span>
              {typePack.is_system && (
                <Shield className="h-3 w-3 text-muted-foreground" />
              )}
            </div>
            <span className="text-xs text-muted-foreground">{typePack.slug}</span>
          </div>
        </div>
      )
    },
    {
      key: 'description',
      label: 'Description',
      render: (typePack) => (
        <span className="text-xs text-muted-foreground line-clamp-2">
          {typePack.description || 'No description'}
        </span>
      )
    },
    {
      key: 'fragments_count',
      label: 'Fragments',
      width: 'w-24',
      render: (typePack) => (
        <div className="flex items-center gap-1">
          <Database className="h-3 w-3 text-muted-foreground" />
          <span className="text-xs text-muted-foreground">{typePack.fragments_count || 0}</span>
        </div>
      )
    },
    {
      key: 'version',
      label: 'Version',
      width: 'w-20',
      render: (typePack) => (
        <Badge variant="outline" className="text-xs">
          v{typePack.version}
        </Badge>
      )
    },
    {
      key: 'is_enabled',
      label: 'Status',
      width: 'w-24',
      render: (typePack) => getStatusBadge(typePack)
    },
    {
      key: 'actions',
      label: '',
      width: 'w-12'
    }
  ]

  const actionItems = [
    { key: 'edit', label: 'Edit Type Pack' },
    { key: 'schema', label: 'View Schema' },
    { key: 'fragments', label: 'View Fragments' },
    { key: 'divider', label: '-' },
    { key: 'refresh', label: 'Refresh Cache' },
    { key: 'delete', label: 'Delete', variant: 'destructive' as const }
  ]

  return (
    <DataManagementModal
      isOpen={isOpen}
      onClose={onClose}
      title="Type Packs"
      data={typePacks}
      columns={columns}
      loading={isLoading}
      error={error?.message}
      onRefresh={fetchTypePacks}
      onRowClick={(typePack) => onSelectTypePack?.(typePack)}
      clickableRows={true}
      searchPlaceholder="Search type packs..."
      searchFields={['slug', 'display_name', 'description']}
      onAction={(action, typePack) => {
        if (action === 'edit') {
          onSelectTypePack?.(typePack)
        } else if (action === 'delete') {
          handleDelete(typePack.slug)
        } else if (action === 'schema') {
          toast.info('Schema viewer coming soon')
        } else if (action === 'fragments') {
          toast.info('Fragment viewer coming soon')
        } else if (action === 'refresh') {
          toast.info('Cache refresh coming soon')
        }
      }}
      actionItems={actionItems}
      customHeader={
        <div className="flex items-center justify-between">
          <div className="text-sm text-muted-foreground">
            Manage fragment type definitions and schemas
          </div>
          {onCreateNew && (
            <Button onClick={onCreateNew} size="sm">
              <Package className="h-4 w-4 mr-2" />
              Create Type Pack
            </Button>
          )}
        </div>
      }
      emptyStateMessage="No type packs found. Create your first type pack to get started."
      emptyStateIcon={<Package className="h-8 w-8" />}
    />
  )
}
