import React from 'react';
import { ResizablePanelGroup, ResizablePanel, ResizableHandle } from '@/components/ui/resizable';
import { cn } from '@/lib/utils';
import { ComponentConfig } from '../types';
import { renderComponent } from '../ComponentRegistry';

export interface ResizableConfig extends ComponentConfig {
  type: 'resizable';
  props: {
    direction?: 'horizontal' | 'vertical';
    panels: Array<{
      id: string;
      defaultSize?: number;
      minSize?: number;
      maxSize?: number;
      content: ComponentConfig[];
    }>;
    withHandle?: boolean;
    className?: string;
  };
}

export function ResizableComponent({ config }: { config: ResizableConfig }) {
  const { props } = config;
  const { direction = 'horizontal', panels = [], withHandle = true, className } = props;

  if (panels.length === 0) {
    return null;
  }

  return (
    <ResizablePanelGroup direction={direction} className={cn(className)}>
      {panels.map((panel, index) => (
        <React.Fragment key={panel.id}>
          <ResizablePanel
            defaultSize={panel.defaultSize}
            minSize={panel.minSize}
            maxSize={panel.maxSize}
          >
            <div className="h-full p-4">
              {panel.content.map((child) => (
                <React.Fragment key={child.id}>
                  {renderComponent(child)}
                </React.Fragment>
              ))}
            </div>
          </ResizablePanel>
          {index < panels.length - 1 && <ResizableHandle withHandle={withHandle} />}
        </React.Fragment>
      ))}
    </ResizablePanelGroup>
  );
}
