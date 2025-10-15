import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from '@/components/ui/tooltip';
import { cn } from '@/lib/utils';
import { TooltipConfig } from '../types';
import { renderComponent } from '../ComponentRegistry';

export function TooltipComponent({ config }: { config: TooltipConfig }) {
  const { props, children } = config;
  const {
    content,
    side = 'top',
    delay = 0,
    className,
  } = props || {};

  const tooltipContent = typeof content === 'string' 
    ? content 
    : renderComponent(content);

  const child = children && children.length > 0 ? children[0] : null;

  if (!child) {
    console.warn('TooltipComponent requires a child component');
    return null;
  }

  return (
    <TooltipProvider delayDuration={delay}>
      <Tooltip>
        <TooltipTrigger asChild>
          {renderComponent(child)}
        </TooltipTrigger>
        <TooltipContent side={side} className={cn(className)}>
          {tooltipContent}
        </TooltipContent>
      </Tooltip>
    </TooltipProvider>
  );
}
