# Sample Command Packs (Manifests)

## `/todo`
```yaml
name: Create To-Do
slug: todo
version: 1.0.0
triggers: { slash: "/todo", aliases: ["/t"], input_mode: "inline" }
requires: { secrets: [], capabilities: ["fragment.create","ai.generate"] }
steps:
  - id: input
    type: transform
    template: "{{ ctx.body | default: ctx.selection | trim }}"
  - id: parse
    type: ai.generate
    prompt: |
      Parse task: """{{ steps.input.output }}"""
      Return JSON: {"title":"...", "status":"open", "due_at": null, "priority":"med"}
    expect: json
  - id: create
    type: fragment.create
    with:
      type: todo
      title: "{{ steps.parse.output.title }}"
      content: "{{ steps.input.output }}"
      state:
        status: "{{ steps.parse.output.status }}"
        due_at: "{{ steps.parse.output.due_at }}"
        priority: "{{ steps.parse.output.priority }}"
  - id: ok
    type: notify
    with: { message: "✅ To-Do created", level: success }
```

## `/note`
```yaml
name: Quick Note
slug: note
version: 1.0.0
triggers: { slash: "/note", aliases: ["/n"], input_mode: "inline" }
requires: { secrets: [], capabilities: ["fragment.create"] }
steps:
  - id: content
    type: transform
    template: "{{ ctx.body | default: ctx.selection | trim }}"
  - id: create
    type: fragment.create
    with:
      type: document
      title: "{{ content | slice:0,80 }}"
      content: "{{ steps.content.output }}"
  - id: ok
    type: notify
    with: { message: "📝 Note saved", level: success }
```

## `/link`
```yaml
name: Quick Link
slug: link
version: 1.0.0
triggers: { slash: "/link", aliases: ["/l"], input_mode: "inline" }
requires: { secrets: [], capabilities: ["fragment.create"] }
steps:
  - id: url
    type: transform
    template: "{{ ctx.body | trim }}"
  - id: create
    type: fragment.create
    with:
      type: link
      title: "{{ steps.url.output }}"
      content: "{{ steps.url.output }}"
      state: { url: "{{ steps.url.output }}" }
  - id: ok
    type: notify
    with: { message: "🔗 Link captured", level: success }
```

## `/recall`
```yaml
name: Recall
slug: recall
version: 1.0.0
triggers: { slash: "/recall", aliases: [], input_mode: "inline" }
requires: { secrets: [], capabilities: ["search.query"] }
steps:
  - id: q
    type: transform
    template: "{{ ctx.body | trim }}"
  - id: search
    type: search.query
    with: { query: "{{ steps.q.output }}", limit: 10 }
  - id: notify
    type: notify
    with:
      message: "Found {{ steps.search.output.count }} results"
      level: info
```

## `/search`
```yaml
name: Search
slug: search
version: 1.0.0
triggers: { slash: "/search", aliases: ["/s"], input_mode: "inline" }
requires: { secrets: [], capabilities: ["search.query"] }
steps:
  - id: q
    type: transform
    template: "{{ ctx.body | trim }}"
  - id: search
    type: search.query
    with: { query: "{{ steps.q.output }}", filters: { type: "*" }, limit: 25 }
  - id: note
    type: notify
    with: { message: "🔎 Search complete", level: info }
```
