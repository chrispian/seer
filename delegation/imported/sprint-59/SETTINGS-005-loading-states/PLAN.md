# SETTINGS-005 Implementation Plan: Per-Section Loading States

## Phase 1: Loading State Infrastructure (2-3 hours)

### 1.1 Create Loading State Types and Hooks (1h)
```typescript
// resources/js/types/loading.ts
export interface SectionLoadingState {
  isLoading: boolean;
  operation: 'save' | 'validate' | 'reset' | 'import' | 'export' | 'provider' | 'model' | null;
  error: string | null;
  lastSaved: Date | null;
  hasUnsavedChanges: boolean;
  isOptimistic: boolean;
}

export interface SettingsLoadingState {
  profile: SectionLoadingState;
  preferences: SectionLoadingState;
  ai: SectionLoadingState;
  notifications: SectionLoadingState;
  admin: SectionLoadingState;
  global: {
    isInitializing: boolean;
    hasError: boolean;
  };
}

// resources/js/hooks/useOptimisticSettings.ts
export const useOptimisticSettings = (section: string) => {
  const [optimisticData, setOptimisticData] = useState(null);
  const queryClient = useQueryClient();
  
  const updateOptimistically = useCallback((updates: any) => {
    // Immediately update UI
    setOptimisticData(prev => ({ ...prev, ...updates }));
    
    // Update React Query cache optimistically
    queryClient.setQueryData(['settings', section], old => ({ ...old, ...updates }));
    
    // Return rollback function
    return () => {
      setOptimisticData(null);
      queryClient.invalidateQueries(['settings', section]);
    };
  }, [section, queryClient]);
  
  const clearOptimistic = useCallback(() => {
    setOptimisticData(null);
  }, []);
  
  return { optimisticData, updateOptimistically, clearOptimistic };
};
```

### 1.2 Create Section Loading Hook (1h)
```typescript
// resources/js/hooks/useSectionLoading.ts
export const useSectionLoading = (section: string) => {
  const [state, setState] = useState<SectionLoadingState>({
    isLoading: false,
    operation: null,
    error: null,
    lastSaved: null,
    hasUnsavedChanges: false,
    isOptimistic: false,
  });
  
  const startOperation = useCallback((operation: string) => {
    setState(prev => ({
      ...prev,
      isLoading: true,
      operation,
      error: null,
    }));
  }, []);
  
  const endOperation = useCallback((success: boolean, error?: string) => {
    setState(prev => ({
      ...prev,
      isLoading: false,
      operation: null,
      error: error || null,
      lastSaved: success ? new Date() : prev.lastSaved,
      hasUnsavedChanges: !success,
      isOptimistic: false,
    }));
  }, []);
  
  const markOptimistic = useCallback(() => {
    setState(prev => ({ ...prev, isOptimistic: true, hasUnsavedChanges: false }));
  }, []);
  
  const markUnsaved = useCallback(() => {
    setState(prev => ({ ...prev, hasUnsavedChanges: true }));
  }, []);
  
  const clearError = useCallback(() => {
    setState(prev => ({ ...prev, error: null }));
  }, []);
  
  return {
    state,
    startOperation,
    endOperation,
    markOptimistic,
    markUnsaved,
    clearError,
  };
};
```

### 1.3 Create Save Coordination Context (30min)
```typescript
// resources/js/contexts/SaveCoordinationContext.tsx
interface SaveCoordinationContextType {
  activeSaves: Set<string>;
  startSave: (section: string) => void;
  endSave: (section: string) => void;
  isAnySaving: boolean;
  isSectionSaving: (section: string) => boolean;
}

const SaveCoordinationContext = createContext<SaveCoordinationContextType | null>(null);

export const SaveCoordinationProvider = ({ children }: { children: React.ReactNode }) => {
  const [activeSaves, setActiveSaves] = useState<Set<string>>(new Set());
  
  const startSave = useCallback((section: string) => {
    setActiveSaves(prev => new Set([...prev, section]));
  }, []);
  
  const endSave = useCallback((section: string) => {
    setActiveSaves(prev => {
      const newSet = new Set(prev);
      newSet.delete(section);
      return newSet;
    });
  }, []);
  
  const isAnySaving = activeSaves.size > 0;
  const isSectionSaving = useCallback((section: string) => activeSaves.has(section), [activeSaves]);
  
  return (
    <SaveCoordinationContext.Provider value={{
      activeSaves,
      startSave,
      endSave,
      isAnySaving,
      isSectionSaving,
    }}>
      {children}
    </SaveCoordinationContext.Provider>
  );
};

export const useSaveCoordination = () => {
  const context = useContext(SaveCoordinationContext);
  if (!context) {
    throw new Error('useSaveCoordination must be used within SaveCoordinationProvider');
  }
  return context;
};
```

## Phase 2: Loading UI Components (2-3 hours)

### 2.1 Create Section Container Component (1h)
```typescript
// resources/js/islands/Settings/components/SectionContainer.tsx
interface SectionContainerProps {
  state: SectionLoadingState;
  children: React.ReactNode;
  title?: string;
  description?: string;
  className?: string;
}

export const SectionContainer = ({ 
  state, 
  children, 
  title, 
  description, 
  className = '' 
}: SectionContainerProps) => {
  return (
    <div className={`relative ${className}`}>
      {/* Section header with status */}
      {(title || description) && (
        <div className="mb-6">
          <div className="flex items-center justify-between">
            <div>
              {title && <h3 className="text-lg font-medium">{title}</h3>}
              {description && <p className="text-sm text-gray-600 mt-1">{description}</p>}
            </div>
            <SectionStatus state={state} />
          </div>
        </div>
      )}
      
      {/* Content with conditional overlay */}
      <div className={`transition-opacity duration-200 ${
        state.isLoading && state.operation !== 'save' ? 'opacity-75' : ''
      }`}>
        {children}
      </div>
      
      {/* Loading overlay for heavy operations */}
      {state.isLoading && ['import', 'export', 'reset'].includes(state.operation || '') && (
        <LoadingOverlay operation={state.operation} />
      )}
      
      {/* Error display */}
      {state.error && (
        <SectionErrorDisplay error={state.error} onDismiss={() => {/* clear error */}} />
      )}
    </div>
  );
};
```

### 2.2 Create Section Status Component (1h)
```typescript
// resources/js/islands/Settings/components/SectionStatus.tsx
export const SectionStatus = ({ state }: { state: SectionLoadingState }) => {
  if (state.isLoading) {
    return (
      <div className="flex items-center space-x-2 text-sm">
        <SmartLoadingIndicator 
          operation={state.operation} 
          size="sm" 
          showText={false} 
        />
        <span className={`${getOperationColor(state.operation)}`}>
          {getOperationLabel(state.operation)}...
        </span>
      </div>
    );
  }
  
  if (state.error) {
    return (
      <div className="flex items-center space-x-2 text-sm">
        <AlertTriangle className="h-4 w-4 text-red-600" />
        <span className="text-red-600">Error</span>
      </div>
    );
  }
  
  if (state.hasUnsavedChanges) {
    return (
      <div className="flex items-center space-x-2 text-sm">
        <Circle className="h-2 w-2 fill-yellow-500 text-yellow-500" />
        <span className="text-yellow-600">Unsaved changes</span>
      </div>
    );
  }
  
  if (state.isOptimistic) {
    return (
      <div className="flex items-center space-x-2 text-sm">
        <Clock className="h-4 w-4 text-blue-600" />
        <span className="text-blue-600">Saving...</span>
      </div>
    );
  }
  
  if (state.lastSaved) {
    return (
      <div className="flex items-center space-x-2 text-sm">
        <Check className="h-4 w-4 text-green-600" />
        <span className="text-green-600">
          Saved {formatRelativeTime(state.lastSaved)}
        </span>
      </div>
    );
  }
  
  return null;
};

const getOperationLabel = (operation: string | null) => {
  const labels = {
    save: 'Saving',
    validate: 'Validating',
    reset: 'Resetting',
    import: 'Importing',
    export: 'Exporting',
    provider: 'Loading provider',
    model: 'Loading models',
  };
  return labels[operation as keyof typeof labels] || 'Processing';
};

const getOperationColor = (operation: string | null) => {
  const colors = {
    save: 'text-blue-600',
    validate: 'text-purple-600',
    reset: 'text-orange-600',
    import: 'text-green-600',
    export: 'text-indigo-600',
    provider: 'text-blue-600',
    model: 'text-blue-600',
  };
  return colors[operation as keyof typeof colors] || 'text-gray-600';
};
```

### 2.3 Create Smart Loading Indicators (1h)
```typescript
// resources/js/islands/Settings/components/SmartLoadingIndicator.tsx
interface SmartLoadingIndicatorProps {
  loading?: boolean;
  operation?: string | null;
  size?: 'sm' | 'md' | 'lg';
  showText?: boolean;
  className?: string;
}

export const SmartLoadingIndicator = ({ 
  loading = true,
  operation, 
  size = 'sm',
  showText = true,
  className = ''
}: SmartLoadingIndicatorProps) => {
  if (!loading) return null;
  
  const config = {
    save: { color: 'blue', text: 'Saving...', icon: Save },
    validate: { color: 'purple', text: 'Validating...', icon: CheckCircle },
    import: { color: 'green', text: 'Importing...', icon: Upload },
    export: { color: 'indigo', text: 'Exporting...', icon: Download },
    reset: { color: 'orange', text: 'Resetting...', icon: RotateCcw },
    provider: { color: 'blue', text: 'Loading provider...', icon: Database },
    model: { color: 'blue', text: 'Loading models...', icon: Cpu },
  };
  
  const { color, text, icon: Icon } = config[operation as keyof typeof config] || { 
    color: 'gray', 
    text: 'Loading...', 
    icon: Loader 
  };
  
  const sizeClasses = {
    sm: 'h-4 w-4',
    md: 'h-5 w-5',
    lg: 'h-6 w-6',
  };
  
  return (
    <div className={`flex items-center space-x-2 text-${color}-600 ${className}`}>
      <Icon className={`${sizeClasses[size]} ${operation !== 'save' ? 'animate-spin' : ''}`} />
      {showText && <span className="text-sm">{text}</span>}
    </div>
  );
};

// Loading overlay for heavy operations
export const LoadingOverlay = ({ operation }: { operation: string | null }) => {
  return (
    <div className="absolute inset-0 bg-white/80 backdrop-blur-sm flex items-center justify-center z-10">
      <div className="bg-white border rounded-lg p-4 shadow-lg">
        <SmartLoadingIndicator 
          operation={operation} 
          size="md" 
          showText={true} 
        />
      </div>
    </div>
  );
};
```

## Phase 3: Auto-Save and Optimistic Updates (2-3 hours)

### 3.1 Create Auto-Save Hook (1h)
```typescript
// resources/js/hooks/useAutoSave.ts
export const useAutoSave = (
  section: string, 
  data: any, 
  enabled = true,
  delay = 2000
) => {
  const [isAutoSaving, setIsAutoSaving] = useState(false);
  const [lastAutoSave, setLastAutoSave] = useState<Date | null>(null);
  const [autoSaveError, setAutoSaveError] = useState<string | null>(null);
  
  const saveCoordination = useSaveCoordination();
  const { showAutoSaveSuccess, showSaveError } = useSettingsToasts();
  
  const debouncedSave = useMemo(
    () => debounce(async (dataToSave: any) => {
      if (!enabled) return;
      
      setIsAutoSaving(true);
      saveCoordination.startSave(`${section}-auto`);
      setAutoSaveError(null);
      
      try {
        await saveSettings(section, dataToSave);
        setLastAutoSave(new Date());
        showAutoSaveSuccess(section);
      } catch (error) {
        const errorMessage = error instanceof Error ? error.message : 'Auto-save failed';
        setAutoSaveError(errorMessage);
        console.warn(`Auto-save failed for ${section}:`, error);
        // Don't show toast for auto-save errors to avoid spam
      } finally {
        setIsAutoSaving(false);
        saveCoordination.endSave(`${section}-auto`);
      }
    }, delay),
    [section, enabled, delay, saveCoordination, showAutoSaveSuccess]
  );
  
  useEffect(() => {
    if (data && enabled) {
      debouncedSave(data);
    }
    
    return () => debouncedSave.cancel();
  }, [data, debouncedSave, enabled]);
  
  // Cancel auto-save when component unmounts
  useEffect(() => {
    return () => debouncedSave.cancel();
  }, [debouncedSave]);
  
  return { 
    isAutoSaving, 
    lastAutoSave, 
    autoSaveError,
    cancelAutoSave: debouncedSave.cancel 
  };
};
```

### 3.2 Create Enhanced Settings Mutations (1h)
```typescript
// resources/js/hooks/useSettingsMutation.ts
export const useSettingsMutation = (section: string) => {
  const queryClient = useQueryClient();
  const saveCoordination = useSaveCoordination();
  const { showSaveSuccess, showSaveError } = useSettingsToasts();
  
  return useMutation({
    mutationFn: (data: any) => saveSettings(section, data),
    
    onMutate: async (newData) => {
      // Cancel any outgoing refetches
      await queryClient.cancelQueries(['settings', section]);
      
      // Snapshot the previous value
      const previousData = queryClient.getQueryData(['settings', section]);
      
      // Optimistically update to the new value
      queryClient.setQueryData(['settings', section], (old: any) => ({
        ...old,
        ...newData,
      }));
      
      // Start save coordination
      saveCoordination.startSave(section);
      
      // Return context with rollback data
      return { previousData };
    },
    
    onError: (error, newData, context) => {
      // Rollback to previous data
      if (context?.previousData) {
        queryClient.setQueryData(['settings', section], context.previousData);
      }
      
      // Show error notification
      const errorMessage = error instanceof Error ? error.message : 'Failed to save settings';
      showSaveError(section, errorMessage);
    },
    
    onSuccess: (data, variables) => {
      // Show success notification
      const changedFields = Object.keys(variables);
      showSaveSuccess(section, changedFields);
    },
    
    onSettled: () => {
      // End save coordination
      saveCoordination.endSave(section);
      
      // Always refetch to ensure we have the latest server state
      queryClient.invalidateQueries(['settings', section]);
    },
  });
};
```

### 3.3 Create Optimistic Recovery Hook (1h)
```typescript
// resources/js/hooks/useOptimisticRecovery.ts
export const useOptimisticRecovery = (section: string) => {
  const queryClient = useQueryClient();
  const [failedUpdates, setFailedUpdates] = useState<FailedUpdate[]>([]);
  
  interface FailedUpdate {
    id: string;
    originalData: any;
    failedData: any;
    error: string;
    timestamp: Date;
  }
  
  const addFailedUpdate = useCallback((originalData: any, failedData: any, error: string) => {
    const failedUpdate: FailedUpdate = {
      id: `${section}-${Date.now()}`,
      originalData,
      failedData,
      error,
      timestamp: new Date(),
    };
    
    setFailedUpdates(prev => [...prev, failedUpdate]);
  }, [section]);
  
  const retryFailedUpdate = useCallback(async (failedUpdate: FailedUpdate) => {
    try {
      // Get current server state
      const currentServerData = await queryClient.fetchQuery(['settings', section]);
      
      // Attempt to merge failed changes with current server state
      const mergedData = mergeSettingsData(currentServerData, failedUpdate.failedData);
      
      // Attempt to save merged data
      await saveSettings(section, mergedData);
      
      // Remove from failed updates on success
      setFailedUpdates(prev => prev.filter(update => update.id !== failedUpdate.id));
      
      // Refresh data
      queryClient.invalidateQueries(['settings', section]);
      
      return true;
    } catch (error) {
      console.error('Failed to retry update:', error);
      return false;
    }
  }, [section, queryClient]);
  
  const dismissFailedUpdate = useCallback((failedUpdateId: string) => {
    setFailedUpdates(prev => prev.filter(update => update.id !== failedUpdateId));
  }, []);
  
  const clearAllFailedUpdates = useCallback(() => {
    setFailedUpdates([]);
  }, []);
  
  return {
    failedUpdates,
    addFailedUpdate,
    retryFailedUpdate,
    dismissFailedUpdate,
    clearAllFailedUpdates,
  };
};

// Utility function to merge settings data
const mergeSettingsData = (serverData: any, localChanges: any): any => {
  // Smart merge logic that handles conflicts
  // For now, simple merge with local changes taking precedence
  return {
    ...serverData,
    ...localChanges,
  };
};
```

## Phase 4: Enhanced Section Components (2-3 hours)

### 4.1 Update Profile Tab with Loading States (1h)
```typescript
// resources/js/islands/Settings/ProfileTab.tsx (enhanced)
export const ProfileTab = () => {
  const { state, startOperation, endOperation, markOptimistic, markUnsaved } = useSectionLoading('profile');
  const { updateOptimistically } = useOptimisticSettings('profile');
  const mutation = useSettingsMutation('profile');
  
  // Auto-save for profile changes
  const [profileData, setProfileData] = useState({});
  const { isAutoSaving } = useAutoSave('profile', profileData, true);
  
  const handleProfileChange = useCallback((field: string, value: any) => {
    const updates = { [field]: value };
    
    // Update local state immediately
    setProfileData(prev => ({ ...prev, ...updates }));
    
    // Mark as unsaved
    markUnsaved();
    
    // Optimistic update for immediate feedback
    updateOptimistically(updates);
    markOptimistic();
  }, [updateOptimistically, markOptimistic, markUnsaved]);
  
  const handleExplicitSave = async () => {
    startOperation('save');
    
    try {
      await mutation.mutateAsync(profileData);
      endOperation(true);
    } catch (error) {
      const errorMessage = error instanceof Error ? error.message : 'Failed to save profile';
      endOperation(false, errorMessage);
    }
  };
  
  const handleAvatarUpload = async (file: File) => {
    startOperation('upload');
    
    try {
      const formData = new FormData();
      formData.append('avatar', file);
      
      await uploadAvatar(formData);
      endOperation(true);
    } catch (error) {
      const errorMessage = error instanceof Error ? error.message : 'Failed to upload avatar';
      endOperation(false, errorMessage);
    }
  };
  
  return (
    <SectionContainer 
      state={state} 
      title="Profile Information"
      description="Manage your personal information and avatar"
    >
      <div className="space-y-6">
        <ProfileForm
          data={profileData}
          onChange={handleProfileChange}
          disabled={state.isLoading && state.operation !== 'save'}
        />
        
        <AvatarUploadSection
          onUpload={handleAvatarUpload}
          loading={state.isLoading && state.operation === 'upload'}
        />
        
        <div className="flex justify-between items-center">
          <div className="text-sm text-gray-600">
            {isAutoSaving && 'Auto-saving...'}
            {state.hasUnsavedChanges && 'You have unsaved changes'}
          </div>
          
          <Button 
            onClick={handleExplicitSave}
            disabled={state.isLoading || !state.hasUnsavedChanges}
          >
            {state.isLoading && state.operation === 'save' ? (
              <SmartLoadingIndicator operation="save" size="sm" showText={false} />
            ) : (
              'Save Changes'
            )}
          </Button>
        </div>
      </div>
    </SectionContainer>
  );
};
```

### 4.2 Update AI Configuration with Loading States (1.5h)
```typescript
// resources/js/islands/Settings/components/AIConfiguration.tsx (enhanced)
export const AIConfiguration = () => {
  const { state, startOperation, endOperation } = useSectionLoading('ai');
  const [providerLoading, setProviderLoading] = useState(false);
  const [modelLoading, setModelLoading] = useState(false);
  const [validationLoading, setValidationLoading] = useState(false);
  
  // Combine multiple loading states
  const combinedState = useMemo(() => ({
    ...state,
    isLoading: state.isLoading || providerLoading || modelLoading || validationLoading,
    operation: state.operation || 
               (providerLoading ? 'provider' : 
                modelLoading ? 'model' : 
                validationLoading ? 'validate' : null),
  }), [state, providerLoading, modelLoading, validationLoading]);
  
  const { data: providers } = useAIProviders();
  const { data: models } = useProviderModels(selectedProvider);
  const { validateConfig } = useConfigValidation();
  
  const handleProviderChange = async (provider: string) => {
    setProviderLoading(true);
    
    try {
      // Optimistically update provider
      updateOptimistically({ provider, model: '' }); // Reset model when provider changes
      
      // This will trigger model loading via React Query
      // No need for explicit model loading here
    } catch (error) {
      console.error('Provider change failed:', error);
    } finally {
      setProviderLoading(false);
    }
  };
  
  const handleModelChange = async (model: string) => {
    setModelLoading(true);
    
    try {
      updateOptimistically({ model });
      
      // Validate new configuration
      setValidationLoading(true);
      await validateConfig({ provider: selectedProvider, model });
    } catch (error) {
      console.error('Model validation failed:', error);
    } finally {
      setModelLoading(false);
      setValidationLoading(false);
    }
  };
  
  const handleParameterChange = (parameter: string, value: any) => {
    updateOptimistically({ [parameter]: value });
    markUnsaved();
    
    // Debounced validation for parameters
    debouncedValidate({ ...currentConfig, [parameter]: value });
  };
  
  return (
    <SectionContainer 
      state={combinedState}
      title="AI Configuration"
      description="Configure your AI provider and model preferences"
    >
      <div className="space-y-6">
        <ProviderSelector
          providers={providers}
          selected={selectedProvider}
          onSelect={handleProviderChange}
          loading={providerLoading}
        />
        
        {selectedProvider && (
          <ModelSelector
            models={models}
            selected={selectedModel}
            onSelect={handleModelChange}
            loading={modelLoading}
          />
        )}
        
        {selectedModel && (
          <AIParameterControls
            config={currentConfig}
            model={selectedModelData}
            onChange={handleParameterChange}
            validationLoading={validationLoading}
          />
        )}
        
        <ConfigurationHealth
          config={currentConfig}
          validation={validationResult}
          loading={validationLoading}
        />
      </div>
    </SectionContainer>
  );
};
```

### 4.3 Create Global Save Indicator (30min)
```typescript
// resources/js/islands/Settings/components/GlobalSaveIndicator.tsx
export const GlobalSaveIndicator = ({ activeSaves }: { activeSaves: Set<string> }) => {
  if (activeSaves.size === 0) return null;
  
  const saveList = Array.from(activeSaves);
  const autoSaves = saveList.filter(save => save.includes('-auto'));
  const manualSaves = saveList.filter(save => !save.includes('-auto'));
  
  return (
    <div className="fixed bottom-4 right-4 z-50">
      <div className="bg-white border border-gray-200 rounded-lg shadow-lg p-4 max-w-sm">
        <div className="flex items-center space-x-2 mb-2">
          <Loader className="h-4 w-4 animate-spin text-blue-600" />
          <span className="font-medium text-sm">Saving Changes</span>
        </div>
        
        {manualSaves.length > 0 && (
          <div className="text-sm text-gray-600">
            Saving: {manualSaves.join(', ')}
          </div>
        )}
        
        {autoSaves.length > 0 && (
          <div className="text-xs text-gray-500">
            Auto-saving: {autoSaves.map(s => s.replace('-auto', '')).join(', ')}
          </div>
        )}
      </div>
    </div>
  );
};
```

## Phase 5: Integration and Testing (1-2 hours)

### 5.1 Update Settings Layout (1h)
```typescript
// resources/js/islands/Settings/SettingsLayout.tsx (updated)
export const SettingsLayout = () => {
  const saveCoordination = useSaveCoordination();
  
  return (
    <SaveCoordinationProvider>
      <div className="max-w-6xl mx-auto p-6">
        <div className="grid grid-cols-1 lg:grid-cols-4 gap-6">
          <SettingsSidebar />
          
          <div className="lg:col-span-3">
            <Outlet /> {/* React Router outlet for tab content */}
          </div>
        </div>
        
        {/* Global save indicator */}
        <GlobalSaveIndicator activeSaves={saveCoordination.activeSaves} />
        
        {/* Global error recovery */}
        <ErrorRecoveryPanel />
      </div>
    </SaveCoordinationProvider>
  );
};
```

### 5.2 Create Toast Integration (30min)
```typescript
// resources/js/hooks/useSettingsToasts.ts
export const useSettingsToasts = () => {
  const { toast } = useToast();
  
  const showSaveSuccess = useCallback((section: string, changedFields?: string[]) => {
    toast({
      title: `${section.charAt(0).toUpperCase() + section.slice(1)} settings saved`,
      description: changedFields 
        ? `Updated: ${changedFields.join(', ')}`
        : 'Your changes have been saved successfully',
      variant: 'success',
      duration: 3000,
    });
  }, [toast]);
  
  const showAutoSaveSuccess = useCallback((section: string) => {
    toast({
      title: 'Auto-saved',
      description: `${section} settings automatically saved`,
      variant: 'success',
      duration: 2000,
    });
  }, [toast]);
  
  const showSaveError = useCallback((section: string, error: string) => {
    toast({
      title: `Failed to save ${section} settings`,
      description: error,
      variant: 'destructive',
      duration: 5000,
      action: {
        label: 'Retry',
        onClick: () => {
          // Trigger retry logic
        },
      },
    });
  }, [toast]);
  
  return { showSaveSuccess, showAutoSaveSuccess, showSaveError };
};
```

### 5.3 Testing and Polish (30min)
```typescript
// Test scenarios for loading states
// 1. Individual section loading while others remain interactive
// 2. Optimistic updates with rollback on error
// 3. Auto-save coordination with manual saves
// 4. Error recovery and retry mechanisms
// 5. Mobile responsiveness of loading indicators
```

## Success Metrics
- [ ] Each settings section has independent loading states
- [ ] Users can interact with other sections during save operations
- [ ] Optimistic updates provide immediate feedback with proper rollback
- [ ] Auto-save works seamlessly with manual save operations
- [ ] Error handling is section-specific with clear recovery options
- [ ] Loading indicators clearly communicate operation progress
- [ ] Save confirmations provide timely success feedback
- [ ] Performance improves with granular state management
- [ ] Mobile experience maintains usability during loading states

## Dependencies
- React Query for optimistic updates and caching
- Toast system for user notifications
- Existing settings API endpoints
- Debounce utility for auto-save
- Loading and success/error UI components