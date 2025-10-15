import { useState } from 'react';
import { format } from 'date-fns';
import { CalendarIcon } from 'lucide-react';
import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { DatePickerConfig } from '../types';

export function DatePickerComponent({ config }: { config: DatePickerConfig }) {
  const { props, actions } = config;
  const {
    value: initialValue,
    placeholder = 'Pick a date',
    format: dateFormat = 'PPP',
    disabled = false,
    className,
  } = props || {};

  const [date, setDate] = useState<Date | undefined>(
    initialValue ? new Date(initialValue) : undefined
  );

  const handleSelect = (newDate: Date | undefined) => {
    setDate(newDate);
    
    if (actions?.change && newDate) {
      const { type, command, event: eventName, payload } = actions.change;
      const changePayload = { ...payload, value: newDate.toISOString() };
      
      if (type === 'command' && command) {
        window.dispatchEvent(new CustomEvent('command:execute', { detail: { command, payload: changePayload } }));
      } else if (type === 'emit' && eventName) {
        window.dispatchEvent(new CustomEvent(eventName, { detail: changePayload }));
      }
    }
  };

  return (
    <Popover>
      <PopoverTrigger asChild>
        <Button
          variant="outline"
          className={cn(
            'w-full justify-start text-left font-normal',
            !date && 'text-muted-foreground',
            className
          )}
          disabled={disabled}
        >
          <CalendarIcon className="mr-2 h-4 w-4" />
          {date ? format(date, dateFormat) : <span>{placeholder}</span>}
        </Button>
      </PopoverTrigger>
      <PopoverContent className="w-auto p-0" align="start">
        <Calendar
          mode="single"
          selected={date}
          onSelect={handleSelect}
          disabled={disabled}
        />
      </PopoverContent>
    </Popover>
  );
}
