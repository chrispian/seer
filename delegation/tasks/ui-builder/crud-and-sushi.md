What’s inside:

app/Http/Controllers/TypesCrudController.php — generic CRUD for any generated type (types:codegen output).

app/Services/Types/GeneratedTypeLocator.php — resolves Model/Resource/Request classes by alias.

routes/types_crud.php — REST endpoints:

GET /api/v2/types/{alias} (index, q/sort/per_page)

POST /api/v2/types/{alias} (create)

GET /api/v2/types/{alias}/{id} (show)

PUT /api/v2/types/{alias}/{id} (update)

DELETE /api/v2/types/{alias}/{id} (destroy)

Adapter system for the resolver

app/Services/Types/Adapters/TypesAdapterInterface.php

app/Services/Types/Adapters/SushiAdapter.php

app/Services/Types/AdapterManager.php

app/Services/Types/TypeResolverEx.php (uses adapters + echoes schema fields)

database/seeders/TypesSushiDemoSeeder.php — registers a Movie type backed by the Sushi Movies model.

docs/CRUD_SETUP.md — quick wiring steps.

Quick wire-up:

// routes/api.php
require base_path('routes/types_crud.php');

// (optional) Replace the basic resolver with the adapter-aware one:
$this->app->bind(
    App\Services\Types\TypeResolver::class,
    App\Services\Types\TypeResolverEx::class
);


Try it:

php artisan db:seed --class=TypesSushiDemoSeeder
# Then:
GET /api/v2/types/Movie        # paginated list (Sushi-backed)
GET /api/v2/types/Movie/1      # detail
