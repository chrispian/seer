export interface BaseComponentConfig {
  id: string;
  type: string;
  props?: Record<string, any>;
  actions?: Record<string, ActionConfig>;
  children?: ComponentConfig[];
}

export interface ActionConfig {
  type: 'command' | 'navigate' | 'emit' | 'http';
  command?: string;
  url?: string;
  event?: string;
  method?: 'GET' | 'POST' | 'PUT' | 'DELETE' | 'PATCH';
  payload?: Record<string, any>;
}

export interface ComponentConfig extends BaseComponentConfig {}

export interface ButtonConfig extends BaseComponentConfig {
  type: 'button' | 'button.icon' | 'button.text';
  props: {
    label?: string;
    icon?: string;
    variant?: 'default' | 'destructive' | 'outline' | 'secondary' | 'ghost' | 'link';
    size?: 'default' | 'sm' | 'lg' | 'icon';
    disabled?: boolean;
    loading?: boolean;
    className?: string;
  };
}

export interface InputConfig extends BaseComponentConfig {
  type: 'input' | 'input.text' | 'input.email' | 'input.password' | 'input.number';
  props: {
    placeholder?: string;
    value?: string;
    defaultValue?: string;
    disabled?: boolean;
    readonly?: boolean;
    required?: boolean;
    type?: string;
    name?: string;
    className?: string;
  };
}

export interface LabelConfig extends BaseComponentConfig {
  type: 'label';
  props: {
    text: string;
    htmlFor?: string;
    required?: boolean;
    className?: string;
  };
}

export interface BadgeConfig extends BaseComponentConfig {
  type: 'badge';
  props: {
    text: string;
    variant?: 'default' | 'secondary' | 'destructive' | 'outline';
    className?: string;
  };
}

export interface AvatarConfig extends BaseComponentConfig {
  type: 'avatar';
  props: {
    src?: string;
    alt?: string;
    fallback?: string;
    size?: 'sm' | 'md' | 'lg' | 'xl';
    className?: string;
  };
}

export interface SkeletonConfig extends BaseComponentConfig {
  type: 'skeleton';
  props: {
    variant?: 'text' | 'circular' | 'rectangular';
    width?: string;
    height?: string;
    lines?: number;
    animate?: boolean;
    className?: string;
  };
}

export interface SpinnerConfig extends BaseComponentConfig {
  type: 'spinner';
  props: {
    size?: 'sm' | 'md' | 'lg';
    className?: string;
  };
}

export interface SeparatorConfig extends BaseComponentConfig {
  type: 'separator';
  props: {
    orientation?: 'horizontal' | 'vertical';
    decorative?: boolean;
    className?: string;
  };
}

export interface KbdConfig extends BaseComponentConfig {
  type: 'kbd';
  props: {
    keys: string[];
    className?: string;
  };
}

export interface TypographyConfig extends BaseComponentConfig {
  type: 'typography' | 'typography.h1' | 'typography.h2' | 'typography.h3' | 'typography.h4' | 'typography.h5' | 'typography.h6' | 'typography.p' | 'typography.blockquote' | 'typography.code' | 'typography.lead' | 'typography.large' | 'typography.small' | 'typography.muted';
  props: {
    text?: string;
    variant?: 'h1' | 'h2' | 'h3' | 'h4' | 'h5' | 'h6' | 'p' | 'blockquote' | 'code' | 'lead' | 'large' | 'small' | 'muted';
    className?: string;
  };
}

export interface CheckboxConfig extends BaseComponentConfig {
  type: 'checkbox';
  props: {
    label?: string;
    checked?: boolean;
    defaultChecked?: boolean;
    disabled?: boolean;
    required?: boolean;
    name?: string;
    value?: string;
    className?: string;
  };
}

export interface RadioGroupConfig extends BaseComponentConfig {
  type: 'radio-group';
  props: {
    options: Array<{ label: string; value: string; disabled?: boolean }>;
    value?: string;
    defaultValue?: string;
    disabled?: boolean;
    required?: boolean;
    name?: string;
    orientation?: 'horizontal' | 'vertical';
    className?: string;
  };
}

export interface SwitchConfig extends BaseComponentConfig {
  type: 'switch';
  props: {
    label?: string;
    checked?: boolean;
    defaultChecked?: boolean;
    disabled?: boolean;
    required?: boolean;
    name?: string;
    className?: string;
  };
}

export interface SliderConfig extends BaseComponentConfig {
  type: 'slider';
  props: {
    min?: number;
    max?: number;
    step?: number;
    value?: number[];
    defaultValue?: number[];
    disabled?: boolean;
    name?: string;
    className?: string;
  };
}

export interface TextareaConfig extends BaseComponentConfig {
  type: 'textarea';
  props: {
    placeholder?: string;
    value?: string;
    defaultValue?: string;
    disabled?: boolean;
    readonly?: boolean;
    required?: boolean;
    rows?: number;
    name?: string;
    className?: string;
  };
}

export interface SelectConfig extends BaseComponentConfig {
  type: 'select';
  props: {
    options: Array<{ label: string; value: string; disabled?: boolean }>;
    placeholder?: string;
    value?: string;
    defaultValue?: string;
    disabled?: boolean;
    required?: boolean;
    name?: string;
    className?: string;
  };
}

export interface FieldConfig extends BaseComponentConfig {
  type: 'field';
  props: {
    label: string;
    required?: boolean;
    error?: string;
    helperText?: string;
    className?: string;
  };
  children?: [InputConfig | TextareaConfig | SelectConfig | CheckboxConfig | SwitchConfig | RadioGroupConfig | SliderConfig];
}

export interface AlertConfig extends BaseComponentConfig {
  type: 'alert';
  props: {
    variant?: 'default' | 'destructive' | 'warning' | 'success';
    title?: string;
    description: string;
    icon?: string;
    dismissible?: boolean;
    className?: string;
  };
}

export interface ProgressConfig extends BaseComponentConfig {
  type: 'progress';
  props: {
    value: number;
    showLabel?: boolean;
    variant?: 'default' | 'success' | 'error' | 'warning';
    size?: 'sm' | 'default' | 'lg';
    className?: string;
  };
}

export interface ToastConfig extends BaseComponentConfig {
  type: 'toast';
  props: {
    title: string;
    description?: string;
    variant?: 'default' | 'destructive' | 'success' | 'warning';
    duration?: number;
  };
}

export interface EmptyConfig extends BaseComponentConfig {
  type: 'empty';
  props: {
    icon?: string;
    title: string;
    description?: string;
    action?: ButtonConfig;
    className?: string;
  };
}

export interface CardConfig extends BaseComponentConfig {
  type: 'card';
  props: {
    title?: string;
    description?: string;
    footer?: ComponentConfig;
    className?: string;
  };
  children?: ComponentConfig[];
}

export interface ScrollAreaConfig extends BaseComponentConfig {
  type: 'scroll-area';
  props: {
    height?: string;
    maxHeight?: string;
    orientation?: 'vertical' | 'horizontal';
    className?: string;
  };
  children?: ComponentConfig[];
}

export interface ResizableConfig extends BaseComponentConfig {
  type: 'resizable';
  props: {
    direction?: 'horizontal' | 'vertical';
    panels: Array<{
      id: string;
      defaultSize?: number;
      minSize?: number;
      maxSize?: number;
      content: ComponentConfig[];
    }>;
    withHandle?: boolean;
    className?: string;
  };
}

export interface AspectRatioConfig extends BaseComponentConfig {
  type: 'aspect-ratio';
  props: {
    ratio?: number | string;
    className?: string;
  };
  children?: ComponentConfig[];
}

export interface CollapsibleConfig extends BaseComponentConfig {
  type: 'collapsible';
  props: {
    title: string;
    defaultOpen?: boolean;
    disabled?: boolean;
    triggerClassName?: string;
    contentClassName?: string;
    className?: string;
  };
  children?: ComponentConfig[];
}

export interface AccordionConfig extends BaseComponentConfig {
  type: 'accordion';
  props: {
    type?: 'single' | 'multiple';
    collapsible?: boolean;
    defaultValue?: string | string[];
    items: Array<{
      value: string;
      title: string;
      content: ComponentConfig[];
      disabled?: boolean;
    }>;
    className?: string;
  };
}

export interface TabsConfig extends BaseComponentConfig {
  type: 'tabs';
  props: {
    defaultValue: string;
    tabs: Array<{
      value: string;
      label: string;
      content: ComponentConfig[];
      disabled?: boolean;
    }>;
    className?: string;
    listClassName?: string;
  };
}

export interface BreadcrumbConfig extends BaseComponentConfig {
  type: 'breadcrumb';
  props: {
    items: Array<{
      label: string;
      href?: string;
      current?: boolean;
    }>;
    separator?: 'chevron' | 'slash' | 'none';
    className?: string;
  };
}

export interface PaginationConfig extends BaseComponentConfig {
  type: 'pagination';
  props: {
    currentPage: number;
    totalPages: number;
    onPageChange?: ActionConfig;
    showFirstLast?: boolean;
    showPrevNext?: boolean;
    maxVisible?: number;
    className?: string;
  };
}

export interface SidebarConfig extends BaseComponentConfig {
  type: 'sidebar';
  props: {
    collapsible?: boolean;
    defaultOpen?: boolean;
    side?: 'left' | 'right';
    variant?: 'sidebar' | 'floating' | 'inset';
    items?: Array<{
      label: string;
      icon?: string;
      href?: string;
      badge?: string;
      active?: boolean;
      children?: Array<{
        label: string;
        href: string;
        active?: boolean;
      }>;
    }>;
    groups?: Array<{
      label: string;
      items: Array<{
        label: string;
        icon?: string;
        href?: string;
        badge?: string;
        active?: boolean;
        children?: Array<{
          label: string;
          href: string;
          active?: boolean;
        }>;
      }>;
    }>;
    className?: string;
  };
}

export interface DialogConfig extends BaseComponentConfig {
  type: 'dialog';
  props: {
    title: string;
    description?: string;
    trigger?: ComponentConfig;
    content: ComponentConfig[];
    footer?: ComponentConfig[];
    size?: 'sm' | 'md' | 'lg' | 'xl' | 'full';
    closeButton?: boolean;
    defaultOpen?: boolean;
    className?: string;
  };
}

export interface PopoverConfig extends BaseComponentConfig {
  type: 'popover';
  props: {
    trigger: ComponentConfig;
    content: ComponentConfig[];
    side?: 'top' | 'right' | 'bottom' | 'left';
    align?: 'start' | 'center' | 'end';
    defaultOpen?: boolean;
    className?: string;
  };
}

export interface TooltipConfig extends BaseComponentConfig {
  type: 'tooltip';
  props: {
    content: string | ComponentConfig;
    side?: 'top' | 'right' | 'bottom' | 'left';
    delay?: number;
    className?: string;
  };
}

export interface SheetConfig extends BaseComponentConfig {
  type: 'sheet';
  props: {
    title: string;
    description?: string;
    side?: 'top' | 'right' | 'bottom' | 'left';
    trigger?: ComponentConfig;
    content: ComponentConfig[];
    footer?: ComponentConfig[];
    defaultOpen?: boolean;
    className?: string;
  };
}

export interface DrawerConfig extends BaseComponentConfig {
  type: 'drawer';
  props: {
    title: string;
    description?: string;
    trigger?: ComponentConfig;
    content: ComponentConfig[];
    footer?: ComponentConfig[];
    direction?: 'bottom' | 'left' | 'right';
    defaultOpen?: boolean;
    className?: string;
  };
}

export interface NavigationMenuConfig extends BaseComponentConfig {
  type: 'navigation-menu';
  props: {
    items: Array<{
      label: string;
      trigger?: 'hover' | 'click';
      content?: ComponentConfig[];
      href?: string;
      items?: Array<{
        label: string;
        href: string;
        description?: string;
        icon?: string;
      }>;
    }>;
    orientation?: 'horizontal' | 'vertical';
    className?: string;
  };
}

export interface CommandConfig extends BaseComponentConfig {
  type: 'command';
  props: {
    placeholder?: string;
    emptyText?: string;
    groups: Array<{
      heading?: string;
      items: Array<{
        label: string;
        icon?: string;
        shortcut?: string;
        value?: string;
        disabled?: boolean;
      }>;
    }>;
    open?: boolean;
    defaultOpen?: boolean;
    showShortcut?: boolean;
    className?: string;
  };
}

export interface ComboboxConfig extends BaseComponentConfig {
  type: 'combobox';
  props: {
    placeholder?: string;
    emptyText?: string;
    searchPlaceholder?: string;
    options: Array<{
      value: string;
      label: string;
      icon?: string;
      disabled?: boolean;
    }>;
    value?: string;
    defaultValue?: string;
    searchable?: boolean;
    disabled?: boolean;
    className?: string;
  };
}

export interface MenuItemConfig {
  type: 'item' | 'checkbox' | 'radio' | 'separator' | 'label' | 'submenu';
  label?: string;
  icon?: string;
  shortcut?: string;
  disabled?: boolean;
  checked?: boolean;
  value?: string;
  items?: MenuItemConfig[];
  action?: ActionConfig;
}

export interface DropdownMenuConfig extends BaseComponentConfig {
  type: 'dropdown-menu';
  props: {
    trigger: ComponentConfig;
    items: MenuItemConfig[];
    align?: 'start' | 'center' | 'end';
    side?: 'top' | 'right' | 'bottom' | 'left';
    className?: string;
  };
}

export interface ContextMenuConfig extends BaseComponentConfig {
  type: 'context-menu';
  props: {
    items: MenuItemConfig[];
    className?: string;
  };
  children?: ComponentConfig[];
}

export interface MenubarConfig extends BaseComponentConfig {
  type: 'menubar';
  props: {
    menus: Array<{
      label: string;
      items: MenuItemConfig[];
    }>;
    className?: string;
  };
}

export interface HoverCardConfig extends BaseComponentConfig {
  type: 'hover-card';
  props: {
    trigger: ComponentConfig;
    content: ComponentConfig[];
    openDelay?: number;
    closeDelay?: number;
    side?: 'top' | 'right' | 'bottom' | 'left';
    align?: 'start' | 'center' | 'end';
    className?: string;
  };
}

export interface FormFieldValidation {
  required?: boolean;
  min?: number;
  max?: number;
  pattern?: string;
  custom?: string;
}

export interface FormField {
  name: string;
  label: string;
  field: ComponentConfig;
  validation?: FormFieldValidation;
  helperText?: string;
}

export interface FormConfig extends BaseComponentConfig {
  type: 'form';
  props: {
    fields: FormField[];
    submitButton?: ComponentConfig;
    onSubmit?: ActionConfig;
    className?: string;
  };
}

export interface InputGroupConfig extends BaseComponentConfig {
  type: 'input-group';
  props: {
    prefix?: string | ComponentConfig;
    suffix?: string | ComponentConfig;
    input: ComponentConfig;
    className?: string;
  };
}

export interface InputOTPConfig extends BaseComponentConfig {
  type: 'input-otp';
  props: {
    length?: number;
    className?: string;
  };
}

export interface DatePickerConfig extends BaseComponentConfig {
  type: 'date-picker';
  props: {
    value?: string;
    placeholder?: string;
    format?: string;
    disabled?: boolean;
    className?: string;
  };
}

export interface CalendarConfig extends BaseComponentConfig {
  type: 'calendar';
  props: {
    value?: string | string[];
    mode?: 'single' | 'multiple' | 'range';
    className?: string;
  };
}

export interface ButtonGroupButton {
  value: string;
  label: string;
  icon?: string;
}

export interface ButtonGroupConfig extends BaseComponentConfig {
  type: 'button-group';
  props: {
    buttons: ButtonGroupButton[];
    value?: string;
    className?: string;
  };
}

export interface ToggleConfig extends BaseComponentConfig {
  type: 'toggle';
  props: {
    pressed?: boolean;
    label?: string;
    icon?: string;
    variant?: 'default' | 'outline';
    size?: 'default' | 'sm' | 'lg';
    disabled?: boolean;
  };
}

export interface ToggleGroupItem {
  value: string;
  label: string;
  icon?: string;
}

export interface ToggleGroupConfig extends BaseComponentConfig {
  type: 'toggle-group';
  props: {
    type?: 'single' | 'multiple';
    items: ToggleGroupItem[];
    value?: string | string[];
    variant?: 'default' | 'outline';
    size?: 'default' | 'sm' | 'lg';
    className?: string;
  };
}

export interface ItemConfig extends BaseComponentConfig {
  type: 'item';
  props: {
    title: string;
    description?: string;
    icon?: string;
    avatar?: string;
    badge?: string;
    trailing?: ComponentConfig;
    className?: string;
  };
}

export interface DataTableColumnConfig {
  key: string;
  label: string;
  sortable?: boolean;
  filterable?: boolean;
  render?: 'text' | 'badge' | 'avatar' | 'actions' | 'custom';
  width?: string;
  align?: 'left' | 'center' | 'right';
}

export interface DataTableConfig extends BaseComponentConfig {
  type: 'data-table';
  props: {
    columns: DataTableColumnConfig[];
    data: any[];
    pagination?: {
      enabled: boolean;
      pageSize: number;
    };
    selection?: {
      enabled: boolean;
      type: 'single' | 'multiple';
    };
    actions?: {
      rowClick?: ActionConfig;
      rowActions?: ComponentConfig[];
    };
    loading?: boolean;
    emptyText?: string;
    className?: string;
  };
}

export interface ChartDataPoint {
  label: string;
  value: number;
  [key: string]: any;
}

export interface ChartConfig extends BaseComponentConfig {
  type: 'chart';
  props: {
    chartType: 'bar' | 'line' | 'pie' | 'area' | 'donut';
    data: ChartDataPoint[];
    title?: string;
    legend?: boolean;
    colors?: string[];
    height?: number;
    xAxisKey?: string;
    yAxisKey?: string;
    showGrid?: boolean;
    showTooltip?: boolean;
    className?: string;
  };
}

export interface CarouselConfig extends BaseComponentConfig {
  type: 'carousel';
  props: {
    items: ComponentConfig[];
    autoplay?: boolean;
    interval?: number;
    loop?: boolean;
    showDots?: boolean;
    showArrows?: boolean;
    className?: string;
  };
}

export interface SonnerConfig extends BaseComponentConfig {
  type: 'sonner';
  props: {
    message: string;
    description?: string;
    action?: {
      label: string;
      action: ActionConfig;
    };
    duration?: number;
    position?: 'top-left' | 'top-center' | 'top-right' | 'bottom-left' | 'bottom-center' | 'bottom-right';
    variant?: 'default' | 'success' | 'error' | 'warning' | 'info';
    className?: string;
  };
}
