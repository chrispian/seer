import React from 'react';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';
import { InputConfig } from '../types';

export function InputComponent({ config }: { config: InputConfig }) {
  const { props = {}, actions } = config;
  const {
    placeholder,
    value,
    defaultValue,
    disabled = false,
    readonly = false,
    required = false,
    type = 'text',
    name,
    className,
  } = props;

  const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    if (actions?.change) {
      const { type: actionType, event: eventName, command, payload } = actions.change;
      
      if (actionType === 'emit' && eventName) {
        window.dispatchEvent(new CustomEvent(eventName, {
          detail: { ...payload, value: e.target.value, name }
        }));
      } else if (actionType === 'command' && command) {
        window.dispatchEvent(new CustomEvent('command:execute', {
          detail: { command, payload: { ...payload, value: e.target.value, name } }
        }));
      }
    }
  };

  const handleBlur = (e: React.FocusEvent<HTMLInputElement>) => {
    if (actions?.blur) {
      const { type: actionType, event: eventName, command, payload } = actions.blur;
      
      if (actionType === 'emit' && eventName) {
        window.dispatchEvent(new CustomEvent(eventName, {
          detail: { ...payload, value: e.target.value, name }
        }));
      } else if (actionType === 'command' && command) {
        window.dispatchEvent(new CustomEvent('command:execute', {
          detail: { command, payload: { ...payload, value: e.target.value, name } }
        }));
      }
    }
  };

  return (
    <Input
      type={type}
      placeholder={placeholder}
      value={value}
      defaultValue={defaultValue}
      disabled={disabled}
      readOnly={readonly}
      required={required}
      name={name}
      onChange={handleChange}
      onBlur={handleBlur}
      className={cn(className)}
      aria-required={required}
    />
  );
}
