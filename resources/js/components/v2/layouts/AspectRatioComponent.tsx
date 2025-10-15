import React from 'react';
import { AspectRatio } from '@/components/ui/aspect-ratio';
import { cn } from '@/lib/utils';
import { ComponentConfig } from '../types';
import { renderComponent } from '../ComponentRegistry';

export interface AspectRatioConfig extends ComponentConfig {
  type: 'aspect-ratio';
  props: {
    ratio?: number | string;
    className?: string;
  };
  children?: ComponentConfig[];
}

export function AspectRatioComponent({ config }: { config: AspectRatioConfig }) {
  const { props = {}, children = [] } = config;
  const { ratio = '16/9', className } = props;

  const numericRatio = typeof ratio === 'string' 
    ? ratio.includes('/') 
      ? eval(ratio)
      : parseFloat(ratio)
    : ratio;

  return (
    <AspectRatio ratio={numericRatio} className={cn(className)}>
      {children.map((child) => (
        <React.Fragment key={child.id}>
          {renderComponent(child)}
        </React.Fragment>
      ))}
    </AspectRatio>
  );
}
