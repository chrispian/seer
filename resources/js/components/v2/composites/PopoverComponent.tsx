import React, { useState } from 'react';
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from '@/components/ui/popover';
import { cn } from '@/lib/utils';
import { PopoverConfig } from '../types';
import { renderComponent } from '../ComponentRegistry';

export function PopoverComponent({ config }: { config: PopoverConfig }) {
  const { props, actions } = config;
  const {
    trigger,
    content = [],
    side = 'bottom',
    align = 'center',
    defaultOpen = false,
    className,
  } = props || {};

  const [open, setOpen] = useState(defaultOpen);

  const handleOpenChange = (isOpen: boolean) => {
    setOpen(isOpen);
    
    if (isOpen && actions?.open) {
      const { type, command, event: eventName, payload } = actions.open;
      if (type === 'command' && command) {
        window.dispatchEvent(new CustomEvent('command:execute', { detail: { command, payload } }));
      } else if (type === 'emit' && eventName) {
        window.dispatchEvent(new CustomEvent(eventName, { detail: payload }));
      }
    } else if (!isOpen && actions?.close) {
      const { type, command, event: eventName, payload } = actions.close;
      if (type === 'command' && command) {
        window.dispatchEvent(new CustomEvent('command:execute', { detail: { command, payload } }));
      } else if (type === 'emit' && eventName) {
        window.dispatchEvent(new CustomEvent(eventName, { detail: payload }));
      }
    }
  };

  return (
    <Popover open={open} onOpenChange={handleOpenChange}>
      <PopoverTrigger asChild>
        {renderComponent(trigger)}
      </PopoverTrigger>
      <PopoverContent side={side} align={align} className={cn(className)}>
        <div className="space-y-3">
          {content.map((child: any) => (
            <React.Fragment key={child.id}>
              {renderComponent(child)}
            </React.Fragment>
          ))}
        </div>
      </PopoverContent>
    </Popover>
  );
}
