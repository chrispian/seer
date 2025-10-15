import { renderComponent } from '../ComponentRegistry';
import { DialogConfig, PopoverConfig, TooltipConfig, SheetConfig, DrawerConfig } from '../types';

export function DialogExample() {
  const config: DialogConfig = {
    id: 'test-dialog-1',
    type: 'dialog',
    props: {
      title: 'Delete Item',
      description: 'This action cannot be undone.',
      size: 'md',
      trigger: {
        id: 'dialog-trigger-1',
        type: 'button',
        props: {
          label: 'Delete',
          variant: 'destructive',
        },
      },
      content: [
        {
          id: 'dialog-msg',
          type: 'typography.p',
          props: {
            text: 'Are you sure you want to delete this item? This will permanently remove it from the database.',
          },
        },
        {
          id: 'dialog-alert',
          type: 'alert',
          props: {
            variant: 'warning',
            title: 'Warning',
            description: 'This operation is irreversible.',
          },
        },
      ],
      footer: [
        {
          id: 'dialog-cancel',
          type: 'button',
          props: {
            label: 'Cancel',
            variant: 'outline',
          },
        },
        {
          id: 'dialog-confirm',
          type: 'button',
          props: {
            label: 'Delete',
            variant: 'destructive',
          },
          actions: {
            click: {
              type: 'command',
              command: 'item:delete',
              payload: { id: '123' },
            },
          },
        },
      ],
    },
  };

  return renderComponent(config);
}

export function PopoverExample() {
  const config: PopoverConfig = {
    id: 'test-popover-1',
    type: 'popover',
    props: {
      side: 'right',
      align: 'start',
      trigger: {
        id: 'popover-trigger',
        type: 'button.icon',
        props: {
          icon: '⋮',
          variant: 'ghost',
          size: 'sm',
        },
      },
      content: [
        {
          id: 'popover-title',
          type: 'typography.h4',
          props: {
            text: 'Quick Actions',
            className: 'mb-2',
          },
        },
        {
          id: 'popover-divider',
          type: 'separator',
          props: {},
        },
        {
          id: 'action-edit',
          type: 'button',
          props: {
            label: 'Edit',
            variant: 'ghost',
            className: 'w-full justify-start',
          },
        },
        {
          id: 'action-duplicate',
          type: 'button',
          props: {
            label: 'Duplicate',
            variant: 'ghost',
            className: 'w-full justify-start',
          },
        },
        {
          id: 'action-delete',
          type: 'button',
          props: {
            label: 'Delete',
            variant: 'ghost',
            className: 'w-full justify-start text-destructive',
          },
        },
      ],
    },
  };

  return renderComponent(config);
}

export function TooltipExample() {
  const config: TooltipConfig = {
    id: 'test-tooltip-1',
    type: 'tooltip',
    props: {
      content: 'Save your changes to the database',
      side: 'top',
      delay: 300,
    },
    children: [
      {
        id: 'tooltip-child',
        type: 'button',
        props: {
          label: 'Save',
          variant: 'default',
        },
        actions: {
          click: {
            type: 'command',
            command: 'data:save',
          },
        },
      },
    ],
  };

  return renderComponent(config);
}

export function SheetExample() {
  const config: SheetConfig = {
    id: 'test-sheet-1',
    type: 'sheet',
    props: {
      title: 'User Settings',
      description: 'Manage your account preferences',
      side: 'right',
      trigger: {
        id: 'sheet-trigger',
        type: 'button',
        props: {
          label: 'Settings',
          icon: '⚙',
        },
      },
      content: [
        {
          id: 'setting-notifications',
          type: 'field',
          props: {
            label: 'Notifications',
            helperText: 'Receive email notifications for updates',
          },
          children: [
            {
              id: 'notifications-switch',
              type: 'switch',
              props: {
                label: 'Enable notifications',
                defaultChecked: true,
              },
            },
          ],
        },
        {
          id: 'setting-theme',
          type: 'field',
          props: {
            label: 'Theme',
          },
          children: [
            {
              id: 'theme-select',
              type: 'select',
              props: {
                options: [
                  { label: 'Light', value: 'light' },
                  { label: 'Dark', value: 'dark' },
                  { label: 'System', value: 'system' },
                ],
                defaultValue: 'system',
              },
            },
          ],
        },
        {
          id: 'setting-language',
          type: 'field',
          props: {
            label: 'Language',
          },
          children: [
            {
              id: 'language-select',
              type: 'select',
              props: {
                options: [
                  { label: 'English', value: 'en' },
                  { label: 'Spanish', value: 'es' },
                  { label: 'French', value: 'fr' },
                ],
                defaultValue: 'en',
              },
            },
          ],
        },
      ],
      footer: [
        {
          id: 'sheet-save',
          type: 'button',
          props: {
            label: 'Save Changes',
            variant: 'default',
          },
          actions: {
            click: {
              type: 'command',
              command: 'settings:save',
            },
          },
        },
      ],
    },
  };

  return renderComponent(config);
}

export function DrawerExample() {
  const config: DrawerConfig = {
    id: 'test-drawer-1',
    type: 'drawer',
    props: {
      title: 'Search Filters',
      description: 'Refine your search results',
      direction: 'bottom',
      trigger: {
        id: 'drawer-trigger',
        type: 'button',
        props: {
          label: 'Show Filters',
          icon: '🔍',
          variant: 'outline',
        },
      },
      content: [
        {
          id: 'filter-status',
          type: 'field',
          props: {
            label: 'Status',
          },
          children: [
            {
              id: 'status-radio',
              type: 'radio-group',
              props: {
                options: [
                  { label: 'All', value: 'all' },
                  { label: 'Active', value: 'active' },
                  { label: 'Inactive', value: 'inactive' },
                ],
                defaultValue: 'all',
              },
            },
          ],
        },
        {
          id: 'filter-date',
          type: 'field',
          props: {
            label: 'Date Range',
          },
          children: [
            {
              id: 'date-input',
              type: 'input.text',
              props: {
                placeholder: 'Select date range',
              },
            },
          ],
        },
        {
          id: 'filter-priority',
          type: 'field',
          props: {
            label: 'Priority',
          },
          children: [
            {
              id: 'priority-checkbox',
              type: 'checkbox',
              props: {
                label: 'High priority only',
              },
            },
          ],
        },
      ],
      footer: [
        {
          id: 'drawer-clear',
          type: 'button',
          props: {
            label: 'Clear All',
            variant: 'outline',
          },
          actions: {
            click: {
              type: 'command',
              command: 'filters:clear',
            },
          },
        },
        {
          id: 'drawer-apply',
          type: 'button',
          props: {
            label: 'Apply Filters',
            variant: 'default',
          },
          actions: {
            click: {
              type: 'command',
              command: 'filters:apply',
            },
          },
        },
      ],
    },
  };

  return renderComponent(config);
}

export function AllExamples() {
  return (
    <div className="space-y-8 p-8">
      <div>
        <h2 className="text-2xl font-bold mb-4">Dialog Example</h2>
        <DialogExample />
      </div>

      <div>
        <h2 className="text-2xl font-bold mb-4">Popover Example</h2>
        <PopoverExample />
      </div>

      <div>
        <h2 className="text-2xl font-bold mb-4">Tooltip Example</h2>
        <TooltipExample />
      </div>

      <div>
        <h2 className="text-2xl font-bold mb-4">Sheet Example</h2>
        <SheetExample />
      </div>

      <div>
        <h2 className="text-2xl font-bold mb-4">Drawer Example</h2>
        <DrawerExample />
      </div>
    </div>
  );
}
