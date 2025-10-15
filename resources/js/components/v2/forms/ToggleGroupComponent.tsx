import { useState } from 'react';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { ToggleGroupConfig } from '../types';
import * as Icons from 'lucide-react';

interface TGItem {
  value: string;
  label: string;
  icon?: string;
}

export function ToggleGroupComponent({ config }: { config: ToggleGroupConfig }) {
  const { props, actions } = config;
  const {
    type = 'single',
    items = [],
    value: initialValue,
    variant = 'default',
    size = 'default',
    className,
  } = props || {};

  const [value, setValue] = useState<string | string[] | undefined>(initialValue);

  const handleValueChange = (newValue: string | string[]) => {
    setValue(newValue);
    
    if (actions?.change) {
      const { type: actionType, command, event: eventName, payload } = actions.change;
      const changePayload = { ...payload, value: newValue };
      
      if (actionType === 'command' && command) {
        window.dispatchEvent(new CustomEvent('command:execute', { detail: { command, payload: changePayload } }));
      } else if (actionType === 'emit' && eventName) {
        window.dispatchEvent(new CustomEvent(eventName, { detail: changePayload }));
      }
    }
  };

  return (
    <ToggleGroup
      type={type as any}
      value={value as any}
      onValueChange={handleValueChange as any}
      variant={variant as any}
      size={size as any}
      className={className}
    >
      {items.map((item: TGItem) => {
        const Icon = item.icon ? (Icons as any)[item.icon] : null;
        
        return (
          <ToggleGroupItem key={item.value} value={item.value}>
            {Icon && <Icon className="h-4 w-4" />}
            {item.label && <span className={item.icon ? 'ml-2' : ''}>{item.label}</span>}
          </ToggleGroupItem>
        );
      })}
    </ToggleGroup>
  );
}
