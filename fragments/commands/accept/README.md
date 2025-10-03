# Accept Command Pack

A command pack for accepting fragments from the inbox.

## Usage

This command accepts a pending fragment from the inbox and optionally applies edits.

### Example Usage

```bash
/accept 123
/accept 456 type:todo tags:urgent,work
```

### With scheduling

```yaml
# Auto-accept fragments matching criteria
name: "Auto-accept notes"
command_pack: "accept"
command_params:
  fragment_id: "{{ fragment.id }}"
  type: "note"
filters:
  type: "note"
  inbox_status: "pending"
```

## Features

- Accepts fragments from pending to accepted status
- Supports optional edits during acceptance
- Validates fragment ID input
- Provides success/error feedback
- Integrates with inbox API

## Command Parameters

- `body` or `fragment_id` (required): ID of fragment to accept
- `tags` (optional): Array of tags to apply
- `type` (optional): Fragment type to set
- `category` (optional): Category to assign

## Output

- Moves fragment to accepted status
- Shows confirmation notification
- Updates review metadata (reviewed_at, reviewed_by)