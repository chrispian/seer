import {
  ContextMenu,
  ContextMenuTrigger,
  ContextMenuContent,
  ContextMenuItem,
  ContextMenuCheckboxItem,
  ContextMenuRadioGroup,
  ContextMenuRadioItem,
  ContextMenuLabel,
  ContextMenuSeparator,
  ContextMenuShortcut,
  ContextMenuSub,
  ContextMenuSubTrigger,
  ContextMenuSubContent,
} from '@/components/ui/context-menu';
import { cn } from '@/lib/utils';
import { ContextMenuConfig, MenuItemConfig } from '../types';
import { renderComponent } from '../ComponentRegistry';
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
  const key = `context-menu-item-${index}`;

  if (item.type === 'separator') {
    return <ContextMenuSeparator key={key} />;
  }

  if (item.type === 'label') {
    return (
      <ContextMenuLabel key={key}>
        {item.label}
      </ContextMenuLabel>
    );
  }

  if (item.type === 'submenu' && item.items) {
    return (
      <ContextMenuSub key={key}>
        <ContextMenuSubTrigger disabled={item.disabled}>
          {renderIcon(item.icon)}
          {item.label}
        </ContextMenuSubTrigger>
        <ContextMenuSubContent>
          {item.items.map((subItem, subIndex) => renderMenuItem(subItem, subIndex))}
        </ContextMenuSubContent>
      </ContextMenuSub>
    );
  }

  if (item.type === 'checkbox') {
    return (
      <ContextMenuCheckboxItem
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
        {item.shortcut && <ContextMenuShortcut>{item.shortcut}</ContextMenuShortcut>}
      </ContextMenuCheckboxItem>
    );
  }

  if (item.type === 'radio') {
    return (
      <ContextMenuRadioItem
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
        {item.shortcut && <ContextMenuShortcut>{item.shortcut}</ContextMenuShortcut>}
      </ContextMenuRadioItem>
    );
  }

  return (
    <ContextMenuItem
      key={key}
      disabled={item.disabled}
      onSelect={(e) => {
        e.preventDefault();
        handleAction(item.action);
      }}
    >
      {renderIcon(item.icon)}
      {item.label}
      {item.shortcut && <ContextMenuShortcut>{item.shortcut}</ContextMenuShortcut>}
    </ContextMenuItem>
  );
}

export function ContextMenuComponent({ config }: { config: ContextMenuConfig }) {
  const { props, children = [] } = config;
  const { items, className } = props;

  const hasRadioItems = items.some((item) => item.type === 'radio');

  return (
    <ContextMenu>
      <ContextMenuTrigger asChild>
        <div>
          {children.map((child) => renderComponent(child))}
        </div>
      </ContextMenuTrigger>
      <ContextMenuContent className={cn(className)}>
        {hasRadioItems ? (
          <ContextMenuRadioGroup>
            {items.map((item, index) => renderMenuItem(item, index))}
          </ContextMenuRadioGroup>
        ) : (
          items.map((item, index) => renderMenuItem(item, index))
        )}
      </ContextMenuContent>
    </ContextMenu>
  );
}
