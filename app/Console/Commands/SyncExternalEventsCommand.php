<?php

namespace App\Console\Commands;

use App\Services\Contracts\EventsExternalSourceInterface;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

#[Signature('events:sync')]
#[Description('Synchronize events from external provider')]
class SyncExternalEventsCommand extends Command
{
    /**
     * @var EventsExternalSourceInterface[]
     */
    private array $externalSources;

    public function __construct(EventsExternalSourceInterface ...$externalSources)
    {
        parent::__construct();
        $this->externalSources = $externalSources;
    }

    public function handle(): int
    {
        $hasFailures = false;

        foreach ($this->externalSources as $externalSource) {
            $sourceName = $externalSource->name();

            try {
                $syncedEvents = $externalSource->sync();

                $this->info("Events synchronized from {$sourceName}: {$syncedEvents}");

                Log::info('External events synchronized.', [
                    'source' => $sourceName,
                    'synced_events' => $syncedEvents,
                ]);
            } catch (Throwable $exception) {
                $hasFailures = true;

                $this->error("Could not synchronize external events from {$sourceName}.");

                Log::error('Could not synchronize external events from provider.', [
                    'source' => $sourceName,
                    'exception' => $exception,
                ]);
            }
        }

        return $hasFailures ? self::FAILURE : self::SUCCESS;
    }
}
