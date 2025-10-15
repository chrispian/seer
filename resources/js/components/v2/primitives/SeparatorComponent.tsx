import { Separator } from '@/components/ui/separator';
import { cn } from '@/lib/utils';
import { SeparatorConfig } from '../types';

export function SeparatorComponent({ config }: { config: SeparatorConfig }) {
  const { props = {} } = config;
  const { orientation = 'horizontal', decorative = true, className } = props;

  return (
    <Separator
      orientation={orientation}
      decorative={decorative}
      className={cn(className)}
    />
  );
}
