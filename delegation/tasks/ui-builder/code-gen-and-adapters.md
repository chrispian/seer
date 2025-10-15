What you get:

types:codegen — generates Model, Migration, FormRequests (create/update), Policy, Resource from a Type schema.

types:migrate — naive schema diff with --dry-run support, then applies safe adds (no destructive ops in MVP).

Sushi adapters: App\Models\Sushi\Movies, App\Models\Sushi\Countries (static/API-like datasets without tables).

Pluggable stubs in resources/stubs/types/*.stub so you can tune the generated code style.

Docs with usage examples.

Quick wire-up:

// app/Console/Kernel.php
protected $commands = [
    \App\Console\Commands\TypesCodegen::class,
    \App\Console\Commands\TypesMigrate::class,
];


Try it (with your earlier seeded Invoice type):

php artisan types:codegen Invoice --force
php artisan types:migrate Invoice --dry-run
php artisan types:migrate Invoice