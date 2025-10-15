import { cn } from '@/lib/utils';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { ItemConfig } from '../types';
import { renderComponent } from '../ComponentRegistry';
import * as Icons from 'lucide-react';

export function ItemComponent({ config }: { config: ItemConfig }) {
  const { props, actions } = config;
  const {
    title,
    description,
    icon,
    avatar,
    badge,
    trailing,
    className,
  } = props || {};

  const handleClick = () => {
    if (actions?.click) {
      const { type, command, event: eventName, url, payload } = actions.click;
      
      if (type === 'command' && command) {
        window.dispatchEvent(new CustomEvent('command:execute', { detail: { command, payload } }));
      } else if (type === 'emit' && eventName) {
        window.dispatchEvent(new CustomEvent(eventName, { detail: payload }));
      } else if (type === 'navigate' && url) {
        window.location.href = url;
      }
    }
  };

  const Icon = icon ? (Icons as any)[icon] : null;
  const isClickable = !!actions?.click;

  return (
    <div
      className={cn(
        'flex items-center gap-3 px-4 py-3 rounded-md transition-colors',
        isClickable && 'cursor-pointer hover:bg-accent',
        className
      )}
      onClick={isClickable ? handleClick : undefined}
    >
      {avatar && (
        <Avatar className="h-10 w-10">
          <AvatarImage src={avatar} alt={title} />
          <AvatarFallback>{title.substring(0, 2).toUpperCase()}</AvatarFallback>
        </Avatar>
      )}
      
      {Icon && !avatar && (
        <div className="flex h-10 w-10 items-center justify-center rounded-md bg-muted">
          <Icon className="h-5 w-5 text-muted-foreground" />
        </div>
      )}

      <div className="flex-1 min-w-0">
        <div className="flex items-center gap-2">
          <p className="text-sm font-medium leading-none truncate">{title}</p>
          {badge && (
            <Badge variant="secondary" className="text-xs">
              {badge}
            </Badge>
          )}
        </div>
        {description && (
          <p className="text-sm text-muted-foreground mt-1 truncate">
            {description}
          </p>
        )}
      </div>

      {trailing && (
        <div className="flex-shrink-0">
          {renderComponent(trailing)}
        </div>
      )}
    </div>
  );
}
