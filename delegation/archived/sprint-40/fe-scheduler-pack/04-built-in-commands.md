# Built-in Command Packs for Scheduler Demos

## `/news-digest-ai` (daily 07:00)
- **Goal:** Fetch AI/dev news for the last 24h and compose a newsletter (10–15 items).
- **Note:** Source adapters can be stubbed (Phase 1) or pointed to RSS/APIs (Phase 2).

```yaml
name: AI News Digest
slug: news-digest-ai
version: 1.0.0
requires: { secrets: [], capabilities: ["ai.generate","fragment.create","tool.call"] }

steps:
  - id: time-window
    type: transform
    template: |
      { "from": "{{ now | date:'-24h' }}", "to": "{{ now }}" }

  - id: fetch
    type: tool.call
    with:
      name: "news.fetch"
      args:
        query: "AI OR artificial intelligence OR LLM OR 'generative'"
        from: "{{ steps.time-window.output.from }}"
        to: "{{ steps.time-window.output.to }}"
        limit: 100

  - id: rank
    type: ai.generate
    prompt: |
      You are a curator. Rank the items by developer interest and novelty. Return top 10–15 with title, source, link, 1–2 sentence summary.
      INPUT:
      {{ steps.fetch.output | take:100 }}
    expect: json

  - id: compose
    type: ai.generate
    prompt: |
      Compose a concise markdown newsletter with sections by theme, include dates and sources.
      ITEMS:
      {{ steps.rank.output }}
    expect: text

  - id: persist
    type: fragment.create
    with:
      type: document
      title: "AI News Digest — {{ now | date:'YYYY-MM-DD' }}"
      content: "{{ steps.compose.output }}"
      tags: ["newsletter","ai","digest"]
```

## `/remind` (one-off or daily/weekly)
```yaml
name: Reminder
slug: remind
version: 1.0.0
requires: { secrets: [], capabilities: ["fragment.create"] }

steps:
  - id: summary
    type: transform
    template: "{{ ctx.payload.message }}"
  - id: create
    type: fragment.create
    with:
      type: todo
      title: "Reminder: {{ steps.summary.output | take:80 }}"
      content: "{{ steps.summary.output }}"
      state:
        status: "open"
        due_at: "{{ ctx.payload.due_at_iso }}"
  - id: ok
    type: notify
    with: { message: "⏰ Reminder queued", level: success }
```
