# Reminder Processing

This prompt processes reminder creation via the scheduler.

## Purpose

Create a reminder fragment that notifies the user about a specific task or event.

## Input Context

- `ctx.body`: The reminder text/message
- `ctx.user_id`: User who should receive the reminder
- `ctx.session_id`: Session context

## Output

Creates a reminder fragment with:
- Clear title indicating this is a reminder
- Original reminder text as content
- Metadata tracking that this was created via scheduler
- Appropriate tags for filtering and search