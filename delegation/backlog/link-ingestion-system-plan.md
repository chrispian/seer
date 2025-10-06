# Link Ingestion & Preview System - Implementation Plan

**Created**: 2025-10-06  
**Sprint**: SPRINT-LINK-INGEST  
**Priority**: High  
**Goal**: 0-1 MVP as fast as possible, then iterate

---

## Product Overview

A comprehensive link ingestion system that allows users to save web links (and eventually other media) to Fragments Engine. Links are fetched, enriched with metadata, optionally summarized by AI, and routed to the inbox for review. Previews are shown in the chat composer and message display.

### Core User Flow
1. User pastes a URL into chat composer → sees instant preview
2. User sends the message → link is ingested in background
3. System fetches page metadata (title, description, favicon, cover image)
4. System extracts article content if obvious (readability)
5. AI generates summary (optional, default on, configurable model)
6. Link fragment created with type `link`, sent to inbox
7. Preview displayed in chat transcript with rich card

---

## Technical Architecture

### Type System Integration

**New Type Pack**: `fragments/types/link/`

```yaml
# type.yaml
name: "Link"
description: "Web link with metadata and content extraction"
version: "1.0.0"
author: "Fragments Engine"

capabilities:
  - "state_validation"
  - "hot_fields"
  - "content_extraction"
  - "media_preview"

ui:
  icon: "link"
  color: "#3B82F6"
  display_name: "Link"
  plural_name: "Links"

default_state:
  url: null
  title: null
  description: null
  favicon_url: null
  cover_image_url: null
  site_name: null
  author: null
  published_at: null
  content_extracted: false
  summarized: false

required_fields:
  - "url"
```

```json
// state.schema.json
{
  "$schema": "http://json-schema.org/draft-07/schema#",
  "title": "Link State Schema",
  "type": "object",
  "properties": {
    "url": {
      "type": "string",
      "format": "uri",
      "description": "Original URL"
    },
    "title": {
      "type": ["string", "null"],
      "description": "Page title"
    },
    "description": {
      "type": ["string", "null"],
      "description": "Meta description or excerpt"
    },
    "favicon_url": {
      "type": ["string", "null"],
      "description": "Downloaded favicon URL"
    },
    "cover_image_url": {
      "type": ["string", "null"],
      "description": "Downloaded cover/OG image URL"
    },
    "site_name": {
      "type": ["string", "null"],
      "description": "Site name (e.g., 'The New York Times')"
    },
    "author": {
      "type": ["string", "null"]
    },
    "published_at": {
      "type": ["string", "null"],
      "format": "date-time"
    },
    "content_type": {
      "type": "string",
      "enum": ["article", "video", "image", "document", "unknown"],
      "default": "unknown"
    },
    "content_extracted": {
      "type": "boolean",
      "default": false
    },
    "summarized": {
      "type": "boolean",
      "default": false
    },
    "oembed_data": {
      "type": ["object", "null"],
      "description": "oEmbed response for rich embeds"
    }
  },
  "required": ["url"]
}
```

### Database Schema

**Fragment Model** (existing, add fields):
- `raw_content` (text, nullable): Original HTML/article content for reference
- `summary_content` (text, nullable): AI-generated summary

**User Settings** (add to profile_settings JSON):
```json
{
  "link_ingestion": {
    "auto_summarize": true,
    "summary_model": "openai:gpt-4o-mini",
    "extract_content": true,
    "download_images": true
  }
}
```

### API Endpoints

**POST /api/links/ingest**
```json
{
  "url": "https://example.com/article",
  "source": "chat", // or "api", "extension"
  "auto_summarize": true // overrides user setting
}
```

Response:
```json
{
  "fragment_id": 123,
  "preview": {
    "title": "Article Title",
    "description": "Preview text...",
    "image_url": "/storage/links/cover-abc123.jpg",
    "favicon_url": "/storage/links/favicon-xyz789.ico"
  }
}
```

**GET /api/links/preview?url=...**
Fast preview endpoint for chat composer (no persistence)

**POST /api/links/oembed**
OEmbed proxy for YouTube, Twitter, etc.

### Services Architecture

```
App/Services/Links/
├── LinkIngestionService.php       # Main orchestrator
├── MetadataExtractor.php          # Fetch & parse HTML metadata
├── ContentExtractor.php           # Readability-style content extraction
├── MediaDownloader.php            # Download & store favicon/images
├── OEmbedService.php              # oEmbed provider integration
└── LinkSummarizer.php             # AI summarization
```

### TipTap Extension

**New Extension**: `resources/js/islands/chat/tiptap/extensions/LinkPreview.tsx`

Detects URLs as user types, shows inline preview card with:
- Favicon
- Title
- Description
- Cover image thumbnail
- Loading states

### Chat Transcript Display

**Component**: `resources/js/components/chat/LinkPreviewCard.tsx`

Rich card rendering in message display:
- Full-width card for standalone links
- Inline preview for links in text
- Click to expand full content
- Actions: Open in new tab, Archive, Add to collection

---

## Implementation Phases

### Phase 1: Core Infrastructure (MVP)
**Goal**: Basic link ingestion with metadata

- [ ] Create `link` type pack
- [ ] Add `raw_content`, `summary_content` to fragments migration
- [ ] Build `LinkIngestionService` with metadata extraction
- [ ] Build `MediaDownloader` for favicon/images
- [ ] Add `/api/links/ingest` endpoint
- [ ] Job queue for async processing

### Phase 2: Chat Integration
**Goal**: URL detection and preview in chat

- [ ] TipTap LinkPreview extension
- [ ] `/api/links/preview` endpoint
- [ ] LinkPreviewCard component for transcript
- [ ] Handle link submission from chat composer

### Phase 3: AI Enrichment
**Goal**: Automatic summarization and content extraction

- [ ] `ContentExtractor` using readability algorithm
- [ ] `LinkSummarizer` service with AI
- [ ] User settings for summary model selection
- [ ] Background job for summarization

### Phase 4: OEmbed & Rich Media
**Goal**: YouTube, Twitter, etc. embeds

- [ ] `OEmbedService` with provider registry
- [ ] Iframe embed rendering
- [ ] Video thumbnail extraction
- [ ] Security: CSP and iframe sandboxing

### Phase 5: Polish & UX
**Goal**: Production-ready experience

- [ ] Error handling (404s, paywalls, etc.)
- [ ] Rate limiting & caching
- [ ] Link deduplication detection
- [ ] Batch import interface
- [ ] Browser extension hooks

---

## Technology Stack

### Backend (PHP/Laravel)
- **Metadata Extraction**: `symfony/dom-crawler` + `symfony/css-selector`
- **Content Extraction**: Custom readability port or `fivefilters/readability.php`
- **oEmbed**: Manual implementation with provider registry
- **Image Processing**: Intervention Image (already installed)
- **Jobs**: Laravel Queue with Horizon

### Frontend (React/TypeScript)
- **TipTap**: Already installed, add Link extension
- **URL Detection**: Regex + validation
- **Preview Cards**: Tailwind UI components
- **Loading States**: Skeleton loaders

### Storage
- **Images**: `storage/app/public/links/{year}/{month}/`
- **Naming**: Hash-based to avoid duplicates
- **Cleanup**: Soft delete + periodic pruning job

---

## User Settings Schema

```json
{
  "profile_settings": {
    "link_ingestion": {
      "auto_summarize": true,
      "summary_model": "openai:gpt-4o-mini",
      "summary_prompt": "Summarize this article in 2-3 sentences, focusing on key insights.",
      "extract_content": true,
      "download_images": true,
      "auto_tag": true,
      "auto_categorize": true,
      "inbox_by_default": true
    }
  }
}
```

---

## Security Considerations

1. **URL Validation**: Sanitize and validate all URLs
2. **SSRF Protection**: Block internal IPs, localhost, cloud metadata endpoints
3. **Rate Limiting**: Prevent abuse of ingestion API
4. **File Size Limits**: Max 5MB for images, 2MB for favicons
5. **Content Security**: Sanitize extracted HTML
6. **Iframe Sandboxing**: `sandbox="allow-scripts allow-same-origin"` for embeds

---

## Testing Strategy

### Unit Tests
- URL validation and sanitization
- Metadata extraction from HTML
- Content extraction algorithm
- Media download and storage
- AI summarization integration

### Integration Tests
- Full ingestion pipeline
- Chat composer → fragment creation
- Preview generation
- OEmbed provider responses

### E2E Tests
- Paste URL in chat → see preview
- Send message → link saved to inbox
- Click link card → view full content
- Settings: Toggle auto-summarize

---

## Success Metrics

**MVP (Week 1)**
- ✅ Paste URL in chat → instant preview appears
- ✅ Send message → link fragment created with metadata
- ✅ Inbox shows link with title, description, favicon
- ✅ Click link → view extracted content

**V1 (Week 2)**
- ✅ AI summary generated for articles
- ✅ YouTube embeds work via oEmbed
- ✅ User can configure summary model
- ✅ Deduplication: Same URL = update existing fragment

**V2 (Future)**
- Browser extension "Save to Fragments"
- Batch import from bookmarks
- Link collections/reading lists
- Offline reading mode
- Full-text search across link content

---

## Open Questions & Decisions

1. **Markdown vs. Raw**: Should TipTap store markdown or let backend handle it?
   - **Decision**: TipTap stores markdown, backend extracts URL and processes

2. **Preview Timing**: Fetch preview on paste or on send?
   - **Decision**: Lightweight preview on paste, full ingestion on send

3. **Duplicate Links**: Update existing or create new fragment?
   - **Decision**: Prompt user if duplicate found, offer "View existing" option

4. **Paywall Handling**: What to do with paywalled content?
   - **Decision**: Store metadata only, flag as paywalled, no content extraction

5. **YouTube Shorts**: Are these oEmbed compatible?
   - **Decision**: Yes, YouTube oEmbed supports all video types

---

## Related Documentation

- Type System: `docs/type-system-crud-review`
- Fragments: `docs/FRAGMENTS_ENGINE_MVP_PRD.md`
- Inbox: `app/Commands/InboxCommand.php`
- TipTap Extensions: `resources/js/islands/chat/tiptap/extensions/`
- Markdown Config: `config/markdown.php`

---

## Dependencies

**Composer Packages** (add):
```json
{
  "fivefilters/readability.php": "^3.0",
  "embed/embed": "^4.4"
}
```

**NPM Packages** (add):
```json
{
  "@tiptap/extension-link": "^2.1.0",
  "oembed-parser": "^1.4.9"
}
```

---

## Next Steps

1. Review and approve this plan
2. Create ORCH sprint with tasks
3. Assign frontend + backend engineers
4. Kick off Phase 1 development
5. Daily standups to track progress
