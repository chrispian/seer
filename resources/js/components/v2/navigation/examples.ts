import { TabsConfig, BreadcrumbConfig, PaginationConfig, SidebarConfig } from '../types';

export const tabsExample: TabsConfig = {
  id: 'tabs-1',
  type: 'tabs',
  props: {
    defaultValue: 'overview',
    tabs: [
      {
        value: 'overview',
        label: 'Overview',
        content: [
          {
            id: 'overview-content',
            type: 'card',
            props: {
              title: 'Overview',
              description: 'View your account overview',
            },
            children: [
              {
                id: 'overview-text',
                type: 'typography.p',
                props: {
                  text: 'This is the overview tab content.',
                },
              },
            ],
          },
        ],
      },
      {
        value: 'analytics',
        label: 'Analytics',
        content: [
          {
            id: 'analytics-content',
            type: 'card',
            props: {
              title: 'Analytics',
              description: 'View your analytics',
            },
            children: [
              {
                id: 'analytics-text',
                type: 'typography.p',
                props: {
                  text: 'This is the analytics tab content.',
                },
              },
            ],
          },
        ],
      },
      {
        value: 'settings',
        label: 'Settings',
        content: [
          {
            id: 'settings-content',
            type: 'card',
            props: {
              title: 'Settings',
              description: 'Manage your settings',
            },
            children: [
              {
                id: 'settings-text',
                type: 'typography.p',
                props: {
                  text: 'This is the settings tab content.',
                },
              },
            ],
          },
        ],
      },
    ],
  },
};

export const breadcrumbExample: BreadcrumbConfig = {
  id: 'breadcrumb-1',
  type: 'breadcrumb',
  props: {
    items: [
      { label: 'Home', href: '/' },
      { label: 'Products', href: '/products' },
      { label: 'Electronics', href: '/products/electronics' },
      { label: 'Laptops', current: true },
    ],
    separator: 'chevron',
  },
};

export const breadcrumbSlashExample: BreadcrumbConfig = {
  id: 'breadcrumb-2',
  type: 'breadcrumb',
  props: {
    items: [
      { label: 'Dashboard', href: '/dashboard' },
      { label: 'Users', href: '/dashboard/users' },
      { label: 'Profile', current: true },
    ],
    separator: 'slash',
  },
};

export const paginationExample: PaginationConfig = {
  id: 'pagination-1',
  type: 'pagination',
  props: {
    currentPage: 1,
    totalPages: 10,
    showFirstLast: true,
    showPrevNext: true,
    onPageChange: {
      type: 'emit',
      event: 'page:changed',
      payload: {},
    },
  },
};

export const paginationSimpleExample: PaginationConfig = {
  id: 'pagination-2',
  type: 'pagination',
  props: {
    currentPage: 5,
    totalPages: 20,
    showFirstLast: false,
    showPrevNext: true,
    maxVisible: 5,
  },
};

export const sidebarExample: SidebarConfig = {
  id: 'sidebar-1',
  type: 'sidebar',
  props: {
    collapsible: true,
    defaultOpen: true,
    items: [
      {
        label: 'Dashboard',
        icon: 'Home',
        href: '/dashboard',
        active: true,
      },
      {
        label: 'Projects',
        icon: 'FolderKanban',
        href: '/projects',
        badge: '3',
      },
      {
        label: 'Settings',
        icon: 'Settings',
        children: [
          { label: 'Profile', href: '/settings/profile' },
          { label: 'Billing', href: '/settings/billing' },
          { label: 'Notifications', href: '/settings/notifications' },
        ],
      },
      {
        label: 'Help',
        icon: 'HelpCircle',
        href: '/help',
      },
    ],
  },
};

export const sidebarGroupedExample: SidebarConfig = {
  id: 'sidebar-2',
  type: 'sidebar',
  props: {
    collapsible: true,
    defaultOpen: true,
    groups: [
      {
        label: 'Main',
        items: [
          {
            label: 'Dashboard',
            icon: 'Home',
            href: '/dashboard',
            active: true,
          },
          {
            label: 'Analytics',
            icon: 'BarChart',
            href: '/analytics',
          },
        ],
      },
      {
        label: 'Management',
        items: [
          {
            label: 'Users',
            icon: 'Users',
            href: '/users',
            badge: '12',
          },
          {
            label: 'Teams',
            icon: 'Users2',
            children: [
              { label: 'Engineering', href: '/teams/engineering' },
              { label: 'Design', href: '/teams/design' },
              { label: 'Marketing', href: '/teams/marketing' },
            ],
          },
        ],
      },
      {
        label: 'System',
        items: [
          {
            label: 'Settings',
            icon: 'Settings',
            href: '/settings',
          },
        ],
      },
    ],
  },
};

export const navigationExamples = {
  tabs: tabsExample,
  breadcrumb: breadcrumbExample,
  breadcrumbSlash: breadcrumbSlashExample,
  pagination: paginationExample,
  paginationSimple: paginationSimpleExample,
  sidebar: sidebarExample,
  sidebarGrouped: sidebarGroupedExample,
};
