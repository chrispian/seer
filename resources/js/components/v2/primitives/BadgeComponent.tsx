import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';
import { BadgeConfig } from '../types';

export function BadgeComponent({ config }: { config: BadgeConfig }) {
  const { props } = config;
  const { text, variant = 'default', className } = props;

  return (
    <Badge variant={variant} className={cn(className)}>
      {text}
    </Badge>
  );
}
