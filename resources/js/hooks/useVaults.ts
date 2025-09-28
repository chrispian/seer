import React from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useAppStore, Vault } from '../stores/useAppStore';
import { useToast } from './useToast';

const API_BASE = '/api';

interface CreateVaultData {
  name: string;
  description?: string;
  is_default?: boolean;
}

interface CreateVaultResponse {
  vault: Vault;
  default_project: {
    id: number;
    name: string;
    description?: string;
    vault_id: number;
    is_default: boolean;
    sort_order: number;
    created_at: string;
    updated_at: string;
  };
}

// API functions
const fetchVaults = async (): Promise<{ vaults: Vault[] }> => {
  const response = await fetch(`${API_BASE}/vaults`);
  if (!response.ok) {
    throw new Error('Failed to fetch vaults');
  }
  return response.json();
};

const createVault = async (data: CreateVaultData): Promise<CreateVaultResponse> => {
  const response = await fetch(`${API_BASE}/vaults`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
    },
    body: JSON.stringify(data),
  });
  
  if (!response.ok) {
    const error = await response.json();
    throw new Error(error.message || 'Failed to create vault');
  }
  
  return response.json();
};

const updateVault = async ({ id, ...data }: { id: number } & Partial<CreateVaultData>): Promise<{ vault: Vault }> => {
  const response = await fetch(`${API_BASE}/vaults/${id}`, {
    method: 'PUT',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
    },
    body: JSON.stringify(data),
  });
  
  if (!response.ok) {
    const error = await response.json();
    throw new Error(error.message || 'Failed to update vault');
  }
  
  return response.json();
};

const deleteVault = async (id: number): Promise<{ message: string }> => {
  const response = await fetch(`${API_BASE}/vaults/${id}`, {
    method: 'DELETE',
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
    },
  });
  
  if (!response.ok) {
    const error = await response.json();
    throw new Error(error.message || 'Failed to delete vault');
  }
  
  return response.json();
};

// React Query hooks
export const useVaults = () => {
  const { setVaults, setLoadingVaults } = useAppStore();
  
  const query = useQuery({
    queryKey: ['vaults'],
    queryFn: fetchVaults,
  });

  // Update store when data changes
  React.useEffect(() => {
    if (query.data) {
      setVaults(query.data.vaults);
    }
  }, [query.data, setVaults]);

  // Update loading state
  React.useEffect(() => {
    setLoadingVaults(query.isLoading);
  }, [query.isLoading, setLoadingVaults]);

  return query;
};

export const useCreateVault = () => {
  const queryClient = useQueryClient();
  const { addVault, addProject, switchToVault } = useAppStore();
  const { success, error } = useToast();
  
  return useMutation({
    mutationFn: createVault,
    onSuccess: async (data) => {
      // Add vault and default project to store
      addVault(data.vault);
      addProject({
        ...data.default_project,
        vault_name: data.vault.name,
        chat_sessions_count: 0,
        fragments_count: 0,
      });
      
      // Auto-switch to new vault (this will set it as default and update projects)
      await switchToVault(data.vault.id, true);
      
      // Invalidate queries to refresh UI
      queryClient.invalidateQueries({ queryKey: ['vaults'] });
      queryClient.invalidateQueries({ queryKey: ['projects'] });
      queryClient.invalidateQueries({ queryKey: ['chat-sessions'] });
      
      // Show success notification
      success('Vault Created', `Successfully created "${data.vault.name}" and switched to it.`);
    },
    onError: (err) => {
      error('Failed to Create Vault', err instanceof Error ? err.message : 'An unexpected error occurred.');
    },
  });
};

export const useUpdateVault = () => {
  const queryClient = useQueryClient();
  const { updateVault: updateVaultInStore } = useAppStore();
  
  return useMutation({
    mutationFn: updateVault,
    onSuccess: (data) => {
      updateVaultInStore(data.vault);
      queryClient.invalidateQueries({ queryKey: ['vaults'] });
    },
  });
};

export const useDeleteVault = () => {
  const queryClient = useQueryClient();
  const { removeVault } = useAppStore();
  
  return useMutation({
    mutationFn: deleteVault,
    onSuccess: (_, vaultId) => {
      removeVault(vaultId);
      queryClient.invalidateQueries({ queryKey: ['vaults'] });
      queryClient.invalidateQueries({ queryKey: ['projects'] });
      queryClient.invalidateQueries({ queryKey: ['chat-sessions'] });
    },
  });
};

// Hook for manually switching to a vault and setting it as default
export const useSwitchToVault = () => {
  const queryClient = useQueryClient();
  const { switchToVault } = useAppStore();
  const { vaults } = useAppStore((state) => ({ vaults: state.vaults }));
  const { success, error } = useToast();
  
  return useMutation({
    mutationFn: async (vaultId: number) => {
      await switchToVault(vaultId, true);
      return vaultId;
    },
    onSuccess: (vaultId) => {
      // Invalidate all context-dependent queries
      queryClient.invalidateQueries({ queryKey: ['projects'] });
      queryClient.invalidateQueries({ queryKey: ['chat-sessions'] });
      
      const vault = vaults.find(v => v.id === vaultId);
      if (vault) {
        success('Switched Vault', `Now working in "${vault.name}"`);
      }
    },
    onError: (err) => {
      error('Failed to Switch Vault', err instanceof Error ? err.message : 'An unexpected error occurred.');
    },
  });
};