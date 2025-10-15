export interface PageConfig {
  id: string
  overlay?: 'modal' | 'sheet' | 'page'
  title?: string
  components: ComponentConfig[]
}

export interface ComponentConfig {
  id: string
  type: string
  dataSource?: string
  resolver?: string
  props?: Record<string, any>
  actions?: Record<string, ActionConfig>
  result?: ResultConfig
  columns?: ColumnConfig[]
  rowAction?: ActionConfig
  toolbar?: ComponentConfig[]
  submit?: boolean
}

export interface ColumnConfig {
  key: string
  label: string
  sortable?: boolean
  filterable?: boolean
}

export interface ActionConfig {
  type: 'command' | 'navigate' | 'datasource' | 'api' | 'modal'
  command?: string
  route?: string
  params?: Record<string, any>
  dataSource?: string
  url?: string
  method?: string
  data?: Record<string, any>
  modal?: string
  title?: string
  fields?: Array<{
    name: string
    label: string
    type: 'text' | 'textarea' | 'select' | 'file'
    required?: boolean
    placeholder?: string
    options?: Array<{ value: string; label: string }>
    accept?: string
  }>
  submitUrl?: string
  submitMethod?: string
  submitLabel?: string
  refreshTarget?: string
}

export interface ResultConfig {
  target: string
  open: 'inline' | 'modal'
}

export interface DataSourceQuery {
  dataSource: string
  filters?: Record<string, any>
  search?: string
  sort?: { field: string; direction: 'asc' | 'desc' }
  pagination?: { page: number; perPage: number }
}

export interface DataSourceResult<T = any> {
  data: T[]
  meta?: {
    total?: number
    page?: number
    perPage?: number
    lastPage?: number
  }
}

export interface ActionRequest {
  type: 'command' | 'navigate'
  command?: string
  route?: string
  params?: Record<string, any>
}

export interface ActionResult {
  success: boolean
  message?: string
  data?: any
  redirect?: string
}

export interface DataSourceCapabilities {
  search?: boolean
  filter?: boolean
  sort?: boolean
  pagination?: boolean
}
