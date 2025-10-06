# SETTINGS-005 Context: Per-Section Loading States

## Current Settings Architecture

### Existing Settings Components
```typescript
// Current settings structure with global loading
/resources/js/islands/Settings/
├── SettingsLayout.tsx              // Tab navigation
├── ProfileTab.tsx                  // Profile management
├── PreferencesTab.tsx              // Theme, notifications, layout
├── AppearanceTab.tsx               // Visual settings
└── components/
    ├── AIConfiguration.tsx         // AI provider/model settings
    ├── NotificationPreferences.tsx // Notification controls
    └── ImportExportControls.tsx    // Import/export functionality
```

### Current Loading State Problems
```typescript
// Current global loading pattern (problematic)
const SettingsLayout = () => {
  const [isLoading, setIsLoading] = useState(false);
  
  // Global loading blocks all interactions
  const handleSave = async () => {
    setIsLoading(true);
    try {
      await saveSettings();
    } finally {
      setIsLoading(false);
    }
  };
  
  return (
    <div className={isLoading ? 'pointer-events-none opacity-50' : ''}>
      {/* All tabs become unresponsive during any save operation */}
    </div>
  );
};
```

## Enhanced Loading State Architecture

### Section-Specific Loading States
```typescript
// New granular loading state structure
interface SectionLoadingState {
  isLoading: boolean;
  operation: 'save' | 'validate' | 'reset' | 'import' | 'export' | null;
  error: string | null;
  lastSaved: Date | null;
  hasUnsavedChanges: boolean;
  isOptimistic: boolean;
}

interface SettingsLoadingState {
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
```

### Optimistic Update Patterns
```typescript
// Optimistic update with rollback capability
const useOptimisticSettings = (section: string) => {
  const [optimisticData, setOptimisticData] = useState(null);
  const queryClient = useQueryClient();
  
  const updateOptimistically = (updates: any) => {
    // Immediately update UI
    setOptimisticData(prev => ({ ...prev, ...updates }));
    
    // Update cache optimistically
    queryClient.setQueryData(['settings', section], old => ({ ...old, ...updates }));
    
    // Return rollback function
    return () => {
      setOptimisticData(null);
      queryClient.invalidateQueries(['settings', section]);
    };
  };
  
  return { optimisticData, updateOptimistically };
};
```

## Section-Specific Loading Implementation

### Profile Section Loading
```typescript
// resources/js/islands/Settings/ProfileTab.tsx
const ProfileTab = () => {
  const [sectionState, setSectionState] = useState<SectionLoadingState>({
    isLoading: false,
    operation: null,
    error: null,
    lastSaved: null,
    hasUnsavedChanges: false,
    isOptimistic: false,
  });
  
  const { updateOptimistically } = useOptimisticSettings('profile');
  
  const handleProfileUpdate = async (updates: ProfileData) => {
    const rollback = updateOptimistically(updates);
    setSectionState(prev => ({ 
      ...prev, 
      isLoading: true, 
      operation: 'save',
      isOptimistic: true,
      hasUnsavedChanges: false 
    }));
    
    try {
      await updateProfile(updates);
      setSectionState(prev => ({ 
        ...prev, 
        isLoading: false, 
        operation: null,
        lastSaved: new Date(),
        isOptimistic: false,
        error: null 
      }));
    } catch (error) {
      rollback();
      setSectionState(prev => ({ 
        ...prev, 
        isLoading: false, 
        operation: null,
        isOptimistic: false,
        error: error.message,
        hasUnsavedChanges: true 
      }));
    }
  };
  
  return (
    <SectionContainer state={sectionState}>
      <ProfileForm onUpdate={handleProfileUpdate} />
      <SectionStatus state={sectionState} />
    </SectionContainer>
  );
};
```

### AI Configuration Loading
```typescript
// Enhanced AI configuration with provider-specific loading
const AIConfiguration = () => {
  const [providerLoading, setProviderLoading] = useState(false);
  const [modelLoading, setModelLoading] = useState(false);
  const [validationLoading, setValidationLoading] = useState(false);
  
  const sectionState = useMemo(() => ({
    isLoading: providerLoading || modelLoading || validationLoading,
    operation: providerLoading ? 'provider' : 
               modelLoading ? 'model' : 
               validationLoading ? 'validate' : null,
    // ... other state
  }), [providerLoading, modelLoading, validationLoading]);
  
  const handleProviderChange = async (provider: string) => {
    setProviderLoading(true);
    try {
      // Optimistically update provider
      updateOptimistically({ provider });
      
      // Load models for new provider
      await loadProviderModels(provider);
    } finally {
      setProviderLoading(false);
    }
  };
  
  return (
    <SectionContainer state={sectionState}>
      <ProviderSelector 
        onChange={handleProviderChange}
        loading={providerLoading}
      />
      <ModelSelector 
        loading={modelLoading}
      />
      <ValidationStatus loading={validationLoading} />
    </SectionContainer>
  );
};
```

## Loading State Components

### Section Container
```typescript
// Universal section container with loading state management
interface SectionContainerProps {
  state: SectionLoadingState;
  children: React.ReactNode;
  title?: string;
  description?: string;
}

const SectionContainer = ({ state, children, title, description }: SectionContainerProps) => {
  return (
    <div className="relative">
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
      <div className={`transition-opacity ${state.isLoading ? 'opacity-75' : ''}`}>
        {children}
      </div>
      
      {/* Loading overlay for heavy operations */}
      {state.isLoading && state.operation === 'import' && (
        <LoadingOverlay operation={state.operation} />
      )}
      
      {/* Error boundary for section-specific errors */}
      <SectionErrorBoundary error={state.error} />
    </div>
  );
};
```

### Section Status Indicator
```typescript
// Visual status indicator for each section
const SectionStatus = ({ state }: { state: SectionLoadingState }) => {
  if (state.isLoading) {
    return (
      <div className="flex items-center space-x-2 text-sm">
        <Loader className="h-4 w-4 animate-spin text-blue-600" />
        <span className="text-blue-600">
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
```

### Smart Loading Indicators
```typescript
// Context-aware loading indicators
const SmartLoadingIndicator = ({ 
  loading, 
  operation, 
  size = 'sm',
  showText = true 
}: any) => {
  if (!loading) return null;
  
  const config = {
    save: { color: 'blue', text: 'Saving...' },
    validate: { color: 'purple', text: 'Validating...' },
    import: { color: 'green', text: 'Importing...' },
    reset: { color: 'orange', text: 'Resetting...' },
  };
  
  const { color, text } = config[operation] || { color: 'gray', text: 'Loading...' };
  
  return (
    <div className={`flex items-center space-x-2 text-${color}-600`}>
      <Loader className={`h-${size === 'sm' ? '4' : '6'} w-${size === 'sm' ? '4' : '6'} animate-spin`} />
      {showText && <span className="text-sm">{text}</span>}
    </div>
  );
};
```

## Auto-Save and Debouncing

### Debounced Auto-Save
```typescript
// Auto-save with debouncing and conflict resolution
const useAutoSave = (section: string, data: any, enabled = true) => {
  const [isAutoSaving, setIsAutoSaving] = useState(false);
  const [lastAutoSave, setLastAutoSave] = useState<Date | null>(null);
  
  const debouncedSave = useMemo(
    () => debounce(async (dataToSave: any) => {
      if (!enabled) return;
      
      setIsAutoSaving(true);
      try {
        await saveSettings(section, dataToSave);
        setLastAutoSave(new Date());
      } catch (error) {
        // Handle auto-save errors gracefully
        console.warn(`Auto-save failed for ${section}:`, error);
      } finally {
        setIsAutoSaving(false);
      }
    }, 2000),
    [section, enabled]
  );
  
  useEffect(() => {
    if (data && enabled) {
      debouncedSave(data);
    }
    
    return () => debouncedSave.cancel();
  }, [data, debouncedSave, enabled]);
  
  return { isAutoSaving, lastAutoSave };
};
```

### Save Coordination
```typescript
// Coordinate saves between related sections
const useSaveCoordination = () => {
  const [activeSaves, setActiveSaves] = useState<Set<string>>(new Set());
  
  const startSave = (section: string) => {
    setActiveSaves(prev => new Set([...prev, section]));
  };
  
  const endSave = (section: string) => {
    setActiveSaves(prev => {
      const newSet = new Set(prev);
      newSet.delete(section);
      return newSet;
    });
  };
  
  const isAnySaving = activeSaves.size > 0;
  const isSectionSaving = (section: string) => activeSaves.has(section);
  
  return { startSave, endSave, isAnySaving, isSectionSaving };
};
```

## Error Handling and Recovery

### Section Error Boundary
```typescript
// Error boundary for individual sections
const SectionErrorBoundary = ({ error, onRetry }: any) => {
  if (!error) return null;
  
  return (
    <Alert variant="destructive" className="mt-4">
      <AlertTriangle className="h-4 w-4" />
      <AlertTitle>Settings Error</AlertTitle>
      <AlertDescription className="space-y-2">
        <p>{error}</p>
        {onRetry && (
          <Button variant="outline" size="sm" onClick={onRetry}>
            Try Again
          </Button>
        )}
      </AlertDescription>
    </Alert>
  );
};
```

### Optimistic Update Recovery
```typescript
// Recovery mechanisms for failed optimistic updates
const useOptimisticRecovery = (section: string) => {
  const queryClient = useQueryClient();
  const [failedUpdates, setFailedUpdates] = useState<any[]>([]);
  
  const recoverFromFailure = async (originalData: any, failedUpdate: any) => {
    // Try to merge failed changes with current server state
    try {
      const currentServerData = await queryClient.fetchQuery(['settings', section]);
      const mergedData = mergeSettingsData(currentServerData, failedUpdate);
      
      // Attempt to save merged data
      await saveSettings(section, mergedData);
      
      // Remove from failed updates
      setFailedUpdates(prev => prev.filter(update => update !== failedUpdate));
    } catch (error) {
      // If merge fails, show conflict resolution UI
      setFailedUpdates(prev => [...prev, { originalData, failedUpdate, error }]);
    }
  };
  
  return { failedUpdates, recoverFromFailure };
};
```

## Success Feedback

### Toast Notifications
```typescript
// Success feedback with section context
const useSettingsToasts = () => {
  const { toast } = useToast();
  
  const showSaveSuccess = (section: string, changedFields?: string[]) => {
    toast({
      title: `${section} settings saved`,
      description: changedFields 
        ? `Updated: ${changedFields.join(', ')}`
        : 'Your changes have been saved successfully',
      variant: 'success',
      duration: 3000,
    });
  };
  
  const showAutoSaveSuccess = (section: string) => {
    toast({
      title: 'Auto-saved',
      description: `${section} settings automatically saved`,
      variant: 'success',
      duration: 2000,
    });
  };
  
  const showSaveError = (section: string, error: string) => {
    toast({
      title: `Failed to save ${section} settings`,
      description: error,
      variant: 'destructive',
      duration: 5000,
    });
  };
  
  return { showSaveSuccess, showAutoSaveSuccess, showSaveError };
};
```

## Integration Points

### Settings Layout Updates
```typescript
// Updated settings layout with section coordination
const SettingsLayout = () => {
  const saveCoordination = useSaveCoordination();
  
  return (
    <SaveCoordinationProvider value={saveCoordination}>
      <div className="grid grid-cols-4 gap-6">
        <SettingsSidebar />
        <div className="col-span-3">
          <SettingsContent />
        </div>
      </div>
      
      {/* Global save indicator for coordinated operations */}
      {saveCoordination.isAnySaving && (
        <GlobalSaveIndicator activeSaves={saveCoordination.activeSaves} />
      )}
    </SaveCoordinationProvider>
  );
};
```

### React Query Integration
```typescript
// Enhanced React Query integration with optimistic updates
const useSettingsQuery = (section: string) => {
  return useQuery({
    queryKey: ['settings', section],
    queryFn: () => fetchSettings(section),
    staleTime: 5 * 60 * 1000, // 5 minutes
    onError: (error) => {
      // Handle query errors without blocking other sections
      console.error(`Failed to load ${section} settings:`, error);
    },
  });
};

const useSettingsMutation = (section: string) => {
  const queryClient = useQueryClient();
  
  return useMutation({
    mutationFn: (data: any) => saveSettings(section, data),
    onMutate: async (newData) => {
      // Optimistic update
      await queryClient.cancelQueries(['settings', section]);
      const previousData = queryClient.getQueryData(['settings', section]);
      queryClient.setQueryData(['settings', section], newData);
      return { previousData };
    },
    onError: (error, newData, context) => {
      // Rollback on error
      if (context?.previousData) {
        queryClient.setQueryData(['settings', section], context.previousData);
      }
    },
    onSettled: () => {
      // Always refetch to ensure consistency
      queryClient.invalidateQueries(['settings', section]);
    },
  });
};
```