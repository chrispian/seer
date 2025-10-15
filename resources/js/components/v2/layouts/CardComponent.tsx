import React from 'react';
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import { ComponentConfig } from '../types';
import { renderComponent } from '../ComponentRegistry';

export interface CardConfig extends ComponentConfig {
  type: 'card';
  props: {
    title?: string;
    description?: string;
    footer?: ComponentConfig;
    className?: string;
  };
  children?: ComponentConfig[];
}

export function CardComponent({ config }: { config: CardConfig }) {
  const { props = {}, children = [] } = config;
  const { title, description, footer, className } = props;

  return (
    <Card className={cn(className)}>
      {(title || description) && (
        <CardHeader>
          {title && <CardTitle>{title}</CardTitle>}
          {description && <CardDescription>{description}</CardDescription>}
        </CardHeader>
      )}
      
      {children.length > 0 && (
        <CardContent>
          {children.map((child) => (
            <React.Fragment key={child.id}>
              {renderComponent(child)}
            </React.Fragment>
          ))}
        </CardContent>
      )}
      
      {footer && (
        <CardFooter>
          {renderComponent(footer)}
        </CardFooter>
      )}
    </Card>
  );
}
