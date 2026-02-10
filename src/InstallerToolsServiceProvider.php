<?php

namespace Laravel\InstallerTools;

use Illuminate\Support\ServiceProvider;
use Laravel\InstallerTools\Console\InstallFeaturesCommand;

class InstallerToolsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallFeaturesCommand::class,
            ]);
        }
    }
}
