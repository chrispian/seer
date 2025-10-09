# Readwise Reader Import

Import saved articles, RSS feeds, emails, and other documents from Readwise Reader into Fragments Engine.

## Overview

Readwise Reader is a read-it-later service where you save web articles, RSS feeds, newsletters, and more. This integration imports all your saved documents as Fragments, preserving metadata like author, tags, reading progress, and summaries.

## Features

- ✅ **Rate Limit Aware** - Automatically stops before hitting API limits (20 req/min)
- ✅ **Resumable** - Picks up where it left off on subsequent runs
- ✅ **Incremental** - Only imports new/updated documents after initial sync
- ✅ **Scheduled** - Runs daily at 2:00 AM UTC when enabled
- ✅ **Rich Metadata** - Preserves author, category, tags, word count, reading progress
- ✅ **Skips Highlights/Notes** - Only imports top-level documents (articles/posts)

## Usage

### Manual Sync

```bash
# Dry run to see what would be imported
php artisan readwise:reader:sync --dry-run

# Import documents
php artisan readwise:reader:sync

# Import only documents updated after specific date
php artisan readwise:reader:sync --since="2025-01-01T00:00:00Z"

# Resume from specific cursor
php artisan readwise:reader:sync --cursor="01k07avn89b6p6td3gj5shfpqq"
```

### Automatic Scheduling

The import runs automatically daily at **2:00 AM UTC** when:
1. Readwise API token is configured
2. `reader_sync_enabled` is set to `true` in user settings

Enable automatic sync:
```bash
php artisan tinker
$user = App\Models\User::first();
$settings = $user->profile_settings ?? [];
$settings['integrations']['readwise']['reader_sync_enabled'] = true;
$user->update(['profile_settings' => $settings]);
```

## Rate Limiting

The Reader API has a rate limit of **20 requests per minute**. The importer:

1. Tracks requests per minute
2. Stops at **15 requests** (5 request buffer for safety)
3. Saves state (cursor + last_synced_at) to resume next run
4. Resumes automatically on next scheduled run or manual execution

**Why it stops:** With 4,279+ documents and ~100 docs per page, it takes ~43 pages = 43 requests. At 15 requests/run, it takes 3 days to complete initial import.

**Daily resumption:** Run manually multiple times per day OR wait for daily scheduled runs.

## Data Stored

Each document creates a Fragment with:

### Core Fields
- `title` - Document title
- `message` - Summary + notes (if any)
- `type` - "log"
- `source` - "Readwise Reader"
- `source_key` - "readwise-reader"
- `created_at` - When document was saved in Reader
- `updated_at` - Last updated in Reader

### Metadata
- `readwise_reader_id` - Unique document ID
- `readwise_reader_url` - Readwise Reader URL
- `author` - Document author
- `category` - article, rss, email, tweet, pdf, epub, video, etc.
- `location` - new, later, archive, feed
- `site_name` - Source website name
- `word_count` - Article word count
- `reading_progress` - 0.0 to 1.0 (0% to 100%)
- `published_date` - Original publication date

### Relationships
- `source_url` - Original article URL
- `image_url` - Cover/preview image

### Tags
All Readwise Reader tags are preserved in the `tags` array.

## What Gets Imported

**Included:**
- Articles (web pages)
- RSS feed items
- Emails/newsletters
- Tweets
- PDFs
- EPUBs
- Videos

**Excluded:**
- Highlights (child documents with `parent_id`)
- Notes (child documents with `parent_id`)

Highlights and notes are skipped because they're nested under parent documents. Only top-level documents are imported.

## Troubleshooting

### All documents skipped
Check that documents don't have `parent_id` set. Only top-level documents are imported.

### Rate limit errors
The importer should stop before hitting limits, but if you see 429 errors:
- Wait 60 seconds
- Run again with `--cursor` from last successful run

### No documents imported
Verify:
```bash
php artisan tinker
$user = App\Models\User::first();
$token = Illuminate\Support\Facades\Crypt::decryptString(
    $user->profile_settings['integrations']['readwise']['api_token']
);
// Token should be a long alphanumeric string
```

### Check import progress
```bash
# See how many Reader documents are imported
php artisan tinker
Fragment::where('source_key', 'readwise-reader')->count();

# See latest imported documents
Fragment::where('source_key', 'readwise-reader')
    ->latest('created_at')
    ->limit(10)
    ->get(['title', 'created_at']);
```

### Check resume state
```bash
php artisan tinker
$user = App\Models\User::first();
$state = $user->profile_settings['integrations']['readwise']['reader'] ?? [];
echo json_encode($state, JSON_PRETTY_PRINT);
```

## Example Output

```
Readwise Reader Sync Summary
+--------------------+----------------------------+
| Metric             | Value                      |
+--------------------+----------------------------+
| Documents (total)  | 500                        |
| Documents imported | 500                        |
| Documents skipped  | 0                          |
| Rate limited       | Yes                        |
| Last cursor        | 01k07avn89b6p6td3gj5shfpqq |
| Last updated       | 2025-10-07T02:08:43+00:00  |
+--------------------+----------------------------+
Rate limit approached. Import stopped to prevent hitting API limits.
Run this command again tomorrow to continue importing.

Or schedule it with: php artisan schedule:work
```

## Architecture

### Files
- `app/Services/Readwise/ReadwiseApiClient.php` - API client with `fetchReaderDocuments()`
- `app/Services/Readwise/ReadwiseReaderImportService.php` - Import logic with rate limiting
- `app/Console/Commands/ReadwiseReaderSyncCommand.php` - CLI command
- `routes/console.php` - Scheduled task registration

### Flow
1. Check user settings for API token + `reader_sync_enabled`
2. Load resume state (cursor + last_synced_at)
3. Fetch 100 documents per page
4. Skip documents with `parent_id` (highlights/notes)
5. Create/update Fragments
6. Track request count, stop at 15 requests
7. Save state (cursor + last updated timestamp)
8. Resume on next run starting from saved cursor

## Related
- [Readwise Highlights Import](./READWISE_HIGHLIGHTS_IMPORT.md) - Import book/article highlights
- [Readwise API Docs](https://readwise.io/reader_api) - Official API documentation
