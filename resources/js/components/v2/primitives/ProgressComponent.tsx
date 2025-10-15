import { Progress } from '@/components/ui/progress';
import { cn } from '@/lib/utils';
import { ProgressConfig } from '../types';

export function ProgressComponent({ config }: { config: ProgressConfig }) {
  const { props } = config;
  const {
    value = 0,
    showLabel = false,
    variant = 'default',
    size = 'default',
    className,
  } = props;

  const clampedValue = Math.min(Math.max(value, 0), 100);

  const variantClasses = {
    default: '[&>div]:bg-primary',
    success: '[&>div]:bg-green-600',
    error: '[&>div]:bg-destructive',
    warning: '[&>div]:bg-yellow-500',
  };

  const sizeClasses = {
    sm: 'h-1',
    default: 'h-2',
    lg: 'h-3',
  };

  return (
    <div className="w-full space-y-2">
      <Progress
        value={clampedValue}
        className={cn(
          sizeClasses[size as keyof typeof sizeClasses],
          variantClasses[variant as keyof typeof variantClasses],
          className
        )}
        aria-valuenow={clampedValue}
        aria-valuemin={0}
        aria-valuemax={100}
        aria-label={showLabel ? `Progress: ${clampedValue}%` : 'Progress'}
      />
      {showLabel && (
        <div className="flex justify-between text-xs text-muted-foreground">
          <span>{clampedValue}%</span>
          {clampedValue === 100 && <span className="text-green-600 font-medium">Complete</span>}
        </div>
      )}
    </div>
  );
}
