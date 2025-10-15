import { cn } from '@/lib/utils';
import { ComponentConfig } from '../types';
import { ChevronRight } from 'lucide-react';

export interface BreadcrumbConfig extends ComponentConfig {
  type: 'breadcrumb';
  props: {
    items: Array<{
      label: string;
      href?: string;
      current?: boolean;
    }>;
    separator?: 'chevron' | 'slash' | 'none';
    className?: string;
  };
}

export function BreadcrumbComponent({ config }: { config: BreadcrumbConfig }) {
  const { props } = config;
  const { items, separator = 'chevron', className } = props;

  const renderSeparator = () => {
    if (separator === 'none') return null;
    if (separator === 'slash') {
      return <span className="mx-2 text-muted-foreground">/</span>;
    }
    return <ChevronRight className="mx-2 h-4 w-4 text-muted-foreground" />;
  };

  return (
    <nav aria-label="Breadcrumb" className={cn('flex items-center text-sm', className)}>
      <ol className="flex items-center gap-0">
        {items.map((item, index) => {
          const isLast = index === items.length - 1;
          const isCurrent = item.current || isLast;

          return (
            <li key={index} className="flex items-center">
              {index > 0 && renderSeparator()}
              {isCurrent ? (
                <span
                  className="font-medium text-foreground"
                  aria-current="page"
                >
                  {item.label}
                </span>
              ) : (
                <a
                  href={item.href}
                  className="text-muted-foreground hover:text-foreground transition-colors"
                >
                  {item.label}
                </a>
              )}
            </li>
          );
        })}
      </ol>
    </nav>
  );
}
