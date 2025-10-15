What’s inside:

app/Http/Controllers/TypesCrudController.php — now authorizes via Gate::authorize() for each action, plus:

trash() → list soft-deleted rows

restore() → restore a soft-deleted row

forceDelete() → permanently delete (policy-gated)

app/Policies/GenericTypePolicy.php — default policy (class-level for viewAny/create, instance-level for others)

app/Providers/AuthServiceProvider.php — example provider showing where to map policies

routes/types_crud_guarded.php — guarded resource routes (auth middleware) with soft-delete endpoints

docs/README.md — quick wiring steps and tips

Wire-up (quick)
// routes/api.php
require base_path('routes/types_crud_guarded.php');

// app/Providers/AuthServiceProvider.php
protected $policies = [
    // Map concrete generated models to their policies, e.g.:
    // App\Models\Invoice::class => App\Policies\InvoicePolicy::class,
];


Soft-deletes work if your generated model uses use Illuminate\Database\Eloquent\SoftDeletes;.

Want me to add a tiny FeatureFlagPolicy hook so you can toggle create/update/delete per type via flags, or wire CRUD mutations to append an ADR v2 audit entry automatically?