import { cn } from '@/lib/utils';
import { InputGroupConfig } from '../types';
import { renderComponent } from '../ComponentRegistry';

export function InputGroupComponent({ config }: { config: InputGroupConfig }) {
  const { props } = config;
  const {
    prefix,
    suffix,
    input,
    className,
  } = props || {};

  return (
    <div className={cn('relative flex items-center', className)}>
      {prefix && (
        <div className="absolute left-3 flex items-center pointer-events-none text-muted-foreground">
          {typeof prefix === 'string' ? (
            <span className="text-sm">{prefix}</span>
          ) : (
            renderComponent(prefix)
          )}
        </div>
      )}

      <div className={cn('flex-1', prefix && 'pl-10', suffix && 'pr-10')}>
        {renderComponent(input)}
      </div>

      {suffix && (
        <div className="absolute right-3 flex items-center">
          {typeof suffix === 'string' ? (
            <span className="text-sm text-muted-foreground">{suffix}</span>
          ) : (
            renderComponent(suffix)
          )}
        </div>
      )}
    </div>
  );
}
