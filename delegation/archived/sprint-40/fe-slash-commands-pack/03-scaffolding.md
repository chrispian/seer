# Scaffolding Command Packs

## Artisan
- `php artisan frag:command:make {slug}`
  - Creates `fragments/commands/{slug}` (or under `storage/...` with `--user`).
  - Generates: `command.yaml`, `prompts/`, `samples/` with sensible defaults.
- `php artisan frag:command:cache`
  - Rebuilds `command_registry` from disk; prints a summary tree.
- `php artisan frag:command:test {slug} {sample?} --dry`
  - Executes the runner in dry mode, printing step outputs and diagnostics.

## Template (generated)
- `command.yaml` with `transform` and `notify` example.
- `prompts/create.md` stub (if applicable).
- `samples/input.txt` and `samples/out.json` placeholders.
