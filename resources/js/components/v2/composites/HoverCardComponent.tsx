import React from 'react';
import { HoverCard, HoverCardTrigger, HoverCardContent } from '@/components/ui/hover-card';
import { cn } from '@/lib/utils';
import { HoverCardConfig } from '../types';
import { renderComponent } from '../ComponentRegistry';

export function HoverCardComponent({ config }: { config: HoverCardConfig }) {
  const { props } = config;
  const {
    trigger,
    content,
    openDelay = 200,
    closeDelay = 300,
    side = 'bottom',
    align = 'center',
    className,
  } = props;

  return (
    <HoverCard openDelay={openDelay} closeDelay={closeDelay}>
      <HoverCardTrigger asChild>
        {renderComponent(trigger)}
      </HoverCardTrigger>
      <HoverCardContent side={side} align={align} className={cn(className)}>
        {content.map((child) => (
          <React.Fragment key={child.id}>
            {renderComponent(child)}
          </React.Fragment>
        ))}
      </HoverCardContent>
    </HoverCard>
  );
}
