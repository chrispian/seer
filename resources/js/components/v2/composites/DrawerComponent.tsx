import React, { useState } from 'react';
import {
  Sheet,
  SheetContent,
  SheetDescription,
  SheetFooter,
  SheetHeader,
  SheetTitle,
  SheetTrigger,
} from '@/components/ui/sheet';
import { cn } from '@/lib/utils';
import { DrawerConfig } from '../types';
import { renderComponent } from '../ComponentRegistry';

export function DrawerComponent({ config }: { config: DrawerConfig }) {
  const { props, actions } = config;
  const {
    title,
    description,
    trigger,
    content = [],
    footer = [],
    direction = 'bottom',
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
    <Sheet open={open} onOpenChange={handleOpenChange}>
      {trigger && (
        <SheetTrigger asChild>
          {renderComponent(trigger)}
        </SheetTrigger>
      )}
      <SheetContent side={direction} className={cn('h-auto', className)}>
        <SheetHeader>
          <SheetTitle>{title}</SheetTitle>
          {description && <SheetDescription>{description}</SheetDescription>}
        </SheetHeader>

        <div className="space-y-4 py-4">
          {content.map((child: any) => (
            <React.Fragment key={child.id}>
              {renderComponent(child)}
            </React.Fragment>
          ))}
        </div>

        {footer && footer.length > 0 && (
          <SheetFooter>
            {footer.map((child: any) => (
              <React.Fragment key={child.id}>
                {renderComponent(child)}
              </React.Fragment>
            ))}
          </SheetFooter>
        )}
      </SheetContent>
    </Sheet>
  );
}
