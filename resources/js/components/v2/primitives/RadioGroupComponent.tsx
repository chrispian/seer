import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import { RadioGroupConfig } from '../types';

export function RadioGroupComponent({ config }: { config: RadioGroupConfig }) {
  const { props, actions } = config;
  const {
    options = [],
    value,
    defaultValue,
    disabled = false,
    required = false,
    name,
    orientation = 'vertical',
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
    <RadioGroup
      value={value}
      defaultValue={defaultValue}
      disabled={disabled}
      required={required}
      name={name}
      onValueChange={handleChange}
      onFocus={handleFocus}
      onBlur={handleBlur}
      className={cn(
        orientation === 'horizontal' && 'flex flex-row space-x-4',
        className
      )}
      aria-required={required}
    >
      {options.map((option: { label: string; value: string; disabled?: boolean }) => {
        const itemId = `${config.id}-${option.value}`;
        return (
          <div key={option.value} className="flex items-center space-x-2">
            <RadioGroupItem
              value={option.value}
              id={itemId}
              disabled={disabled || option.disabled}
            />
            <Label
              htmlFor={itemId}
              className={cn(
                "text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70",
                (disabled || option.disabled) && "opacity-50 cursor-not-allowed"
              )}
            >
              {option.label}
            </Label>
          </div>
        );
      })}
    </RadioGroup>
  );
}
