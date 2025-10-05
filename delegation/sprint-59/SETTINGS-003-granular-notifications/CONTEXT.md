# SETTINGS-003 Context: Granular Notification Preferences

## Current Notification Architecture

### Existing Notification Structure
```php
// Current profile_settings.notifications format
"notifications": {
    "email": true,
    "desktop": true, 
    "sound": false
}
```

### Laravel Notification System
```php
// Existing notification classes (if any)
app/Notifications/
├── UserMentioned.php
├── ContentUpdated.php
└── SystemAlert.php

// Notification channels
- Mail (email)
- Database (in-app)
- Broadcast (real-time)
```

## Enhanced Notification Schema

### New Notification Structure
```php
// Enhanced profile_settings.notifications format
"notifications": {
    "channels": {
        "email": {
            "enabled": true,
            "frequency": "instant", // instant, digest, weekly
            "quiet_hours": {
                "enabled": true,
                "start": "22:00",
                "end": "08:00",
                "timezone": "user_timezone"
            }
        },
        "desktop": {
            "enabled": true,
            "priority": "urgent", // all, urgent, none
            "sound": {
                "enabled": true,
                "volume": 0.7,
                "sound_id": "default"
            }
        },
        "mobile": {
            "enabled": false,
            "priority": "urgent"
        }
    },
    "categories": {
        "system": {
            "email": "instant",
            "desktop": "all",
            "mobile": "urgent"
        },
        "activity": {
            "email": "digest",
            "desktop": "all",
            "mobile": "none"
        },
        "content": {
            "email": "weekly",
            "desktop": "urgent",
            "mobile": "none"
        },
        "marketing": {
            "email": "weekly",
            "desktop": "none",
            "mobile": "none"
        },
        "administrative": {
            "email": "instant",
            "desktop": "urgent", 
            "mobile": "urgent"
        }
    },
    "preferences": {
        "digest_time": "09:00",
        "digest_timezone": "user_timezone",
        "batch_delay": 15, // minutes
        "marketing_opt_in": false
    }
}
```

## Notification Categories & Types

### System Notifications
- Security alerts (login attempts, password changes)
- System maintenance notifications
- Service status updates
- Error alerts and system issues

### Activity Notifications  
- Mentions in comments or content
- Collaboration invitations
- Content sharing notifications
- Team activity updates

### Content Notifications
- New content recommendations
- AI processing completions
- Content update notifications
- Scheduled content releases

### Marketing Communications
- Feature announcements
- Product updates and tips
- Newsletter subscriptions
- Educational content

### Administrative
- Billing and payment notifications
- Account changes and verifications
- Compliance and legal updates
- Subscription status changes

## Implementation Architecture

### Backend Notification Service
```php
// app/Services/NotificationPreferenceService.php
class NotificationPreferenceService
{
    public function getUserPreferences(User $user): array
    public function updatePreferences(User $user, array $preferences): void
    public function shouldSendNotification(User $user, string $type, string $channel): bool
    public function getChannelSettings(User $user, string $channel): array
    public function isInQuietHours(User $user): bool
    public function getDigestFrequency(User $user, string $category): string
}
```

### Enhanced Notification Classes
```php
// Update existing notifications to check preferences
abstract class BaseNotification extends Notification
{
    protected string $category; // 'system', 'activity', etc.
    protected string $priority; // 'urgent', 'normal', 'low'
    
    public function via($notifiable): array
    {
        $service = app(NotificationPreferenceService::class);
        $channels = [];
        
        foreach (['mail', 'database', 'broadcast'] as $channel) {
            if ($service->shouldSendNotification($notifiable, $this->category, $channel)) {
                $channels[] = $channel;
            }
        }
        
        return $channels;
    }
}
```

### Frontend Components Structure
```typescript
// Enhanced notification preferences UI
/resources/js/islands/Settings/components/NotificationPreferences/
├── NotificationPreferences.tsx         // Main component
├── ChannelSettings.tsx                 // Email, desktop, mobile settings
├── CategorySettings.tsx                // Notification category controls
├── QuietHoursSettings.tsx             // Do not disturb scheduling
├── NotificationPreview.tsx            // Preview notification examples
└── BulkControls.tsx                   // Quick toggle controls
```

## UI Component Design

### Main Notification Preferences
```typescript
interface NotificationPreferences {
  channels: {
    email: ChannelSettings;
    desktop: ChannelSettings;
    mobile: ChannelSettings;
  };
  categories: {
    system: CategorySettings;
    activity: CategorySettings;
    content: CategorySettings;
    marketing: CategorySettings;
    administrative: CategorySettings;
  };
  preferences: GlobalSettings;
}

interface ChannelSettings {
  enabled: boolean;
  frequency?: 'instant' | 'digest' | 'weekly';
  priority?: 'all' | 'urgent' | 'none';
  quiet_hours?: QuietHours;
  sound?: SoundSettings;
}

interface CategorySettings {
  email: 'instant' | 'digest' | 'weekly' | 'none';
  desktop: 'all' | 'urgent' | 'none';
  mobile: 'urgent' | 'none';
}
```

### Channel Configuration Components
```typescript
// Email channel settings
const EmailChannelSettings = ({ settings, onChange }) => {
  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <div>
          <h4 className="font-medium">Email Notifications</h4>
          <p className="text-sm text-gray-600">Receive notifications via email</p>
        </div>
        <Switch 
          checked={settings.enabled}
          onCheckedChange={(enabled) => onChange({ enabled })}
        />
      </div>
      
      {settings.enabled && (
        <>
          <div>
            <label className="text-sm font-medium">Frequency</label>
            <Select value={settings.frequency} onValueChange={(frequency) => onChange({ frequency })}>
              <SelectItem value="instant">Instant</SelectItem>
              <SelectItem value="digest">Daily Digest</SelectItem>
              <SelectItem value="weekly">Weekly Summary</SelectItem>
            </Select>
          </div>
          
          <QuietHoursToggle 
            settings={settings.quiet_hours}
            onChange={(quiet_hours) => onChange({ quiet_hours })}
          />
        </>
      )}
    </div>
  );
};
```

### Category Matrix Interface
```typescript
// Matrix view of categories vs channels
const CategoryMatrix = ({ categories, onChange }) => {
  const categoryInfo = {
    system: {
      name: 'System & Security',
      description: 'Important system updates and security alerts',
      examples: ['Login alerts', 'System maintenance', 'Security notifications']
    },
    activity: {
      name: 'Activity & Collaboration', 
      description: 'Updates about your interactions and team activity',
      examples: ['Mentions', 'Comments', 'Collaboration invites']
    },
    // ... other categories
  };
  
  return (
    <div className="space-y-6">
      {Object.entries(categoryInfo).map(([key, info]) => (
        <div key={key} className="border rounded-lg p-4">
          <div className="flex items-start justify-between mb-4">
            <div>
              <h4 className="font-medium">{info.name}</h4>
              <p className="text-sm text-gray-600">{info.description}</p>
            </div>
            <NotificationPreview examples={info.examples} />
          </div>
          
          <div className="grid grid-cols-3 gap-4">
            <ChannelControl
              label="Email"
              options={['instant', 'digest', 'weekly', 'none']}
              value={categories[key].email}
              onChange={(value) => onChange(key, 'email', value)}
            />
            <ChannelControl
              label="Desktop"
              options={['all', 'urgent', 'none']}
              value={categories[key].desktop}
              onChange={(value) => onChange(key, 'desktop', value)}
            />
            <ChannelControl
              label="Mobile"
              options={['urgent', 'none']}
              value={categories[key].mobile}
              onChange={(value) => onChange(key, 'mobile', value)}
            />
          </div>
        </div>
      ))}
    </div>
  );
};
```

## Migration Strategy

### Backward Compatibility
```php
// Migration to transform existing boolean settings
// In NotificationPreferenceService
private function migrateFromLegacyFormat(array $oldSettings): array
{
    return [
        'channels' => [
            'email' => [
                'enabled' => $oldSettings['email'] ?? true,
                'frequency' => 'instant',
            ],
            'desktop' => [
                'enabled' => $oldSettings['desktop'] ?? true,
                'priority' => 'all',
                'sound' => [
                    'enabled' => $oldSettings['sound'] ?? false,
                    'volume' => 0.7,
                    'sound_id' => 'default'
                ]
            ],
            'mobile' => ['enabled' => false]
        ],
        'categories' => $this->getDefaultCategorySettings(),
        'preferences' => $this->getDefaultGlobalSettings(),
    ];
}
```

### Smart Defaults
```php
private function getDefaultCategorySettings(): array
{
    return [
        'system' => ['email' => 'instant', 'desktop' => 'all', 'mobile' => 'urgent'],
        'activity' => ['email' => 'digest', 'desktop' => 'all', 'mobile' => 'none'],
        'content' => ['email' => 'weekly', 'desktop' => 'urgent', 'mobile' => 'none'],
        'marketing' => ['email' => 'none', 'desktop' => 'none', 'mobile' => 'none'],
        'administrative' => ['email' => 'instant', 'desktop' => 'urgent', 'mobile' => 'urgent'],
    ];
}
```

## Integration Points

### Existing Notification System
- Update notification classes to check new preference structure
- Maintain fallback to simple boolean checks for compatibility
- Implement preference checking in notification dispatch

### Email System
- Integrate with digest batching system
- Respect quiet hours for email sending
- Implement weekly summary generation

### Real-time Systems
- Update WebSocket notification filtering
- Implement priority-based desktop notifications
- Add sound customization support

## Testing Strategy

### Backend Tests
- Preference migration from legacy format
- Notification filtering based on preferences
- Quiet hours calculation
- Digest scheduling logic

### Frontend Tests
- Preference matrix interactions
- Bulk control functionality
- Settings persistence
- Preview system behavior

### Integration Tests
- End-to-end notification delivery
- Preference respect across channels
- Migration compatibility
- Default setting application