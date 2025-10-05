<?php

namespace App\Providers;

use App\Events\FragmentAccepted;
use App\Events\FragmentArchived;
use App\Listeners\Metrics\FragmentMetricsListener;
use App\Listeners\Projectors\FragmentProjector;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class FragmentsEventServiceProvider extends ServiceProvider
{
    protected $listen = [
        FragmentAccepted::class => [
            FragmentProjector::class.'@onAccepted',
            FragmentMetricsListener::class.'@onAccepted',
        ],
        FragmentArchived::class => [
            FragmentProjector::class.'@onArchived',
            FragmentMetricsListener::class.'@onArchived',
        ],
    ];

    public function boot(): void
    {
        parent::boot();
    }
}
