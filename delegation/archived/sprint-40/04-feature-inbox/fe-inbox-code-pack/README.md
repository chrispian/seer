# Fragments Engine — Inbox/Review (Code-Ready Pack)

Laravel stubs to add a deterministic Inbox/Review flow with optional AI-assist for title/summary/suggested edit.
- **MVP**: backend endpoints, services, events, migrations, and CLI.
- **AI options** are toggled by `.env` (per-install default). A future version can promote these to per-user settings.

## Install
1. Copy to your Laravel app (preserve paths).
2. Run migrations: `php artisan migrate`
3. Add routes in `routes/api.php` from `routes/api.inbox.php`.
4. (Optional) Enable AI assists in `.env`:
   ```env
   FRAG_INBOX_AI_TITLES=true
   FRAG_INBOX_AI_SUMMARIES=true
   FRAG_INBOX_AI_SUGGEST_EDIT=true
   FRAG_INBOX_AI_MODEL=gpt-4o-mini   # or your adapter
   FRAG_INBOX_AI_TEMPERATURE=0.2
   ```
5. Use endpoints or the provided Slash Command manifests (if using the Command Pack runner).
