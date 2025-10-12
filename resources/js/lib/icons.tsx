import React from 'react'
import * as LucideIcons from 'lucide-react'
import { LucideProps } from 'lucide-react'

export function getIconComponent(iconName: string | null): React.ComponentType<LucideProps> | null {
  if (!iconName) return null
  
  // Convert kebab-case to PascalCase (bookmark -> Bookmark, check-square -> CheckSquare)
  const pascalCase = iconName
    .split('-')
    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
    .join('')
  
  const IconComponent = (LucideIcons as any)[pascalCase]
  
  return IconComponent || null
}

export function renderIcon(iconName: string | null, props?: LucideProps) {
  const IconComponent = getIconComponent(iconName)
  if (!IconComponent) return null
  return <IconComponent {...props} />
}
