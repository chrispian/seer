import React, { useState, useEffect } from 'react';
import {
  Command,
  CommandDialog,
  CommandEmpty,
  CommandGroup,
  CommandInput,
  CommandItem,
  CommandList,
  CommandSeparator,
  CommandShortcut,
} from '@/components/ui/command';
import { cn } from '@/lib/utils';
import { ComponentConfig } from '../types';
import * as Icons from 'lucide-react';

export interface CommandConfig extends ComponentConfig {
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
  actions?: {
    onSelect?: {
      type: 'command' | 'navigate' | 'emit' | 'http';
      command?: string;
      url?: string;
      event?: string;
      method?: 'GET' | 'POST' | 'PUT' | 'DELETE' | 'PATCH';
      payload?: Record<string, any>;
    };
  };
}

export function CommandComponent({ config }: { config: CommandConfig }) {
  const { props, actions } = config;
  const {
    placeholder = 'Type a command or search...',
    emptyText = 'No results found.',
    groups,
    open: controlledOpen,
    defaultOpen = false,
    showShortcut = true,
    className,
  } = props;

  const [open, setOpen] = useState(defaultOpen);

  useEffect(() => {
    if (controlledOpen !== undefined) {
      setOpen(controlledOpen);
    }
  }, [controlledOpen]);

  useEffect(() => {
    const down = (e: KeyboardEvent) => {
      if (e.key === 'k' && (e.metaKey || e.ctrlKey)) {
        e.preventDefault();
        setOpen((open) => !open);
      }
    };

    document.addEventListener('keydown', down);
    return () => document.removeEventListener('keydown', down);
  }, []);

  const handleSelect = (selectedValue: string) => {
    if (actions?.onSelect) {
      const action = actions.onSelect;
      
      switch (action.type) {
        case 'command':
          if (action.command) {
            console.log('Execute command:', action.command, { value: selectedValue });
          }
          break;
        case 'navigate':
          if (action.url) {
            window.location.href = action.url;
          }
          break;
        case 'emit':
          if (action.event) {
            window.dispatchEvent(new CustomEvent(action.event, { detail: action.payload }));
          }
          break;
        case 'http':
          if (action.url) {
            fetch(action.url, {
              method: action.method || 'GET',
              headers: { 'Content-Type': 'application/json' },
              body: action.payload ? JSON.stringify(action.payload) : undefined,
            });
          }
          break;
      }
    }
    
    setOpen(false);
  };

  const CommandContent = () => (
    <>
      <CommandInput placeholder={placeholder} />
      <CommandList>
        <CommandEmpty>{emptyText}</CommandEmpty>
        {groups.map((group, groupIndex) => (
          <React.Fragment key={groupIndex}>
            {groupIndex > 0 && <CommandSeparator />}
            <CommandGroup heading={group.heading}>
              {group.items.map((item, itemIndex) => {
                const Icon = item.icon ? (Icons as any)[item.icon] : null;
                const itemValue = item.value || item.label;

                return (
                  <CommandItem
                    key={itemIndex}
                    value={itemValue}
                    disabled={item.disabled}
                    onSelect={() => handleSelect(itemValue)}
                  >
                    {Icon && <Icon className="mr-2 h-4 w-4" />}
                    <span>{item.label}</span>
                    {showShortcut && item.shortcut && (
                      <CommandShortcut>{item.shortcut}</CommandShortcut>
                    )}
                  </CommandItem>
                );
              })}
            </CommandGroup>
          </React.Fragment>
        ))}
      </CommandList>
    </>
  );

  if (controlledOpen !== undefined || defaultOpen) {
    return (
      <CommandDialog open={open} onOpenChange={setOpen}>
        <CommandContent />
      </CommandDialog>
    );
  }

  return (
    <Command className={cn('rounded-lg border shadow-md', className)}>
      <CommandContent />
    </Command>
  );
}
