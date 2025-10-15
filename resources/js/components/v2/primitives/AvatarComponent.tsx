import { Avatar, AvatarImage, AvatarFallback } from '@/components/ui/avatar';
import { cn } from '@/lib/utils';
import { AvatarConfig } from '../types';

export function AvatarComponent({ config }: { config: AvatarConfig }) {
  const { props = {} } = config;
  const { src, alt, fallback, size = 'md', className } = props;

  const sizeClasses = {
    sm: 'h-8 w-8',
    md: 'h-10 w-10',
    lg: 'h-12 w-12',
    xl: 'h-16 w-16',
  };

  return (
    <Avatar className={cn(sizeClasses[size], className)}>
      {src && <AvatarImage src={src} alt={alt || 'Avatar'} />}
      <AvatarFallback>{fallback || '?'}</AvatarFallback>
    </Avatar>
  );
}
