import { cn } from '@/lib/utils';
import { EmptyConfig, ComponentConfig } from '../types';
import { ButtonComponent } from './ButtonComponent';
import { FileQuestion } from 'lucide-react';

export function EmptyComponent({ config }: { config: EmptyConfig }) {
  const { props, children } = config;
  const {
    icon,
    title,
    description,
    action,
    className,
  } = props;

  return (
    <div
      className={cn(
        'flex flex-col items-center justify-center p-8 text-center',
        'min-h-[400px] w-full',
        className
      )}
      role="status"
      aria-live="polite"
    >
      <div className="flex flex-col items-center gap-4 max-w-md">
        <div className="rounded-full bg-muted p-4" aria-hidden="true">
          {icon ? (
            <span className="text-4xl">{icon}</span>
          ) : (
            <FileQuestion className="h-10 w-10 text-muted-foreground" />
          )}
        </div>

        <div className="space-y-2">
          <h3 className="text-lg font-semibold text-foreground">
            {title}
          </h3>
          {description && (
            <p className="text-sm text-muted-foreground">
              {description}
            </p>
          )}
        </div>

        {action && (
          <div className="mt-2">
            <ButtonComponent config={action} />
          </div>
        )}

        {children && children.length > 0 && (
          <div className="mt-4 w-full">
            {children.map((child: ComponentConfig, index: number) => (
              <div key={child.id || index}>
                {/* Render child components if needed */}
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}
