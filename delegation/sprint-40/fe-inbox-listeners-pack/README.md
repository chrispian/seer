# Fragments Engine — Listeners, Projectors & Metrics (Code-Ready)

This pack wires **event listeners**, **read-model projectors**, and **metrics emitters** for the Inbox/Review flow (and is compatible with your scheduler/command runner).

## Install
1. Copy into your Laravel app (preserve paths).
2. Run migrations:
   ```bash
   php artisan migrate
   ```
3. Register the provider in `config/app.php` (or auto-discover via PSR-4):
   ```php
   'providers' => [
     // ...
     App\Providers\FragmentsEventServiceProvider::class,
   ]
   ```
4. (Optional) Configure metrics driver in `.env`:
   ```env
   FRAG_METRICS_DRIVER=log   # null|log|prom
   ```
5. Backfill daily metrics (optional):
   ```bash
   php artisan frag:metrics:backfill --days=30
   ```

## What you get
- **Listeners** to handle `FragmentAccepted`, `FragmentArchived`
- **Projector** updates read models:
  - `inbox_metrics_daily` (per-day acceptance/archival counts & avg time-to-review)
  - `fragment_activity` (append-only audit log)
- **Metrics** emitters (null/log/prom stubs)
- **Backfill command** for metrics
