<?php

namespace App\Providers;

use App\Console\Commands\SyncExternalEventsCommand;
use App\Services\Contracts\EventsExternalSourceInterface;
use App\Services\FeverUpEventsExternalSourceStrategy;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->tag([
            FeverUpEventsExternalSourceStrategy::class,
        ], 'events.external-sources');

        $this->app
            ->when(SyncExternalEventsCommand::class)
            ->needs(EventsExternalSourceInterface::class)
            ->giveTagged('events.external-sources');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
