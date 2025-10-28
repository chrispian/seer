/**
 * UI Builder TypeScript Types
 * Version: 2.1.0
 * 
 * These types match the database schema and API structure
 * for UI Builder pages and components.
 */

// ============================================================================
// Database Models
// ============================================================================

export interface UiComponent {
  id: number;
  key: string;                           // component.button.default
  type: string;                          // button, input, data-table, etc.
  kind: ComponentKind;
  variant?: string;                      // default, primary, outlined, etc.
  config: Record<string, any>;
  schema_json: ComponentSchema | null;
  defaults_json: Record<string, any> | null;
  capabilities_json: string[] | null;
  hash: string;
  version: number;
  created_at: string;
  updated_at: string;
}

export interface UiPage {
  id: number;
  key: string;                           // page.ui-builder.pages.list
  route: string | null;                  // /ui/pages
  module_key: string | null;             // core.ui-builder
  enabled: boolean;
  config: PageConfig;
  meta_json: Record<string, any> | null;
  guards_json: string[] | null;
  hash: string;
  version: number;
  created_at: string;
  updated_at: string;
}

export interface UiModule {
  id: number;
  key: string;                           // core.ui-builder
  name: string;
  description: string | null;
  enabled: boolean;
  config: Record<string, any>;
  created_at: string;
  updated_at: string;
}

// ============================================================================
// Component System
// ============================================================================

export type ComponentKind = 
  | 'primitive'      // Basic UI elements (button, input, label)
  | 'composite'      // Complex components (data-table, form)
  | 'container';     // Layout components (card, panel, section)

export interface ComponentSchema {
  props: Record<string, PropSchema>;
  slots?: Record<string, SlotSchema>;
  events?: Record<string, EventSchema>;
}

export interface PropSchema {
  type: 'string' | 'number' | 'boolean' | 'array' | 'object' | 'action';
  required?: boolean;
  default?: any;
  description?: string;
  enum?: any[];
}

export interface SlotSchema {
  description?: string;
  required?: boolean;
}

export interface EventSchema {
  payload?: Record<string, PropSchema>;
  description?: string;
}

// ============================================================================
// Page Configuration
// ============================================================================

export interface PageConfig {
  id: string;                            // Unique page identifier
  overlay: OverlayType;
  title: string;
  layout: LayoutConfig;
  meta?: Record<string, any>;
}

export type OverlayType = 
  | 'page'           // Full page
  | 'modal'          // Modal dialog
  | 'drawer';        // Side drawer

export interface LayoutConfig {
  type: LayoutType;
  id: string;
  children: ComponentConfig[];
  props?: Record<string, any>;
}

export type LayoutType = 
  | 'rows'           // Vertical stack
  | 'columns'        // Horizontal stack
  | 'grid'           // CSS grid
  | 'stack';         // Layered (z-index)

// ============================================================================
// Component Configuration (in Pages)
// ============================================================================

export interface ComponentConfig {
  id: string;                            // Unique instance ID in page
  type: string;                          // Component type (button, data-table, etc.)
  props?: ComponentProps;                // Component-specific properties
  actions?: ComponentActions;            // Event handlers
  result?: SearchResultConfig;           // For search components
  children?: ComponentConfig[];          // For container components
}

export interface ComponentProps extends Record<string, any> {
  // Common props (not all components use all props)
  label?: string;
  placeholder?: string;
  variant?: string;
  className?: string;
  disabled?: boolean;
  
  // Data table specific
  dataSource?: string;
  columns?: ColumnConfig[];
  rowAction?: ActionConfig;
  pagination?: {
    enabled: boolean;
    pageSize: number;
  };
  selection?: {
    enabled: boolean;
    type: 'single' | 'multiple';
  };
  
  // Toolbar specific
  items?: ComponentConfig[];
}

export interface ComponentActions {
  click?: ActionConfig;
  rowClick?: ActionConfig;
  submit?: ActionConfig;
  [key: string]: ActionConfig | undefined;
}

export interface SearchResultConfig {
  target: string;                        // Target component ID
  open: 'inline' | 'modal';
}

// ============================================================================
// Actions
// ============================================================================

export type ActionConfig = 
  | CommandAction
  | ModalAction
  | NavigateAction
  | HttpAction
  | EmitAction;

export interface CommandAction {
  type: 'command';
  command: string;                       // Command to execute
  params?: Record<string, any>;
}

export interface ModalAction {
  type: 'modal';
  title: string;
  url: string;                           // URL with {{row.id}} template
  fields: FieldConfig[];
  modal?: 'detail' | 'form';
}

export interface NavigateAction {
  type: 'navigate';
  url: string;
}

export interface HttpAction {
  type: 'http';
  url: string;
  method: 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE';
  payload?: Record<string, any>;
}

export interface EmitAction {
  type: 'emit';
  event: string;
  payload?: Record<string, any>;
}

export interface FieldConfig {
  key: string;                           // Data key to display
  label: string;                         // Display label
  type: FieldType;
  render?: string;                       // Custom renderer
}

export type FieldType = 
  | 'text'
  | 'date'
  | 'datetime'
  | 'number'
  | 'boolean'
  | 'badge'
  | 'json';

// ============================================================================
// Data Table
// ============================================================================

export interface ColumnConfig {
  key: string;                           // Data key
  label: string;                         // Column header
  sortable?: boolean;
  filterable?: boolean;
  render?: string;                       // badge, date, etc.
  width?: string | number;
}

// ============================================================================
// API Responses
// ============================================================================

export interface ApiResponse<T> {
  data: T;
}

export interface ApiListResponse<T> {
  data: T[];
  meta: {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
  };
}

export interface ApiErrorResponse {
  error: string;
}

// ============================================================================
// DataSource Query
// ============================================================================

export interface DataSourceQueryParams {
  search?: string;
  page?: number;
  per_page?: number;
  sort?: string;
  direction?: 'asc' | 'desc';
  filters?: Record<string, any>;
}

// ============================================================================
// Common Component Types
// ============================================================================

export interface ButtonProps {
  label: string;
  variant?: 'default' | 'primary' | 'secondary' | 'ghost' | 'link';
  size?: 'sm' | 'md' | 'lg';
  disabled?: boolean;
  icon?: string;
}

export interface DataTableProps {
  dataSource: string;                    // Agent, UiPage, UiComponent, etc.
  columns: ColumnConfig[];
  rowAction?: ActionConfig;
  toolbar?: ComponentConfig[];
  pagination?: {
    enabled: boolean;
    pageSize: number;
  };
  selection?: {
    enabled: boolean;
    type: 'single' | 'multiple';
  };
  emptyText?: string;
}

export interface SearchBarProps {
  placeholder?: string;
  debounce?: number;
  className?: string;
}

export interface CardProps {
  title?: string;
  description?: string;
  variant?: 'default' | 'outlined' | 'filled';
  padding?: string;
  className?: string;
}

export interface FormProps {
  fields: FormFieldConfig[];
  submitLabel?: string;
  cancelLabel?: string;
  onSubmit?: ActionConfig;
}

export interface FormFieldConfig {
  name: string;
  label: string;
  type: 'text' | 'email' | 'password' | 'number' | 'select' | 'textarea' | 'checkbox' | 'radio';
  required?: boolean;
  placeholder?: string;
  defaultValue?: any;
  options?: Array<{ label: string; value: any }>;
  validation?: {
    pattern?: string;
    min?: number;
    max?: number;
    minLength?: number;
    maxLength?: number;
  };
}

// ============================================================================
// Type Guards
// ============================================================================

export function isPageConfig(obj: any): obj is PageConfig {
  return (
    typeof obj === 'object' &&
    obj !== null &&
    typeof obj.id === 'string' &&
    typeof obj.title === 'string' &&
    typeof obj.overlay === 'string' &&
    typeof obj.layout === 'object'
  );
}

export function isComponentConfig(obj: any): obj is ComponentConfig {
  return (
    typeof obj === 'object' &&
    obj !== null &&
    typeof obj.id === 'string' &&
    typeof obj.type === 'string'
  );
}

export function isLayoutConfig(obj: any): obj is LayoutConfig {
  return (
    typeof obj === 'object' &&
    obj !== null &&
    typeof obj.type === 'string' &&
    typeof obj.id === 'string' &&
    Array.isArray(obj.children)
  );
}

// ============================================================================
// Validation Helpers
// ============================================================================

export class PageConfigValidator {
  static validate(config: unknown): { valid: boolean; errors: string[] } {
    const errors: string[] = [];
    
    if (!isPageConfig(config)) {
      errors.push('Invalid page config structure');
      return { valid: false, errors };
    }
    
    // Validate required fields
    if (!config.id) errors.push('Missing page id');
    if (!config.title) errors.push('Missing page title');
    if (!config.overlay) errors.push('Missing overlay type');
    if (!config.layout) errors.push('Missing layout configuration');
    
    // Validate layout
    if (!isLayoutConfig(config.layout)) {
      errors.push('Invalid layout configuration');
    } else {
      // Validate children are component configs
      config.layout.children.forEach((child, index) => {
        if (!isComponentConfig(child)) {
          errors.push(`Invalid component config at index ${index}`);
        }
      });
    }
    
    return { valid: errors.length === 0, errors };
  }
}

// ============================================================================
// Builder Helpers
// ============================================================================

export class PageConfigBuilder {
  private config: Partial<PageConfig> = {};
  
  setId(id: string): this {
    this.config.id = id;
    return this;
  }
  
  setTitle(title: string): this {
    this.config.title = title;
    return this;
  }
  
  setOverlay(overlay: OverlayType): this {
    this.config.overlay = overlay;
    return this;
  }
  
  setLayout(layout: LayoutConfig): this {
    this.config.layout = layout;
    return this;
  }
  
  build(): PageConfig {
    const result = PageConfigValidator.validate(this.config);
    if (!result.valid) {
      throw new Error(`Invalid page config: ${result.errors.join(', ')}`);
    }
    return this.config as PageConfig;
  }
}

export class ComponentConfigBuilder {
  private config: Partial<ComponentConfig> = {};
  
  setId(id: string): this {
    this.config.id = id;
    return this;
  }
  
  setType(type: string): this {
    this.config.type = type;
    return this;
  }
  
  setProps(props: ComponentProps): this {
    this.config.props = props;
    return this;
  }
  
  setActions(actions: ComponentActions): this {
    this.config.actions = actions;
    return this;
  }
  
  addChild(child: ComponentConfig): this {
    if (!this.config.children) {
      this.config.children = [];
    }
    this.config.children.push(child);
    return this;
  }
  
  build(): ComponentConfig {
    if (!this.config.id || !this.config.type) {
      throw new Error('Component must have id and type');
    }
    return this.config as ComponentConfig;
  }
}
