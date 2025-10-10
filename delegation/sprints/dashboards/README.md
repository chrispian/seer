# Dashboards Feature

## Overview
Comprehensive dashboard system for monitoring and managing the Fragments Engine application. Provides real-time insights into system health, telemetry, orchestration tasks, audit logs, and agent activity.

## Feature Structure
```
delegation/features/dashboards/
├── README.md                    # This file - feature overview
├── ARCHITECTURE.md              # Technical architecture and data flow
├── tasks/
│   ├── DASH-001-planning.md     # Dashboard system planning and design
│   ├── DASH-002-backend-api.md  # Backend API endpoints
│   ├── DASH-003-system.md       # System Overview dashboard
│   ├── DASH-004-telemetry.md    # Telemetry dashboard
│   ├── DASH-005-orchestration.md # Tasks/Sprints/Agents dashboard
│   ├── DASH-006-audit.md        # Audit Logs dashboard
│   └── DASH-007-integration.md  # Navigation, routing, polish
└── designs/
    └── wireframes.md            # UI mockups and wireframes
```

## Dashboards Scope

### 1. System Overview Dashboard
**Purpose:** High-level health and activity monitoring

**Metrics:**
- System uptime
- Active users
- Database health (connection pool, query times)
- Queue health (pending jobs, failed jobs, workers)
- Cache hit rate
- Storage usage
- Recent errors/exceptions
- API response times (p50, p95, p99)

**Visualizations:**
- Real-time activity timeline
- Health status cards (green/yellow/red)
- Quick stats grid
- Recent activity feed

### 2. Telemetry Dashboard
**Purpose:** Performance monitoring and debugging

**Data Sources:**
- `telemetry_events` table
- `telemetry_metrics` table
- `telemetry_performance_snapshots` table
- `telemetry_correlation_chains` table

**Metrics:**
- LLM request latency (by provider/model)
- Fragment processing times
- Pipeline execution metrics
- Tool invocation stats
- Error rates by component
- Correlation chain analysis

**Visualizations:**
- Time-series charts (latency over time)
- Scatter plots (latency distribution)
- Correlation chain viewer (trace visualization)
- Error rate trends
- Provider/model comparison charts

### 3. Tasks/Sprints/Agents Dashboard
**Purpose:** Project orchestration and agent activity

**Data Sources:**
- `task_activities` table
- `sprints` table
- `sprint_items` table
- `agents` table
- `agent_logs` table
- `orchestration_artifacts` table

**Metrics:**
- Sprint progress (% complete)
- Task status breakdown (todo/in-progress/done/blocked)
- Agent activity (recent actions, decisions)
- Delegation status
- Velocity metrics (tasks completed per sprint)
- Bottleneck identification

**Visualizations:**
- Kanban board view
- Sprint burndown chart
- Agent activity timeline
- Task status pie chart
- Dependency graph

### 4. Audit Logs Dashboard
**Purpose:** Security and compliance monitoring

**Data Sources:**
- `activity_log` table (Spatie)
- `command_audit_logs` table
- `notifications` table

**Metrics:**
- Recent activities (all types)
- Destructive commands executed
- Failed commands
- User activity heatmap
- Model change frequency
- Security alerts

**Visualizations:**
- Activity timeline
- Command execution table
- User activity breakdown
- Alert feed
- Change history viewer (with diff)

### 5. Agent Activity Dashboard (NEW - Suggested)
**Purpose:** Deep dive into AI agent behavior and decisions

**Data Sources:**
- `agent_logs` table
- `agent_decisions` table
- `agent_profiles` table
- `telemetry_events` (agent-related)

**Metrics:**
- Agent invocation frequency
- Decision quality/accuracy
- Tool usage patterns
- Error rates per agent
- Token consumption
- Average response time

**Visualizations:**
- Agent decision tree
- Tool usage heatmap
- Success/failure rates
- Token usage trends
- Agent comparison view

## Additional Dashboard Suggestions

### 6. Fragment Analytics Dashboard (SUGGESTED)
**Purpose:** Content insights and fragment lifecycle

**Metrics:**
- Fragments created/updated/deleted (time series)
- Fragment type distribution
- Tag usage frequency
- Search query analytics
- Inbox processing metrics
- Fragment relationships graph

### 7. Integration Health Dashboard (SUGGESTED)
**Purpose:** Monitor external integrations

**Metrics:**
- Readwise sync status
- Obsidian sync status
- Hardcover sync status
- MCP server health
- API rate limit usage
- OAuth token expiration alerts

### 8. User Engagement Dashboard (FUTURE)
**Purpose:** Multi-user environment analytics

**Metrics:**
- Active users (DAU/WAU/MAU)
- Session duration
- Feature usage
- User retention
- Growth metrics

## Technical Stack

### Frontend
- **Framework:** React 18+ with TypeScript
- **UI Library:** shadcn/ui (Radix UI primitives)
- **Styling:** Tailwind CSS
- **Charts:** Recharts (React charting library)
- **Data Fetching:** TanStack Query (React Query)
- **Routing:** React Router v6
- **State:** Zustand (lightweight state management)

### Backend
- **Framework:** Laravel 12
- **Database:** PostgreSQL
- **Cache:** Redis
- **Queue:** Laravel Horizon
- **APIs:** RESTful JSON APIs

### Real-time Updates
- **Polling:** React Query with refetchInterval
- **WebSockets (optional):** Laravel Reverb/Pusher for live updates

## Design Principles

1. **Progressive Disclosure:** Start with high-level metrics, drill down on demand
2. **Responsive:** Mobile-first design with adaptive layouts
3. **Accessible:** WCAG 2.1 AA compliance
4. **Performance:** Lazy loading, virtualization for large datasets
5. **Consistent:** Shared component library, design tokens
6. **Contextual:** Link related data across dashboards

## Data Refresh Strategy

- **Real-time metrics:** 5-10 second polling
- **Hourly metrics:** 1 minute polling
- **Daily metrics:** 5 minute polling
- **Manual refresh:** Button available on all dashboards
- **WebSocket updates (future):** Instant for critical events

## Navigation Structure

```
Dashboard (root)
├── System Overview    (default landing)
├── Telemetry
│   ├── Performance
│   ├── Errors
│   └── Traces
├── Orchestration
│   ├── Tasks
│   ├── Sprints
│   └── Agents
├── Audit Logs
│   ├── Activities
│   ├── Commands
│   └── Alerts
└── Settings
    └── Dashboard Preferences
```

## Implementation Phases

### Phase 1: Foundation (DASH-001, DASH-002)
- Dashboard architecture planning
- Backend API endpoints
- Shared component library
- Base layout and navigation

### Phase 2: Core Dashboards (DASH-003 to DASH-006)
- System Overview dashboard
- Telemetry dashboard
- Orchestration dashboard
- Audit Logs dashboard

### Phase 3: Polish & Integration (DASH-007)
- Navigation integration
- Responsive design polish
- Performance optimization
- Testing and bug fixes

### Phase 4: Advanced Features (Future)
- Agent Activity dashboard
- Fragment Analytics dashboard
- Integration Health dashboard
- WebSocket real-time updates
- Advanced filtering and search
- Export/reporting capabilities

## Success Metrics

- Dashboard load time < 2 seconds
- Data refresh without UI jank
- Mobile usable (tested on phones/tablets)
- 100% API endpoint coverage for metrics
- Accessible (keyboard nav, screen readers)
- User feedback positive (when multi-user)

## Related Documentation
- Audit Logging: `docs/AUDIT_LOGGING.md`
- Telemetry: `docs/TELEMETRY.md` (if exists)
- Orchestration: `docs/ORCHESTRATION.md` (if exists)
- API Documentation: To be created

## Next Steps
1. Review and approve dashboard scope (this file)
2. Create DASH-001 planning task
3. Design wireframes and mockups
4. Build backend APIs
5. Implement frontend dashboards
6. Test and iterate
