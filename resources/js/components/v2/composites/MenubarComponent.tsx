import {
  Menubar,
  MenubarMenu,
  MenubarTrigger,
  MenubarContent,
  MenubarItem,
  MenubarCheckboxItem,
  MenubarRadioGroup,
  MenubarRadioItem,
  MenubarLabel,
  MenubarSeparator,
  MenubarShortcut,
  MenubarSub,
  MenubarSubTrigger,
  MenubarSubContent,
} from '@/components/ui/menubar';
import { cn } from '@/lib/utils';
import { MenubarConfig, MenuItemConfig } from '../types';
import * as LucideIcons from 'lucide-react';

function handleAction(action?: MenuItemConfig['action']) {
  if (!action) return;

  const { type, command, url, event: eventName, payload, method } = action;

  if (type === 'command' && command) {
    window.dispatchEvent(new CustomEvent('command:execute', { detail: { command, payload } }));
  } else if (type === 'navigate' && url) {
    window.location.href = url;
  } else if (type === 'emit' && eventName) {
    window.dispatchEvent(new CustomEvent(eventName, { detail: payload }));
  } else if (type === 'http' && url) {
    fetch(url, {
      method: method || 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    }).catch(console.error);
  }
}

function renderIcon(iconName?: string) {
  if (!iconName) return null;
  const Icon = (LucideIcons as any)[iconName];
  if (!Icon) return null;
  return <Icon className="h-4 w-4" />;
}

function renderMenuItem(item: MenuItemConfig, index: number) {
  const key = `menubar-item-${index}`;

  if (item.type === 'separator') {
    return <MenubarSeparator key={key} />;
  }

  if (item.type === 'label') {
    return (
      <MenubarLabel key={key}>
        {item.label}
      </MenubarLabel>
    );
  }

  if (item.type === 'submenu' && item.items) {
    return (
      <MenubarSub key={key}>
        <MenubarSubTrigger disabled={item.disabled}>
          {renderIcon(item.icon)}
          {item.label}
        </MenubarSubTrigger>
        <MenubarSubContent>
          {item.items.map((subItem, subIndex) => renderMenuItem(subItem, subIndex))}
        </MenubarSubContent>
      </MenubarSub>
    );
  }

  if (item.type === 'checkbox') {
    return (
      <MenubarCheckboxItem
        key={key}
        checked={item.checked}
        disabled={item.disabled}
        onSelect={(e) => {
          e.preventDefault();
          handleAction(item.action);
        }}
      >
        {renderIcon(item.icon)}
        {item.label}
        {item.shortcut && <MenubarShortcut>{item.shortcut}</MenubarShortcut>}
      </MenubarCheckboxItem>
    );
  }

  if (item.type === 'radio') {
    return (
      <MenubarRadioItem
        key={key}
        value={item.value || item.label || ''}
        disabled={item.disabled}
        onSelect={(e) => {
          e.preventDefault();
          handleAction(item.action);
        }}
      >
        {renderIcon(item.icon)}
        {item.label}
        {item.shortcut && <MenubarShortcut>{item.shortcut}</MenubarShortcut>}
      </MenubarRadioItem>
    );
  }

  return (
    <MenubarItem
      key={key}
      disabled={item.disabled}
      onSelect={(e) => {
        e.preventDefault();
        handleAction(item.action);
      }}
    >
      {renderIcon(item.icon)}
      {item.label}
      {item.shortcut && <MenubarShortcut>{item.shortcut}</MenubarShortcut>}
    </MenubarItem>
  );
}

export function MenubarComponent({ config }: { config: MenubarConfig }) {
  const { props } = config;
  const { menus, className } = props;

  return (
    <Menubar className={cn(className)}>
      {menus.map((menu, menuIndex) => {
        const hasRadioItems = menu.items.some((item) => item.type === 'radio');

        return (
          <MenubarMenu key={`menu-${menuIndex}`}>
            <MenubarTrigger>{menu.label}</MenubarTrigger>
            <MenubarContent>
              {hasRadioItems ? (
                <MenubarRadioGroup>
                  {menu.items.map((item, index) => renderMenuItem(item, index))}
                </MenubarRadioGroup>
              ) : (
                menu.items.map((item, index) => renderMenuItem(item, index))
              )}
            </MenubarContent>
          </MenubarMenu>
        );
      })}
    </Menubar>
  );
}
