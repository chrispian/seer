<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Support\ToolRegistry;
use App\Tools\DbQueryTool;
use App\Tools\MemoryWriteTool;
use App\Tools\MemorySearchTool;
use App\Tools\ExportGenerateTool;

class ToolServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ToolRegistry::class, function () {
            return new ToolRegistry();
        });
    }

    public function boot(): void
    {
        /** @var ToolRegistry $reg */
        $reg = $this->app->make(ToolRegistry::class);

        // Register core tools
        $reg->register($this->app->make(DbQueryTool::class));
        $reg->register($this->app->make(MemoryWriteTool::class));
        $reg->register($this->app->make(MemorySearchTool::class));
        $reg->register($this->app->make(ExportGenerateTool::class));
    }
}
