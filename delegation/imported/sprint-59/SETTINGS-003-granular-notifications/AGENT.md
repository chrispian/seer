# SETTINGS-003: Granular Notification Preferences

## Role
You are a Laravel + React developer implementing enhanced notification preferences with granular channel controls and contextual preference groupings.

## Context
The current notification settings offer basic boolean toggles for email, desktop, and sound notifications. This should be expanded into a comprehensive notification management system with channel-specific controls, preference groupings, and contextual copy.

## Current State
- Basic notification booleans: `email`, `desktop`, `sound`
- Stored in `profile_settings` JSON column
- Single preference endpoint handles all settings
- Limited notification channel options
- No contextual grouping or advanced controls

## Task Scope
Expand notification preferences into granular channel system:

### Enhanced Notification Channels
- Email notifications with frequency controls (instant, digest, weekly)
- Desktop push notifications with priority filtering
- Sound notifications with volume and sound selection
- Mobile notifications (when applicable)
- SMS notifications (optional future extension)

### Preference Groupings
- **System Notifications**: Updates, maintenance, security alerts
- **Activity Notifications**: Comments, mentions, collaboration events
- **Content Notifications**: New content, recommendations, AI completions
- **Marketing Communications**: Feature announcements, tips, newsletters
- **Administrative**: Billing, account changes, compliance

### Advanced Controls
- Quiet hours configuration (Do Not Disturb scheduling)
- Priority filtering (only urgent, all, none)
- Frequency controls (instant, batched, scheduled)
- Channel-specific overrides per notification type
- Contextual help explaining each notification type

### User Experience
- Visual notification channel icons and descriptions
- Preview system showing example notifications
- Granular but not overwhelming control layout
- Smart defaults based on user behavior
- Bulk toggle controls for convenience

## Success Criteria
- [ ] Notification preferences organized into logical groups
- [ ] Multiple channels with individual frequency controls
- [ ] Contextual descriptions help users understand options
- [ ] Smart defaults minimize configuration burden
- [ ] Bulk controls allow quick preference changes
- [ ] Settings persist and validate correctly
- [ ] Preview system demonstrates notification behavior

## Technical Constraints
- Must maintain backward compatibility with existing notification system
- Coordinate with existing email/push notification infrastructure
- Follow Laravel notification patterns
- Use React patterns consistent with other settings
- Ensure notification delivery respects new preferences