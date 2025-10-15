import {
  ButtonConfig,
  InputConfig,
  LabelConfig,
  BadgeConfig,
  AvatarConfig,
  SkeletonConfig,
  SpinnerConfig,
  SeparatorConfig,
  KbdConfig,
  TypographyConfig,
} from './types';

export const exampleButton: ButtonConfig = {
  id: 'btn-save',
  type: 'button',
  props: {
    label: 'Save Changes',
    variant: 'default',
    size: 'default',
    disabled: false,
    loading: false,
  },
  actions: {
    click: {
      type: 'command',
      command: 'save:changes',
    },
  },
};

export const exampleIconButton: ButtonConfig = {
  id: 'btn-delete',
  type: 'button.icon',
  props: {
    icon: '🗑️',
    variant: 'destructive',
    size: 'icon',
  },
  actions: {
    click: {
      type: 'command',
      command: 'delete:item',
    },
  },
};

export const exampleInput: InputConfig = {
  id: 'input-email',
  type: 'input.email',
  props: {
    placeholder: 'Enter your email',
    type: 'email',
    name: 'email',
    required: true,
  },
  actions: {
    change: {
      type: 'emit',
      event: 'email:changed',
    },
  },
};

export const exampleLabel: LabelConfig = {
  id: 'label-email',
  type: 'label',
  props: {
    text: 'Email Address',
    htmlFor: 'input-email',
    required: true,
  },
};

export const exampleBadge: BadgeConfig = {
  id: 'badge-status',
  type: 'badge',
  props: {
    text: 'Active',
    variant: 'default',
  },
};

export const exampleBadgeDestructive: BadgeConfig = {
  id: 'badge-error',
  type: 'badge',
  props: {
    text: 'Error',
    variant: 'destructive',
  },
};

export const exampleAvatar: AvatarConfig = {
  id: 'avatar-user',
  type: 'avatar',
  props: {
    src: 'https://github.com/shadcn.png',
    alt: 'User avatar',
    fallback: 'CN',
    size: 'md',
  },
};

export const exampleSkeleton: SkeletonConfig = {
  id: 'skeleton-loading',
  type: 'skeleton',
  props: {
    variant: 'rectangular',
    width: '100%',
    height: '20px',
    lines: 3,
    animate: true,
  },
};

export const exampleSpinner: SpinnerConfig = {
  id: 'spinner-loading',
  type: 'spinner',
  props: {
    size: 'md',
  },
};

export const exampleSeparator: SeparatorConfig = {
  id: 'separator-horizontal',
  type: 'separator',
  props: {
    orientation: 'horizontal',
    decorative: true,
  },
};

export const exampleKbd: KbdConfig = {
  id: 'kbd-shortcut',
  type: 'kbd',
  props: {
    keys: ['⌘', 'K'],
  },
};

export const exampleTypographyH1: TypographyConfig = {
  id: 'typo-heading',
  type: 'typography.h1',
  props: {
    text: 'Welcome to UI Builder v2',
    variant: 'h1',
  },
};

export const exampleTypographyP: TypographyConfig = {
  id: 'typo-paragraph',
  type: 'typography.p',
  props: {
    text: 'This is a config-driven typography component with full Shadcn parity.',
    variant: 'p',
  },
};

export const exampleTypographyMuted: TypographyConfig = {
  id: 'typo-muted',
  type: 'typography.muted',
  props: {
    text: 'Last updated 2 hours ago',
    variant: 'muted',
  },
};
