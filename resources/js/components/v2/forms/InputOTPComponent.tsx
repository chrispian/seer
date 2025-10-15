import { useState } from 'react';
import { OTPInput } from 'input-otp';
import { cn } from '@/lib/utils';
import { InputOTPConfig } from '../types';

export function InputOTPComponent({ config }: { config: InputOTPConfig }) {
  const { props, actions } = config;
  const {
    length = 6,
    className,
  } = props || {};

  const [value, setValue] = useState('');

  const handleChange = (newValue: string) => {
    setValue(newValue);
    
    if (newValue.length === length && actions?.complete) {
      const { type, command, event: eventName, payload } = actions.complete;
      const completePayload = { ...payload, value: newValue };
      
      if (type === 'command' && command) {
        window.dispatchEvent(new CustomEvent('command:execute', { detail: { command, payload: completePayload } }));
      } else if (type === 'emit' && eventName) {
        window.dispatchEvent(new CustomEvent(eventName, { detail: completePayload }));
      }
    }
  };

  return (
    <OTPInput
      value={value}
      onChange={handleChange}
      maxLength={length}
      render={({ slots }) => (
        <div className={cn('flex gap-2', className)}>
          {slots.map((slot, idx) => (
            <div
              key={idx}
              className={cn(
                'relative flex h-14 w-12 items-center justify-center border-y border-r border-input text-sm transition-all first:rounded-l-md first:border-l last:rounded-r-md',
                slot.isActive && 'z-10 ring-2 ring-ring ring-offset-background',
                slot.char && 'bg-accent'
              )}
            >
              {slot.char}
              {slot.hasFakeCaret && (
                <div className="pointer-events-none absolute inset-0 flex items-center justify-center">
                  <div className="h-4 w-px animate-caret-blink bg-foreground duration-1000" />
                </div>
              )}
            </div>
          ))}
        </div>
      )}
    />
  );
}
