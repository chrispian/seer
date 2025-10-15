import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import { LabelConfig } from '../types';

export function LabelComponent({ config }: { config: LabelConfig }) {
  const { props } = config;
  const { text, htmlFor, required = false, className } = props;

  return (
    <Label htmlFor={htmlFor} className={cn(className)}>
      {text}
      {required && (
        <span className="text-destructive ml-1" aria-label="required">*</span>
      )}
    </Label>
  );
}
