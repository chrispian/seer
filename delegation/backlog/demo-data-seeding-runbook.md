# Demo Data Seeding Runbook

Status: draft (2025-10-05)
Owner: Fragments Demo Dataset Initiative

## Purpose

This runbook captures the steps required to seed, validate, and re-run the demo dataset introduced via `Database\Seeders\Demo\DemoDataSeeder`. It also records the quick sanity checks we run after seeding and the commands we use to inspect data integrity.

## Prerequisites

- Local environment configured for the project (`composer install`, `npm install`).
- SQLite (default) or another configured database connection.
- Optional: remove or back up `database/database.sqlite` if you want a truly clean slate.

## Seed Workflow

```bash
# Ensure the SQLite database file exists
[ -f database/database.sqlite ] || touch database/database.sqlite

# Reset schema and run all seeders (includes DemoDataSeeder)
php artisan migrate:fresh --seed
```

The top-level seeder (`DatabaseSeeder`) ensures demo data runs automatically in `local`, `development`, or `testing` environments, or whenever `app.seed_demo_data=true`.

## What to Expect

After a successful run (October 5 snapshot):

- **Users:** 1 (`demo@example.com` retained via `UserSeeder`)
- **Vaults:** 6 (default + routing demo + new demo work/personal vaults)
- **Projects:** 8 total, 4 of which are flagged as demo projects
- **Fragments:** 175 total
  - Todos: 100 fragments (`metadata.demo_category = "todo"`)
  - Contacts: 25 fragments (`metadata.demo_category = "contact"`)
  - Chat messages: 50 fragments (`metadata.demo_category = "chat_message"`)
- **Todos:** 100 records (tied 1:1 to todo fragments)
- **Contacts:** 25 records
- **Chat Sessions:** 10 sessions, each storing its message array plus linked message fragments

Time distribution (todos + chat messages) is generated over ~90 days. Current run produced `created_at` spanning `2025-07-08` through `2025-10-17`.

## Validation Snippets

```bash
# High-level counts
php artisan tinker --execute='use App\Models\{User,Vault,Project,Fragment,Todo,Contact,ChatSession};
$summary = [
    "users" => User::count(),
    "vaults" => Vault::count(),
    "projects" => Project::count(),
    "fragments" => Fragment::count(),
    "todos" => Todo::count(),
    "contacts" => Contact::count(),
    "chat_sessions" => ChatSession::count(),
    "chat_message_fragments" => Fragment::where("metadata->demo_category", "chat_message")->count(),
    "demo_fragment_breakdown" => Fragment::where("metadata->demo_seed", true)
        ->get()->groupBy(fn($f) => $f->metadata["demo_category"] ?? "unknown")->map->count(),
];
echo json_encode($summary);
'

# Timeline sanity check for demo fragments
php artisan tinker --execute='use App\Models\Fragment;
echo json_encode([
    "min_created_at" => Fragment::where("metadata->demo_seed", true)->min("created_at"),
    "max_created_at" => Fragment::where("metadata->demo_seed", true)->max("created_at"),
]);
'

# Status + priority distribution for todos
php artisan tinker --execute='use App\Models\Todo;
$baseQuery = Todo::whereHas("fragment", fn($q) => $q->where("metadata->demo_seed", true));
echo json_encode([
    "status" => $baseQuery->get()->groupBy(fn($todo) => $todo->state["status"] ?? "unknown")->map->count(),
    "priority" => $baseQuery->get()->groupBy(fn($todo) => $todo->state["priority"] ?? "unknown")->map->count(),
]);
'

# Spot-check chat sessions and linked message fragments
php artisan tinker --execute='use App\Models\{ChatSession,Fragment};
$session = ChatSession::where("metadata->demo_seed", true)->first();
$fragment = Fragment::where("metadata->demo_category", "chat_message")->first();
echo json_encode([
    "session" => $session?->only(["id","title","message_count","last_activity_at"]),
    "messages_sample" => $session?->messages ? array_slice($session->messages, 0, 2) : null,
    "fragment" => $fragment?->only(["id","title","created_at","metadata"]),
]);
'
```

## Cleanup & Reruns

- Running `php artisan migrate:fresh --seed` resets the database and repopulates demo data.
- Individual sub-seeders implement cleanup logic. For example:
  - `VaultSeeder` removes fragments tagged with `metadata.demo_seed` before reseeding.
  - `TodoSeeder`, `ContactSeeder`, and `ChatSeeder` hard-delete their fragments and related models.
  - `UserSeeder` intentionally keeps the demo user to avoid breaking stored credentials.
- If you need to remove demo data without a full reset, call the cleanup phase manually:

```bash
php artisan db:seed --class=Database\\Seeders\\Demo\\DemoDataSeeder --clean
```

*(Note: `--clean` is not yet implemented; use `migrate:fresh` until a cleanup command wrapper exists.)*

## Known Observations / Follow-ups

- `DemoRoutingDataSeeder` creates `work`, `personal`, and `clients` vaults before the demo vaults run. As a result the final vault list includes both the routing entries and the demo-friendly vault variants. Decide whether to consolidate or keep both sets.
- Chat message fragments currently live outside any explicit relationship table; they are linked via `metadata.chat_session_id`. Future schema work may introduce a dedicated relation.
- Consider producing an export (SQL or JSON) once the dataset stabilizes so we can ship the demo pack without re-running `faker` in downstream environments.

## References

- Seeder source: `database/seeders/Demo/`
- Supporting utilities: `DemoSeedContext`, `TimelineGenerator`
- Timeline generator currently spreads timestamps across the last ~90 days (see `TimelineGenerator::generate`).
