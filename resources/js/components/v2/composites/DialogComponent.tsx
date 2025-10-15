import React, { useState } from 'react';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import { cn } from '@/lib/utils';
import { DialogConfig } from '../types';
import { renderComponent } from '../ComponentRegistry';

const sizeClasses: Record<string, string> = {
  sm: 'max-w-sm',
  md: 'max-w-md',
  lg: 'max-w-lg',
  xl: 'max-w-xl',
  full: 'max-w-full',
};

export function DialogComponent({ config }: { config: DialogConfig }) {
  const { props, actions } = config;
  const {
    title,
    description,
    trigger,
    content = [],
    footer = [],
    size = 'lg',
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
    <Dialog open={open} onOpenChange={handleOpenChange}>
      {trigger && (
        <DialogTrigger asChild>
          {renderComponent(trigger)}
        </DialogTrigger>
      )}
      <DialogContent className={cn(sizeClasses[size], className)}>
        <DialogHeader>
          <DialogTitle>{title}</DialogTitle>
          {description && <DialogDescription>{description}</DialogDescription>}
        </DialogHeader>

        <div className="space-y-4">
          {content.map((child: any) => (
            <React.Fragment key={child.id}>
              {renderComponent(child)}
            </React.Fragment>
          ))}
        </div>

        {footer && footer.length > 0 && (
          <DialogFooter>
            {footer.map((child: any) => (
              <React.Fragment key={child.id}>
                {renderComponent(child)}
              </React.Fragment>
            ))}
          </DialogFooter>
        )}
      </DialogContent>
    </Dialog>
  );
}
