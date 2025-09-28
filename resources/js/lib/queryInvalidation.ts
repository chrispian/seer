import { QueryClient } from '@tanstack/react-query';

/**
 * Centralized query invalidation patterns to avoid redundant invalidations
 * and provide consistent cache management across the application.
 */

export interface InvalidationContext {
  vaultId?: number;
  projectId?: number;
  sessionId?: number;
}

export class QueryInvalidationManager {
  constructor(private queryClient: QueryClient) {}

  /**
   * Invalidate queries when vault context changes
   */
  invalidateVaultContext(vaultId: number, options?: { skipSessions?: boolean }) {
    const promises = [
      this.queryClient.invalidateQueries({ 
        queryKey: ['vaults'],
        type: 'active' // Only invalidate active queries for better performance
      }),
      this.queryClient.invalidateQueries({ 
        queryKey: ['projects', vaultId],
        type: 'active' 
      }),
    ];

    if (!options?.skipSessions) {
      promises.push(
        this.queryClient.invalidateQueries({ 
          queryKey: ['chat-sessions', vaultId],
          type: 'active' 
        })
      );
    }

    return Promise.all(promises);
  }

  /**
   * Invalidate queries when project context changes
   */
  invalidateProjectContext(vaultId: number, projectId: number) {
    return Promise.all([
      this.queryClient.invalidateQueries({ 
        queryKey: ['projects', vaultId],
        type: 'active' 
      }),
      this.queryClient.invalidateQueries({ 
        queryKey: ['chat-sessions', vaultId, projectId],
        type: 'active' 
      }),
    ]);
  }

  /**
   * Invalidate queries when session changes
   */
  invalidateSessionContext(sessionId: number) {
    return Promise.all([
      this.queryClient.invalidateQueries({ 
        queryKey: ['chat-session', sessionId],
        type: 'active' 
      }),
      this.queryClient.invalidateQueries({ 
        queryKey: ['fragments', sessionId],
        type: 'active' 
      }),
    ]);
  }

  /**
   * Smart invalidation based on entity type and operation
   */
  invalidateEntity(
    entityType: 'vault' | 'project' | 'session',
    operation: 'create' | 'update' | 'delete' | 'switch',
    context: InvalidationContext
  ) {
    switch (entityType) {
      case 'vault':
        if (operation === 'create' || operation === 'delete') {
          // Full vault list needs refresh
          return this.queryClient.invalidateQueries({ queryKey: ['vaults'] });
        } else if (operation === 'switch' && context.vaultId) {
          // Switch context - invalidate dependent queries
          return this.invalidateVaultContext(context.vaultId);
        }
        break;

      case 'project':
        if (operation === 'create' || operation === 'delete') {
          // Project list for current vault needs refresh
          return Promise.all([
            this.queryClient.invalidateQueries({ queryKey: ['projects'] }),
            context.vaultId ? this.queryClient.invalidateQueries({ 
              queryKey: ['chat-sessions', context.vaultId] 
            }) : Promise.resolve(),
          ]);
        } else if (operation === 'switch' && context.vaultId && context.projectId) {
          return this.invalidateProjectContext(context.vaultId, context.projectId);
        }
        break;

      case 'session':
        if (operation === 'create' || operation === 'delete') {
          // Session list needs refresh
          return this.queryClient.invalidateQueries({ 
            queryKey: ['chat-sessions'],
            exact: false // Invalidate all chat-sessions queries
          });
        } else if (operation === 'switch' && context.sessionId) {
          return this.invalidateSessionContext(context.sessionId);
        }
        break;
    }

    return Promise.resolve();
  }

  /**
   * Optimistic update for session switching - set cache immediately
   */
  setSessionCache(sessionId: number, sessionData: any) {
    this.queryClient.setQueryData(['chat-session', sessionId], {
      session: sessionData
    });
  }

  /**
   * Prefetch related data when switching contexts
   */
  prefetchRelatedData(context: InvalidationContext) {
    const promises: Promise<any>[] = [];

    // Prefetch project sessions if we have project context
    if (context.vaultId && context.projectId) {
      promises.push(
        this.queryClient.prefetchQuery({
          queryKey: ['chat-sessions', context.vaultId, context.projectId],
          staleTime: 1000 * 60 * 2, // Consider stale after 2 minutes
        })
      );
    }

    // Prefetch pinned sessions for current vault
    if (context.vaultId) {
      promises.push(
        this.queryClient.prefetchQuery({
          queryKey: ['chat-sessions', 'pinned', context.vaultId],
          staleTime: 1000 * 60 * 5, // Pinned sessions change less frequently
        })
      );
    }

    return Promise.all(promises);
  }

  /**
   * Clear stale cache entries to free memory
   */
  clearStaleCache() {
    this.queryClient.getQueryCache().clear();
  }

  /**
   * Get cache statistics for monitoring
   */
  getCacheStats() {
    const cache = this.queryClient.getQueryCache();
    const queries = cache.getAll();
    
    return {
      totalQueries: queries.length,
      staleQueries: queries.filter(q => q.isStale()).length,
      inactiveQueries: queries.filter(q => !q.getObserversCount()).length,
      errorQueries: queries.filter(q => q.state.status === 'error').length,
    };
  }
}

// Export singleton instance
let invalidationManager: QueryInvalidationManager | null = null;

export const getQueryInvalidationManager = (queryClient: QueryClient): QueryInvalidationManager => {
  if (!invalidationManager) {
    invalidationManager = new QueryInvalidationManager(queryClient);
  }
  return invalidationManager;
};

// Utility function for common invalidation patterns
export const createInvalidationHelper = (queryClient: QueryClient) => {
  const manager = getQueryInvalidationManager(queryClient);
  
  return {
    onVaultSwitch: (vaultId: number) => 
      manager.invalidateEntity('vault', 'switch', { vaultId }),
    
    onProjectSwitch: (vaultId: number, projectId: number) => 
      manager.invalidateEntity('project', 'switch', { vaultId, projectId }),
    
    onSessionSwitch: (sessionId: number) => 
      manager.invalidateEntity('session', 'switch', { sessionId }),
    
    onEntityCreate: (type: 'vault' | 'project' | 'session', context: InvalidationContext) =>
      manager.invalidateEntity(type, 'create', context),
    
    onEntityDelete: (type: 'vault' | 'project' | 'session', context: InvalidationContext) =>
      manager.invalidateEntity(type, 'delete', context),
      
    prefetchForContext: (context: InvalidationContext) =>
      manager.prefetchRelatedData(context),
      
    getCacheStats: () => manager.getCacheStats(),
  };
};