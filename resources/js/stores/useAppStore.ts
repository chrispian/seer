import { create } from 'zustand';
import { persist } from 'zustand/middleware';

export interface Vault {
  id: number;
  name: string;
  description?: string;
  is_default: boolean;
  sort_order: number;
  created_at: string;
  updated_at: string;
  projects_count: number;
  chat_sessions_count: number;
  fragments_count: number;
}

export interface Project {
  id: number;
  name: string;
  description?: string;
  vault_id: number;
  vault_name: string;
  is_default: boolean;
  sort_order: number;
  created_at: string;
  updated_at: string;
  chat_sessions_count: number;
  fragments_count: number;
}

export interface ChatSession {
  id: number;
  title: string;
  channel_display: string;
  message_count: number;
  last_activity_at: string;
  is_pinned: boolean;
  sort_order: number;
  vault_id: number;
  project_id: number;
}

interface AppState {
  // Current session state
  currentVaultId: number | null;
  currentProjectId: number | null;
  currentSessionId: number | null;

  // Data cache
  vaults: Vault[];
  projects: Project[];
  chatSessions: ChatSession[];

  // Loading states
  isLoadingVaults: boolean;
  isLoadingProjects: boolean;
  isLoadingSessions: boolean;

  // Actions
  setCurrentVault: (vaultId: number | null) => void;
  setCurrentProject: (projectId: number | null) => void;
  setCurrentSession: (sessionId: number | null) => void;
  
  // Context switching with server sync
  switchToVault: (vaultId: number, setAsDefault?: boolean) => Promise<void>;
  switchToProject: (projectId: number, setAsDefault?: boolean) => Promise<void>;
  initializeFromContext: (contextData: any) => void;
  
  setVaults: (vaults: Vault[]) => void;
  setProjects: (projects: Project[]) => void;
  setChatSessions: (sessions: ChatSession[]) => void;
  
  addVault: (vault: Vault) => void;
  updateVault: (vault: Vault) => void;
  removeVault: (vaultId: number) => void;
  
  addProject: (project: Project) => void;
  updateProject: (project: Project) => void;
  removeProject: (projectId: number) => void;
  
  addChatSession: (session: ChatSession) => void;
  updateChatSession: (session: ChatSession) => void;
  removeChatSession: (sessionId: number) => void;
  
  setLoadingVaults: (loading: boolean) => void;
  setLoadingProjects: (loading: boolean) => void;
  setLoadingSessions: (loading: boolean) => void;

  // Computed getters
  getCurrentVault: () => Vault | null;
  getCurrentProject: () => Project | null;
  getCurrentSession: () => ChatSession | null;
  getProjectsForCurrentVault: () => Project[];
  getSessionsForCurrentContext: () => ChatSession[];
}

export const useAppStore = create<AppState>()(
  persist(
    (set, get) => ({
      // Initial state
      currentVaultId: null,
      currentProjectId: null,
      currentSessionId: null,
      
      vaults: [],
      projects: [],
      chatSessions: [],
      
      isLoadingVaults: false,
      isLoadingProjects: false,
      isLoadingSessions: false,

      // Session setters
      setCurrentVault: (vaultId) => {
        set({ 
          currentVaultId: vaultId,
          // Reset dependent state
          currentProjectId: null,
          currentSessionId: null,
        });
      },
      
      setCurrentProject: (projectId) => {
        set({ 
          currentProjectId: projectId,
          // Reset dependent state
          currentSessionId: null,
        });
      },
      
      setCurrentSession: (sessionId) => {
        set({ currentSessionId: sessionId });
      },

      // Context switching with server sync
      switchToVault: async (vaultId, setAsDefault = true) => {
        // Optimistically update the store
        set({ 
          currentVaultId: vaultId,
          currentProjectId: null,
          currentSessionId: null,
        });

        if (setAsDefault) {
          try {
            const response = await fetch(`/api/vaults/${vaultId}/set-default`, {
              method: 'POST',
              headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
              },
            });

            if (response.ok) {
              const data = await response.json();
              
              // Update vault in store
              set((state) => ({
                vaults: state.vaults.map(v => ({
                  ...v,
                  is_default: v.id === vaultId
                })),
                currentProjectId: data.default_project_id || null,
              }));
            }
          } catch (error) {
            console.error('Failed to set vault as default:', error);
            // Don't revert optimistic update - the local state is still valid
          }
        }
      },

      switchToProject: async (projectId, setAsDefault = true) => {
        // Find the project to get its vault_id
        const state = get();
        const project = state.projects.find(p => p.id === projectId);
        
        if (!project) {
          console.error('Project not found in store:', projectId);
          return;
        }

        // Optimistically update the store
        set({ 
          currentVaultId: project.vault_id,
          currentProjectId: projectId,
          currentSessionId: null,
        });

        if (setAsDefault) {
          try {
            const response = await fetch(`/api/projects/${projectId}/set-default`, {
              method: 'POST',
              headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
              },
            });

            if (response.ok) {
              // Update project in store
              set((state) => ({
                projects: state.projects.map(p => ({
                  ...p,
                  is_default: p.vault_id === project.vault_id ? p.id === projectId : p.is_default
                }))
              }));
            }
          } catch (error) {
            console.error('Failed to set project as default:', error);
            // Don't revert optimistic update - the local state is still valid
          }
        }
      },

      initializeFromContext: (contextData) => {
        set({
          vaults: contextData.vaults || [],
          projects: contextData.projects || [],
          currentVaultId: contextData.current_vault_id || null,
          currentProjectId: contextData.current_project_id || null,
        });
      },

      // Data setters
      setVaults: (vaults) => set({ vaults }),
      setProjects: (projects) => set({ projects }),
      setChatSessions: (chatSessions) => set({ chatSessions }),

      // Vault management
      addVault: (vault) => {
        set((state) => ({
          vaults: [...state.vaults, vault].sort((a, b) => a.sort_order - b.sort_order),
        }));
      },
      
      updateVault: (vault) => {
        set((state) => ({
          vaults: state.vaults.map((v) => (v.id === vault.id ? vault : v)),
        }));
      },
      
      removeVault: (vaultId) => {
        set((state) => ({
          vaults: state.vaults.filter((v) => v.id !== vaultId),
          // Reset if current vault is being removed
          currentVaultId: state.currentVaultId === vaultId ? null : state.currentVaultId,
          currentProjectId: state.currentVaultId === vaultId ? null : state.currentProjectId,
          currentSessionId: state.currentVaultId === vaultId ? null : state.currentSessionId,
        }));
      },

      // Project management
      addProject: (project) => {
        set((state) => ({
          projects: [...state.projects, project].sort((a, b) => a.sort_order - b.sort_order),
        }));
      },
      
      updateProject: (project) => {
        set((state) => ({
          projects: state.projects.map((p) => (p.id === project.id ? project : p)),
        }));
      },
      
      removeProject: (projectId) => {
        set((state) => ({
          projects: state.projects.filter((p) => p.id !== projectId),
          // Reset if current project is being removed
          currentProjectId: state.currentProjectId === projectId ? null : state.currentProjectId,
          currentSessionId: state.currentProjectId === projectId ? null : state.currentSessionId,
        }));
      },

      // Chat session management
      addChatSession: (session) => {
        set((state) => ({
          chatSessions: [session, ...state.chatSessions],
        }));
      },
      
      updateChatSession: (session) => {
        set((state) => ({
          chatSessions: state.chatSessions.map((s) => (s.id === session.id ? session : s)),
        }));
      },
      
      removeChatSession: (sessionId) => {
        set((state) => ({
          chatSessions: state.chatSessions.filter((s) => s.id !== sessionId),
          // Reset if current session is being removed
          currentSessionId: state.currentSessionId === sessionId ? null : state.currentSessionId,
        }));
      },

      // Loading states
      setLoadingVaults: (loading) => set({ isLoadingVaults: loading }),
      setLoadingProjects: (loading) => set({ isLoadingProjects: loading }),
      setLoadingSessions: (loading) => set({ isLoadingSessions: loading }),

      // Computed getters
      getCurrentVault: () => {
        const state = get();
        return state.vaults.find((v) => v.id === state.currentVaultId) || null;
      },
      
      getCurrentProject: () => {
        const state = get();
        return state.projects.find((p) => p.id === state.currentProjectId) || null;
      },
      
      getCurrentSession: () => {
        const state = get();
        return state.chatSessions.find((s) => s.id === state.currentSessionId) || null;
      },
      
      getProjectsForCurrentVault: () => {
        const state = get();
        if (!state.currentVaultId) return [];
        return state.projects.filter((p) => p.vault_id === state.currentVaultId);
      },
      
      getSessionsForCurrentContext: () => {
        const state = get();
        let sessions = state.chatSessions;
        
        if (state.currentVaultId) {
          sessions = sessions.filter((s) => s.vault_id === state.currentVaultId);
        }
        
        if (state.currentProjectId) {
          sessions = sessions.filter((s) => s.project_id === state.currentProjectId);
        }
        
        return sessions;
      },
    }),
    {
      name: 'seer-app-store',
      // Only persist session state, not data cache or loading states
      partialize: (state) => ({
        currentVaultId: state.currentVaultId,
        currentProjectId: state.currentProjectId,
        currentSessionId: state.currentSessionId,
      }),
    }
  )
);