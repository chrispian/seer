import React from 'react';
import { Accordion, AccordionItem, AccordionTrigger, AccordionContent } from '@/components/ui/accordion';
import { cn } from '@/lib/utils';
import { ComponentConfig } from '../types';
import { renderComponent } from '../ComponentRegistry';

export interface AccordionConfig extends ComponentConfig {
  type: 'accordion';
  props: {
    type?: 'single' | 'multiple';
    collapsible?: boolean;
    defaultValue?: string | string[];
    items: Array<{
      value: string;
      title: string;
      content: ComponentConfig[];
      disabled?: boolean;
    }>;
    className?: string;
  };
}

export function AccordionComponent({ config }: { config: AccordionConfig }) {
  const { props } = config;
  const { 
    type = 'single', 
    collapsible = true, 
    defaultValue, 
    items = [], 
    className 
  } = props;

  if (items.length === 0) {
    return null;
  }

  const accordionProps: any = {
    type,
    className: cn(className),
  };

  if (type === 'single') {
    accordionProps.collapsible = collapsible;
    if (defaultValue && typeof defaultValue === 'string') {
      accordionProps.defaultValue = defaultValue;
    }
  } else {
    if (defaultValue && Array.isArray(defaultValue)) {
      accordionProps.defaultValue = defaultValue;
    }
  }

  return (
    <Accordion {...accordionProps}>
      {items.map((item) => (
        <AccordionItem key={item.value} value={item.value} disabled={item.disabled}>
          <AccordionTrigger>{item.title}</AccordionTrigger>
          <AccordionContent>
            <div className="space-y-2">
              {item.content.map((child) => (
                <React.Fragment key={child.id}>
                  {renderComponent(child)}
                </React.Fragment>
              ))}
            </div>
          </AccordionContent>
        </AccordionItem>
      ))}
    </Accordion>
  );
}
