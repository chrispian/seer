import React, { useState } from 'react';
import { Check, ChevronsUpDown } from 'lucide-react';
import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import {
  Command,
  CommandEmpty,
  CommandGroup,
  CommandInput,
  CommandItem,
  CommandList,
} from '@/components/ui/command';
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from '@/components/ui/popover';
import { ComponentConfig } from '../types';
import * as Icons from 'lucide-react';

export interface ComboboxConfig extends ComponentConfig {
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
  actions?: {
    onChange?: {
      type: 'command' | 'navigate' | 'emit' | 'http';
      command?: string;
      url?: string;
      event?: string;
      method?: 'GET' | 'POST' | 'PUT' | 'DELETE' | 'PATCH';
      payload?: Record<string, any>;
    };
  };
}

export function ComboboxComponent({ config }: { config: ComboboxConfig }) {
  const { props, actions } = config;
  const {
    placeholder = 'Select option...',
    emptyText = 'No option found.',
    searchPlaceholder = 'Search...',
    options,
    value: controlledValue,
    defaultValue = '',
    searchable = true,
    disabled = false,
    className,
  } = props;

  const [open, setOpen] = useState(false);
  const [value, setValue] = useState(controlledValue || defaultValue);

  React.useEffect(() => {
    if (controlledValue !== undefined) {
      setValue(controlledValue);
    }
  }, [controlledValue]);

  const handleSelect = (currentValue: string) => {
    const newValue = currentValue === value ? '' : currentValue;
    setValue(newValue);
    setOpen(false);

    if (actions?.onChange) {
      const action = actions.onChange;
      
      switch (action.type) {
        case 'command':
          if (action.command) {
            console.log('Execute command:', action.command, { value: newValue });
          }
          break;
        case 'navigate':
          if (action.url) {
            window.location.href = action.url;
          }
          break;
        case 'emit':
          if (action.event) {
            window.dispatchEvent(
              new CustomEvent(action.event, {
                detail: { ...action.payload, value: newValue },
              })
            );
          }
          break;
        case 'http':
          if (action.url) {
            fetch(action.url, {
              method: action.method || 'POST',
              headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
              body: JSON.stringify({ ...action.payload, value: newValue }),
            });
          }
          break;
      }
    }
  };

  const selectedOption = options.find((option) => option.value === value);

  return (
    <Popover open={open} onOpenChange={setOpen}>
      <PopoverTrigger asChild>
        <Button
          variant="outline"
          role="combobox"
          aria-expanded={open}
          disabled={disabled}
          className={cn('w-[200px] justify-between', className)}
        >
          <span className="flex items-center gap-2 truncate">
            {selectedOption?.icon && (() => {
              const Icon = (Icons as any)[selectedOption.icon];
              return Icon ? <Icon className="h-4 w-4 shrink-0" /> : null;
            })()}
            {selectedOption ? selectedOption.label : placeholder}
          </span>
          <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
        </Button>
      </PopoverTrigger>
      <PopoverContent className="w-[200px] p-0">
        <Command>
          {searchable && <CommandInput placeholder={searchPlaceholder} />}
          <CommandList>
            <CommandEmpty>{emptyText}</CommandEmpty>
            <CommandGroup>
              {options.map((option) => {
                const Icon = option.icon ? (Icons as any)[option.icon] : null;
                
                return (
                  <CommandItem
                    key={option.value}
                    value={option.value}
                    disabled={option.disabled}
                    onSelect={handleSelect}
                  >
                    <Check
                      className={cn(
                        'mr-2 h-4 w-4',
                        value === option.value ? 'opacity-100' : 'opacity-0'
                      )}
                    />
                    {Icon && <Icon className="mr-2 h-4 w-4" />}
                    {option.label}
                  </CommandItem>
                );
              })}
            </CommandGroup>
          </CommandList>
        </Command>
      </PopoverContent>
    </Popover>
  );
}
