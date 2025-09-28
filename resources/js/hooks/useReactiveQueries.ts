import { useEffect } from 'react';
import { useQueryClient } from '@tanstack/react-query';
import { useAppStore } from '../stores/useAppStore';

/**
 * Central hook for managing reactive query invalidation based on context changes
 * This ensures all content automatically updates when vault/project context changes
 */
export const useReactiveQueries = () => {
  const queryClient = useQueryClient();
  const { currentVaultId, currentProjectId, currentSessionId } = useAppStore();

  // Invalidate all context-dependent queries when vault changes
  useEffect(() => {
    if (currentVaultId) {
      // Invalidate all queries that depend on vault context
      queryClient.invalidateQueries({ queryKey: ['chat-sessions'] });
      queryClient.invalidateQueries({ queryKey: ['projects'] });
      queryClient.invalidateQueries({ queryKey: ['fragments'] });
      queryClient.invalidateQueries({ queryKey: ['bookmarks'] });
      
      console.log(`🔄 Vault context changed to ${currentVaultId} - invalidating dependent queries`);
    }
  }, [currentVaultId, queryClient]);

  // Invalidate session-dependent queries when project changes
  useEffect(() => {
    if (currentProjectId) {
      // Invalidate queries that depend on project context
      queryClient.invalidateQueries({ queryKey: ['chat-sessions'] });
      queryClient.invalidateQueries({ queryKey: ['fragments'] });
      
      console.log(`🔄 Project context changed to ${currentProjectId} - invalidating dependent queries`);
    }
  }, [currentProjectId, queryClient]);

  // Invalidate current session data when session changes
  useEffect(() => {
    if (currentSessionId) {
      // Refetch current session details
      queryClient.invalidateQueries({ queryKey: ['chat-session', currentSessionId] });
      
      console.log(`🔄 Session context changed to ${currentSessionId} - invalidating session queries`);
    }
  }, [currentSessionId, queryClient]);

  return {
    invalidateAllQueries: () => {
      queryClient.invalidateQueries();
      console.log('🔄 Invalidated all queries');
    },
    invalidateContextQueries: () => {
      queryClient.invalidateQueries({ queryKey: ['chat-sessions'] });
      queryClient.invalidateQueries({ queryKey: ['projects'] });
      queryClient.invalidateQueries({ queryKey: ['fragments'] });
      console.log('🔄 Invalidated context-dependent queries');
    },
    invalidateSessionQueries: () => {
      queryClient.invalidateQueries({ queryKey: ['chat-sessions'] });
      console.log('🔄 Invalidated session queries');
    },
  };
};