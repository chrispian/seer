# Accept Fragment Processing

This command processes inbox fragment acceptance via API.

## Purpose

Accept a fragment from the inbox, moving it from pending status to accepted status with optional edits.

## Input Context

- `ctx.body`: Fragment ID to accept
- `ctx.fragment_id`: Alternative way to specify fragment ID
- `ctx.tags`: Optional tags to apply during acceptance
- `ctx.type`: Optional type to set during acceptance
- `ctx.category`: Optional category to set during acceptance

## API Integration

Makes HTTP POST request to `/api/inbox/{id}/accept` with optional edits.

## Output

- Updates fragment status to "accepted"
- Applies any provided edits
- Shows success notification
- Sets reviewed_at and reviewed_by fields