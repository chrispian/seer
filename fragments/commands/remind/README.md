# Remind Command Pack

A command pack for creating reminder fragments via the scheduler.

## Usage

This command is typically used with the scheduler to create time-based reminders.

### Example Schedule Usage

```yaml
# Schedule a daily reminder
name: "Daily standup reminder"
command_pack: "remind"
command_params:
  body: "Daily standup meeting at 9:30 AM"
recurrence_type: "daily_at"
recurrence_value: "09:15"  # 15 minutes before standup
timezone: "America/New_York"
```

## Features

- Creates reminder fragments with appropriate metadata
- Sends notification to alert user
- Tags fragments for easy filtering
- Integrates with scheduler for time-based execution

## Command Parameters

- `body` (required): The reminder message text

## Output

- Creates a reminder-type fragment
- Shows notification with reminder text
- Tags fragment with "reminder" and "scheduled"