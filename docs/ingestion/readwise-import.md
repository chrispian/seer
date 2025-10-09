# Readwise Highlight Import

The Readwise integration pulls your highlights on a daily schedule and stores them as fragments for inbox/to-read workflows.

## Setup

1. Generate a Readwise API token (`https://readwise.io/api_deets`).
2. Navigate to **Settings → Integrations** and paste the token into the Readwise card.
3. Enable the “Daily import” switch to allow the scheduler to run `readwise:sync` each night (02:30 UTC).

Tokens are stored encrypted for now and will be migrated to secure storage in an upcoming sprint.

## Manual Sync

```bash
php artisan readwise:sync                 # Uses stored cursor/last-sync info
php artisan readwise:sync --dry-run       # Preview without writing fragments
php artisan readwise:sync --since=2025-09-01T00:00:00Z
php artisan readwise:sync --cursor=eyJwYWdlIjo0fQ==
```

## Fragment Structure

Each highlight becomes a fragment with:
- `source_key = readwise`
- `title` from the book/article title
- `message` containing the highlight text and note (if present)
- metadata: highlight id, URL, author, category, location, tags

## MCP Tool

The `system-tools:mcp` server exposes `readwise/highlights.fetch` for on-demand retrieval:

```bash
echo '{"method":"readwise/highlights.fetch","params":{"page_size":50}}' | php artisan system-tools:mcp
```

The tool honours the stored API token and accepts optional `pageCursor` and `updatedAfter` parameters.

## Scheduling

The scheduler runs once per day and only executes when a token exists and automatic sync is enabled. Check `routes/console.php` for the exact timing.
