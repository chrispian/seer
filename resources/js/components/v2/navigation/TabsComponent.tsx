import React from 'react';
import { Tabs, TabsList, TabsTrigger, TabsContent } from '@/components/ui/tabs';
import { cn } from '@/lib/utils';
import { ComponentConfig } from '../types';
import { renderComponent } from '../ComponentRegistry';

export interface TabsConfig extends ComponentConfig {
  type: 'tabs';
  props: {
    defaultValue: string;
    tabs: Array<{
      value: string;
      label: string;
      content: ComponentConfig[];
      disabled?: boolean;
    }>;
    className?: string;
    listClassName?: string;
  };
}

export function TabsComponent({ config }: { config: TabsConfig }) {
  const { props } = config;
  const { defaultValue, tabs, className, listClassName } = props;

  return (
    <Tabs defaultValue={defaultValue} className={cn(className)}>
      <TabsList className={cn(listClassName)}>
        {tabs.map((tab) => (
          <TabsTrigger
            key={tab.value}
            value={tab.value}
            disabled={tab.disabled}
          >
            {tab.label}
          </TabsTrigger>
        ))}
      </TabsList>
      {tabs.map((tab) => (
        <TabsContent key={tab.value} value={tab.value}>
          {tab.content.map((child) => (
            <React.Fragment key={child.id}>
              {renderComponent(child)}
            </React.Fragment>
          ))}
        </TabsContent>
      ))}
    </Tabs>
  );
}
