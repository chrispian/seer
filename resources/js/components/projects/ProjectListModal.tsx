import React from 'react'
import { DataManagementModal, ColumnDefinition } from '@/components/ui/DataManagementModal'
import { Badge } from '@/components/ui/badge'
import { Folder } from 'lucide-react'

interface Project {
  id: string | number
  name: string
  description?: string
  vault_name?: string
  is_default?: boolean
  is_active?: boolean
  created_human?: string
  enabled?: boolean
}

interface ProjectListModalProps {
  isOpen: boolean
  onClose: () => void
  projects: Project[]
  onRefresh?: () => void
}

export function ProjectListModal({ isOpen, onClose, projects, onRefresh }: ProjectListModalProps) {
  const columns: ColumnDefinition<Project>[] = [
    {
      key: 'name',
      label: 'Project',
      render: (project: Project) => (
        <div className="flex items-center gap-2">
          <div className="flex-shrink-0 w-8 h-8 rounded bg-blue-500/10 flex items-center justify-center">
            <Folder className="h-4 w-4 text-blue-500" />
          </div>
          <div className="flex flex-col">
            <div className="flex items-center gap-2">
              <span className="font-medium text-sm">{project.name}</span>
              {project.is_default && <Badge variant="secondary" className="text-xs">Default</Badge>}
            </div>
            {project.description && (
              <span className="text-xs text-muted-foreground line-clamp-1">
                {project.description}
              </span>
            )}
          </div>
        </div>
      )
    },
    {
      key: 'vault_name',
      label: 'Vault',
      width: 'w-32',
      render: (project: Project) => (
        <span className="text-xs text-muted-foreground">
          {project.vault_name || '—'}
        </span>
      )
    },
    {
      key: 'created_human',
      label: 'Created',
      width: 'w-32',
      render: (project: Project) => (
        <span className="text-xs text-muted-foreground">
          {project.created_human || '—'}
        </span>
      )
    }
  ]

  return (
    <DataManagementModal
      isOpen={isOpen}
      onClose={onClose}
      title="Projects"
      data={projects}
      columns={columns}
      searchPlaceholder="Search projects..."
      searchFields={['name', 'description', 'vault_name']}
      onRefresh={onRefresh}
      emptyStateMessage="No projects found"
      emptyStateIcon={<Folder className="h-8 w-8" />}
    />
  )
}
