import { useQuery } from '@tanstack/react-query'

export interface User {
  id: number
  name: string
  email: string
  display_name?: string
  avatar_path?: string
  avatar_url?: string
  use_gravatar: boolean
  profile_settings?: Record<string, any>
  profile_completed_at?: string
}

export function useUser() {
  return useQuery<{ user: User }>({
    queryKey: ['user'],
    queryFn: async () => {
      const response = await fetch('/api/user', {
        headers: {
          'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
        },
      })
      
      if (!response.ok) {
        throw new Error('Failed to fetch user data')
      }
      
      return response.json()
    },
    staleTime: 5 * 60 * 1000, // 5 minutes
    gcTime: 10 * 60 * 1000, // 10 minutes
  })
}

export function getUserAvatarUrl(user: User): string {
  // The backend already provides the correct avatar URL
  if (user.avatar_url) {
    return user.avatar_url
  }
  
  // Fallback: use Gravatar with generic user icon
  return `https://www.gravatar.com/avatar/default?d=mp&s=128`
}

export function getUserDisplayName(user: User): string {
  return user.display_name || user.name || 'User'
}