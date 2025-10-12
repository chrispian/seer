# Dashboard Architecture Specification
## Monitoring & Observability System
## Focus: Business Process Visibility

---

## Core Purpose

The Dashboard system provides real-time visibility into:
- **Sprint/Task Activity** - What's happening now
- **System Health** - Is everything working
- **Performance Metrics** - How fast/efficient
- **User Activity** - Who's doing what
- **Pipeline Status** - Async job monitoring

---

## Architecture Overview

### Three-Layer Design

```
┌─────────────────────────────────────┐
│         Dashboard Shell             │  <- Container/Router
├─────────────────────────────────────┤
│     Widget Layer (Pluggable)        │  <- Individual metrics
├─────────────────────────────────────┤
│    Data Provider Layer (APIs)       │  <- Real-time data feeds
└─────────────────────────────────────┘
```

### Dashboard Types

1. **Activity Dashboard** - Current work status
2. **Telemetry Dashboard** - System metrics
3. **Security Dashboard** - Access & audit logs
4. **Pipeline Dashboard** - Queue/job status
5. **Custom Dashboards** - User-configurable

---

## Implementation Design

### 1. Dashboard Registry (Database)

```sql
-- dashboards table
CREATE TABLE dashboards (
    id BIGINT PRIMARY KEY,
    slug VARCHAR(50) UNIQUE,
    name VARCHAR(100),
    description TEXT,
    category VARCHAR(50),
    layout JSON,  -- Grid configuration
    widgets JSON,  -- Widget placement
    refresh_interval INT DEFAULT 30000,
    is_system BOOLEAN DEFAULT false,
    is_public BOOLEAN DEFAULT false,
    created_by BIGINT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- dashboard_widgets table  
CREATE TABLE dashboard_widgets (
    id BIGINT PRIMARY KEY,
    dashboard_id BIGINT REFERENCES dashboards(id),
    widget_type VARCHAR(50),  -- 'metric', 'chart', 'list', 'timeline'
    position JSON,  -- {"x": 0, "y": 0, "w": 4, "h": 2}
    config JSON,  -- Widget-specific configuration
    data_source VARCHAR(100),  -- API endpoint or query
    refresh_interval INT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### 2. Dashboard Commands

#### Main Dashboard Command
```php
namespace App\Commands\Dashboard;

class DashboardCommand extends BaseCommand {
    protected ?string $dashboard = 'activity';  // Default dashboard
    
    public function handle(): array {
        $dashboard = $this->loadDashboard($this->dashboard);
        
        if (!$dashboard) {
            return $this->error("Dashboard '{$this->dashboard}' not found");
        }
        
        return $this->respond([
            'component' => 'DashboardContainer',
            'data' => [
                'dashboard' => $dashboard->toArray(),
                'widgets' => $this->loadWidgetData($dashboard->widgets),
                'available_dashboards' => $this->getAvailableDashboards(),
            ]
        ]);
    }
    
    private function loadWidgetData(array $widgets): array {
        $widgetData = [];
        
        foreach ($widgets as $widget) {
            $widgetData[$widget['id']] = match($widget['type']) {
                'sprint_status' => $this->getSprintStatus(),
                'task_metrics' => $this->getTaskMetrics(),
                'activity_feed' => $this->getActivityFeed(),
                'queue_status' => $this->getQueueStatus(),
                default => null
            };
        }
        
        return $widgetData;
    }
}
```

### 3. Widget System

#### Base Widget Class
```php
abstract class BaseWidget {
    protected array $config;
    protected int $refreshInterval = 30000;
    
    abstract public function getData(): array;
    abstract public function getComponent(): string;
    
    public function toArray(): array {
        return [
            'component' => $this->getComponent(),
            'data' => $this->getData(),
            'refresh_interval' => $this->refreshInterval,
            'config' => $this->config,
        ];
    }
}
```

#### Example Widget: Sprint Status
```php
class SprintStatusWidget extends BaseWidget {
    public function getData(): array {
        $activeSprint = Sprint::where('status', 'active')->first();
        
        if (!$activeSprint) {
            return ['message' => 'No active sprint'];
        }
        
        return [
            'sprint' => $activeSprint->toArray(),
            'progress' => [
                'total_tasks' => $activeSprint->tasks()->count(),
                'completed' => $activeSprint->tasks()->where('status', 'completed')->count(),
                'in_progress' => $activeSprint->tasks()->where('status', 'in_progress')->count(),
                'blocked' => $activeSprint->tasks()->where('status', 'blocked')->count(),
            ],
            'burndown' => $this->calculateBurndown($activeSprint),
            'velocity' => $this->calculateVelocity($activeSprint),
        ];
    }
    
    public function getComponent(): string {
        return 'SprintStatusWidget';
    }
}
```

#### Example Widget: Activity Feed
```php
class ActivityFeedWidget extends BaseWidget {
    public function getData(): array {
        $activities = Activity::with('user', 'subject')
            ->latest()
            ->limit($this->config['limit'] ?? 20)
            ->get();
            
        return [
            'activities' => $activities->map(fn($a) => [
                'id' => $a->id,
                'type' => $a->type,
                'description' => $a->description,
                'user' => $a->user->name,
                'time' => $a->created_at->diffForHumans(),
                'subject' => [
                    'type' => class_basename($a->subject_type),
                    'id' => $a->subject_id,
                    'name' => $a->subject->name ?? $a->subject->title ?? null,
                ],
            ])->toArray(),
        ];
    }
    
    public function getComponent(): string {
        return 'ActivityFeedWidget';
    }
}
```

### 4. Frontend Components

#### Dashboard Container
```typescript
interface DashboardContainerProps {
    dashboard: Dashboard
    widgets: Record<string, WidgetData>
    availableDashboards: Dashboard[]
}

export function DashboardContainer({ 
    dashboard, 
    widgets, 
    availableDashboards 
}: DashboardContainerProps) {
    const [layout, setLayout] = useState(dashboard.layout)
    const [isEditMode, setEditMode] = useState(false)
    
    return (
        <div className="dashboard-container">
            {/* Dashboard Header */}
            <div className="dashboard-header flex justify-between items-center p-4">
                <div className="flex items-center gap-4">
                    <h1 className="text-2xl font-bold">{dashboard.name}</h1>
                    <DashboardSelector 
                        current={dashboard}
                        available={availableDashboards}
                    />
                </div>
                
                <div className="flex gap-2">
                    <Button onClick={() => setEditMode(!isEditMode)}>
                        {isEditMode ? 'Save Layout' : 'Edit Layout'}
                    </Button>
                    <RefreshButton interval={dashboard.refresh_interval} />
                </div>
            </div>
            
            {/* Widget Grid */}
            <ResponsiveGridLayout
                className="dashboard-grid"
                layouts={layout}
                onLayoutChange={setLayout}
                isDraggable={isEditMode}
                isResizable={isEditMode}
                cols={{ lg: 12, md: 10, sm: 6, xs: 4 }}
                rowHeight={60}
            >
                {dashboard.widgets.map(widget => (
                    <div key={widget.id} data-grid={widget.position}>
                        <WidgetContainer
                            widget={widget}
                            data={widgets[widget.id]}
                            isEditMode={isEditMode}
                        />
                    </div>
                ))}
            </ResponsiveGridLayout>
        </div>
    )
}
```

#### Widget Container
```typescript
export function WidgetContainer({ widget, data, isEditMode }: WidgetContainerProps) {
    const WidgetComponent = WIDGET_COMPONENTS[widget.type]
    
    if (!WidgetComponent) {
        return <div>Unknown widget type: {widget.type}</div>
    }
    
    return (
        <Card className="widget-container h-full">
            {isEditMode && (
                <div className="widget-controls absolute top-2 right-2 z-10">
                    <Button size="sm" variant="ghost">⚙️</Button>
                    <Button size="sm" variant="ghost">✕</Button>
                </div>
            )}
            
            <WidgetComponent data={data} config={widget.config} />
        </Card>
    )
}
```

#### Sprint Status Widget Component
```typescript
export function SprintStatusWidget({ data, config }: WidgetProps) {
    if (!data.sprint) {
        return (
            <div className="p-4 text-center text-gray-500">
                {data.message || 'No active sprint'}
            </div>
        )
    }
    
    const { sprint, progress, burndown } = data
    const completionRate = (progress.completed / progress.total_tasks) * 100
    
    return (
        <div className="p-4 space-y-4">
            <div className="widget-header">
                <h3 className="font-semibold">{sprint.title}</h3>
                <Badge>{sprint.status}</Badge>
            </div>
            
            <div className="progress-stats grid grid-cols-4 gap-2 text-sm">
                <div>
                    <div className="text-gray-500">Total</div>
                    <div className="text-xl font-bold">{progress.total_tasks}</div>
                </div>
                <div>
                    <div className="text-gray-500">Done</div>
                    <div className="text-xl font-bold text-green-600">
                        {progress.completed}
                    </div>
                </div>
                <div>
                    <div className="text-gray-500">Active</div>
                    <div className="text-xl font-bold text-blue-600">
                        {progress.in_progress}
                    </div>
                </div>
                <div>
                    <div className="text-gray-500">Blocked</div>
                    <div className="text-xl font-bold text-red-600">
                        {progress.blocked}
                    </div>
                </div>
            </div>
            
            <div className="completion-bar">
                <Progress value={completionRate} className="h-2" />
                <div className="text-xs text-gray-500 mt-1">
                    {completionRate.toFixed(1)}% Complete
                </div>
            </div>
            
            {config.show_burndown && (
                <BurndownChart data={burndown} height={150} />
            )}
        </div>
    )
}
```

---

## Dashboard Configurations

### 1. Activity Dashboard
```json
{
    "slug": "activity",
    "name": "Activity Dashboard",
    "widgets": [
        {
            "type": "sprint_status",
            "position": {"x": 0, "y": 0, "w": 4, "h": 3},
            "config": {"show_burndown": true}
        },
        {
            "type": "task_board",
            "position": {"x": 4, "y": 0, "w": 4, "h": 3},
            "config": {"columns": ["todo", "in_progress", "done"]}
        },
        {
            "type": "activity_feed",
            "position": {"x": 8, "y": 0, "w": 4, "h": 3},
            "config": {"limit": 10}
        },
        {
            "type": "user_presence",
            "position": {"x": 0, "y": 3, "w": 12, "h": 2},
            "config": {"show_activity": true}
        }
    ]
}
```

### 2. Telemetry Dashboard
```json
{
    "slug": "telemetry",
    "name": "System Telemetry",
    "widgets": [
        {
            "type": "command_usage",
            "position": {"x": 0, "y": 0, "w": 6, "h": 3},
            "config": {"period": "24h", "top_n": 10}
        },
        {
            "type": "response_times",
            "position": {"x": 6, "y": 0, "w": 6, "h": 3},
            "config": {"show_p95": true}
        },
        {
            "type": "error_rate",
            "position": {"x": 0, "y": 3, "w": 4, "h": 2},
            "config": {"threshold": 5}
        },
        {
            "type": "database_stats",
            "position": {"x": 4, "y": 3, "w": 4, "h": 2},
            "config": {}
        },
        {
            "type": "cache_performance",
            "position": {"x": 8, "y": 3, "w": 4, "h": 2},
            "config": {}
        }
    ]
}
```

### 3. Pipeline Dashboard
```json
{
    "slug": "pipeline",
    "name": "Pipeline Monitor",
    "widgets": [
        {
            "type": "horizon_status",
            "position": {"x": 0, "y": 0, "w": 12, "h": 2},
            "config": {"show_workers": true}
        },
        {
            "type": "queue_depths",
            "position": {"x": 0, "y": 2, "w": 6, "h": 3},
            "config": {"queues": ["default", "high", "low"]}
        },
        {
            "type": "job_throughput",
            "position": {"x": 6, "y": 2, "w": 6, "h": 3},
            "config": {"period": "1h"}
        },
        {
            "type": "failed_jobs",
            "position": {"x": 0, "y": 5, "w": 12, "h": 3},
            "config": {"limit": 20}
        }
    ]
}
```

---

## Data Sources & APIs

### REST Endpoints
```php
// routes/api.php
Route::prefix('dashboards')->group(function () {
    Route::get('/', [DashboardController::class, 'index']);
    Route::get('/{slug}', [DashboardController::class, 'show']);
    Route::get('/{slug}/widgets/{widget}', [DashboardController::class, 'widget']);
    Route::post('/{slug}/layout', [DashboardController::class, 'saveLayout']);
});
```

### WebSocket Events (Real-time Updates)
```php
// Real-time activity broadcasting
class SprintUpdated implements ShouldBroadcast {
    public function broadcastOn() {
        return new Channel('dashboards.activity');
    }
    
    public function broadcastWith() {
        return [
            'widget' => 'sprint_status',
            'data' => (new SprintStatusWidget)->getData()
        ];
    }
}
```

### GraphQL Subscriptions (Optional)
```graphql
subscription DashboardUpdates($dashboardId: ID!) {
    dashboardWidget(dashboardId: $dashboardId) {
        widgetId
        type
        data
        timestamp
    }
}
```

---

## Widget Catalog

### Metric Widgets
- **Counter** - Single metric with trend
- **Gauge** - Percentage/progress indicator
- **Sparkline** - Mini time-series chart
- **KPI Card** - Key metric with comparison

### Chart Widgets
- **Line Chart** - Time series data
- **Bar Chart** - Categorical comparisons
- **Pie Chart** - Distribution/composition
- **Heatmap** - Two-dimensional density

### List Widgets
- **Activity Feed** - Recent events
- **Task List** - Current work items
- **Alert List** - System notifications
- **User List** - Active users

### Status Widgets
- **System Health** - Service status indicators
- **Pipeline Status** - Queue/job monitoring
- **Sprint Board** - Kanban-style view
- **Calendar** - Time-based events

---

## Performance Considerations

### Caching Strategy
```php
// Widget-level caching
Cache::remember("widget.{$type}.{$id}", $ttl, function () {
    return $widget->getData();
});

// Dashboard-level caching
Cache::tags(['dashboards'])->remember(
    "dashboard.{$slug}", 
    300,  // 5 minutes
    fn() => $dashboard->load('widgets')->toArray()
);
```

### Query Optimization
```php
// Eager load relationships
Sprint::with(['tasks', 'tasks.assignee'])
    ->where('status', 'active')
    ->first();

// Use database aggregations
DB::table('tasks')
    ->select('status', DB::raw('COUNT(*) as count'))
    ->groupBy('status')
    ->get();
```

### Frontend Optimization
```typescript
// Lazy load heavy widgets
const HeavyWidget = lazy(() => import('./widgets/HeavyWidget'))

// Virtualize long lists
<VirtualList
    items={activities}
    itemHeight={60}
    renderItem={ActivityItem}
/>

// Debounce refresh
const debouncedRefresh = useMemo(
    () => debounce(refreshWidget, 500),
    []
)
```

---

## Security & Permissions

### Dashboard Access Control
```php
class DashboardPolicy {
    public function view(User $user, Dashboard $dashboard): bool {
        if ($dashboard->is_public) {
            return true;
        }
        
        return $user->id === $dashboard->created_by ||
               $user->hasPermission('dashboards.view');
    }
    
    public function edit(User $user, Dashboard $dashboard): bool {
        return $user->id === $dashboard->created_by ||
               $user->hasPermission('dashboards.manage');
    }
}
```

### Widget Data Filtering
```php
class SecureWidgetData {
    public function filter(array $data, User $user): array {
        // Filter sensitive data based on user permissions
        if (!$user->hasPermission('view.sensitive')) {
            unset($data['sensitive_metrics']);
        }
        
        return $data;
    }
}
```

---

## Implementation Timeline

### Week 1: Foundation
1. Database schema creation
2. Dashboard command implementation
3. Base widget system
4. Container component

### Week 2: Core Widgets
1. Sprint status widget
2. Task metrics widget
3. Activity feed widget
4. Basic chart widgets

### Week 3: Advanced Features
1. Real-time updates
2. Custom dashboard creation
3. Widget configuration UI
4. Export/sharing capabilities

### Week 4: Polish
1. Performance optimization
2. Mobile responsive design
3. Accessibility improvements
4. Documentation

---

## Success Metrics

### Technical Metrics
- Page load time < 1 second
- Widget refresh < 500ms
- Support 20+ concurrent users
- 99.9% uptime

### Business Metrics
- 80% of users check dashboard daily
- Average session time > 5 minutes
- Custom dashboard creation rate > 50%
- User satisfaction score > 4.5/5

---

## Future Enhancements

1. **AI-Powered Insights**
   - Anomaly detection
   - Predictive analytics
   - Smart alerts

2. **Advanced Visualizations**
   - 3D charts
   - Network graphs
   - Geospatial maps

3. **Collaboration Features**
   - Shared dashboards
   - Annotations
   - Real-time cursors

4. **Mobile App**
   - Native iOS/Android apps
   - Push notifications
   - Offline support

5. **Integration Hub**
   - Third-party data sources
   - Webhook receivers
   - API connectors