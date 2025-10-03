# Fragments Engine — Code-Ready Pack (Scheduler + Command Packs + Tool Calls)

> Laravel stubs to drop into your app. PHP 8.2+, Laravel 11+ recommended. Postgres preferred.

## Contents
- **Scheduler**: `schedules`, `schedule_runs`, tick command, job, TZ-safe next runs.
- **Command Packs**: file-based registry + YAML DSL runner + core steps.
- **Tool Calls**: Tool registry + providers (Shell, FS, MCP, Gmail, Todoist), invocation logging.
- **Built-in demo command packs**: `/news-digest-ai`, `/remind`, `/todo`, `/note`, `/link`, `/recall`, `/search`.

## Install
1. Copy files into your Laravel project root (preserve paths).
2. Run migrations:
   ```bash
   php artisan migrate
   ```
3. Configure paths and features in `config/fragments.php`.
4. Schedule the ticker (cron/systemd) to run each minute:
   ```bash
   * * * * * php /path/to/artisan frag:scheduler:tick --quiet
   ```
5. Rebuild registries:
   ```bash
   php artisan frag:command:cache
   php artisan frag:tool:cache
   ```

## Notes
- Steps are stubs. Wire your AI client (OpenAI/Anthropic/local) in `AiGenerateStep`.
- Tool providers are **capability-gated** and **allowlisted**; review before enabling in production.
- All tool invocations are logged in `tool_invocations` table.
