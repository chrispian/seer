import React from 'react'
import { Badge } from '@/components/ui/badge'
import { 
  FileText, 
  CheckSquare, 
  Clipboard, 
  Calendar, 
  User, 
  MessageSquare,
  Folder,
  Link,
  Bell,
  Lightbulb,
  Package
} from 'lucide-react'
import { TypeInfo } from '../hooks/useTypes'

interface TypeBadgeProps {
  type: string | TypeInfo
  variant?: 'default' | 'secondary' | 'outline' | 'destructive'
  size?: 'sm' | 'default' | 'lg'
  showIcon?: boolean
  showCapabilities?: boolean
  className?: string
}

const typeIconMap: Record<string, React.ComponentType<any>> = {
  'check-square': CheckSquare,
  'file-text': FileText,
  'clipboard': Clipboard,
  'calendar': Calendar,
  'user': User,
  'message-square': MessageSquare,
  'folder': Folder,
  'link': Link,
  'bell': Bell,
  'lightbulb': Lightbulb,
  'package': Package,
}

const defaultTypeColors: Record<string, string> = {
  'todo': '#10B981',
  'note': '#3B82F6',
  'contact': '#8B5CF6',
  'event': '#F59E0B',
  'link': '#06B6D4',
  'document': '#6B7280',
  'task': '#EF4444',
  'idea': '#F97316',
}

export function TypeBadge({ 
  type, 
  variant = 'outline',
  size = 'default',
  showIcon = true,
  showCapabilities = false,
  className 
}: TypeBadgeProps) {
  const typeInfo = typeof type === 'string' ? null : type
  const typeSlug = typeof type === 'string' ? type : type.slug
  
  const displayName = typeInfo?.ui?.display_name || typeInfo?.name || typeSlug
  const icon = typeInfo?.ui?.icon || 'file-text'
  const color = typeInfo?.ui?.color || defaultTypeColors[typeSlug] || '#6B7280'
  
  const IconComponent = typeIconMap[icon] || FileText

  const badgeStyle = variant === 'outline' ? {
    borderColor: color,
    color: color,
  } : variant === 'default' ? {
    backgroundColor: color,
    borderColor: color,
    color: '#FFFFFF',
  } : {}

  return (
    <div className="flex flex-col gap-1">
      <Badge
        variant={variant}
        className={`${
          size === 'sm' ? 'text-xs h-5' : 
          size === 'lg' ? 'text-sm h-7' : 
          'text-xs h-6'
        } ${className}`}
        style={badgeStyle}
      >
        {showIcon && (
          <IconComponent className={`${
            size === 'sm' ? 'w-3 h-3' : 'w-3 h-3'
          } mr-1 flex-shrink-0`} />
        )}
        <span className="truncate">{displayName}</span>
      </Badge>
      
      {showCapabilities && typeInfo?.capabilities && typeInfo.capabilities.length > 0 && (
        <div className="flex flex-wrap gap-1">
          {typeInfo.capabilities.slice(0, 3).map((capability, index) => (
            <Badge key={index} variant="secondary" className="text-xs">
              {capability.replace(/_/g, ' ')}
            </Badge>
          ))}
          {typeInfo.capabilities.length > 3 && (
            <Badge variant="secondary" className="text-xs">
              +{typeInfo.capabilities.length - 3} more
            </Badge>
          )}
        </div>
      )}
    </div>
  )
}

export function TypeValidationIndicator({ 
  isValid, 
  errors, 
  className 
}: { 
  isValid?: boolean
  errors?: string[]
  className?: string 
}) {
  if (isValid === undefined) return null

  return (
    <Badge
      variant={isValid ? 'default' : 'destructive'}
      className={`text-xs ${className}`}
      title={errors?.join(', ')}
    >
      {isValid ? (
        <>
          <CheckSquare className="w-3 h-3 mr-1" />
          Valid
        </>
      ) : (
        <>
          <FileText className="w-3 h-3 mr-1" />
          {errors?.length || 0} errors
        </>
      )}
    </Badge>
  )
}