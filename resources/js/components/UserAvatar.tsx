import React from 'react'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { useUser, getUserAvatarUrl, getUserDisplayName } from '@/hooks/useUser'

interface UserAvatarProps {
  className?: string
  size?: 'sm' | 'md' | 'lg'
  showFallback?: boolean
}

export function UserAvatar({ className = '', size = 'md', showFallback = true }: UserAvatarProps) {
  const { data: userData, isLoading, error } = useUser()
  
  const sizeClasses = {
    sm: 'h-6 w-6',
    md: 'h-8 w-8', 
    lg: 'h-12 w-12'
  }
  
  // Handle loading state
  if (isLoading) {
    return (
      <Avatar className={`${sizeClasses[size]} ${className}`}>
        <AvatarFallback>...</AvatarFallback>
      </Avatar>
    )
  }
  
  // Handle error state  
  if (error || !userData?.user) {
    return (
      <Avatar className={`${sizeClasses[size]} ${className}`}>
        <AvatarFallback>{showFallback ? 'U' : ''}</AvatarFallback>
      </Avatar>
    )
  }
  
  const user = userData.user
  const avatarUrl = getUserAvatarUrl(user)
  const displayName = getUserDisplayName(user)
  const initials = displayName
    .split(' ')
    .map(word => word.charAt(0))
    .join('')
    .toUpperCase()
    .slice(0, 2)
  
  return (
    <Avatar className={`${sizeClasses[size]} ${className}`}>
      <AvatarImage 
        src={avatarUrl} 
        alt={`${displayName} Avatar`}
      />
      <AvatarFallback>
        {showFallback ? initials : ''}
      </AvatarFallback>
    </Avatar>
  )
}

export function useUserDisplayName() {
  const { data: userData, isLoading } = useUser()
  
  if (isLoading || !userData?.user) {
    return 'User'
  }
  
  return getUserDisplayName(userData.user)
}