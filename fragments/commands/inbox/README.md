# Inbox Command Pack

A command pack for listing and reviewing fragments in the inbox.

## Usage

This command displays pending fragments awaiting review in the inbox.

### Example Usage

```bash
/inbox
/inbox type:todo
/inbox tags:urgent limit:20
/inbox vault:work category:meeting
```

### With scheduling

```yaml
# Daily inbox summary
name: "Daily Inbox Report"
command_pack: "inbox"
command_params:
  limit: 25
recurrence_type: "daily_at"
recurrence_value: "09:00"
```

## Features

- Lists pending inbox fragments
- Supports filtering by type, tags, category, vault
- Configurable result limits
- Shows formatted fragment previews
- Displays pagination information
- Integrates with inbox API

## Command Parameters

- `type` (optional): Filter by fragment type
- `tags` (optional): Filter by tags
- `category` (optional): Filter by category
- `vault` (optional): Filter by vault
- `limit` (optional): Number of results (default: 10)

## Output

- Formatted list with fragment details
- ID, title/preview, type, timestamp
- Tags display when present
- Total count and pagination info
- Organized numbered list format