# News Digest Generation

This prompt generates AI-powered news digests on specified topics.

## Purpose

Create automated news summaries that can be scheduled to run daily, weekly, or at custom intervals.

## Input Context

- `ctx.topics`: Comma-separated list of topics to include in digest (optional, defaults to tech topics)
- `ctx.user_id`: User who will receive the digest
- `ctx.session_id`: Session context

## AI Integration

Uses OpenAI GPT-4 to generate:
- Current event summaries
- Factual, concise content
- Well-structured markdown output
- Topic-focused information

## Output

Creates a news digest fragment with:
- Date-based title
- AI-generated content
- Metadata about generation process
- Appropriate tags for organization

## Scheduling Examples

- Daily morning briefing: `09:00` daily
- Weekly roundup: `MON:08:00` weekly  
- Custom topics: Technology, Business, Science