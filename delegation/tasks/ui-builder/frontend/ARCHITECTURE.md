# UI Builder v2 - Architecture Overview

## System Flow

```
┌─────────────────┐
│  V2ShellPage    │  Entry point - registers components
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  PageRenderer   │  Fetches config, orchestrates rendering
└────────┬────────┘
         │
         ├──────────────────────┬──────────────────────┐
         ▼                      ▼                      ▼
┌──────────────┐      ┌──────────────┐      ┌──────────────┐
│   Layouts    │      │  Primitives  │      │   Hooks      │
├──────────────┤      ├──────────────┤      ├──────────────┤
│ ModalLayout  │      │ TableComp    │      │ usePageConf  │
│ RowsLayout   │      │ SearchBar    │      │ useDataSrc   │
│ ColumnsLayout│      │ ButtonIcon   │      │ useAction    │
└──────────────┘      └──────────────┘      └──────────────┘
```

## Component Communication

```
┌──────────────────┐
│  SearchBarComp   │
│  (types search)  │
└────────┬─────────┘
         │ useDataSource fetches results
         │
         ▼
┌──────────────────┐
│   SlotBinder     │  Pub/Sub for data flow
│  (broadcasts to  │
│   target comp)   │
└────────┬─────────┘
         │
         ▼
┌──────────────────┐
│  TableComponent  │
│  (subscribes to  │
│   slot updates)  │
└────────┬─────────┘
         │ User clicks row
         │
         ▼
┌──────────────────┐
│ ActionDispatcher │  Executes action
│  (POST /action)  │
└──────────────────┘
```

## Data Flow

```
Backend Config (JSON)
         │
         ▼
  GET /api/v2/ui/pages/{key}
         │
         ▼
   usePageConfig (fetch)
         │
         ▼
   PageRenderer (parse)
         │
         ├──────────────────────────────┐
         ▼                              ▼
ComponentRegistry.get(type)    useDataSource
         │                              │
         ▼                              ▼
   Component Instance        POST /datasource/{alias}/query
         │                              │
         │◄─────────────────────────────┘
         │        (receives data)
         ▼
    Render UI
```

## Action Execution Flow

```
User Interaction (click/search/etc)
         │
         ▼
  Component Handler
         │
         ▼
     useAction
         │
         ▼
  ActionDispatcher
         │
         ├─────────────┬─────────────┐
         ▼             ▼             ▼
   type=command   type=navigate   Interpolate
         │             │          {{params}}
         │             │             │
         ▼             ▼             ▼
  POST /api/v2/ui/action
         │
         ▼
    Backend Router
         │
         ├──────────────┬──────────────┐
         ▼              ▼              ▼
   Execute Cmd    Update State    Return Result
         │              │              │
         └──────────────┴──────────────┘
                        │
                        ▼
                  ActionResult
                        │
                        ├──────────────┬──────────────┐
                        ▼              ▼              ▼
                  Show Toast     Update UI      Navigate
```

## Component Registry Pattern

```
┌────────────────────────────────────────┐
│         ComponentRegistry              │
├────────────────────────────────────────┤
│  Map<string, ComponentRenderer>        │
│                                        │
│  'table'       → TableComponent        │
│  'search.bar'  → SearchBarComponent    │
│  'button.icon' → ButtonIconComponent   │
│  ...                                   │
└────────────────────────────────────────┘
                    ▲
                    │
         registerComponents()
                    │
    ┌───────────────┼───────────────┐
    │               │               │
TableComp      SearchBar      ButtonIcon
```

## Type System

```
┌─────────────────────────────────────────────────┐
│              types.ts                           │
├─────────────────────────────────────────────────┤
│                                                 │
│  PageConfig                                     │
│    ├─ id: string                                │
│    ├─ overlay: 'modal' | 'sheet' | 'page'       │
│    ├─ title?: string                            │
│    └─ components: ComponentConfig[]             │
│                                                 │
│  ComponentConfig                                │
│    ├─ id: string                                │
│    ├─ type: string                              │
│    ├─ dataSource?: string                       │
│    ├─ actions?: Record<string, ActionConfig>    │
│    ├─ result?: ResultConfig                     │
│    └─ ...                                       │
│                                                 │
│  ActionConfig                                   │
│    ├─ type: 'command' | 'navigate'              │
│    ├─ command?: string                          │
│    ├─ params?: Record<string, any>              │
│    └─ ...                                       │
│                                                 │
│  DataSourceQuery / DataSourceResult             │
│  ActionRequest / ActionResult                   │
└─────────────────────────────────────────────────┘
```

## Hook Dependencies

```
┌─────────────────┐
│ usePageConfig   │  Fetches page JSON
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ PageRenderer    │  Uses config to render
└────────┬────────┘
         │
         ├──────────────────────┐
         ▼                      ▼
┌─────────────────┐    ┌─────────────────┐
│ useDataSource   │    │   useAction     │
│  (components    │    │  (components    │
│   fetch data)   │    │   execute acts) │
└─────────────────┘    └─────────────────┘
         │                      │
         ▼                      ▼
┌─────────────────┐    ┌─────────────────┐
│  SlotBinder     │    │ ActionDispatcher│
│  (data binding) │    │ (POST actions)  │
└─────────────────┘    └─────────────────┘
```

## File Organization

```
resources/js/components/v2/
├── types.ts                    # TypeScript definitions
├── ComponentRegistry.ts        # Component type → React component mapping
├── ActionDispatcher.ts         # Action execution engine
├── SlotBinder.ts              # Pub/sub for component communication
├── PageRenderer.tsx           # Main orchestrator
├── registerComponents.ts      # Registration utility
├── V2ShellPage.tsx            # Entry point
├── index.ts                   # Public API
├── demo.tsx                   # Usage example
├── README.md                  # Documentation
│
├── hooks/
│   ├── usePageConfig.ts       # Fetch page configs
│   ├── useDataSource.ts       # Query datasources
│   └── useAction.ts           # Execute actions
│
├── layouts/
│   ├── ModalLayout.tsx        # Dialog wrapper
│   ├── RowsLayout.tsx         # Vertical flex
│   └── ColumnsLayout.tsx      # Horizontal flex
│
└── primitives/
    ├── TableComponent.tsx     # Data table
    ├── SearchBarComponent.tsx # Search input
    └── ButtonIconComponent.tsx # Action button
```

## Key Design Decisions

### 1. Component Registry over Direct Imports
- **Why**: Allows backend to control which components exist
- **Trade-off**: Extra indirection, but more flexible

### 2. Slot Binder over Props Drilling
- **Why**: Components don't need to know about each other
- **Trade-off**: Pub/sub is harder to debug, but scales better

### 3. Single Action Dispatcher
- **Why**: Backend controls all business logic
- **Trade-off**: Extra network hop, but consistent security

### 4. Config-First Design
- **Why**: Backend can update UI without deploys
- **Trade-off**: Less type safety at boundaries, but more agile

### 5. Shadcn UI Only
- **Why**: Consistent design system
- **Trade-off**: Vendor lock-in, but better DX

## Extension Points

### Add Custom Component
```tsx
import { componentRegistry } from '@/components/v2'

const MyComponent = ({ config }) => <div>...</div>
componentRegistry.register('my.custom', MyComponent)
```

### Add Custom Action Handler
```tsx
import { actionDispatcher } from '@/components/v2'

// Backend handles this - no frontend changes needed
```

### Add Custom Datasource
```tsx
import { useDataSource } from '@/components/v2'

const { data } = useDataSource({ dataSource: 'MyCustomSource' })
// Backend implements /api/v2/ui/datasource/MyCustomSource/query
```

## Performance Considerations

1. **Page Config Caching**: usePageConfig caches by key
2. **DataSource Debouncing**: Search uses 300ms debounce
3. **Slot Updates**: Only subscribed components re-render
4. **Component Registry**: O(1) lookup by type
5. **Action Execution**: Single POST, no batching yet

## Security Considerations

1. **CSRF Protection**: All mutations include CSRF token
2. **Backend Validation**: All actions validated server-side
3. **No Eval**: Config parsed as JSON, never executed
4. **XSS Protection**: React auto-escapes by default
5. **Auth**: Handled by backend (Laravel middleware)
