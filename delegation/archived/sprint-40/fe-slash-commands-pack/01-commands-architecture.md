# Slash Commands Architecture (Backend-first)

## Command Packs (files; overrideable)
```
fragments/commands/{slug}/
  command.yaml          # manifest + steps (DSL)
  prompts/*.md          # optional prompt templates
  samples/*             # test inputs/outputs
```
**Precedence:** `storage/fragments/commands` (user) > `fragments/commands` (core) > `modules/*/fragments/commands` (plugins).

## Registry Cache (DB)
Table: `command_registry`
- `slug` TEXT UNIQUE
- `version` TEXT
- `source_path` TEXT
- `steps_hash` TEXT
- `capabilities` JSONB
- `requires_secrets` JSONB
- `reserved` BOOLEAN DEFAULT FALSE
- `created_at`, `updated_at`

## Runner
- Resolve pack by slug → validate DSL → execute steps with context.
- Context: `ctx.body`, `ctx.selection`, `ctx.user`, `ctx.session`, `ctx.workspace`, `ctx.now`.
- Merge-tag engine (Liquid-like) + filters.
- Each step yields a typed `output`. Downstream steps refer via `steps.<id>.output`.
