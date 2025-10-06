# Architecture: Type Packs & Registry

## Type Packs (files, versioned, overrideable)

```
fragments/
  types/
    todo/
      type.yaml
      state.schema.json
      indexes.yaml
      recall.md
      prompts/
        classify.md
        enrich.md
        agent.md
      views/
        view.blade.php
        edit.blade.php
```

### `type.yaml` (minimal example)
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

### `state.schema.json` (excerpt)
```json
{
  "$schema": "https://json-schema.org/draft/2020-12/schema",
  "type": "object",
  "properties": {
    "status": { "type": "string", "enum": ["open","in_progress","blocked","done","canceled"] },
    "due_at": { "type": ["string","null"], "format": "date-time" },
    "priority": { "type": "string", "enum": ["low","med","high","urgent"] }
  },
  "required": ["status"]
}
```

### `indexes.yaml` (drives generated columns + partial indexes)
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

## Registry & Precedence
1. `storage/fragments/types/*` (user overrides) – highest
2. `fragments/types/*` (core app defaults)
3. `modules/*/fragments/types/*` (plugins, optional)

On boot or `frag:type:cache`: load ➜ validate ➜ compute hashes ➜ store in `fragment_type_registry` for fast lookup.
