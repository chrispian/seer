import { DataManagementModal, ColumnDefinition } from '@/components/ui/DataManagementModal'
import { Badge } from '@/components/ui/badge'
import { Hash, Lock, Eye, Users, ExternalLink } from 'lucide-react'

interface Channel {
  id: string
  name: string
  description: string
  type: 'public' | 'private' | 'read-only'
  member_count: number
  is_active: boolean
  created_at: string
}

interface ChannelListModalProps {
  isOpen: boolean
  onClose: () => void
  channels: Channel[]
  loading?: boolean
  error?: string | null
  onRefresh?: () => void
  onChannelSelect?: (channel: Channel) => void
}

export function ChannelListModal({ 
  isOpen, 
  onClose, 
  channels, 
  loading = false, 
  error = null,
  onRefresh,
  onChannelSelect 
}: ChannelListModalProps) {

  const getTypeColor = (type: string) => {
    switch (type?.toLowerCase()) {
      case 'public':
        return 'bg-blue-100 text-blue-800'
      case 'private':
        return 'bg-purple-100 text-purple-800'
      case 'read-only':
        return 'bg-gray-100 text-gray-800'
      default:
        return 'bg-gray-100 text-gray-800'
    }
  }

  const getTypeIcon = (type: string) => {
    switch (type?.toLowerCase()) {
      case 'public':
        return <Hash className="h-3 w-3" />
      case 'private':
        return <Lock className="h-3 w-3" />
      case 'read-only':
        return <Eye className="h-3 w-3" />
      default:
        return <Hash className="h-3 w-3" />
    }
  }

  const columns: ColumnDefinition<Channel>[] = [
    {
      key: 'name',
      label: 'Channel',
      render: (channel) => (
        <div className="flex flex-col gap-1">
          <span className="font-medium">{channel.name}</span>
          <span className="text-xs text-muted-foreground truncate max-w-[300px]">
            {channel.description}
          </span>
        </div>
      )
    },
    {
      key: 'type',
      label: 'Type',
      width: 'w-28',
      render: (channel) => (
        <Badge variant="outline" className={`text-xs flex items-center gap-1 ${getTypeColor(channel.type)}`}>
          {getTypeIcon(channel.type)}
          {channel.type}
        </Badge>
      )
    },
    {
      key: 'member_count',
      label: 'Members',
      width: 'w-20',
      render: (channel) => (
        <div className="flex items-center gap-1 text-xs text-muted-foreground">
          <Users className="h-3 w-3" />
          <span>{channel.member_count}</span>
        </div>
      )
    },
    {
      key: 'is_active',
      label: 'Status',
      width: 'w-24',
      render: (channel) => (
        <Badge variant="outline" className={`text-xs ${channel.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}`}>
          {channel.is_active ? 'Active' : 'Inactive'}
        </Badge>
      )
    },
    {
      key: 'actions',
      label: '',
      width: 'w-12',
      render: () => (
        <div className="flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
          <ExternalLink className="h-4 w-4 text-muted-foreground" />
        </div>
      )
    }
  ]

  const filters = [
    {
      key: 'type',
      label: 'Type',
      options: [
        { value: 'all', label: 'All', count: channels.length },
        { value: 'public', label: 'Public', count: channels.filter(c => c.type === 'public').length },
        { value: 'private', label: 'Private', count: channels.filter(c => c.type === 'private').length },
        { value: 'read-only', label: 'Read Only', count: channels.filter(c => c.type === 'read-only').length }
      ]
    },
    {
      key: 'is_active',
      label: 'Status',
      options: [
        { value: 'all', label: 'All', count: channels.length },
        { value: 'true', label: 'Active', count: channels.filter(c => c.is_active).length },
        { value: 'false', label: 'Inactive', count: channels.filter(c => !c.is_active).length }
      ]
    }
  ]

  const handleChannelClick = (channel: Channel) => {
    if (onChannelSelect) {
      onChannelSelect(channel)
    } else {
      alert('Channel interaction coming soon. This will allow joining/viewing channel details.')
    }
  }

  return (
    <DataManagementModal
      isOpen={isOpen}
      onClose={onClose}
      title="Channels"
      data={channels}
      columns={columns}
      loading={loading}
      error={error ?? undefined}
      filters={filters}
      searchPlaceholder="Search channels..."
      searchFields={['name', 'description', 'type']}
      clickableRows={true}
      onRowClick={handleChannelClick}
      onRefresh={onRefresh}
      emptyStateMessage="No channels found"
      emptyStateIcon={<Hash className="h-8 w-8" />}
    />
  )
}
