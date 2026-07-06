<?php

namespace App\Console\Commands;

use App\Services\ExternalEventsService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

#[Signature('events:sync')]
#[Description('Synchronize events from external provider')]
class SyncExternalEventsCommand extends Command
{
    public function handle(ExternalEventsService $externalEventsService): int
    {
        try {
            $syncedEvents = $externalEventsService->sync();

            $this->info("Events synchronized: {$syncedEvents}");

            Log::info("Events synchronized: {$syncedEvents}");
            return self::SUCCESS;
        } catch (Throwable $exception) {
            report($exception);

            $this->error('Could not synchronize external events.');

            Log::error('Could not synchronize external events.', ['exception' => $exception]);
            return self::FAILURE;
        }
    }

}
