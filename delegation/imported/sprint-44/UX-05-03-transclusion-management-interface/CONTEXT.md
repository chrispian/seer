# UX-05-03 Transclusion Management Interface Context

## Technical Architecture

### Component Structure
```
resources/js/components/transclusion/
├── TransclusionManagementModal.tsx (main modal)
├── TransclusionList.tsx (list display)
├── TransclusionItem.tsx (individual item)
├── BrokenLinkDetector.tsx (health checking)
├── ConflictResolver.tsx (conflict resolution)
├── RelationshipViewer.tsx (dependency visualization)
└── BatchOperations.tsx (bulk actions)
```

### Modal Integration Patterns
- **CommandResultModal**: Base modal styling and behavior
- **TodoManagementModal**: Data table and management patterns
- **Shadcn Components**: Dialog, Table, Button, Badge, Tooltip
- **React Query**: Data fetching and caching

### Data Management
- TransclusionSpec CRUD operations
- Fragment relationship tracking
- Real-time status monitoring
- Conflict detection and resolution
- Batch operation support

### Dependencies
- All previous Sprint 44 task packs
- Existing modal and table patterns
- Fragment relationship system
- React Query for data management

### UI/UX Patterns
- Data table with sorting and filtering
- Status indicators and health badges
- Action buttons and confirmation dialogs
- Relationship visualization with graphs
- Bulk selection and operations