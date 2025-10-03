<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use App\Events\FragmentAccepted;
use App\Events\FragmentArchived;
use App\Listeners\Projectors\FragmentProjector;
use App\Listeners\Metrics\FragmentMetricsListener;

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
