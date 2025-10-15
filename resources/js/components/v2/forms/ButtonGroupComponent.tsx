import { useState } from 'react';
import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import { ButtonGroupConfig } from '../types';
import * as Icons from 'lucide-react';

interface ButtonGroupButton {
  value: string;
  label: string;
  icon?: string;
}

export function ButtonGroupComponent({ config }: { config: ButtonGroupConfig }) {
  const { props, actions } = config;
  const {
    buttons = [],
    value: initialValue,
    className,
  } = props || {};

  const [selected, setSelected] = useState<string | undefined>(initialValue);

  const handleSelect = (value: string) => {
    setSelected(value);
    
    if (actions?.change) {
      const { type, command, event: eventName, payload } = actions.change;
      const changePayload = { ...payload, value };
      
      if (type === 'command' && command) {
        window.dispatchEvent(new CustomEvent('command:execute', { detail: { command, payload: changePayload } }));
      } else if (type === 'emit' && eventName) {
        window.dispatchEvent(new CustomEvent(eventName, { detail: changePayload }));
      }
    }
  };

  return (
    <div className={cn('inline-flex rounded-md shadow-sm', className)} role="group">
      {buttons.map((button: ButtonGroupButton, index: number) => {
        const Icon = button.icon ? (Icons as any)[button.icon] : null;
        const isSelected = selected === button.value;
        
        return (
          <Button
            key={button.value}
            type="button"
            variant={isSelected ? 'default' : 'outline'}
            onClick={() => handleSelect(button.value)}
            className={cn(
              'rounded-none',
              index === 0 && 'rounded-l-md',
              index === buttons.length - 1 && 'rounded-r-md',
              index !== 0 && '-ml-px'
            )}
          >
            {Icon && <Icon className="h-4 w-4 mr-2" />}
            {button.label}
          </Button>
        );
      })}
    </div>
  );
}
