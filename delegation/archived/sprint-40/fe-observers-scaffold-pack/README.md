# Fragments Engine — Observers/Projectors Scaffold Pack

This pack scaffolds **events**, **listeners/projectors**, **migrations**, and **metrics** wiring for:
- **Scheduler runs** (start/finish)
- **Tool calls** (invoked/completed)
- **Command runner** (started/completed)
- **Fragment lifecycle** (created/updated/deleted)

It mirrors the pattern used in your Inbox/Review listeners pack so agents can fill in details incrementally.

## Install
1. Copy into your Laravel app (preserve paths).
2. Run migrations: `php artisan migrate`
3. Register provider in `config/app.php` if not using auto-discovery:
   ```php
   App\Providers\FragmentsPipelineEventServiceProvider::class,
   ```
4. (Optional) Set metrics driver (null|log|prom):
   ```env
   FRAG_METRICS_DRIVER=log
   ```

## Backfills
- `php artisan frag:backfill:metrics --days=30` (scheduler/commands/tools aggregates)
