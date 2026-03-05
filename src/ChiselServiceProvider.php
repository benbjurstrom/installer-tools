<?php

namespace Laravel\Chisel;

use Illuminate\Support\ServiceProvider;
use Laravel\Chisel\Console\ChiselCommand;

class ChiselServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ChiselCommand::class,
            ]);
        }
    }
}
