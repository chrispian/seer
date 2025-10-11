import React from 'react'
import { DataManagementModal, ColumnDefinition } from '@/components/ui/DataManagementModal'
import { Badge } from '@/components/ui/badge'
import { Database } from 'lucide-react'

interface Vault {
  id: string | number
  name: string
  description?: string
  is_default?: boolean
  is_active?: boolean
  created_human?: string
  enabled?: boolean
}

interface VaultListModalProps {
  isOpen: boolean
  onClose: () => void
  vaults: Vault[]
  onRefresh?: () => void
}

export function VaultListModal({ isOpen, onClose, vaults, onRefresh }: VaultListModalProps) {
  const columns: ColumnDefinition<Vault>[] = [
    {
      key: 'name',
      label: 'Vault',
      render: (vault: Vault) => (
        <div className="flex items-center gap-2">
          <div className="flex-shrink-0 w-8 h-8 rounded bg-purple-500/10 flex items-center justify-center">
            <Database className="h-4 w-4 text-purple-500" />
          </div>
          <div className="flex flex-col">
            <div className="flex items-center gap-2">
              <span className="font-medium text-sm">{vault.name}</span>
              {vault.is_default && <Badge variant="secondary" className="text-xs">Default</Badge>}
            </div>
            {vault.description && (
              <span className="text-xs text-muted-foreground line-clamp-1">
                {vault.description}
              </span>
            )}
          </div>
        </div>
      )
    },
    {
      key: 'created_human',
      label: 'Created',
      width: 'w-32',
      render: (vault: Vault) => (
        <span className="text-xs text-muted-foreground">
          {vault.created_human || '—'}
        </span>
      )
    }
  ]

  return (
    <DataManagementModal
      isOpen={isOpen}
      onClose={onClose}
      title="Vaults"
      data={vaults}
      columns={columns}
      searchPlaceholder="Search vaults..."
      searchFields={['name', 'description']}
      onRefresh={onRefresh}
      emptyStateMessage="No vaults found"
      emptyStateIcon={<Database className="h-8 w-8" />}
    />
  )
}
