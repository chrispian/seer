import { Switch } from '@/components/ui/switch';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import { SwitchConfig } from '../types';

export function SwitchComponent({ config }: { config: SwitchConfig }) {
  const { props = {}, actions } = config;
  const {
    label,
    checked,
    defaultChecked,
    disabled = false,
    required = false,
    name,
    className,
  } = props;

  const handleChange = (checkedState: boolean) => {
    if (actions?.change) {
      const { type: actionType, event: eventName, command, payload } = actions.change;
      
      if (actionType === 'emit' && eventName) {
        window.dispatchEvent(new CustomEvent(eventName, {
          detail: { ...payload, checked: checkedState, name }
        }));
      } else if (actionType === 'command' && command) {
        window.dispatchEvent(new CustomEvent('command:execute', {
          detail: { command, payload: { ...payload, checked: checkedState, name } }
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

  const switchId = `switch-${config.id}`;

  return (
    <div className={cn("flex items-center space-x-2", className)}>
      <Switch
        id={switchId}
        checked={checked}
        defaultChecked={defaultChecked}
        disabled={disabled}
        required={required}
        name={name}
        onCheckedChange={handleChange}
        onFocus={handleFocus}
        onBlur={handleBlur}
        aria-required={required}
      />
      {label && (
        <Label
          htmlFor={switchId}
          className={cn(
            "text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70",
            disabled && "opacity-50 cursor-not-allowed"
          )}
        >
          {label}
          {required && <span className="text-destructive ml-1">*</span>}
        </Label>
      )}
    </div>
  );
}
