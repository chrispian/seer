# Advanced Components (Tier 3C)

Advanced, specialized components with complex functionality for data display and interaction.

## Components

### 1. DataTable
**File**: `DataTableComponent.tsx`

Enhanced table component with sorting, filtering, pagination, and selection.

**Features**:
- Column sorting (click headers)
- Row selection (single/multiple)
- Pagination with page size control
- Row actions dropdown
- Click handlers on rows
- Custom cell renderers (text, badge, avatar)
- Loading and empty states
- Full TanStack Table integration

**Props**:
```typescript
{
  columns: Array<{
    key: string;
    label: string;
    sortable?: boolean;
    filterable?: boolean;
    render?: 'text' | 'badge' | 'avatar' | 'actions';
    width?: string;
    align?: 'left' | 'center' | 'right';
  }>;
  data: any[];
  pagination?: {
    enabled: boolean;
    pageSize: number;
  };
  selection?: {
    enabled: boolean;
    type: 'single' | 'multiple';
  };
  actions?: {
    rowClick?: ActionConfig;
    rowActions?: ComponentConfig[];
  };
  loading?: boolean;
  emptyText?: string;
  className?: string;
}
```

**Example**:
```json
{
  "id": "users-table",
  "type": "data-table",
  "props": {
    "columns": [
      { "key": "id", "label": "ID", "sortable": true },
      { "key": "user", "label": "User", "render": "avatar" },
      { "key": "status", "label": "Status", "render": "badge" }
    ],
    "data": [...],
    "pagination": { "enabled": true, "pageSize": 10 },
    "selection": { "enabled": true, "type": "multiple" }
  }
}
```

### 2. Chart
**File**: `ChartComponent.tsx`

Flexible chart component using Recharts library.

**Features**:
- Multiple chart types (bar, line, pie, donut, area)
- Responsive design
- Legend support
- Tooltips
- Custom colors
- Grid display
- Configurable axes

**Props**:
```typescript
{
  chartType: 'bar' | 'line' | 'pie' | 'area' | 'donut';
  data: Array<{ label: string; value: number }>;
  title?: string;
  legend?: boolean;
  colors?: string[];
  height?: number;
  xAxisKey?: string;
  yAxisKey?: string;
  showGrid?: boolean;
  showTooltip?: boolean;
  className?: string;
}
```

**Example**:
```json
{
  "id": "revenue-chart",
  "type": "chart",
  "props": {
    "chartType": "bar",
    "title": "Monthly Revenue",
    "data": [
      { "label": "Jan", "value": 4000 },
      { "label": "Feb", "value": 3000 }
    ],
    "legend": true,
    "height": 300
  }
}
```

### 3. Carousel
**File**: `CarouselComponent.tsx`

Image/content carousel with auto-play and navigation.

**Features**:
- Auto-play with configurable interval
- Loop/no-loop modes
- Dot indicators
- Arrow navigation
- Touch/swipe support (via CSS)
- Pause on hover
- Nested component support

**Props**:
```typescript
{
  items: ComponentConfig[];
  autoplay?: boolean;
  interval?: number;
  loop?: boolean;
  showDots?: boolean;
  showArrows?: boolean;
  className?: string;
}
```

**Example**:
```json
{
  "id": "hero-carousel",
  "type": "carousel",
  "props": {
    "items": [
      { "type": "card", "props": { "title": "Slide 1" } },
      { "type": "card", "props": { "title": "Slide 2" } }
    ],
    "autoplay": true,
    "interval": 5000,
    "showDots": true
  }
}
```

### 4. Sonner
**File**: `SonnerComponent.tsx`

Advanced toast notification system using Sonner library.

**Features**:
- Multiple variants (default, success, error, warning, info)
- Stacked notifications
- Action buttons
- Auto-dismiss with configurable duration
- Position control
- Rich descriptions

**Props**:
```typescript
{
  message: string;
  description?: string;
  action?: {
    label: string;
    action: ActionConfig;
  };
  duration?: number;
  position?: 'top-left' | 'top-center' | 'top-right' | 
             'bottom-left' | 'bottom-center' | 'bottom-right';
  variant?: 'default' | 'success' | 'error' | 'warning' | 'info';
  className?: string;
}
```

**Example**:
```json
{
  "id": "success-toast",
  "type": "sonner",
  "props": {
    "message": "Success!",
    "description": "Your changes have been saved",
    "variant": "success",
    "duration": 3000
  }
}
```

## Dependencies

All required dependencies are already installed:
- `@tanstack/react-table` (^8.21.3) - DataTable
- `recharts` (^2.15.4) - Chart
- `sonner` (^2.0.7) - Sonner

## Usage

### Import Registry Function
```typescript
import { registerAdvancedComponents } from '@/components/v2/ComponentRegistry';

// Register all advanced components
registerAdvancedComponents();
```

### Render Components
```typescript
import { renderComponent } from '@/components/v2/ComponentRegistry';

const config = {
  id: 'my-table',
  type: 'data-table',
  props: { ... }
};

const element = renderComponent(config);
```

## Examples

See `examples.ts` for complete working examples of all components with realistic data.

## Best Practices

### DataTable
- Use `render: 'badge'` for status columns
- Use `render: 'avatar'` for user columns
- Enable pagination for large datasets (>20 rows)
- Define row actions for common operations
- Use loading state during data fetches

### Chart
- Keep data points reasonable (max ~50 for line/bar, ~10 for pie)
- Use donut charts for percentage breakdowns
- Use area charts for trends over time
- Provide meaningful labels
- Use consistent color schemes

### Carousel
- Keep slides count reasonable (3-10)
- Use autoplay sparingly (5-7 seconds interval)
- Always show navigation on desktop
- Test on mobile for touch support
- Use high-quality images

### Sonner
- Use appropriate variants (success/error/warning)
- Keep messages concise
- Use descriptions for details
- Provide actions when applicable (undo, retry)
- Don't over-notify users

## Testing

All components include:
- TypeScript types
- Error states
- Empty states
- Loading states
- Responsive design
- Accessibility features

## Notes

- DataTable is the most complex component - study the code carefully
- Chart supports 5 chart types - extend as needed
- Carousel uses CSS transitions - no heavy dependencies
- Sonner automatically stacks notifications - no manual management
