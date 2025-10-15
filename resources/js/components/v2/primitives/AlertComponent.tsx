import { useState } from 'react';
import { Alert, AlertTitle, AlertDescription } from '@/components/ui/alert';
import { cn } from '@/lib/utils';
import { AlertConfig } from '../types';
import { X, AlertCircle, CheckCircle, AlertTriangle, Info } from 'lucide-react';
import { Button } from '@/components/ui/button';

const alertIcons = {
  default: Info,
  destructive: AlertCircle,
  warning: AlertTriangle,
  success: CheckCircle,
};

export function AlertComponent({ config }: { config: AlertConfig }) {
  const { props } = config;
  const {
    variant = 'default',
    title,
    description,
    icon,
    dismissible = false,
    className,
  } = props;

  const [dismissed, setDismissed] = useState(false);

  if (dismissed) return null;

  const Icon = icon ? null : alertIcons[variant as keyof typeof alertIcons];

  const variantMapping = {
    default: 'default' as const,
    destructive: 'destructive' as const,
    warning: 'default' as const,
    success: 'default' as const,
  };

  const customVariantClasses = {
    warning: 'border-yellow-500/50 text-yellow-900 dark:text-yellow-400 [&>svg]:text-yellow-600',
    success: 'border-green-500/50 text-green-900 dark:text-green-400 [&>svg]:text-green-600',
  };

  return (
    <Alert
      variant={variantMapping[variant as keyof typeof variantMapping]}
      className={cn(
        variant === 'warning' && customVariantClasses.warning,
        variant === 'success' && customVariantClasses.success,
        dismissible && 'pr-12',
        className
      )}
      role="alert"
      aria-live={variant === 'destructive' ? 'assertive' : 'polite'}
    >
      {Icon && <Icon className="h-4 w-4" aria-hidden="true" />}
      {icon && <span className="text-xl" aria-hidden="true">{icon}</span>}
      {title && <AlertTitle>{title}</AlertTitle>}
      <AlertDescription>{description}</AlertDescription>
      {dismissible && (
        <Button
          variant="ghost"
          size="icon"
          className="absolute right-2 top-2 h-6 w-6 opacity-70 hover:opacity-100"
          onClick={() => setDismissed(true)}
          aria-label="Dismiss alert"
        >
          <X className="h-4 w-4" />
        </Button>
      )}
    </Alert>
  );
}
