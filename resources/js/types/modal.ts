export interface BaseModalProps {
  isOpen: boolean
  onClose: () => void
  data: any
  config?: ConfigObject
  onRefresh?: () => void
  onItemSelect?: (item: any) => void
  onBack?: () => void
}

export interface ConfigObject {
  type?: {
    slug: string
    display_name: string
    plural_name?: string
    storage_type: 'model' | 'fragment'
    default_card_component?: string
    default_detail_component?: string
    icon?: string
    color?: string
  }
  ui?: {
    modal_container?: string
    layout_mode?: 'table' | 'grid' | 'list' | 'kanban'
    card_component?: string
    detail_component?: string
    filters?: Record<string, any>
    default_sort?: {
      field: string
      direction: 'asc' | 'desc'
    }
    pagination_default?: number
  }
  command?: {
    command: string
    name: string
    description?: string
    category?: string
  }
}
