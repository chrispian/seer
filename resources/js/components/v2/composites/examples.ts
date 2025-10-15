import {
  DropdownMenuConfig,
  ContextMenuConfig,
  MenubarConfig,
  HoverCardConfig,
} from '../types';

export const dropdownMenuExamples: DropdownMenuConfig[] = [
  {
    id: 'dropdown-menu-1',
    type: 'dropdown-menu',
    props: {
      trigger: {
        id: 'trigger-1',
        type: 'button',
        props: {
          label: 'Open Menu',
          variant: 'outline',
        },
      },
      items: [
        { type: 'label', label: 'My Account' },
        { type: 'separator' },
        { type: 'item', label: 'Profile', icon: 'User', shortcut: '⌘P' },
        { type: 'item', label: 'Settings', icon: 'Settings', shortcut: '⌘S' },
        { type: 'separator' },
        { type: 'item', label: 'Logout', icon: 'LogOut' },
      ],
      align: 'start',
    },
  },
  {
    id: 'dropdown-menu-2',
    type: 'dropdown-menu',
    props: {
      trigger: {
        id: 'trigger-2',
        type: 'button',
        props: {
          label: 'View Options',
          variant: 'ghost',
        },
      },
      items: [
        { type: 'checkbox', label: 'Show Toolbar', checked: true },
        { type: 'checkbox', label: 'Show Sidebar', checked: false },
        { type: 'checkbox', label: 'Show Footer', checked: true },
        { type: 'separator' },
        {
          type: 'submenu',
          label: 'More Options',
          icon: 'MoreHorizontal',
          items: [
            { type: 'item', label: 'Reset Layout' },
            { type: 'item', label: 'Export Settings' },
          ],
        },
      ],
    },
  },
  {
    id: 'dropdown-menu-3',
    type: 'dropdown-menu',
    props: {
      trigger: {
        id: 'trigger-3',
        type: 'button',
        props: {
          label: 'Theme',
          variant: 'outline',
          icon: 'Palette',
        },
      },
      items: [
        { type: 'radio', label: 'Light', value: 'light' },
        { type: 'radio', label: 'Dark', value: 'dark' },
        { type: 'radio', label: 'System', value: 'system' },
      ],
    },
  },
];

export const contextMenuExamples: ContextMenuConfig[] = [
  {
    id: 'context-menu-1',
    type: 'context-menu',
    props: {
      items: [
        { type: 'item', label: 'Copy', icon: 'Copy', shortcut: '⌘C' },
        { type: 'item', label: 'Cut', icon: 'Scissors', shortcut: '⌘X' },
        { type: 'item', label: 'Paste', icon: 'Clipboard', shortcut: '⌘V' },
        { type: 'separator' },
        { type: 'item', label: 'Delete', icon: 'Trash' },
      ],
    },
    children: [
      {
        id: 'card-1',
        type: 'card',
        props: {
          title: 'Right-click me',
          description: 'Try right-clicking on this card',
          className: 'w-64 cursor-context-menu',
        },
      },
    ],
  },
  {
    id: 'context-menu-2',
    type: 'context-menu',
    props: {
      items: [
        { type: 'item', label: 'Open', icon: 'ExternalLink' },
        { type: 'item', label: 'Open in New Tab', icon: 'ExternalLink' },
        { type: 'separator' },
        {
          type: 'submenu',
          label: 'Share',
          icon: 'Share2',
          items: [
            { type: 'item', label: 'Copy Link', icon: 'Link' },
            { type: 'item', label: 'Email', icon: 'Mail' },
            { type: 'item', label: 'Twitter', icon: 'Twitter' },
          ],
        },
        { type: 'separator' },
        { type: 'item', label: 'Download', icon: 'Download' },
      ],
    },
    children: [
      {
        id: 'badge-1',
        type: 'badge',
        props: {
          text: 'Right-click for options',
          variant: 'outline',
        },
      },
    ],
  },
];

export const menubarExamples: MenubarConfig[] = [
  {
    id: 'menubar-1',
    type: 'menubar',
    props: {
      menus: [
        {
          label: 'File',
          items: [
            { type: 'item', label: 'New File', icon: 'FilePlus', shortcut: '⌘N' },
            { type: 'item', label: 'Open...', icon: 'FolderOpen', shortcut: '⌘O' },
            { type: 'separator' },
            { type: 'item', label: 'Save', icon: 'Save', shortcut: '⌘S' },
            { type: 'item', label: 'Save As...', shortcut: '⌘⇧S' },
            { type: 'separator' },
            { type: 'item', label: 'Exit', icon: 'LogOut' },
          ],
        },
        {
          label: 'Edit',
          items: [
            { type: 'item', label: 'Undo', icon: 'Undo', shortcut: '⌘Z' },
            { type: 'item', label: 'Redo', icon: 'Redo', shortcut: '⌘⇧Z' },
            { type: 'separator' },
            { type: 'item', label: 'Cut', icon: 'Scissors', shortcut: '⌘X' },
            { type: 'item', label: 'Copy', icon: 'Copy', shortcut: '⌘C' },
            { type: 'item', label: 'Paste', icon: 'Clipboard', shortcut: '⌘V' },
          ],
        },
        {
          label: 'View',
          items: [
            { type: 'checkbox', label: 'Show Sidebar', checked: true, shortcut: '⌘B' },
            { type: 'checkbox', label: 'Show Toolbar', checked: false },
            { type: 'separator' },
            {
              type: 'submenu',
              label: 'Zoom',
              items: [
                { type: 'item', label: 'Zoom In', shortcut: '⌘+' },
                { type: 'item', label: 'Zoom Out', shortcut: '⌘-' },
                { type: 'item', label: 'Reset Zoom', shortcut: '⌘0' },
              ],
            },
          ],
        },
        {
          label: 'Help',
          items: [
            { type: 'item', label: 'Documentation', icon: 'BookOpen' },
            { type: 'item', label: 'Keyboard Shortcuts', icon: 'Keyboard' },
            { type: 'separator' },
            { type: 'item', label: 'About', icon: 'Info' },
          ],
        },
      ],
    },
  },
];

export const hoverCardExamples: HoverCardConfig[] = [
  {
    id: 'hover-card-1',
    type: 'hover-card',
    props: {
      trigger: {
        id: 'trigger-1',
        type: 'button',
        props: {
          label: 'Hover me',
          variant: 'link',
        },
      },
      content: [
        {
          id: 'card-content-1',
          type: 'card',
          props: {
            title: 'John Doe',
            description: 'Software Engineer',
          },
          children: [
            {
              id: 'text-1',
              type: 'typography.p',
              props: {
                text: 'Full-stack developer with 5+ years of experience building modern web applications.',
                className: 'text-sm',
              },
            },
          ],
        },
      ],
      openDelay: 300,
      closeDelay: 200,
    },
  },
  {
    id: 'hover-card-2',
    type: 'hover-card',
    props: {
      trigger: {
        id: 'trigger-2',
        type: 'badge',
        props: {
          text: '@username',
          variant: 'secondary',
        },
      },
      content: [
        {
          id: 'avatar-1',
          type: 'avatar',
          props: {
            src: 'https://github.com/shadcn.png',
            fallback: 'JD',
            size: 'lg',
          },
        },
        {
          id: 'title-1',
          type: 'typography.h4',
          props: {
            text: 'John Doe',
            className: 'mt-2',
          },
        },
        {
          id: 'desc-1',
          type: 'typography.p',
          props: {
            text: '@username • Joined March 2024',
            className: 'text-sm text-muted-foreground',
          },
        },
        {
          id: 'separator-1',
          type: 'separator',
          props: {
            className: 'my-2',
          },
        },
        {
          id: 'bio-1',
          type: 'typography.p',
          props: {
            text: 'Building amazing things with code. Open source enthusiast.',
            className: 'text-sm',
          },
        },
      ],
      openDelay: 200,
      side: 'right',
      align: 'start',
    },
  },
];

export const allCompositeExamples = {
  dropdownMenu: dropdownMenuExamples,
  contextMenu: contextMenuExamples,
  menubar: menubarExamples,
  hoverCard: hoverCardExamples,
};
