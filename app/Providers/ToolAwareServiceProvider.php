<?php

namespace App\Providers;

use App\Services\Orchestration\ToolAware\ContextBroker;
use App\Services\Orchestration\ToolAware\Contracts\ComposerInterface;
use App\Services\Orchestration\ToolAware\Contracts\ContextBrokerInterface;
use App\Services\Orchestration\ToolAware\Contracts\RouterInterface;
use App\Services\Orchestration\ToolAware\Contracts\ToolRunnerInterface;
use App\Services\Orchestration\ToolAware\Contracts\ToolSelectorInterface;
use App\Services\Orchestration\ToolAware\FinalComposer;
use App\Services\Orchestration\ToolAware\OutcomeSummarizer;
use App\Services\Orchestration\ToolAware\Router;
use App\Services\Orchestration\ToolAware\ToolAwarePipeline;
use App\Services\Orchestration\ToolAware\ToolRunner;
use App\Services\Orchestration\ToolAware\ToolSelector;
use Illuminate\Support\ServiceProvider;

class ToolAwareServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind interfaces to implementations
        $this->app->bind(ContextBrokerInterface::class, ContextBroker::class);
        $this->app->bind(RouterInterface::class, Router::class);
        $this->app->bind(ToolSelectorInterface::class, ToolSelector::class);
        $this->app->bind(ToolRunnerInterface::class, ToolRunner::class);
        $this->app->bind(ComposerInterface::class, FinalComposer::class);

        // Register the summarizer (no interface)
        $this->app->singleton(OutcomeSummarizer::class);

        // Register the main pipeline as singleton
        $this->app->singleton(ToolAwarePipeline::class);
    }

    public function boot(): void
    {
        //
    }
}
