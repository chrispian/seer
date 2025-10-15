import { cn } from '@/lib/utils';
import { KbdConfig } from '../types';

export function KbdComponent({ config }: { config: KbdConfig }) {
  const { props } = config;
  const { keys, className } = props;

  return (
    <kbd
      className={cn(
        'pointer-events-none inline-flex h-5 select-none items-center gap-1 rounded border bg-muted px-1.5 font-mono text-[10px] font-medium text-muted-foreground opacity-100',
        className
      )}
    >
      {keys.map((key: string, index: number) => (
        <span key={index} className="inline-flex items-center gap-1">
          {index > 0 && <span className="text-xs">+</span>}
          <span>{key}</span>
        </span>
      ))}
    </kbd>
  );
}
