import React from 'react';
import { ScrollArea } from '@/components/ui/scroll-area';
import { cn } from '@/lib/utils';
import { ComponentConfig } from '../types';
import { renderComponent } from '../ComponentRegistry';

export interface ScrollAreaConfig extends ComponentConfig {
  type: 'scroll-area';
  props: {
    height?: string;
    maxHeight?: string;
    orientation?: 'vertical' | 'horizontal';
    className?: string;
  };
  children?: ComponentConfig[];
}

export function ScrollAreaComponent({ config }: { config: ScrollAreaConfig }) {
  const { props = {}, children = [] } = config;
  const { height = '400px', maxHeight, className } = props;

  const style: React.CSSProperties = {
    height,
    ...(maxHeight && { maxHeight }),
  };

  return (
    <ScrollArea className={cn(className)} style={style}>
      <div className="p-4">
        {children.map((child) => (
          <React.Fragment key={child.id}>
            {renderComponent(child)}
          </React.Fragment>
        ))}
      </div>
    </ScrollArea>
  );
}
