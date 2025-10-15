import { Skeleton } from '@/components/ui/skeleton';
import { cn } from '@/lib/utils';
import { SkeletonConfig } from '../types';

export function SkeletonComponent({ config }: { config: SkeletonConfig }) {
  const { props = {} } = config;
  const {
    variant = 'rectangular',
    width,
    height,
    lines = 1,
    animate = true,
    className,
  } = props;

  return (
    <Skeleton
      variant={variant}
      width={width}
      height={height}
      lines={lines}
      animate={animate}
      className={cn(className)}
    />
  );
}
