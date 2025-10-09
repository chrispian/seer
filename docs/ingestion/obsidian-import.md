# Obsidian Vault Import

The Obsidian integration syncs your markdown notes from an Obsidian vault into Fragments Engine on a daily schedule, storing them in the `codex` vault for knowledge management workflows.

## Setup

1. Navigate to **Settings → Integrations** and find the "Obsidian Vault Import" card.
2. Enter the full path to your Obsidian vault directory (e.g., `/Users/you/Documents/ObsidianVault`).
3. Click **Test** to verify the path is valid and see how many markdown files were found.
4. Enable the "Daily import" switch to allow the scheduler to run `obsidian:sync` each night (03:00 UTC).
5. (Optional) Enable "AI Enrichment" to run AI type inference and entity extraction during sync.
6. Click **Save Integration**.

## Manual Sync

```bash
php artisan obsidian:sync                           # Uses stored vault path and enrichment settings
php artisan obsidian:sync --vault-path=/path/to/vault   # Override vault path
php artisan obsidian:sync --dry-run                 # Preview import without writing fragments
php artisan obsidian:sync --enrich                  # Override: Force AI enrichment ON
php artisan obsidian:sync --no-enrich               # Override: Force AI enrichment OFF
php artisan obsidian:sync --force                   # Force re-import all files (ignore mtime)
```

**AI Enrichment Setting:**
- The `--enrich` flag overrides the setting configured in Settings → Integrations
- Without the flag, the sync uses the "AI Enrichment" toggle value from settings
- Scheduled syncs always use the setting (cannot pass flags to scheduler)

### Import Statistics

The sync command now provides detailed statistics:

```bash
php artisan obsidian:sync

# Output includes:
# - files_total: Total .md files found
# - files_imported: New fragments created
# - files_updated: Existing fragments updated
# - files_skipped: Unchanged files (based on mtime)
# - types_inferred: Distribution of inferred types
#   Example: {'contact': 12, 'meeting': 5, 'note': 876}
```

## Fragment Structure

Each markdown file becomes a fragment with:
- `vault` = `codex`
- `source_key` = `obsidian`
- `type` = `note` (or inferred type if using `--enrich`)
- `project_id` = Root project in codex vault
- `message` = Full markdown body (H1 headings, content, etc.)
- `title` = Extracted in priority order:
  1. Front matter `title:` field (if present)
  2. Filename (without `.md` extension)
  3. First H1 heading (if found)
  4. First line of content (fallback)
- `tags` = Folder name (normalized) + front matter tags + `obsidian`
- `metadata`:
  - `obsidian_path`: Relative path from vault root (e.g., `Daily Notes/2025-01-06.md`)
  - `obsidian_modified_at`: File modification timestamp
  - `front_matter`: Full YAML front matter as array

## Front Matter Mapping

YAML front matter is parsed and mapped as follows:

### Title Priority
Title extraction follows this order:

1. **Front matter title** (highest priority):
```yaml
---
title: My Important Note
---
```
→ `Fragment.title` = "My Important Note"

2. **Filename** (if no front matter title):
- File: `Meeting Notes.md`
- → `Fragment.title` = "Meeting Notes"

3. **First H1 heading** (fallback):
```markdown
# Project Kickoff
Content...
```
→ `Fragment.title` = "Project Kickoff"

4. **First line** (last resort if no H1)

### Tags (Array Format)
```yaml
---
tags:
  - work
  - project
  - urgent
---
```
→ `Fragment.tags` = `['work', 'project', 'urgent', 'folder-name', 'obsidian']`

### Tags (Comma-Separated)
```yaml
---
tags: work, project, urgent
---
```
→ Same as above

### Full Front Matter
All front matter fields are preserved in `metadata.front_matter` for custom workflows.

## Deterministic Pipeline

The Obsidian import uses a deterministic pipeline to intelligently infer fragment types and generate tags WITHOUT AI calls (fast and free).

### Type Inference Priority

1. **Front matter `type:` field** (highest - explicit user intent)
   ```yaml
   ---
   type: meeting
   ---
   ```
   → Fragment type = `meeting`

2. **Path-based rules** (folder patterns)
   - `Contacts/`, `People/` → `contact`
   - `Meetings/`, `Meeting Notes/` → `meeting`
   - `Tasks/`, `TODO/`, `To Do/` → `task`
   - `Projects/` → `project`
   - `Ideas/` → `idea`
   - `References/` → `reference`
   - `Clippings/` → `clip`
   - `Bookmarks/` → `bookmark`
   - `Daily Notes/`, `Journal/` → `log`
   
3. **Content patterns** (markdown analysis)
   - `# Meeting:` → `meeting`
   - `- [ ] Task item` → `task` (checkbox syntax)
   - `## Action Items` → `meeting`
   - `Project:` → `project`

4. **Default fallback** → `note`

### Smart Tag Generation

Tags are merged from multiple sources:

1. **Front matter tags**
   ```yaml
   ---
   tags: [work, urgent]
   # or
   tags: work, urgent
   ---
   ```

2. **Folder hierarchy**
   - File: `Work/Projects/Q1/Planning.md`
   - Tags: `['work', 'projects', 'q1']`

3. **Content hashtags**
   - Content: `This is a #meeting about #budget`
   - Tags: `['meeting', 'budget']`

4. **Obsidian tag** (always added)

All tags are normalized (lowercase, slugified) and deduplicated.

### Custom Metadata Extraction

The pipeline extracts these fields from front matter:
- `author`, `date`, `project`, `priority`
- `status`, `category`, `url`, `source_url`

Stored in `metadata->custom_fields` for use in queries and automation.

### Example

```yaml
---
type: contact
tags: [work, sales]
author: John Smith
company: Acme Corp
email: john@acme.com
---
# Jane Doe

Sales contact from Acme...
```

**Result:**
- Type: `contact` (from front matter)
- Tags: `['contacts', 'work', 'sales', 'obsidian']` (folder + front matter + auto)
- Custom fields: `author`, `company`, `email`

## Folder Structure

Folders are flattened and converted to tags:
- `Daily Notes/2025-01-06.md` → tag: `daily-notes`
- `Projects/Fragments/Design.md` → tag: `projects`
- `Note.md` (root level) → no folder tag

**Note**: Only the immediate parent folder becomes a tag. Nested folders like `Projects/Fragments/` will use `Projects` as the tag.

## Sync Behavior

### File Tracking
- Files are tracked by their relative path (`metadata->obsidian_path`)
- File modification time (`filemtime()`) is compared to `metadata->obsidian_modified_at`
- Only new or modified files are processed on subsequent syncs

### Update Strategy
- **New files**: Create new fragment
- **Modified files**: Update existing fragment (same `obsidian_path`)
- **Unchanged files**: Skip (based on mtime comparison)
- **Deleted files**: Ignored in MVP (manual cleanup required)

### Performance
- Deterministic mode (default): ~100ms per file
- Enrichment mode (`--enrich`): ~5-10s per file (AI processing)

## Scheduling

The scheduler runs once per day (03:00 UTC) and only executes when:
1. Vault path is configured in settings
2. Automatic sync is enabled
3. Vault directory is readable

Check `routes/console.php` for scheduling configuration.

## Limitations (MVP)

### Not Supported
- **Internal links**: `[[wikilinks]]` are stripped from content (text remains)
- **Media files**: Images, PDFs, attachments are ignored
- **Bidirectional sync**: Changes in Fragments Engine are not written back to Obsidian
- **Nested folders**: Only immediate parent folder becomes a tag
- **Deleted files**: Must be manually removed from fragments table
- **Multiple vaults**: Only one vault path supported

### Future Enhancements
See `delegation/sprints/SPRINT-52-PLAN.md` for roadmap of wikilinks, bidirectional sync, and media support.

## Troubleshooting

### "Vault path does not exist"
- Verify the path is absolute (e.g., `/Users/you/Documents/Vault` not `~/Documents/Vault`)
- Check directory exists and is readable: `ls -la /path/to/vault`

### "Directory is not readable"
- Check file permissions: `chmod 755 /path/to/vault`
- Ensure web server user has read access

### No files imported
- Run with `--dry-run` to see what would be imported
- Check that `.md` files exist in vault
- Verify files have been modified since last sync

### YAML parsing errors
- Check `storage/logs/laravel.log` for parse warnings
- Ensure front matter is valid YAML (use YAML linter)
- Malformed YAML is logged but import continues

### Slow imports
- Use default deterministic mode (skip `--enrich`)
- Consider running sync during off-hours
- Large vaults (1000+ files) may take several minutes on first import

## Examples

### Basic vault structure
```
MyVault/
  Daily Notes/
    2025-01-06.md
  Projects/
    Fragments.md
    Ideas.md
  Meeting Notes/
    Team Sync.md
```

After import:
- `Daily Notes/2025-01-06.md` → tags: `daily-notes`, `obsidian`
- `Projects/Fragments.md` → tags: `projects`, `obsidian`
- `Meeting Notes/Team Sync.md` → tags: `meeting-notes`, `obsidian`

### Front matter example
```markdown
---
title: Product Roadmap Q1
tags: planning, product, urgent
created: 2025-01-01
---

# Q1 Goals
- Launch feature X
- Improve performance

[[Related Note]]
```

Becomes fragment:
- **Title**: "Product Roadmap Q1"
- **Tags**: `['planning', 'product', 'urgent', 'projects', 'obsidian']`
- **Body**: Full markdown (wikilink stripped to "Related Note")
- **Metadata**: `{ front_matter: { created: '2025-01-01', ... }, ... }`

## Testing

Run parser tests:
```bash
./vendor/bin/pest tests/Unit/Services/ObsidianMarkdownParserTest.php
```

Run full test suite (when implemented):
```bash
./vendor/bin/pest tests/Feature/Console/ObsidianSyncCommandTest.php
```

## Related Documentation
- [Readwise Import](./readwise-import.md)
- [ChatGPT Import](./chatgpt-import.md)
- [Sprint 52 Plan](../../delegation/sprints/SPRINT-52-PLAN.md)
