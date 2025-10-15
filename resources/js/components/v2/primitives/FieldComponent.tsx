import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import { FieldConfig } from '../types';
import { renderComponent } from '../ComponentRegistry';

export function FieldComponent({ config }: { config: FieldConfig }) {
  const { props, children } = config;
  const {
    label,
    required = false,
    error,
    helperText,
    className,
  } = props || {};

  const fieldId = `field-${config.id}`;
  const fieldChild = children?.[0];

  return (
    <div className={cn("space-y-2", className)}>
      <Label
        htmlFor={fieldChild?.id || fieldId}
        className="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
      >
        {label}
        {required && <span className="text-destructive ml-1">*</span>}
      </Label>
      
      {fieldChild && (
        <div className="relative">
          {renderComponent(fieldChild)}
        </div>
      )}
      
      {error && (
        <p className="text-sm font-medium text-destructive" role="alert">
          {error}
        </p>
      )}
      
      {!error && helperText && (
        <p className="text-sm text-muted-foreground">
          {helperText}
        </p>
      )}
    </div>
  );
}
