<?php

namespace App\Providers;

use App\Services\Security\ApprovalManager;
use App\Services\Security\DryRunSimulator;
use App\Services\Security\PolicyRegistry;
use App\Services\Security\RiskScorer;
use Illuminate\Support\ServiceProvider;

class SecurityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PolicyRegistry::class);
        $this->app->singleton(RiskScorer::class);
        $this->app->singleton(DryRunSimulator::class);
        $this->app->singleton(ApprovalManager::class);
    }

    public function boot(): void
    {
        // Future: Add policy validation on boot in non-production
    }
}
