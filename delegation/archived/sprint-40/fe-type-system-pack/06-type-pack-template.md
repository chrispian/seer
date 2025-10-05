# Type Pack Template (todo)

## type.yaml
```yaml
name: To‑Do
slug: todo
version: 1.0.0
extends: null
min_requirements:
  - state.status
hot_fields:
  - state.status
  - state.due_at
ui:
  view: views/view.blade.php
  edit: views/edit.blade.php
prompts:
  classify: prompts/classify.md
  enrich: prompts/enrich.md
  agent: prompts/agent.md
policy:
  enrichable: true
  schedulable: true
```

## state.schema.json
```json
{
  "$schema": "https://json-schema.org/draft/2020-12/schema",
  "type": "object",
  "properties": {
    "status": { "type": "string", "enum": ["open","in_progress","blocked","done","canceled"] },
    "due_at": { "type": ["string","null"], "format": "date-time" },
    "priority": { "type": "string", "enum": ["low","med","high","urgent"], "default": "med" }
  },
  "required": ["status"]
}
```

## indexes.yaml
```yaml
generated_columns:
  - name: status
    path: $.state.status
    type: varchar(32)

  - name: due_at
    path: $.state.due_at
    type: timestamp with time zone

partial_indexes:
  - name: idx_fragments_todo_due_at
    columns: ["due_at"]
    where: "type = 'todo' AND due_at IS NOT NULL"
  - name: idx_fragments_todo_status
    columns: ["status"]
    where: "type = 'todo'"
```

## prompts/classify.md
```
Classify the fragment into a {type} and extract minimal state.
Return JSON: { "state": { "status": "...", "due_at": null } }
```

## prompts/enrich.md
```
Given content and current state, propose state improvements (keep minimal).
Return JSON patch.
```

## prompts/agent.md
```
Agent guidance for handling todos: keep updates idempotent; never change title without confirmation.
```

## views/view.blade.php
```php
<div class="space-y-1">
  <div class="font-semibold">Status: {{ $fragment->status }}</div>
  @if ($fragment->due_at)
    <div class="text-sm">Due: {{ $fragment->due_at->toDayDateTimeString() }}</div>
  @endif
  <div class="prose mt-2">{!! nl2br(e($fragment->content)) !!}</div>
</div>
```
