<?php

namespace HollisLabs\ToolCrate;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use HollisLabs\ToolCrate\Console\DevJqCommand;
use HollisLabs\ToolCrate\Console\DevSearchCommand;

class ToolCrateServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-tool-crate')
            ->hasConfigFile('tool-crate')
            ->hasRoute('ai')
            ->hasCommands([DevJqCommand::class, DevSearchCommand::class]);
    }
}
