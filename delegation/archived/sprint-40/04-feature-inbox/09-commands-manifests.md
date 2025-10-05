# Command Pack Manifests (MVP)

## `/inbox`
```yaml
name: Inbox Summary
slug: inbox
version: 1.0.0
requires: { secrets: [], capabilities: ["tool.call"] }
steps:
  - id: list
    type: tool.call
    with:
      name: "mcp"
      args: { server: "fragments", method: "inboxList", params: { status: "pending", limit: 20 } }
  - id: notify
    type: notify
    with:
      message: "Inbox: {{ steps.list.output.count }} pending"
```

## `/accept`
```yaml
name: Accept Items
slug: accept
version: 1.0.0
requires: { secrets: [], capabilities: ["tool.call"] }
steps:
  - id: run
    type: tool.call
    with:
      name: "mcp"
      args:
        server: "fragments"
        method: "inboxAccept"
        params:
          ids: "{{ ctx.body }}"           # e.g., "id1,id2" or "all"
          edits:
            tags: []
            type: null
            category: null
            vault: null
  - id: ok
    type: notify
    with: { message: "✅ Accepted", level: success }
```

## `/archive`
```yaml
name: Archive Items
slug: archive
version: 1.0.0
requires: { secrets: [], capabilities: ["tool.call"] }
steps:
  - id: run
    type: tool.call
    with:
      name: "mcp"
      args:
        server: "fragments"
        method: "inboxArchive"
        params: { ids: "{{ ctx.body }}" }
```

## `/tag`
```yaml
name: Tag Item
slug: tag
version: 1.0.0
requires: { secrets: [], capabilities: ["tool.call"] }
steps:
  - id: run
    type: tool.call
    with:
      name: "mcp"
      args:
        server: "fragments"
        method: "inboxTag"
        params: { id: "{{ ctx.body.id }}", add: ["#inbox"], remove: [] }
```
