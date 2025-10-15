import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { cn } from '@/lib/utils';
import { SelectConfig } from '../types';

export function SelectComponent({ config }: { config: SelectConfig }) {
  const { props, actions } = config;
  const {
    options = [],
    placeholder,
    value,
    defaultValue,
    disabled = false,
    required = false,
    name,
    className,
  } = props || {};

  const handleChange = (newValue: string) => {
    if (actions?.change) {
      const { type: actionType, event: eventName, command, payload } = actions.change;
      
      if (actionType === 'emit' && eventName) {
        window.dispatchEvent(new CustomEvent(eventName, {
          detail: { ...payload, value: newValue, name }
        }));
      } else if (actionType === 'command' && command) {
        window.dispatchEvent(new CustomEvent('command:execute', {
          detail: { command, payload: { ...payload, value: newValue, name } }
        }));
      }
    }
  };

  const handleFocus = () => {
    if (actions?.focus) {
      const { type: actionType, event: eventName, command, payload } = actions.focus;
      
      if (actionType === 'emit' && eventName) {
        window.dispatchEvent(new CustomEvent(eventName, { detail: { ...payload, name } }));
      } else if (actionType === 'command' && command) {
        window.dispatchEvent(new CustomEvent('command:execute', {
          detail: { command, payload: { ...payload, name } }
        }));
      }
    }
  };

  const handleBlur = () => {
    if (actions?.blur) {
      const { type: actionType, event: eventName, command, payload } = actions.blur;
      
      if (actionType === 'emit' && eventName) {
        window.dispatchEvent(new CustomEvent(eventName, { detail: { ...payload, name } }));
      } else if (actionType === 'command' && command) {
        window.dispatchEvent(new CustomEvent('command:execute', {
          detail: { command, payload: { ...payload, name } }
        }));
      }
    }
  };

  return (
    <Select
      value={value}
      defaultValue={defaultValue}
      disabled={disabled}
      required={required}
      name={name}
      onValueChange={handleChange}
      onOpenChange={(open) => {
        if (open) {
          handleFocus();
        } else {
          handleBlur();
        }
      }}
    >
      <SelectTrigger className={cn(className)} aria-required={required}>
        <SelectValue placeholder={placeholder} />
      </SelectTrigger>
      <SelectContent>
        {options.map((option: { label: string; value: string; disabled?: boolean }) => (
          <SelectItem
            key={option.value}
            value={option.value}
            disabled={option.disabled}
          >
            {option.label}
          </SelectItem>
        ))}
      </SelectContent>
    </Select>
  );
}
