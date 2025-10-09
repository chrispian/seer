# ChatGPT Chat Importer

The `chatgpt:import` artisan command ingests conversations exported from ChatGPT Web and recreates them as native chat sessions plus fragments.

## Usage

```bash
php artisan chatgpt:import --path=/path/to/chatgpt-export
php artisan chatgpt:import --path=/path/to/chatgpt-export --dry-run
php artisan chatgpt:import --path=/path/to/chatgpt-export --pipeline
```

- `--path` must point to a directory containing `conversations.json` or to the file itself.
- `--dry-run` parses conversations and prints statistics without touching the database.
- `--pipeline` is reserved for a future specialised enrichment pipeline; it is ignored for now.

## Behaviour

- The importer walks the active branch of each conversation (`current_node`) and keeps user/assistant turns only. Hidden scaffold messages, tool outputs, and media references are skipped in the MVP.
- Messages become `Fragment` records with `source_key` of `chatgpt-user` or `chatgpt-web` and retain original timestamps and message ids in metadata.
- Chat sessions are upserted by `chatgpt_conversation_id`, with ordered message arrays that link back to fragment ids. Existing sessions are updated rather than duplicated.
- Default vault/project assignments follow the current default rows (created automatically at install time).

## Prerequisites

Run migrations to seed chatGPT source records:

```bash
php artisan migrate
```

Ensure the ChatGPT export has been unzipped locally; media assets are ignored in this iteration.

## Future Work

- Import referenced images/files alongside message metadata.
- Capture tool responses, thoughts, and alternate branches in a structured fashion.
- Add a dedicated enrichment pipeline step tailored to imported conversations.
