# UX-06-01: React Provider Management Interface - Context

## Existing UI Architecture

### Component Patterns
**Shadcn UI Components Available**:
- Card, CardHeader, CardContent, CardDescription, CardTitle
- Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger
- Sheet, SheetContent, SheetHeader, SheetTitle, SheetTrigger
- Button, Input, Label, Select, Switch, Textarea
- Badge, Alert, AlertDescription, Separator, Tabs
- Command, Popover, Dropdown, Tooltip

**Project UI Patterns**:
- **SettingsPage**: Tabs layout, form handling, save/loading states
- **ModelPicker**: API data fetching, loading states, error handling
- **AppShell/AppSidebar**: Navigation patterns, responsive layout
- **SetupWizard**: Multi-step forms, validation, progress tracking

### API Integration Context
**Provider API Endpoints** (from ENG-07-02):
```typescript
GET /api/providers              // List providers with status
GET /api/providers/{provider}   // Provider details
PUT /api/providers/{provider}   // Update provider config
POST /api/providers/{provider}/toggle  // Enable/disable
POST /api/providers/{provider}/test    // Test connectivity

GET /api/providers/{provider}/credentials    // List credentials
POST /api/providers/{provider}/credentials   // Add credentials
PUT /api/providers/{provider}/credentials/{id}  // Update
DELETE /api/providers/{provider}/credentials/{id}  // Remove
```

**Response Format**:
```typescript
interface ApiResponse<T> {
  data: T
  meta?: { pagination?, counts? }
  status: 'success' | 'error'
  message: string
}

interface Provider {
  id: string
  name: string
  enabled: boolean
  status: 'healthy' | 'unhealthy' | 'unknown'
  capabilities: string[]
  models: Model[]
  credentials_count: number
  last_health_check?: string
}

interface Credential {
  id: number
  credential_type: string
  is_active: boolean
  created_at: string
  expires_at?: string
  metadata?: object
  // Note: raw credentials never exposed
}
```

## UI Requirements

### Provider List Interface
**Main View**:
- Grid/list toggle for provider cards
- Provider status indicators (badges, colors)
- Quick enable/disable toggle switches
- Health status with last check timestamps
- Credential count and status indicators
- Add new provider button

**Provider Card Content**:
- Provider name and logo/icon
- Status badge (enabled/disabled, healthy/unhealthy)
- Model count and capabilities summary
- Quick actions (test, configure, disable)
- Health check timestamp and status

### Provider Detail/Edit Interface
**Provider Configuration**:
- Enable/disable provider toggle
- UI preferences (theme, display options)
- Provider capabilities and limits
- Usage statistics and analytics
- Health check history

**Credential Management**:
- List existing credentials (masked)
- Add new credential form
- Edit credential metadata
- Test credential functionality
- Remove/deactivate credentials

### Form Components Needed
**Provider Forms**:
- Provider configuration form
- Enable/disable toggle with confirmation
- Bulk actions for multiple providers

**Credential Forms**:
- Add credential form with provider-specific validation
- Credential testing with loading states
- Secure input fields (password type for API keys)
- Expiration date handling for OAuth tokens

## Technical Considerations

### State Management
**Local State**: Component-level state for forms and UI interactions
**API State**: React Query or SWR for server state management
**Real-time Updates**: WebSocket or polling for health status updates

### Security Requirements
- Never display raw credentials in forms (masked inputs)
- Secure form validation and sanitization
- Proper error handling without exposing sensitive info
- CSRF protection for state-changing operations

### Performance Considerations
- Lazy loading for provider details
- Optimistic updates for quick actions
- Debounced health checks and testing
- Efficient re-rendering with proper memo usage

### Responsive Design
- Mobile-friendly provider cards
- Touch-friendly action buttons
- Collapsible sections for smaller screens
- Accessible keyboard navigation

## Integration Points

### Existing Components
**Reuse From**:
- SettingsPage tab pattern for organization
- ModelPicker patterns for provider selection
- Dialog/Sheet patterns for forms and details
- Card patterns for provider listing

**Navigation Integration**:
- Add to AppSidebar under Settings
- Integrate with existing settings navigation
- Maintain consistent routing patterns

### API Integration
**HTTP Client**: Use existing Axios/fetch patterns
**Error Handling**: Follow existing error boundary patterns
**Loading States**: Use consistent loading spinners and skeletons
**Success/Error Messages**: Integrate with existing toast system

## Accessibility Requirements
- Proper ARIA labels for all interactive elements
- Keyboard navigation support
- Screen reader friendly status announcements
- Color contrast compliance for status indicators
- Focus management in modals and forms