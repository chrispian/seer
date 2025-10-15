import React from 'react';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';
import { TextareaConfig } from '../types';

export function TextareaComponent({ config }: { config: TextareaConfig }) {
  const { props = {}, actions } = config;
  const {
    placeholder,
    value,
    defaultValue,
    disabled = false,
    readonly = false,
    required = false,
    rows = 3,
    name,
    className,
  } = props;

  const handleChange = (e: React.ChangeEvent<HTMLTextAreaElement>) => {
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

  const handleBlur = (e: React.FocusEvent<HTMLTextAreaElement>) => {
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

  const handleFocus = (e: React.FocusEvent<HTMLTextAreaElement>) => {
    if (actions?.focus) {
      const { type: actionType, event: eventName, command, payload } = actions.focus;
      
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
    <Textarea
      placeholder={placeholder}
      value={value}
      defaultValue={defaultValue}
      disabled={disabled}
      readOnly={readonly}
      required={required}
      rows={rows}
      name={name}
      onChange={handleChange}
      onBlur={handleBlur}
      onFocus={handleFocus}
      className={cn(className)}
      aria-required={required}
    />
  );
}
