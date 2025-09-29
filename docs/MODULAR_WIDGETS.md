# Modular Widget System

This document describes the modular widget architecture implemented for the right rail.

## Architecture

### Widget Structure
```
resources/js/widgets/
├── index.ts                    # Central exports
├── [widget-name]/
│   ├── [WidgetName]Widget.tsx  # Main component
│   ├── types.ts                # TypeScript interfaces
│   ├── hooks/
│   │   └── use[WidgetName].ts  # Data fetching hooks
│   └── components/             # Sub-components (optional)
│       └── *.tsx
```

### Current Widgets

#### 1. TodayActivityWidget
- **Purpose**: Shows daily activity metrics (messages, tokens, costs)
- **Data Source**: Aggregates Fragment metadata for today's AI responses
- **API**: `GET /api/widgets/today-activity`
- **Features**: Real-time updates, hourly chart data, model usage tracking

#### 2. RecentBookmarksWidget  
- **Purpose**: Displays recent bookmarks with search functionality
- **Data Source**: Bookmark model with vault/project scoping
- **API**: `GET /api/widgets/bookmarks`
- **Features**: Live search, infinite scroll, session scoping

#### 3. SessionInfoWidget
- **Purpose**: Shows current chat session metadata
- **Data Source**: Current session from useAppStore
- **Features**: Real-time session info, vault/project context

#### 4. ToolCallsWidget
- **Purpose**: Displays CoT reasoning and tool calls
- **Data Source**: Fragment metadata for tool call information  
- **API**: `GET /api/widgets/tool-calls`
- **Features**: Expandable cards, detailed metadata display

## API Endpoints

### Today Activity
```
GET /api/widgets/today-activity
Response: {
  messages: number,
  commands: number,
  totalTokensIn: number,
  totalTokensOut: number,
  totalCost: number,
  avgResponseTime: number,
  modelsUsed: string[],
  chartData: { hour: string, messages: number, tokens: number, cost: number }[]
}
```

### Bookmarks
```
GET /api/widgets/bookmarks?vault_id=1&project_id=2&query=search&limit=5&offset=0
Response: {
  bookmarks: BookmarkData[],
  total: number,
  hasMore: boolean
}
```

### Tool Calls
```
GET /api/widgets/tool-calls?session_id=1&type=tool_call&provider=openai&limit=20&offset=0
Response: ToolCallData[]
```

## Data Sources

### Fragment Metadata Structure
Widgets rely on Fragment.metadata JSON field containing:
- `turn`: 'prompt' | 'response'
- `session_id`: string
- `provider`: string
- `model`: string
- `token_usage`: { input: number, output: number }
- `cost_usd`: number
- `latency_ms`: number
- `reasoning`: string (optional)
- `tools_used`: string[] (optional)
- `confidence`: number (optional)

### Database Enhancements
- Added indexes for widget query performance
- Enhanced Bookmark model with vault_id/project_id
- Foreign key constraints for data integrity

## Usage

### Adding a New Widget

1. **Create widget directory**:
   ```bash
   mkdir resources/js/widgets/my-widget
   ```

2. **Create component**:
   ```tsx
   // resources/js/widgets/my-widget/MyWidget.tsx
   export function MyWidget() {
     const { data, isLoading } = useMyWidget()
     return <Card>...</Card>
   }
   ```

3. **Create types**:
   ```ts
   // resources/js/widgets/my-widget/types.ts
   export interface MyWidgetData {
     // Define your data structure
   }
   ```

4. **Create hook**:
   ```ts
   // resources/js/widgets/my-widget/hooks/useMyWidget.ts
   export function useMyWidget() {
     return useQuery({
       queryKey: ['widgets', 'my-widget'],
       queryFn: fetchMyWidgetData,
     })
   }
   ```

5. **Add to index**:
   ```ts
   // resources/js/widgets/index.ts
   export { MyWidget } from './my-widget/MyWidget'
   ```

6. **Use in RightRail**:
   ```tsx
   // resources/js/islands/shell/RightRail.tsx
   import { MyWidget } from '@/widgets'
   // Add <MyWidget /> to component
   ```

### Performance Considerations

- Widgets use React Query with appropriate stale times
- Database queries are optimized with indexes
- Real-time updates use configurable intervals (10-30s)
- Error boundaries isolate widget failures

### Styling Guidelines

- Use Shadcn components for consistency
- Follow existing Card/CardHeader/CardContent patterns  
- Use consistent spacing and typography
- Support both light/dark themes
- Ensure responsive design

## Future Enhancements

1. **Widget Configuration**: Allow users to reorder/hide widgets
2. **Chart Integration**: Add Shadcn charts to TodayActivityWidget
3. **Real-time Updates**: Consider WebSocket for live data
4. **Widget Marketplace**: Plugin system for custom widgets
5. **Export Functionality**: Allow data export from widgets