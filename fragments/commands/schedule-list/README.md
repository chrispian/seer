# Schedule List Command

Lists all scheduled tasks with their status, next run times, and basic statistics.

## Usage

```
/schedule:list
/schedules
```

## Output

Shows a table with:
- Schedule ID and name
- Command being scheduled  
- Current status (active, paused, completed)
- Recurrence pattern
- Next and last run times
- Run count statistics

## Related Commands

- `/schedule:create` - Create new schedules
- `/schedule:detail <id>` - View detailed schedule information
- `/schedule:pause <id>` - Pause a schedule
- `/schedule:resume <id>` - Resume a paused schedule
- `/schedule:delete <id>` - Delete a schedule