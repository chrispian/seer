import React from 'react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { ButtonConfig } from '../types';
import { LoadingSpinner } from '@/components/ui/loading-spinner';

export function ButtonComponent({ config }: { config: ButtonConfig }) {
  const { props = {}, actions } = config;
  const {
    label,
    icon,
    variant = 'default',
    size = 'default',
    disabled = false,
    loading = false,
    className,
  } = props;

  const handleClick = (e: React.MouseEvent<HTMLButtonElement>) => {
    if (actions?.click) {
      e.preventDefault();
      const { type, command, url, event: eventName, payload } = actions.click;
      
      if (type === 'command' && command) {
        window.dispatchEvent(new CustomEvent('command:execute', { detail: { command, payload } }));
      } else if (type === 'navigate' && url) {
        window.location.href = url;
      } else if (type === 'emit' && eventName) {
        window.dispatchEvent(new CustomEvent(eventName, { detail: payload }));
      } else if (type === 'http' && url) {
        fetch(url, {
          method: actions.click.method || 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload),
        }).catch(console.error);
      }
    }
  };

  return (
    <Button
      variant={variant}
      size={size}
      disabled={disabled || loading}
      onClick={handleClick}
      className={cn(className)}
      aria-busy={loading}
    >
      {loading && <LoadingSpinner size="sm" className="mr-2" />}
      {icon && !loading && (
        <span className="mr-2" aria-hidden="true">{icon}</span>
      )}
      {label}
    </Button>
  );
}
