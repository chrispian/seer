# SETTINGS-003 TODO: Granular Notification Preferences

## Backend Implementation

### Notification Preference Service
- [ ] Create `app/Services/NotificationPreferenceService.php`
  - [ ] `getUserPreferences(User $user)` - Get user preferences with migration
  - [ ] `updatePreferences(User $user, array $preferences)` - Update and validate
  - [ ] `shouldSendNotification(User $user, string $category, string $channel)` - Check if notification should be sent
  - [ ] `isInQuietHours(User $user)` - Check quiet hours based on timezone
  - [ ] `getChannelSettings(User $user, string $channel)` - Get channel-specific settings
  - [ ] `getDigestFrequency(User $user, string $category)` - Get digest scheduling
  - [ ] Private migration methods:
    - [ ] `isLegacyFormat(array $settings)` - Detect old boolean format
    - [ ] `migrateFromLegacyFormat(array $old)` - Convert to new structure
    - [ ] `getDefaultPreferences()` - Smart defaults for new users
    - [ ] `getDefaultCategorySettings()` - Default category matrix
    - [ ] `validatePreferences(array $preferences)` - Validate structure and values

### Settings Controller Updates
- [ ] Update `app/Http/Controllers/Settings/PreferencesController.php`
  - [ ] `getNotificationPreferences()` - GET endpoint for notification settings
  - [ ] `updateNotifications(Request $request)` - PATCH endpoint for updates
  - [ ] Validation rules for new notification structure
  - [ ] Error handling for malformed preference data

### API Routes
- [ ] Add routes to `routes/api.php`:
  - [ ] `GET /api/settings/notifications` - Fetch current preferences
  - [ ] `PATCH /api/settings/notifications` - Update preferences
- [ ] Add authentication middleware
- [ ] Add rate limiting for update endpoint

### Notification System Integration
- [ ] Create `app/Notifications/BaseNotification.php`
  - [ ] Abstract class with category and priority properties
  - [ ] Override `via()` method to check user preferences
  - [ ] Integration with NotificationPreferenceService
  - [ ] Fallback to legacy boolean format for compatibility

- [ ] Update existing notification classes:
  - [ ] Add category property (`system`, `activity`, `content`, `marketing`, `administrative`)
  - [ ] Add priority property (`urgent`, `normal`, `low`)
  - [ ] Extend BaseNotification instead of Notification
  - [ ] Test preference checking in notification delivery

## Frontend Type Definitions

### Core Types
- [ ] Create `resources/js/types/notifications.ts`
  - [ ] `NotificationPreferences` interface - Main preferences structure
  - [ ] `EmailChannelSettings` interface - Email-specific settings
  - [ ] `DesktopChannelSettings` interface - Desktop notification settings
  - [ ] `MobileChannelSettings` interface - Mobile notification settings
  - [ ] `CategorySettings` interface - Per-category channel preferences
  - [ ] `QuietHoursSettings` interface - Do not disturb configuration
  - [ ] `SoundSettings` interface - Sound preferences
  - [ ] `GlobalNotificationSettings` interface - Global preferences

### Constants & Configuration
- [ ] Create `resources/js/constants/notifications.ts`
  - [ ] `NOTIFICATION_CATEGORIES` - Category metadata with descriptions and examples
  - [ ] `CHANNEL_FREQUENCIES` - Available frequency options per channel
  - [ ] `SOUND_OPTIONS` - Available notification sounds
  - [ ] `TIMEZONE_OPTIONS` - Timezone selection for quiet hours
  - [ ] Helper functions for category and channel information

## API Integration

### Custom Hooks
- [ ] Create `resources/js/hooks/useNotificationPreferences.ts`
  - [ ] Fetch preferences with React Query
  - [ ] Update preferences with mutation
  - [ ] Optimistic updates for better UX
  - [ ] Error handling and retry logic
  - [ ] Cache invalidation on updates

- [ ] Create `resources/js/hooks/useNotificationCategories.ts`
  - [ ] Category metadata and configuration
  - [ ] Helper functions for category management
  - [ ] Example notification generation for preview

## UI Components

### Channel Settings Components
- [ ] Create `resources/js/islands/Settings/components/NotificationPreferences/ChannelSettings.tsx`
  - [ ] Main channel configuration component
  - [ ] Email channel card with frequency and quiet hours
  - [ ] Desktop channel card with priority and sound settings
  - [ ] Mobile channel card with priority settings
  - [ ] Channel enable/disable toggles

- [ ] Create `EmailChannelCard` sub-component
  - [ ] Email enable toggle
  - [ ] Frequency selection (instant, digest, weekly)
  - [ ] Quiet hours configuration
  - [ ] Digest time preference

- [ ] Create `DesktopChannelCard` sub-component
  - [ ] Desktop notifications enable toggle
  - [ ] Priority filtering (all, urgent, none)
  - [ ] Sound settings (enable, volume, sound selection)
  - [ ] Desktop notification permissions handling

- [ ] Create `MobileChannelCard` sub-component
  - [ ] Mobile notifications enable toggle
  - [ ] Priority filtering (urgent, none)
  - [ ] Push notification permissions handling

### Category Matrix Components
- [ ] Create `resources/js/islands/Settings/components/NotificationPreferences/CategoryMatrix.tsx`
  - [ ] Category-based notification management
  - [ ] Matrix view of categories vs channels
  - [ ] Individual category cards with controls
  - [ ] Preview examples for each category

- [ ] Create `CategoryCard` sub-component
  - [ ] Category header with icon and description
  - [ ] Channel controls (email, desktop, mobile)
  - [ ] Example notifications display
  - [ ] Category-specific help text

- [ ] Create `ChannelControl` sub-component
  - [ ] Channel-specific frequency selection
  - [ ] Visual indicators for settings
  - [ ] Descriptions for each option
  - [ ] Disabled state handling

### Helper Components
- [ ] Create `QuietHoursControl` component
  - [ ] Enable/disable quiet hours
  - [ ] Start and end time selection
  - [ ] Timezone configuration
  - [ ] Visual schedule representation

- [ ] Create `SoundControl` component
  - [ ] Sound enable toggle
  - [ ] Volume slider control
  - [ ] Sound selection dropdown
  - [ ] Sound preview functionality

- [ ] Create `NotificationPreview` component
  - [ ] Preview modal/popover
  - [ ] Example notifications for each category
  - [ ] Visual representation of notification delivery
  - [ ] Help text and explanations

### Bulk Controls
- [ ] Create `resources/js/islands/Settings/components/NotificationPreferences/BulkControls.tsx`
  - [ ] Quick action buttons
  - [ ] "Enable All Email" action
  - [ ] "Disable All Email" action
  - [ ] "Essential Only" preset
  - [ ] "Quiet Mode" preset
  - [ ] Custom preset creation and saving

## Main Integration Component

### NotificationPreferences Component
- [ ] Create `resources/js/islands/Settings/components/NotificationPreferences/NotificationPreferences.tsx`
  - [ ] Main component integrating all sub-components
  - [ ] State management for local changes
  - [ ] Change detection and save prompts
  - [ ] Loading and error states
  - [ ] Success feedback on save

### State Management
- [ ] Local state for unsaved changes
- [ ] Change detection and dirty state tracking
- [ ] Optimistic updates during save
- [ ] Rollback capability on error
- [ ] Auto-save with debouncing

### User Experience
- [ ] Progressive disclosure for advanced settings
- [ ] Contextual help and tooltips
- [ ] Visual feedback for setting changes
- [ ] Confirmation dialogs for bulk changes
- [ ] Mobile-responsive design

## Integration with Settings Tab

### PreferencesTab Updates
- [ ] Update `resources/js/islands/Settings/PreferencesTab.tsx`
  - [ ] Include NotificationPreferences component
  - [ ] Coordinate with other preference sections
  - [ ] Shared loading and error states
  - [ ] Consistent styling and layout

### Settings Layout
- [ ] Update settings navigation if needed
- [ ] Add notification-specific help sections
- [ ] Coordinate with import/export functionality
- [ ] Ensure mobile navigation works properly

## Validation & Error Handling

### Frontend Validation
- [ ] Real-time validation of time inputs
- [ ] Validation of frequency combinations
- [ ] Cross-channel dependency validation
- [ ] Visual validation feedback

### Error States
- [ ] Network error handling
- [ ] Validation error display
- [ ] Graceful degradation when APIs fail
- [ ] Recovery suggestions for users

### Loading States
- [ ] Component-level loading indicators
- [ ] Skeleton loading for preferences
- [ ] Progressive loading of category data
- [ ] Smooth transitions between states

## Testing

### Backend Tests
- [ ] Create `tests/Feature/Settings/NotificationPreferencesTest.php`
  - [ ] Test preference retrieval with defaults
  - [ ] Test preference updates and validation
  - [ ] Test legacy format migration
  - [ ] Test quiet hours calculation
  - [ ] Test notification filtering logic
  - [ ] Test category-channel combinations

- [ ] Create `tests/Unit/Services/NotificationPreferenceServiceTest.php`
  - [ ] Test service methods in isolation
  - [ ] Test preference validation rules
  - [ ] Test migration logic
  - [ ] Test quiet hours edge cases

### Frontend Tests
- [ ] Component interaction tests
  - [ ] Channel toggle functionality
  - [ ] Category matrix interactions
  - [ ] Bulk control actions
  - [ ] Save/reset functionality

- [ ] Hook tests
  - [ ] API integration testing
  - [ ] State management validation
  - [ ] Error handling verification
  - [ ] Cache behavior testing

- [ ] Integration tests
  - [ ] End-to-end preference flow
  - [ ] Migration from legacy format
  - [ ] Bulk action workflows
  - [ ] Mobile responsive behavior

## Migration & Compatibility

### Legacy Support
- [ ] Automatic migration from boolean format
- [ ] Backward compatibility for existing notifications
- [ ] Graceful handling of missing preference data
- [ ] Rollback capability if migration fails

### Default Configuration
- [ ] Smart defaults for new users
- [ ] Industry best practices for notification frequency
- [ ] Opt-in approach for marketing communications
- [ ] Essential notifications always enabled

### Performance
- [ ] Efficient preference queries
- [ ] Caching of frequently accessed settings
- [ ] Lazy loading of category metadata
- [ ] Debounced save operations

## Documentation & Polish

### User Documentation
- [ ] Help text for each notification category
- [ ] Examples of notification types
- [ ] Best practices for notification management
- [ ] Troubleshooting common issues

### Developer Documentation
- [ ] API documentation for preference endpoints
- [ ] Integration guide for new notification types
- [ ] Category and channel extension points
- [ ] Testing patterns and examples

### Accessibility
- [ ] Screen reader support for all controls
- [ ] Keyboard navigation throughout
- [ ] ARIA labels and descriptions
- [ ] High contrast mode support
- [ ] Focus management in modals/popovers

## Success Criteria Checklist
- [ ] Notification preferences organized into logical categories
- [ ] Multiple channels with granular frequency controls
- [ ] Quiet hours and scheduling functionality
- [ ] Legacy settings migrate automatically
- [ ] Category matrix provides intuitive control
- [ ] Bulk actions enable quick configuration
- [ ] Settings persist correctly across sessions
- [ ] Preview system shows notification examples
- [ ] Mobile experience is fully functional
- [ ] All interactions are accessible
- [ ] Performance is smooth with proper optimization
- [ ] Error states provide clear guidance