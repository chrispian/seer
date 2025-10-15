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
