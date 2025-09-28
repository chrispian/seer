import { useEffect, useState } from 'react';
import { useQueryClient } from '@tanstack/react-query';
import { useToast } from './useToast';

/**
 * Hook for managing offline support and optimistic updates
 */
export const useOfflineSupport = () => {
  const [isOnline, setIsOnline] = useState(navigator.onLine);
  const queryClient = useQueryClient();
  const { warning, success } = useToast();

  useEffect(() => {
    const handleOnline = () => {
      setIsOnline(true);
      success('Connection Restored', 'You are back online. Syncing data...');
      
      // Retry failed queries when coming back online
      queryClient.refetchQueries({ 
        predicate: (query) => query.state.status === 'error' 
      });
    };

    const handleOffline = () => {
      setIsOnline(false);
      warning('Connection Lost', 'You are offline. Some features may be limited.');
    };

    window.addEventListener('online', handleOnline);
    window.addEventListener('offline', handleOffline);

    return () => {
      window.removeEventListener('online', handleOnline);
      window.removeEventListener('offline', handleOffline);
    };
  }, [queryClient, success, warning]);

  // Enhanced cache management for offline scenarios
  const optimisticUpdate = <T>(
    queryKey: any[],
    updateFn: (oldData: T | undefined) => T,
    rollbackFn?: (oldData: T | undefined) => T
  ) => {
    // Store previous data for rollback
    const previousData = queryClient.getQueryData<T>(queryKey);
    
    // Optimistically update cache
    queryClient.setQueryData<T>(queryKey, updateFn);
    
    return {
      rollback: () => {
        if (rollbackFn) {
          queryClient.setQueryData<T>(queryKey, rollbackFn(previousData));
        } else {
          queryClient.setQueryData<T>(queryKey, previousData);
        }
      },
      previousData,
    };
  };

  // Prefetch critical data when online
  const prefetchCriticalData = () => {
    if (!isOnline) return;
    
    // Prefetch essential queries
    queryClient.prefetchQuery({
      queryKey: ['app-context'],
      staleTime: 1000 * 60 * 10, // 10 minutes
    });
    
    queryClient.prefetchQuery({
      queryKey: ['vaults'],
      staleTime: 1000 * 60 * 10,
    });
  };

  // Cache-first strategy for offline usage
  const getCachedData = <T>(queryKey: any[]): T | undefined => {
    return queryClient.getQueryData<T>(queryKey);
  };

  // Check if we have sufficient cached data to work offline
  const canWorkOffline = (): boolean => {
    const hasContext = !!queryClient.getQueryData(['app-context']);
    const hasVaults = !!queryClient.getQueryData(['vaults']);
    return hasContext && hasVaults;
  };

  return {
    isOnline,
    optimisticUpdate,
    prefetchCriticalData,
    getCachedData,
    canWorkOffline,
    
    // Helper for offline-aware mutations
    createOfflineAwareMutation: <TData, TVariables>(
      mutationFn: (variables: TVariables) => Promise<TData>,
      options?: {
        onOptimisticUpdate?: (variables: TVariables) => void;
        onRollback?: (variables: TVariables) => void;
      }
    ) => {
      return async (variables: TVariables) => {
        if (!isOnline) {
          // If offline, only apply optimistic update
          options?.onOptimisticUpdate?.(variables);
          throw new Error('Operation queued for when online');
        }
        
        try {
          const result = await mutationFn(variables);
          return result;
        } catch (error) {
          options?.onRollback?.(variables);
          throw error;
        }
      };
    },
  };
};

/**
 * Hook for optimistic session switching
 */
export const useOptimisticSessionSwitch = () => {
  const { optimisticUpdate, isOnline } = useOfflineSupport();
  const queryClient = useQueryClient();

  const switchSessionOptimistically = (
    sessionId: number,
    sessionData?: any
  ) => {
    // If we have session data, optimistically update the cache
    if (sessionData) {
      optimisticUpdate(
        ['chat-session', sessionId],
        () => ({ session: sessionData })
      );
    }

    // Always update the current session immediately for UI responsiveness
    return {
      sessionId,
      isOptimistic: !isOnline || !sessionData,
    };
  };

  const prefetchSessionIfNeeded = (sessionId: number) => {
    if (!isOnline) return;
    
    // Check if session is already cached
    const cachedSession = queryClient.getQueryData(['chat-session', sessionId]);
    
    if (!cachedSession) {
      // Prefetch in background
      queryClient.prefetchQuery({
        queryKey: ['chat-session', sessionId],
        staleTime: 1000 * 60 * 2, // 2 minutes
      });
    }
  };

  return {
    switchSessionOptimistically,
    prefetchSessionIfNeeded,
    isOnline,
  };
};