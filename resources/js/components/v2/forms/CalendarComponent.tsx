import { useState } from 'react';
import { Calendar } from '@/components/ui/calendar';
import { CalendarConfig } from '../types';
import { cn } from '@/lib/utils';

export function CalendarComponent({ config }: { config: CalendarConfig }) {
  const { props, actions } = config;
  const {
    value: initialValue,
    mode = 'single',
    className,
  } = props || {};

  const [selected, setSelected] = useState<Date | Date[] | undefined>(() => {
    if (!initialValue) return undefined;
    if (mode === 'single') return new Date(initialValue as string);
    if (mode === 'multiple') return (initialValue as string[]).map(d => new Date(d));
    return undefined;
  });

  const handleSelect = (newSelected: Date | Date[] | undefined) => {
    setSelected(newSelected);
    
    if (actions?.change) {
      const { type, command, event: eventName, payload } = actions.change;
      
      let changeValue: string | string[] | undefined;
      if (newSelected instanceof Date) {
        changeValue = newSelected.toISOString();
      } else if (Array.isArray(newSelected)) {
        changeValue = newSelected.map(d => d.toISOString());
      }
      
      const changePayload = { ...payload, value: changeValue };
      
      if (type === 'command' && command) {
        window.dispatchEvent(new CustomEvent('command:execute', { detail: { command, payload: changePayload } }));
      } else if (type === 'emit' && eventName) {
        window.dispatchEvent(new CustomEvent(eventName, { detail: changePayload }));
      }
    }
  };

  return (
    <Calendar
      mode={mode as any}
      selected={selected as any}
      onSelect={handleSelect as any}
      className={cn('rounded-md border', className)}
    />
  );
}
