What’s inside:

app/Policies/Concerns/ChecksFeatureFlags.php — policy helper trait to evaluate flags.

app/Policies/FlagAwareTypePolicy.php — gates create/update/delete/restore/forceDelete by feature flags:

Flag naming convention: types.<Alias>.<ability> (e.g., types.Invoice.create).

database/migrations/…create_fe_adr_audits_table.php — ADR audit log store.

app/Models/FeAdrAudit.php — Eloquent model.

app/Services/AdrAuditLogger.php — tiny service to record audits.

config/fe_type_feature_flags.php — documents abilities and convention.

app/Http/Controllers/TypesCrudController.php — patched to:

Authorize via policies.

Emit ADR audit entries on create/update/delete/restore/forceDelete.

Read optional X-ADR-Ref header to attach a decision reference.

docs/README.md — wiring steps and examples.

Quick wire-up:

// app/Providers/AuthServiceProvider.php
use App\Policies\FlagAwareTypePolicy;
use Illuminate\Support\Facades\Gate;

public function boot(): void
{
    $this->registerPolicies();

    // Map generated models to FlagAwareTypePolicy (example)
    Gate::policy(App\Models\Invoice::class, FlagAwareTypePolicy::class);
}

php artisan migrate


Create flags in your DB for each ability you want active, e.g.:

types.Invoice.create, types.Invoice.update, types.Invoice.delete, types.Invoice.restore, types.Invoice.forceDelete

Then, when performing write ops, optionally include:

X-ADR-Ref: ADR-2025-10-15-Types-CRUD