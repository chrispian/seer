# Schedule Create Command

Creates a new scheduled task with specified recurrence pattern.

## Usage

```
/schedule:create "Name|command|recurrence|timezone"
```

## Parameters

Format: `"Name|command|recurrence|timezone"`

- **Name**: Human-readable name for the schedule
- **Command**: Command slug to execute (e.g., `orchestration:tasks`)
- **Recurrence**: When to run (see patterns below)
- **Timezone**: Optional timezone (defaults to UTC)

## Recurrence Patterns

### Daily
```
daily_at:HH:MM
```
Example: `daily_at:09:00` (runs daily at 9:00 AM)

### Weekly
```
weekly_at:DAYS:HH:MM
```
Example: `weekly_at:MON,FRI:10:00` (runs Mondays and Fridays at 10:00 AM)

### Cron Expression
```
cron:MIN HOUR DAY MONTH DOW
```
Example: `cron:0 */4 * * *` (runs every 4 hours)

### One-time
```
one_off:YYYY-MM-DD HH:MM
```
Example: `one_off:2024-12-01 15:30` (runs once on Dec 1, 2024 at 3:30 PM)

## Examples

```
/schedule:create "Daily Task Review|orchestration:tasks|daily_at:09:00|America/New_York"
/schedule:create "Weekly Sprint Check|orchestration:sprint:detail|weekly_at:MON:10:00|UTC"
/schedule:create "Task Import|delegation:import|cron:0 6 * * *|UTC"
/schedule:create "One-time Report|orchestration:tasks|one_off:2024-12-01 15:30|UTC"
```

## Available Commands

- `orchestration:tasks` - List work items
- `orchestration:task:assign` - Assign tasks to agents  
- `orchestration:sprint:detail` - Sprint summaries
- `delegation:import` - Import new tasks