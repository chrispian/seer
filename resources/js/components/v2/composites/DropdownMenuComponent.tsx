
import {
  DropdownMenu,
  DropdownMenuTrigger,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuCheckboxItem,
  DropdownMenuRadioGroup,
  DropdownMenuRadioItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuShortcut,
  DropdownMenuSub,
  DropdownMenuSubTrigger,
  DropdownMenuSubContent,
} from '@/components/ui/dropdown-menu';
import { cn } from '@/lib/utils';
import { DropdownMenuConfig, MenuItemConfig } from '../types';
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
  const key = `menu-item-${index}`;

  if (item.type === 'separator') {
    return <DropdownMenuSeparator key={key} />;
  }

  if (item.type === 'label') {
    return (
      <DropdownMenuLabel key={key}>
        {item.label}
      </DropdownMenuLabel>
    );
  }

  if (item.type === 'submenu' && item.items) {
    return (
      <DropdownMenuSub key={key}>
        <DropdownMenuSubTrigger disabled={item.disabled}>
          {renderIcon(item.icon)}
          {item.label}
        </DropdownMenuSubTrigger>
        <DropdownMenuSubContent>
          {item.items.map((subItem, subIndex) => renderMenuItem(subItem, subIndex))}
        </DropdownMenuSubContent>
      </DropdownMenuSub>
    );
  }

  if (item.type === 'checkbox') {
    return (
      <DropdownMenuCheckboxItem
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
        {item.shortcut && <DropdownMenuShortcut>{item.shortcut}</DropdownMenuShortcut>}
      </DropdownMenuCheckboxItem>
    );
  }

  if (item.type === 'radio') {
    return (
      <DropdownMenuRadioItem
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
        {item.shortcut && <DropdownMenuShortcut>{item.shortcut}</DropdownMenuShortcut>}
      </DropdownMenuRadioItem>
    );
  }

  return (
    <DropdownMenuItem
      key={key}
      disabled={item.disabled}
      onSelect={(e) => {
        e.preventDefault();
        handleAction(item.action);
      }}
    >
      {renderIcon(item.icon)}
      {item.label}
      {item.shortcut && <DropdownMenuShortcut>{item.shortcut}</DropdownMenuShortcut>}
    </DropdownMenuItem>
  );
}

export function DropdownMenuComponent({ config }: { config: DropdownMenuConfig }) {
  const { props } = config;
  const { trigger, items, align = 'center', side = 'bottom', className } = props;

  const hasRadioItems = items.some((item) => item.type === 'radio');

  return (
    <DropdownMenu>
      <DropdownMenuTrigger asChild>
        {renderComponent(trigger)}
      </DropdownMenuTrigger>
      <DropdownMenuContent align={align} side={side} className={cn(className)}>
        {hasRadioItems ? (
          <DropdownMenuRadioGroup>
            {items.map((item, index) => renderMenuItem(item, index))}
          </DropdownMenuRadioGroup>
        ) : (
          items.map((item, index) => renderMenuItem(item, index))
        )}
      </DropdownMenuContent>
    </DropdownMenu>
  );
}
