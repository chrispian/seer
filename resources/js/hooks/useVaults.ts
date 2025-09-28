import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useAppStore, Vault } from '../stores/useAppStore';

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
  
  return useQuery({
    queryKey: ['vaults'],
    queryFn: fetchVaults,
    onSuccess: (data) => {
      setVaults(data.vaults);
      setLoadingVaults(false);
    },
    onError: () => {
      setLoadingVaults(false);
    },
    onLoading: () => {
      setLoadingVaults(true);
    },
  });
};

export const useCreateVault = () => {
  const queryClient = useQueryClient();
  const { addVault, addProject, setCurrentVault, setCurrentProject } = useAppStore();
  
  return useMutation({
    mutationFn: createVault,
    onSuccess: (data) => {
      // Add vault and default project to store
      addVault(data.vault);
      addProject({
        ...data.default_project,
        vault_name: data.vault.name,
        chat_sessions_count: 0,
        fragments_count: 0,
      });
      
      // Auto-switch to new vault and its default project
      setCurrentVault(data.vault.id);
      setCurrentProject(data.default_project.id);
      
      // Invalidate queries to refresh UI
      queryClient.invalidateQueries({ queryKey: ['vaults'] });
      queryClient.invalidateQueries({ queryKey: ['projects'] });
      queryClient.invalidateQueries({ queryKey: ['chat-sessions'] });
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