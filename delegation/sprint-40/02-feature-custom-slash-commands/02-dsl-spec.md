# DSL Spec (v1)

## Manifest
```yaml
name: Create To-Do
slug: todo
version: 1.0.0
triggers:
  slash: "/todo"
  aliases: ["/t"]
  input_mode: "inline|modal"
reserved: false

requires:
  secrets: []
  capabilities: ["ai.generate", "fragment.create"]

steps:
  - id: coerce-input
    type: transform
    template: |
      {{ ctx.body | default: ctx.selection | trim }}

  - id: build-state
    type: ai.generate
    prompt: prompts/create.md
    expect: json

  - id: create-fragment
    type: fragment.create
    with:
      type: "todo"
      title: "{{ steps.build-state.output.title }}"
      content: "{{ steps.coerce-input.output }}"
      state:
        status: "{{ steps.build-state.output.status }}"
        due_at: "{{ steps.build-state.output.due_at }}"
        priority: "{{ steps.build-state.output.priority }}"

  - id: toast
    type: notify
    with:
      message: "✅ To-Do created"
      level: success
```

## Step Types (v1)
- `transform` — interpolate a template/regex; output `text|json`.
- `ai.generate` — runs an LLM call with a prompt template; `expect: text|json`.
- `fragment.create` — validates against Type Pack schema; writes fragment.
- `search.query` — runs recall; returns json results.
- `notify` — system-level toast/log (no UI requirement).
- `tool.call` — whitelist only (schema-validated args).

## Merge Tags & Filters
Sources: `ctx.*`, `env.*` (gated), `steps.*.output`, `now`, `uuid`, `ulid`.
Filters: `trim`, `lower`, `upper`, `slug`, `default:x`, `take:n`, `date:ISO8601`, `jsonpath:$.foo`.
