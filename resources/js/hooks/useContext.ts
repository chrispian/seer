import { useQuery, useQueryClient } from '@tanstack/react-query';
import { useAppStore } from '../stores/useAppStore';
import { useEffect } from 'react';

const API_BASE = '/api';

interface ContextResponse {
  vaults: Array<{
    id: number;
    name: string;
    description: string;
    is_default: boolean;
  }>;
  projects: Array<{
    id: number;
    name: string;
    description: string;
    vault_id: number;
  }>;
  current_vault_id: number | null;
  current_project_id: number | null;
}

// API function
const fetchContext = async (): Promise<ContextResponse> => {
  const response = await fetch(`${API_BASE}/chat-sessions/context`);
  if (!response.ok) {
    throw new Error('Failed to fetch application context');
  }
  return response.json();
};

// React Query hook for fetching and managing application context
export const useAppContext = () => {
  const { initializeFromContext, currentVaultId, currentProjectId } = useAppStore();
  const queryClient = useQueryClient();
  
  const query = useQuery({
    queryKey: ['app-context'],
    queryFn: fetchContext,
    staleTime: 1000 * 60 * 5, // 5 minutes
    refetchOnWindowFocus: false,
  });

  // Initialize store when data is available
  useEffect(() => {
    if (query.data) {
      initializeFromContext(query.data);
    }
  }, [query.data, initializeFromContext]);

  // Function to manually refresh context (useful after major changes)
  const refreshContext = () => {
    queryClient.invalidateQueries({ queryKey: ['app-context'] });
  };

  // Effect to invalidate related queries when context changes
  useEffect(() => {
    if (currentVaultId || currentProjectId) {
      // Invalidate all context-dependent queries when context changes
      queryClient.invalidateQueries({ queryKey: ['chat-sessions'] });
      queryClient.invalidateQueries({ queryKey: ['projects'] });
    }
  }, [currentVaultId, currentProjectId, queryClient]);

  // Auto-create session when context is ready but no sessions exist
  const { createSessionIfNeeded } = useAppStore();
  useEffect(() => {
    if (query.data && currentVaultId && currentProjectId) {
      // Small delay to allow other queries to load first
      const timer = setTimeout(() => {
        createSessionIfNeeded();
      }, 1000);
      
      return () => clearTimeout(timer);
    }
  }, [query.data, currentVaultId, currentProjectId, createSessionIfNeeded]);

  return {
    ...query,
    refreshContext,
    isContextLoaded: !!query.data && !query.isLoading,
  };
};

// Hook to get current context state from Zustand store
export const useCurrentContext = () => {
  const store = useAppStore();
  
  return {
    currentVaultId: store.currentVaultId,
    currentProjectId: store.currentProjectId,
    currentSessionId: store.currentSessionId,
    currentVault: store.getCurrentVault(),
    currentProject: store.getCurrentProject(),
    currentSession: store.getCurrentSession(),
    projectsForCurrentVault: store.getProjectsForCurrentVault(),
    sessionsForCurrentContext: store.getSessionsForCurrentContext(),
    vaults: store.vaults,
    projects: store.projects,
    chatSessions: store.chatSessions,
    isLoadingVaults: store.isLoadingVaults,
    isLoadingProjects: store.isLoadingProjects,
    isLoadingSessions: store.isLoadingSessions,
  };
};