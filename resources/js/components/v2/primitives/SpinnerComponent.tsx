import { LoadingSpinner } from '@/components/ui/loading-spinner';
import { cn } from '@/lib/utils';
import { SpinnerConfig } from '../types';

export function SpinnerComponent({ config }: { config: SpinnerConfig }) {
  const { props = {} } = config;
  const { size = 'md', className } = props;

  return <LoadingSpinner size={size} className={cn(className)} />;
}
