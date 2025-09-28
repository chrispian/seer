import React from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useAppStore, ChatSession } from '../stores/useAppStore';
import { useToast } from './useToast';

const API_BASE = '/api';

interface CreateChatSessionData {
  vault_id?: number;
  project_id?: number;
  title?: string;
}

interface ChatSessionResponse {
  session: {
    id: number;
    title: string;
    channel_display: string;
    message_count: number;
    last_activity_at: string;
    is_pinned: boolean;
    sort_order: number;
    vault_id: number;
    project_id: number;
    messages: any[];
    metadata: any;
  };
}

interface ChatSessionsResponse {
  sessions: Array<{
    id: number;
    title: string;
    channel_display: string;
    message_count: number;
    last_activity_at: string;
    is_pinned: boolean;
    sort_order: number;
    vault_id: number;
    project_id: number;
  }>;
}

// API functions
const fetchChatSessions = async (vaultId?: number, projectId?: number, limit = 20): Promise<ChatSessionsResponse> => {
  const params = new URLSearchParams();
  if (vaultId) params.append('vault_id', vaultId.toString());
  if (projectId) params.append('project_id', projectId.toString());
  params.append('limit', limit.toString());

  const response = await fetch(`${API_BASE}/chat-sessions?${params}`);
  if (!response.ok) {
    throw new Error('Failed to fetch chat sessions');
  }
  return response.json();
};

const fetchPinnedChatSessions = async (vaultId?: number, projectId?: number): Promise<ChatSessionsResponse> => {
  const params = new URLSearchParams();
  if (vaultId) params.append('vault_id', vaultId.toString());
  if (projectId) params.append('project_id', projectId.toString());

  const response = await fetch(`${API_BASE}/chat-sessions/pinned?${params}`);
  if (!response.ok) {
    throw new Error('Failed to fetch pinned chat sessions');
  }
  return response.json();
};

const createChatSession = async (data: CreateChatSessionData): Promise<ChatSessionResponse> => {
  const response = await fetch(`${API_BASE}/chat-sessions`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
    },
    body: JSON.stringify(data),
  });
  
  if (!response.ok) {
    const error = await response.json();
    throw new Error(error.message || 'Failed to create chat session');
  }
  
  return response.json();
};

const updateChatSession = async ({ id, ...data }: { id: number } & Partial<CreateChatSessionData>): Promise<ChatSessionResponse> => {
  const response = await fetch(`${API_BASE}/chat-sessions/${id}`, {
    method: 'PUT',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
    },
    body: JSON.stringify(data),
  });
  
  if (!response.ok) {
    const error = await response.json();
    throw new Error(error.message || 'Failed to update chat session');
  }
  
  return response.json();
};

const deleteChatSession = async (id: number): Promise<{ message: string }> => {
  const response = await fetch(`${API_BASE}/chat-sessions/${id}`, {
    method: 'DELETE',
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
    },
  });
  
  if (!response.ok) {
    const error = await response.json();
    throw new Error(error.message || 'Failed to delete chat session');
  }
  
  return response.json();
};

const togglePinChatSession = async (id: number): Promise<ChatSessionResponse> => {
  const response = await fetch(`${API_BASE}/chat-sessions/${id}/pin`, {
    method: 'POST',
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
    },
  });
  
  if (!response.ok) {
    const error = await response.json();
    throw new Error(error.message || 'Failed to toggle pin status');
  }
  
  return response.json();
};

const fetchChatSessionDetails = async (id: number): Promise<ChatSessionResponse> => {
  const response = await fetch(`${API_BASE}/chat-sessions/${id}`, {
    method: 'GET',
    headers: {
      'Content-Type': 'application/json',
    },
  });
  
  if (!response.ok) {
    const error = await response.json();
    throw new Error(error.message || 'Failed to fetch chat session details');
  }
  
  return response.json();
};

// React Query hooks
export const useChatSessions = (limit = 20) => {
  const { currentVaultId, currentProjectId, setChatSessions, setLoadingSessions } = useAppStore();
  
  const query = useQuery({
    queryKey: ['chat-sessions', currentVaultId, currentProjectId, limit],
    queryFn: () => fetchChatSessions(currentVaultId || undefined, currentProjectId || undefined, limit),
    enabled: !!currentVaultId,
  });

  // Update store when data changes
  React.useEffect(() => {
    if (query.data) {
      const sessions: ChatSession[] = query.data.sessions.map(session => ({
        id: session.id,
        title: session.title,
        channel_display: session.channel_display,
        message_count: session.message_count,
        last_activity_at: session.last_activity_at,
        is_pinned: session.is_pinned,
        sort_order: session.sort_order,
        vault_id: session.vault_id,
        project_id: session.project_id,
      }));
      setChatSessions(sessions);
    }
  }, [query.data]); // Remove setChatSessions from deps

  // Update loading state
  React.useEffect(() => {
    setLoadingSessions(query.isLoading);
  }, [query.isLoading]); // Remove setLoadingSessions from deps

  return query;
};

export const usePinnedChatSessions = () => {
  const { currentVaultId, currentProjectId } = useAppStore();
  
  return useQuery({
    queryKey: ['chat-sessions', 'pinned', currentVaultId, currentProjectId],
    queryFn: () => fetchPinnedChatSessions(currentVaultId || undefined, currentProjectId || undefined),
    enabled: !!currentVaultId,
  });
};

export const useChatSessionDetails = (sessionId: number | null) => {
  const { updateChatSession: updateChatSessionInStore } = useAppStore();
  
  const query = useQuery({
    queryKey: ['chat-session', sessionId],
    queryFn: () => fetchChatSessionDetails(sessionId!),
    enabled: !!sessionId,
    staleTime: 1000 * 60 * 2, // 2 minutes - messages don't change often
  });

  // Update store when session details are loaded
  React.useEffect(() => {
    if (query.data?.session) {
      const session: ChatSession = {
        id: query.data.session.id,
        title: query.data.session.title,
        channel_display: query.data.session.channel_display,
        message_count: query.data.session.message_count,
        last_activity_at: query.data.session.last_activity_at,
        is_pinned: query.data.session.is_pinned,
        sort_order: query.data.session.sort_order,
        vault_id: query.data.session.vault_id,
        project_id: query.data.session.project_id,
        messages: query.data.session.messages,
        metadata: query.data.session.metadata,
      };
      
      // Update the session in the store with full details
      updateChatSessionInStore(session);
    }
  }, [query.data]); // Remove updateChatSessionInStore from deps

  return query;
};

export const useCreateChatSession = () => {
  const queryClient = useQueryClient();
  const { currentVaultId, currentProjectId, addChatSession, setCurrentSession } = useAppStore();
  const { success, error } = useToast();
  
  return useMutation({
    mutationFn: (data: Partial<CreateChatSessionData> = {}) => {
      return createChatSession({
        vault_id: data.vault_id || currentVaultId || undefined,
        project_id: data.project_id || currentProjectId || undefined,
        title: data.title,
      });
    },
    onSuccess: (data) => {
      const session: ChatSession = {
        id: data.session.id,
        title: data.session.title,
        channel_display: data.session.channel_display,
        message_count: data.session.message_count,
        last_activity_at: data.session.last_activity_at,
        is_pinned: data.session.is_pinned,
        sort_order: data.session.sort_order,
        vault_id: data.session.vault_id,
        project_id: data.session.project_id,
      };
      
      // Add session to store and set as current
      addChatSession(session);
      setCurrentSession(data.session.id);
      
      // Invalidate queries to refresh UI
      queryClient.invalidateQueries({ queryKey: ['chat-sessions'] });
      
      // Show success notification
      success('Chat Created', `Started new conversation: "${data.session.title}"`);
    },
    onError: (err) => {
      error('Failed to Create Chat', err instanceof Error ? err.message : 'An unexpected error occurred.');
    },
  });
};

export const useUpdateChatSession = () => {
  const queryClient = useQueryClient();
  const { updateChatSession: updateChatSessionInStore } = useAppStore();
  
  return useMutation({
    mutationFn: updateChatSession,
    onSuccess: (data) => {
      const session: ChatSession = {
        id: data.session.id,
        title: data.session.title,
        channel_display: data.session.channel_display,
        message_count: data.session.message_count,
        last_activity_at: data.session.last_activity_at,
        is_pinned: data.session.is_pinned,
        sort_order: data.session.sort_order,
        vault_id: data.session.vault_id,
        project_id: data.session.project_id,
      };
      
      updateChatSessionInStore(session);
      queryClient.invalidateQueries({ queryKey: ['chat-sessions'] });
    },
  });
};

export const useDeleteChatSession = () => {
  const queryClient = useQueryClient();
  const { removeChatSession } = useAppStore();
  
  return useMutation({
    mutationFn: deleteChatSession,
    onSuccess: (_, sessionId) => {
      removeChatSession(sessionId);
      queryClient.invalidateQueries({ queryKey: ['chat-sessions'] });
    },
  });
};

export const useTogglePinChatSession = () => {
  const queryClient = useQueryClient();
  const { updateChatSession: updateChatSessionInStore } = useAppStore();
  
  return useMutation({
    mutationFn: togglePinChatSession,
    onSuccess: (data) => {
      const session: ChatSession = {
        id: data.session.id,
        title: data.session.title,
        channel_display: data.session.channel_display,
        message_count: data.session.message_count,
        last_activity_at: data.session.last_activity_at,
        is_pinned: data.session.is_pinned,
        sort_order: data.session.sort_order,
        vault_id: data.session.vault_id,
        project_id: data.session.project_id,
      };
      
      updateChatSessionInStore(session);
      queryClient.invalidateQueries({ queryKey: ['chat-sessions'] });
    },
  });
};