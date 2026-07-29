<?php

namespace App\Providers;

use App\Console\Commands\SyncExternalEventsCommand;
use App\Services\Contracts\EventsExternalSourceInterface;
use App\Services\TestEventsExternalSourceStrategy;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->tag([
            TestEventsExternalSourceStrategy::class,
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
