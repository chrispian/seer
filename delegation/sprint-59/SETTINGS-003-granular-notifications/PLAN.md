# SETTINGS-003 Implementation Plan: Granular Notification Preferences

## Phase 1: Backend Foundation (3-4 hours)

### 1.1 Create Notification Preference Service (2h)
```php
// app/Services/NotificationPreferenceService.php
class NotificationPreferenceService
{
    public function getUserPreferences(User $user): array
    {
        $settings = $user->profile_settings['notifications'] ?? [];
        
        // Migrate legacy format if needed
        if ($this->isLegacyFormat($settings)) {
            $settings = $this->migrateFromLegacyFormat($settings);
            $this->updateUserPreferences($user, $settings);
        }
        
        return array_merge($this->getDefaultPreferences(), $settings);
    }
    
    public function updatePreferences(User $user, array $preferences): void
    {
        $validated = $this->validatePreferences($preferences);
        
        $profileSettings = $user->profile_settings;
        $profileSettings['notifications'] = $validated;
        
        $user->update(['profile_settings' => $profileSettings]);
    }
    
    public function shouldSendNotification(User $user, string $category, string $channel): bool
    {
        $preferences = $this->getUserPreferences($user);
        
        // Check if channel is enabled
        if (!($preferences['channels'][$channel]['enabled'] ?? true)) {
            return false;
        }
        
        // Check quiet hours for email/mobile
        if (in_array($channel, ['email', 'mobile']) && $this->isInQuietHours($user)) {
            return false;
        }
        
        // Check category-specific settings
        $categorySettings = $preferences['categories'][$category] ?? [];
        $channelPreference = $categorySettings[$channel] ?? 'none';
        
        return $channelPreference !== 'none';
    }
    
    private function isLegacyFormat(array $settings): bool
    {
        return isset($settings['email']) && is_bool($settings['email']);
    }
    
    private function migrateFromLegacyFormat(array $old): array
    {
        return [
            'channels' => [
                'email' => [
                    'enabled' => $old['email'] ?? true,
                    'frequency' => 'instant',
                    'quiet_hours' => ['enabled' => false]
                ],
                'desktop' => [
                    'enabled' => $old['desktop'] ?? true,
                    'priority' => 'all',
                    'sound' => [
                        'enabled' => $old['sound'] ?? false,
                        'volume' => 0.7,
                        'sound_id' => 'default'
                    ]
                ],
                'mobile' => ['enabled' => false, 'priority' => 'urgent']
            ],
            'categories' => $this->getDefaultCategorySettings(),
            'preferences' => $this->getDefaultGlobalSettings()
        ];
    }
}
```

### 1.2 Update Settings Controller (1h)
```php
// app/Http/Controllers/Settings/PreferencesController.php
public function updateNotifications(Request $request)
{
    $validated = $request->validate([
        'notifications' => 'required|array',
        'notifications.channels' => 'required|array',
        'notifications.categories' => 'required|array',
        'notifications.preferences' => 'sometimes|array',
    ]);
    
    $service = app(NotificationPreferenceService::class);
    $service->updatePreferences(auth()->user(), $validated['notifications']);
    
    return response()->json(['success' => true]);
}

public function getNotificationPreferences()
{
    $service = app(NotificationPreferenceService::class);
    $preferences = $service->getUserPreferences(auth()->user());
    
    return response()->json($preferences);
}
```

### 1.3 Add API Routes (30min)
```php
// routes/api.php
Route::middleware(['auth:sanctum'])->prefix('settings')->group(function () {
    Route::get('/notifications', [PreferencesController::class, 'getNotificationPreferences']);
    Route::patch('/notifications', [PreferencesController::class, 'updateNotifications']);
});
```

## Phase 2: Frontend Hook & Types (2-3 hours)

### 2.1 Create Type Definitions (1h)
```typescript
// resources/js/types/notifications.ts
export interface NotificationPreferences {
  channels: {
    email: EmailChannelSettings;
    desktop: DesktopChannelSettings;
    mobile: MobileChannelSettings;
  };
  categories: {
    system: CategorySettings;
    activity: CategorySettings;
    content: CategorySettings;
    marketing: CategorySettings;
    administrative: CategorySettings;
  };
  preferences: GlobalNotificationSettings;
}

export interface EmailChannelSettings {
  enabled: boolean;
  frequency: 'instant' | 'digest' | 'weekly';
  quiet_hours: QuietHoursSettings;
}

export interface DesktopChannelSettings {
  enabled: boolean;
  priority: 'all' | 'urgent' | 'none';
  sound: SoundSettings;
}

export interface MobileChannelSettings {
  enabled: boolean;
  priority: 'urgent' | 'none';
}

export interface CategorySettings {
  email: 'instant' | 'digest' | 'weekly' | 'none';
  desktop: 'all' | 'urgent' | 'none';
  mobile: 'urgent' | 'none';
}

export interface QuietHoursSettings {
  enabled: boolean;
  start: string; // "22:00"
  end: string;   // "08:00"
  timezone: string;
}

export interface SoundSettings {
  enabled: boolean;
  volume: number; // 0-1
  sound_id: string;
}

export interface GlobalNotificationSettings {
  digest_time: string;
  digest_timezone: string;
  batch_delay: number;
  marketing_opt_in: boolean;
}
```

### 2.2 Create API Hook (1h)
```typescript
// resources/js/hooks/useNotificationPreferences.ts
export const useNotificationPreferences = () => {
  const { data, isLoading, error } = useQuery({
    queryKey: ['notification-preferences'],
    queryFn: () => api.get('/settings/notifications').then(res => res.data),
  });
  
  const updatePreferences = useMutation({
    mutationFn: (preferences: Partial<NotificationPreferences>) =>
      api.patch('/settings/notifications', { notifications: preferences }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['notification-preferences'] });
    },
  });
  
  return {
    preferences: data as NotificationPreferences | undefined,
    isLoading,
    error,
    updatePreferences: updatePreferences.mutate,
    isUpdating: updatePreferences.isPending,
  };
};
```

### 2.3 Create Notification Constants (30min)
```typescript
// resources/js/constants/notifications.ts
export const NOTIFICATION_CATEGORIES = {
  system: {
    name: 'System & Security',
    description: 'Important system updates and security alerts',
    examples: ['Login from new device', 'Password changed', 'System maintenance'],
    icon: 'shield',
  },
  activity: {
    name: 'Activity & Collaboration',
    description: 'Updates about your interactions and team activity',
    examples: ['Someone mentioned you', 'Comment on your content', 'Team invitation'],
    icon: 'users',
  },
  content: {
    name: 'Content & AI',
    description: 'Content updates and AI processing notifications',
    examples: ['AI analysis complete', 'New content recommendations', 'Content published'],
    icon: 'file-text',
  },
  marketing: {
    name: 'Marketing & Updates',
    description: 'Product announcements and educational content',
    examples: ['New feature announcement', 'Tips and tricks', 'Newsletter'],
    icon: 'megaphone',
  },
  administrative: {
    name: 'Administrative',
    description: 'Account, billing, and compliance notifications',
    examples: ['Payment processed', 'Subscription renewal', 'Terms updated'],
    icon: 'settings',
  },
} as const;

export const CHANNEL_FREQUENCIES = {
  email: [
    { value: 'instant', label: 'Instant', description: 'Send immediately' },
    { value: 'digest', label: 'Daily Digest', description: 'Bundle into daily email' },
    { value: 'weekly', label: 'Weekly Summary', description: 'Include in weekly summary' },
    { value: 'none', label: 'Never', description: 'Don\'t send via email' },
  ],
  desktop: [
    { value: 'all', label: 'All Notifications', description: 'Show all notifications' },
    { value: 'urgent', label: 'Urgent Only', description: 'Only show important notifications' },
    { value: 'none', label: 'None', description: 'No desktop notifications' },
  ],
  mobile: [
    { value: 'urgent', label: 'Urgent Only', description: 'Only critical notifications' },
    { value: 'none', label: 'None', description: 'No mobile notifications' },
  ],
} as const;
```

## Phase 3: Core UI Components (3-4 hours)

### 3.1 Create Channel Settings Components (2h)
```typescript
// resources/js/islands/Settings/components/NotificationPreferences/ChannelSettings.tsx
interface ChannelSettingsProps {
  channels: NotificationPreferences['channels'];
  onChange: (updates: Partial<NotificationPreferences['channels']>) => void;
}

export const ChannelSettings = ({ channels, onChange }: ChannelSettingsProps) => {
  return (
    <div className="space-y-6">
      <div>
        <h3 className="text-lg font-medium">Notification Channels</h3>
        <p className="text-sm text-gray-600 mt-1">
          Configure how you receive notifications across different channels.
        </p>
      </div>
      
      <EmailChannelCard
        settings={channels.email}
        onChange={(email) => onChange({ email })}
      />
      
      <DesktopChannelCard
        settings={channels.desktop}
        onChange={(desktop) => onChange({ desktop })}
      />
      
      <MobileChannelCard
        settings={channels.mobile}
        onChange={(mobile) => onChange({ mobile })}
      />
    </div>
  );
};

const EmailChannelCard = ({ settings, onChange }: any) => (
  <div className="border rounded-lg p-4">
    <div className="flex items-center justify-between mb-4">
      <div className="flex items-center space-x-3">
        <Mail className="h-5 w-5 text-blue-600" />
        <div>
          <h4 className="font-medium">Email Notifications</h4>
          <p className="text-sm text-gray-600">Receive notifications via email</p>
        </div>
      </div>
      <Switch
        checked={settings.enabled}
        onCheckedChange={(enabled) => onChange({ ...settings, enabled })}
      />
    </div>
    
    {settings.enabled && (
      <div className="space-y-4 ml-8">
        <div>
          <label className="text-sm font-medium">Frequency</label>
          <Select 
            value={settings.frequency} 
            onValueChange={(frequency) => onChange({ ...settings, frequency })}
          >
            {CHANNEL_FREQUENCIES.email.map(option => (
              <SelectItem key={option.value} value={option.value}>
                <div>
                  <div className="font-medium">{option.label}</div>
                  <div className="text-xs text-gray-600">{option.description}</div>
                </div>
              </SelectItem>
            ))}
          </Select>
        </div>
        
        <QuietHoursControl
          settings={settings.quiet_hours}
          onChange={(quiet_hours) => onChange({ ...settings, quiet_hours })}
        />
      </div>
    )}
  </div>
);
```

### 3.2 Create Category Matrix Component (1.5h)
```typescript
// resources/js/islands/Settings/components/NotificationPreferences/CategoryMatrix.tsx
interface CategoryMatrixProps {
  categories: NotificationPreferences['categories'];
  onChange: (updates: Partial<NotificationPreferences['categories']>) => void;
}

export const CategoryMatrix = ({ categories, onChange }: CategoryMatrixProps) => {
  const handleCategoryChange = (category: string, channel: string, value: string) => {
    onChange({
      ...categories,
      [category]: {
        ...categories[category as keyof typeof categories],
        [channel]: value,
      },
    });
  };
  
  return (
    <div className="space-y-6">
      <div>
        <h3 className="text-lg font-medium">Notification Categories</h3>
        <p className="text-sm text-gray-600 mt-1">
          Customize how different types of notifications are delivered.
        </p>
      </div>
      
      {Object.entries(NOTIFICATION_CATEGORIES).map(([key, info]) => (
        <CategoryCard
          key={key}
          categoryKey={key}
          info={info}
          settings={categories[key as keyof typeof categories]}
          onChange={(channel, value) => handleCategoryChange(key, channel, value)}
        />
      ))}
    </div>
  );
};

const CategoryCard = ({ categoryKey, info, settings, onChange }: any) => {
  const Icon = getIconComponent(info.icon);
  
  return (
    <div className="border rounded-lg p-4">
      <div className="flex items-start justify-between mb-4">
        <div className="flex items-start space-x-3">
          <Icon className="h-5 w-5 mt-0.5 text-gray-600" />
          <div>
            <h4 className="font-medium">{info.name}</h4>
            <p className="text-sm text-gray-600">{info.description}</p>
          </div>
        </div>
        <NotificationPreview examples={info.examples} />
      </div>
      
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4 ml-8">
        <ChannelControl
          label="Email"
          options={CHANNEL_FREQUENCIES.email}
          value={settings.email}
          onChange={(value) => onChange('email', value)}
        />
        <ChannelControl
          label="Desktop"
          options={CHANNEL_FREQUENCIES.desktop}
          value={settings.desktop}
          onChange={(value) => onChange('desktop', value)}
        />
        <ChannelControl
          label="Mobile"
          options={CHANNEL_FREQUENCIES.mobile}
          value={settings.mobile}
          onChange={(value) => onChange('mobile', value)}
        />
      </div>
    </div>
  );
};
```

### 3.3 Create Helper Components (30min)
```typescript
// resources/js/islands/Settings/components/NotificationPreferences/helpers.tsx
const ChannelControl = ({ label, options, value, onChange }: any) => (
  <div>
    <label className="text-sm font-medium text-gray-700">{label}</label>
    <Select value={value} onValueChange={onChange}>
      <SelectTrigger className="mt-1">
        <SelectValue />
      </SelectTrigger>
      <SelectContent>
        {options.map((option: any) => (
          <SelectItem key={option.value} value={option.value}>
            <div>
              <div className="font-medium">{option.label}</div>
              <div className="text-xs text-gray-600">{option.description}</div>
            </div>
          </SelectItem>
        ))}
      </SelectContent>
    </Select>
  </div>
);

const QuietHoursControl = ({ settings, onChange }: any) => (
  <div>
    <div className="flex items-center justify-between">
      <label className="text-sm font-medium">Quiet Hours</label>
      <Switch
        checked={settings.enabled}
        onCheckedChange={(enabled) => onChange({ ...settings, enabled })}
      />
    </div>
    {settings.enabled && (
      <div className="grid grid-cols-2 gap-2 mt-2">
        <Input
          type="time"
          value={settings.start}
          onChange={(e) => onChange({ ...settings, start: e.target.value })}
          placeholder="Start time"
        />
        <Input
          type="time"
          value={settings.end}
          onChange={(e) => onChange({ ...settings, end: e.target.value })}
          placeholder="End time"
        />
      </div>
    )}
  </div>
);

const NotificationPreview = ({ examples }: { examples: string[] }) => (
  <Button variant="outline" size="sm" className="text-xs">
    <Eye className="h-3 w-3 mr-1" />
    Preview
  </Button>
);
```

## Phase 4: Main Component Integration (2 hours)

### 4.1 Create Main NotificationPreferences Component (1.5h)
```typescript
// resources/js/islands/Settings/components/NotificationPreferences/NotificationPreferences.tsx
export const NotificationPreferences = () => {
  const { preferences, isLoading, updatePreferences, isUpdating } = useNotificationPreferences();
  const [localPreferences, setLocalPreferences] = useState<NotificationPreferences | null>(null);
  const [hasChanges, setHasChanges] = useState(false);
  
  useEffect(() => {
    if (preferences && !localPreferences) {
      setLocalPreferences(preferences);
    }
  }, [preferences, localPreferences]);
  
  const handleChange = useCallback((section: string, updates: any) => {
    if (!localPreferences) return;
    
    const newPreferences = {
      ...localPreferences,
      [section]: { ...localPreferences[section as keyof NotificationPreferences], ...updates },
    };
    
    setLocalPreferences(newPreferences);
    setHasChanges(true);
  }, [localPreferences]);
  
  const handleSave = useCallback(() => {
    if (localPreferences) {
      updatePreferences(localPreferences);
      setHasChanges(false);
    }
  }, [localPreferences, updatePreferences]);
  
  const handleReset = useCallback(() => {
    setLocalPreferences(preferences || null);
    setHasChanges(false);
  }, [preferences]);
  
  if (isLoading || !localPreferences) {
    return <div>Loading notification preferences...</div>;
  }
  
  return (
    <div className="space-y-8">
      <div>
        <h2 className="text-xl font-semibold">Notification Preferences</h2>
        <p className="text-gray-600 mt-1">
          Customize how and when you receive notifications.
        </p>
      </div>
      
      <BulkControls
        preferences={localPreferences}
        onChange={(updates) => setLocalPreferences({ ...localPreferences, ...updates })}
      />
      
      <ChannelSettings
        channels={localPreferences.channels}
        onChange={(updates) => handleChange('channels', updates)}
      />
      
      <CategoryMatrix
        categories={localPreferences.categories}
        onChange={(updates) => handleChange('categories', updates)}
      />
      
      <GlobalSettings
        preferences={localPreferences.preferences}
        onChange={(updates) => handleChange('preferences', updates)}
      />
      
      {hasChanges && (
        <div className="flex items-center justify-between p-4 bg-blue-50 border border-blue-200 rounded-lg">
          <div className="flex items-center space-x-2">
            <Info className="h-4 w-4 text-blue-600" />
            <span className="text-sm text-blue-800">You have unsaved changes</span>
          </div>
          <div className="flex space-x-2">
            <Button variant="outline" size="sm" onClick={handleReset}>
              Reset
            </Button>
            <Button size="sm" onClick={handleSave} disabled={isUpdating}>
              {isUpdating ? 'Saving...' : 'Save Changes'}
            </Button>
          </div>
        </div>
      )}
    </div>
  );
};
```

### 4.2 Create Bulk Controls Component (30min)
```typescript
// resources/js/islands/Settings/components/NotificationPreferences/BulkControls.tsx
interface BulkControlsProps {
  preferences: NotificationPreferences;
  onChange: (updates: Partial<NotificationPreferences>) => void;
}

export const BulkControls = ({ preferences, onChange }: BulkControlsProps) => {
  const enableAllEmail = () => {
    const newCategories = { ...preferences.categories };
    Object.keys(newCategories).forEach(category => {
      newCategories[category as keyof typeof newCategories].email = 'instant';
    });
    onChange({ 
      channels: { ...preferences.channels, email: { ...preferences.channels.email, enabled: true } },
      categories: newCategories 
    });
  };
  
  const disableAllEmail = () => {
    const newCategories = { ...preferences.categories };
    Object.keys(newCategories).forEach(category => {
      newCategories[category as keyof typeof newCategories].email = 'none';
    });
    onChange({ categories: newCategories });
  };
  
  const enableEssentialOnly = () => {
    const newCategories = {
      system: { email: 'instant', desktop: 'all', mobile: 'urgent' },
      activity: { email: 'none', desktop: 'urgent', mobile: 'none' },
      content: { email: 'none', desktop: 'none', mobile: 'none' },
      marketing: { email: 'none', desktop: 'none', mobile: 'none' },
      administrative: { email: 'instant', desktop: 'urgent', mobile: 'urgent' },
    };
    onChange({ categories: newCategories });
  };
  
  return (
    <div className="bg-gray-50 rounded-lg p-4">
      <h3 className="text-sm font-medium mb-3">Quick Actions</h3>
      <div className="flex flex-wrap gap-2">
        <Button variant="outline" size="sm" onClick={enableAllEmail}>
          Enable All Email
        </Button>
        <Button variant="outline" size="sm" onClick={disableAllEmail}>
          Disable All Email
        </Button>
        <Button variant="outline" size="sm" onClick={enableEssentialOnly}>
          Essential Only
        </Button>
      </div>
    </div>
  );
};
```

## Phase 5: Testing & Integration (1-2 hours)

### 5.1 Backend Tests (1h)
```php
// tests/Feature/Settings/NotificationPreferencesTest.php
class NotificationPreferencesTest extends TestCase
{
    public function test_gets_default_notification_preferences()
    public function test_updates_notification_preferences()
    public function test_migrates_legacy_notification_format()
    public function test_validates_notification_preferences()
    public function test_respects_quiet_hours()
    public function test_filters_notifications_by_category()
}
```

### 5.2 Frontend Integration (1h)
```typescript
// Update PreferencesTab to include NotificationPreferences
// Test component interactions and state management
// Verify API integration and persistence
```

## Success Metrics
- [ ] Notification preferences organized into logical categories
- [ ] Multiple notification channels with granular controls
- [ ] Quiet hours and frequency controls functional
- [ ] Legacy settings migrate seamlessly
- [ ] Category matrix allows fine-grained control
- [ ] Bulk controls provide convenient shortcuts
- [ ] Settings persist correctly across sessions
- [ ] Preview system demonstrates notification types

## Dependencies
- Existing notification infrastructure
- Current preferences endpoint and storage
- UI components (Select, Switch, Button, etc.)
- React Query for state management
- Time zone handling for quiet hours