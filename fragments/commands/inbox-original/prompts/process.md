# Inbox List Processing

This command retrieves and displays pending fragments from the inbox.

## Purpose

Show a formatted list of fragments currently pending review in the inbox.

## Input Context

- `ctx.type`: Optional type filter
- `ctx.tags`: Optional tags filter
- `ctx.category`: Optional category filter  
- `ctx.vault`: Optional vault filter
- `ctx.limit`: Number of results to show (default: 10)

## API Integration

Makes HTTP GET request to `/api/inbox` with query parameters for filtering.

## Output

- Formatted list of pending fragments
- Shows ID, title/preview, type, timestamp
- Includes tags if present
- Shows pagination info if applicable
- Displays total count