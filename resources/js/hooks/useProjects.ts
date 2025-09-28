import React from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useAppStore, Project } from '../stores/useAppStore';
import { useToast } from './useToast';

const API_BASE = '/api';

interface CreateProjectData {
  vault_id: number;
  name: string;
  description?: string;
  is_default?: boolean;
}

// API functions
const fetchProjects = async (vaultId?: number): Promise<{ projects: Project[] }> => {
  const url = vaultId ? `${API_BASE}/projects?vault_id=${vaultId}` : `${API_BASE}/projects`;
  const response = await fetch(url);
  if (!response.ok) {
    throw new Error('Failed to fetch projects');
  }
  return response.json();
};

const fetchProjectsForVault = async (vaultId: number): Promise<{ projects: Project[] }> => {
  const response = await fetch(`${API_BASE}/vaults/${vaultId}/projects`);
  if (!response.ok) {
    throw new Error('Failed to fetch projects for vault');
  }
  return response.json();
};

const createProject = async (data: CreateProjectData): Promise<{ project: Project }> => {
  const response = await fetch(`${API_BASE}/projects`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
    },
    body: JSON.stringify(data),
  });
  
  if (!response.ok) {
    const error = await response.json();
    throw new Error(error.message || 'Failed to create project');
  }
  
  return response.json();
};

const updateProject = async ({ id, ...data }: { id: number } & Partial<Omit<CreateProjectData, 'vault_id'>>): Promise<{ project: Project }> => {
  const response = await fetch(`${API_BASE}/projects/${id}`, {
    method: 'PUT',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
    },
    body: JSON.stringify(data),
  });
  
  if (!response.ok) {
    const error = await response.json();
    throw new Error(error.message || 'Failed to update project');
  }
  
  return response.json();
};

const deleteProject = async (id: number): Promise<{ message: string }> => {
  const response = await fetch(`${API_BASE}/projects/${id}`, {
    method: 'DELETE',
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
    },
  });
  
  if (!response.ok) {
    const error = await response.json();
    throw new Error(error.message || 'Failed to delete project');
  }
  
  return response.json();
};

// React Query hooks
export const useProjects = (vaultId?: number) => {
  const { setProjects, setLoadingProjects } = useAppStore();
  
  const query = useQuery({
    queryKey: ['projects', vaultId],
    queryFn: () => fetchProjects(vaultId),
  });

  // Update store when data changes
  React.useEffect(() => {
    if (query.data) {
      setProjects(query.data.projects);
    }
  }, [query.data, setProjects]);

  // Update loading state
  React.useEffect(() => {
    setLoadingProjects(query.isLoading);
  }, [query.isLoading, setLoadingProjects]);

  return query;
};

export const useProjectsForVault = (vaultId: number) => {
  const { setProjects, setLoadingProjects } = useAppStore();
  
  const query = useQuery({
    queryKey: ['projects', 'vault', vaultId],
    queryFn: () => fetchProjectsForVault(vaultId),
    enabled: !!vaultId,
  });

  // Update store when data changes
  React.useEffect(() => {
    if (query.data) {
      setProjects(query.data.projects);
    }
  }, [query.data, setProjects]);

  // Update loading state
  React.useEffect(() => {
    setLoadingProjects(query.isLoading);
  }, [query.isLoading, setLoadingProjects]);

  return query;
};

export const useCreateProject = () => {
  const queryClient = useQueryClient();
  const { addProject, switchToProject } = useAppStore();
  const { success, error } = useToast();
  
  return useMutation({
    mutationFn: createProject,
    onSuccess: async (data) => {
      // Add project to store
      addProject(data.project);
      
      // Auto-switch to new project (this will set it as default)
      await switchToProject(data.project.id, true);
      
      // Invalidate queries to refresh UI
      queryClient.invalidateQueries({ queryKey: ['projects'] });
      queryClient.invalidateQueries({ queryKey: ['chat-sessions'] });
      
      // Show success notification
      success('Project Created', `Successfully created "${data.project.name}" and switched to it.`);
    },
    onError: (err) => {
      error('Failed to Create Project', err instanceof Error ? err.message : 'An unexpected error occurred.');
    },
  });
};

export const useUpdateProject = () => {
  const queryClient = useQueryClient();
  const { updateProject: updateProjectInStore } = useAppStore();
  
  return useMutation({
    mutationFn: updateProject,
    onSuccess: (data) => {
      updateProjectInStore(data.project);
      queryClient.invalidateQueries({ queryKey: ['projects'] });
    },
  });
};

export const useDeleteProject = () => {
  const queryClient = useQueryClient();
  const { removeProject } = useAppStore();
  
  return useMutation({
    mutationFn: deleteProject,
    onSuccess: (_, projectId) => {
      removeProject(projectId);
      queryClient.invalidateQueries({ queryKey: ['projects'] });
      queryClient.invalidateQueries({ queryKey: ['chat-sessions'] });
    },
  });
};

// Hook for manually switching to a project and setting it as default
export const useSwitchToProject = () => {
  const queryClient = useQueryClient();
  const { switchToProject } = useAppStore();
  const { projects } = useAppStore((state) => ({ projects: state.projects }));
  const { success, error } = useToast();
  
  return useMutation({
    mutationFn: async (projectId: number) => {
      await switchToProject(projectId, true);
      return projectId;
    },
    onSuccess: (projectId) => {
      // Invalidate chat sessions to refresh for new project context
      queryClient.invalidateQueries({ queryKey: ['chat-sessions'] });
      
      const project = projects.find(p => p.id === projectId);
      if (project) {
        success('Switched Project', `Now working in "${project.name}"`);
      }
    },
    onError: (err) => {
      error('Failed to Switch Project', err instanceof Error ? err.message : 'An unexpected error occurred.');
    },
  });
};