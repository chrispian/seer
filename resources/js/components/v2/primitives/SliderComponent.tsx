import { Slider } from '@/components/ui/slider';
import { cn } from '@/lib/utils';
import { SliderConfig } from '../types';

export function SliderComponent({ config }: { config: SliderConfig }) {
  const { props = {}, actions } = config;
  const {
    min = 0,
    max = 100,
    step = 1,
    value,
    defaultValue = [50],
    disabled = false,
    name,
    className,
  } = props;

  const handleChange = (newValue: number[]) => {
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

  return (
    <div className={cn("w-full", className)}>
      <Slider
        min={min}
        max={max}
        step={step}
        value={value}
        defaultValue={defaultValue}
        disabled={disabled}
        name={name}
        onValueChange={handleChange}
        onFocus={handleFocus}
        aria-valuemin={min}
        aria-valuemax={max}
        aria-valuenow={value?.[0] || defaultValue[0]}
      />
      <div className="flex justify-between text-xs text-muted-foreground mt-2">
        <span>{min}</span>
        <span>{value?.[0] || defaultValue[0]}</span>
        <span>{max}</span>
      </div>
    </div>
  );
}
