<?php

namespace Modules\WsopFantasy\Providers;

use Modules\WsopFantasy\Console\Commands\ImportPoyCommand;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

class WsopFantasyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/config.php', 'wsop-fantasy');
    }

    public function boot(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);

            $schedule->command(ImportPoyCommand::class)
                ->hourly()
                ->runInBackground();
        });
    }
}
