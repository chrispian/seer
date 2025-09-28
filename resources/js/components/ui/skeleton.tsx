import React from 'react';
import { cn } from "@/lib/utils"

interface SkeletonProps extends React.HTMLAttributes<HTMLDivElement> {
  width?: string;
  height?: string;
  variant?: 'text' | 'circular' | 'rectangular';
  lines?: number;
  animate?: boolean;
}

function Skeleton({
  className,
  width,
  height,
  variant = 'rectangular',
  lines = 1,
  animate = true,
  ...props
}: SkeletonProps) {
  const baseClasses = cn(
    "bg-neutral-200 dark:bg-neutral-800",
    animate && "animate-pulse",
    variant === 'text' && "h-4 rounded",
    variant === 'circular' && "rounded-full",
    variant === 'rectangular' && "rounded-md",
    className
  );
  
  const style: React.CSSProperties = { ...props.style };
  if (width) style.width = width;
  if (height) style.height = height;

  if (lines > 1) {
    return (
      <div className="space-y-2">
        {Array.from({ length: lines }).map((_, index) => (
          <div
            key={index}
            className={baseClasses}
            style={{
              ...style,
              width: index === lines - 1 ? '75%' : style.width || '100%', // Last line shorter
            }}
          />
        ))}
      </div>
    );
  }

  return <div className={baseClasses} style={style} {...props} />;
}

// Specialized skeleton components for common UI patterns
const ChatSessionSkeleton: React.FC = () => (
  <div className="p-3 border-b dark:border-neutral-800 animate-pulse">
    <div className="flex items-start gap-3">
      <Skeleton variant="circular" width="32px" height="32px" />
      <div className="flex-1 min-w-0">
        <Skeleton variant="text" width="60%" height="16px" className="mb-1" />
        <Skeleton variant="text" width="80%" height="14px" />
      </div>
      <Skeleton variant="text" width="40px" height="12px" />
    </div>
  </div>
);

const ChatMessageSkeleton: React.FC = () => (
  <div className="p-4 animate-pulse">
    <div className="flex items-start gap-3">
      <Skeleton variant="circular" width="32px" height="32px" />
      <div className="flex-1 min-w-0">
        <Skeleton variant="text" lines={3} />
      </div>
    </div>
  </div>
);

const SidebarSkeleton: React.FC = () => (
  <div className="p-4 space-y-4">
    {/* Vault/Project selectors */}
    <div className="space-y-2">
      <Skeleton variant="text" width="100%" height="36px" />
      <Skeleton variant="text" width="100%" height="36px" />
    </div>
    
    {/* Pinned sessions */}
    <div className="space-y-1">
      <Skeleton variant="text" width="60%" height="16px" />
      {Array.from({ length: 3 }).map((_, i) => (
        <ChatSessionSkeleton key={`pinned-${i}`} />
      ))}
    </div>
    
    {/* Recent sessions */}
    <div className="space-y-1">
      <Skeleton variant="text" width="60%" height="16px" />
      {Array.from({ length: 5 }).map((_, i) => (
        <ChatSessionSkeleton key={`recent-${i}`} />
      ))}
    </div>
  </div>
);

const VaultProjectSelectorSkeleton: React.FC = () => (
  <div className="space-y-2">
    <Skeleton variant="rectangular" width="100%" height="40px" />
    <Skeleton variant="rectangular" width="100%" height="40px" />
  </div>
);

const ChatHeaderSkeleton: React.FC = () => (
  <div className="flex items-center justify-between p-4 border-b dark:border-neutral-800 animate-pulse">
    <div className="flex items-center gap-3">
      <Skeleton variant="circular" width="32px" height="32px" />
      <div>
        <Skeleton variant="text" width="120px" height="18px" className="mb-1" />
        <Skeleton variant="text" width="80px" height="14px" />
      </div>
    </div>
    <div className="flex items-center gap-2">
      <Skeleton variant="circular" width="32px" height="32px" />
      <Skeleton variant="circular" width="32px" height="32px" />
    </div>
  </div>
);

export { 
  Skeleton, 
  ChatSessionSkeleton,
  ChatMessageSkeleton, 
  SidebarSkeleton,
  VaultProjectSelectorSkeleton,
  ChatHeaderSkeleton 
};
