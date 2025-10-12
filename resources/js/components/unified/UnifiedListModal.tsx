import React from 'react'
import { useState } from 'react'
import { DataManagementModal, ColumnDefinition } from '@/components/ui/DataManagementModal'
import { Badge } from '@/components/ui/badge'
import { HelpCircle } from 'lucide-react'
import { renderIcon } from '@/lib/icons'
import { UnifiedDetailModal } from './UnifiedDetailModal'

interface TypeConfig {
  slug: string
  display_name: string
  plural_name: string
  icon: string | null
  color: string | null
  container_component: string
  row_display_mode: string
  list_columns: any
  filters: any
  actions: any
  default_sort: any
  pagination_default: number
  detail_component: string | null
  detail_fields: any
}

interface UnifiedListModalProps {
  isOpen: boolean
  onClose: () => void
  data: {
    items: any[]
    typeConfig: TypeConfig
  }
  onRefresh?: () => void
}

export function UnifiedListModal({ isOpen, onClose, data, onRefresh }: UnifiedListModalProps) {
  const { items, typeConfig } = data
  const [selectedItem, setSelectedItem] = useState<any | null>(null)

  // Build columns from config or use defaults
  const columns: ColumnDefinition<any>[] = typeConfig.list_columns 
    ? buildColumnsFromConfig(typeConfig.list_columns)
    : getDefaultColumns(typeConfig)

  // If detail view is open and detail_component is configured
  if (selectedItem && typeConfig.detail_component) {
    return (
      <UnifiedDetailModal
        isOpen={isOpen}
        onClose={onClose}
        onBack={() => setSelectedItem(null)}
        item={selectedItem}
        typeConfig={typeConfig}
      />
    )
  }

  return (
    <DataManagementModal
      isOpen={isOpen}
      onClose={onClose}
      title={typeConfig.plural_name || typeConfig.display_name}
      data={items.map(item => ({ ...item, id: item.id || item.slug }))}
      columns={columns}
      loading={false}
      onRefresh={onRefresh}
      clickableRows={!!typeConfig.detail_component}
      onRowClick={typeConfig.detail_component ? (item) => setSelectedItem(item) : undefined}
    />
  )
}

function buildColumnsFromConfig(columnConfig: any[]): ColumnDefinition<any>[] {
  return columnConfig.map(col => ({
    key: col.key,
    label: col.label,
    width: col.width,
    render: col.render ? eval(col.render) : undefined,
  }))
}

function getDefaultColumns(typeConfig: TypeConfig): ColumnDefinition<any>[] {
  return [
    {
      key: 'title',
      label: 'Item',
      render: (item: any) => (
        <div className="flex items-center gap-2">
          <div 
            className="flex-shrink-0 w-8 h-8 rounded flex items-center justify-center"
            style={{ backgroundColor: typeConfig.color || '#94a3b8' }}
          >
            {typeConfig.icon ? (
              renderIcon(typeConfig.icon, { className: 'h-4 w-4 text-white' })
            ) : (
              <HelpCircle className="h-4 w-4 text-white" />
            )}
          </div>
          <div className="flex flex-col">
            {item.title && (
              <span className="font-medium text-sm">{item.title}</span>
            )}
            <span className="text-xs text-muted-foreground line-clamp-2">
              {item.preview || item.message}
            </span>
          </div>
        </div>
      )
    },
    {
      key: 'created_at',
      label: 'Created',
      width: 'w-32',
      render: (item: any) => (
        <span className="text-xs text-muted-foreground">
          {item.created_human || item.created_at}
        </span>
      )
    },
    {
      key: 'type',
      label: 'Type',
      width: 'w-24',
      render: (item: any) => (
        <Badge variant="outline" className="text-xs">
          {item.type}
        </Badge>
      )
    },
  ]
}
