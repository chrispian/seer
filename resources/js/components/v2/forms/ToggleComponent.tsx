import { useState } from 'react';
import { Toggle } from '@/components/ui/toggle';
import { ToggleConfig } from '../types';
import * as Icons from 'lucide-react';

export function ToggleComponent({ config }: { config: ToggleConfig }) {
  const { props, actions } = config;
  const {
    pressed: initialPressed = false,
    label,
    icon,
    variant = 'default',
    size = 'default',
    disabled = false,
  } = props || {};

  const [pressed, setPressed] = useState(initialPressed);

  const handlePressedChange = (newPressed: boolean) => {
    setPressed(newPressed);
    
    if (actions?.change) {
      const { type, command, event: eventName, payload } = actions.change;
      const changePayload = { ...payload, pressed: newPressed };
      
      if (type === 'command' && command) {
        window.dispatchEvent(new CustomEvent('command:execute', { detail: { command, payload: changePayload } }));
      } else if (type === 'emit' && eventName) {
        window.dispatchEvent(new CustomEvent(eventName, { detail: changePayload }));
      }
    }
  };

  const Icon = icon ? (Icons as any)[icon] : null;

  return (
    <Toggle
      pressed={pressed}
      onPressedChange={handlePressedChange}
      variant={variant as any}
      size={size as any}
      disabled={disabled}
    >
      {Icon && <Icon className="h-4 w-4" />}
      {label && <span className={icon ? 'ml-2' : ''}>{label}</span>}
    </Toggle>
  );
}
