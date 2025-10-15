import React, { useState } from 'react';
import { Collapsible, CollapsibleTrigger, CollapsibleContent } from '@/components/ui/collapsible';
import { Button } from '@/components/ui/button';
import { ChevronDown } from 'lucide-react';
import { cn } from '@/lib/utils';
import { ComponentConfig } from '../types';
import { renderComponent } from '../ComponentRegistry';

export interface CollapsibleConfig extends ComponentConfig {
  type: 'collapsible';
  props: {
    title: string;
    defaultOpen?: boolean;
    disabled?: boolean;
    triggerClassName?: string;
    contentClassName?: string;
    className?: string;
  };
  children?: ComponentConfig[];
}

export function CollapsibleComponent({ config }: { config: CollapsibleConfig }) {
  const { props, children = [] } = config;
  const { title, defaultOpen = false, disabled = false, triggerClassName, contentClassName, className } = props;
  const [isOpen, setIsOpen] = useState(defaultOpen);

  return (
    <Collapsible
      open={isOpen}
      onOpenChange={setIsOpen}
      disabled={disabled}
      className={cn('space-y-2', className)}
    >
      <CollapsibleTrigger asChild>
        <Button
          variant="ghost"
          size="sm"
          className={cn('flex w-full justify-between p-2', triggerClassName)}
          disabled={disabled}
        >
          <span className="font-semibold">{title}</span>
          <ChevronDown
            className={cn(
              'h-4 w-4 transition-transform duration-200',
              isOpen && 'rotate-180'
            )}
          />
        </Button>
      </CollapsibleTrigger>
      <CollapsibleContent className={cn('space-y-2', contentClassName)}>
        {children.map((child) => (
          <React.Fragment key={child.id}>
            {renderComponent(child)}
          </React.Fragment>
        ))}
      </CollapsibleContent>
    </Collapsible>
  );
}
