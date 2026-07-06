<?php

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\SyncExternalEventsCommand;
use App\Services\Contracts\EventsExternalSourceInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Mockery;
use RuntimeException;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\TestCase;

class SyncExternalEventsCommandTest extends TestCase
{
    public function test_it_synchronizes_events_from_external_source_successfully(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->with('External events synchronized.', [
                'source' => 'fake-source',
                'synced_events' => 3,
            ]);

        Log::shouldReceive('error')->never();

        $externalSource = Mockery::mock(EventsExternalSourceInterface::class);

        $externalSource
            ->shouldReceive('name')
            ->once()
            ->andReturn('fake-source');

        $externalSource
            ->shouldReceive('sync')
            ->once()
            ->andReturn(3);

        $tester = $this->commandTester($externalSource);

        $exitCode = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);

        $this->assertStringContainsString(
            'Events synchronized from fake-source: 3',
            $tester->getDisplay(),
        );
    }

    public function test_it_returns_failure_when_external_source_sync_fails(): void
    {
        $exception = new RuntimeException('Provider unavailable.');

        Log::shouldReceive('info')->never();

        Log::shouldReceive('error')
            ->once()
            ->with(
                'Could not synchronize external events from provider.',
                Mockery::on(
                    fn (array $context): bool =>
                        $context['source'] === 'fake-source'
                        && $context['exception'] === $exception
                ),
            );

        $externalSource = Mockery::mock(EventsExternalSourceInterface::class);

        $externalSource
            ->shouldReceive('name')
            ->once()
            ->andReturn('fake-source');

        $externalSource
            ->shouldReceive('sync')
            ->once()
            ->andThrow($exception);

        $tester = $this->commandTester($externalSource);

        $exitCode = $tester->execute([]);

        $this->assertSame(Command::FAILURE, $exitCode);

        $this->assertStringContainsString(
            'Could not synchronize external events from fake-source.',
            $tester->getDisplay(),
        );
    }

    public function test_it_continues_synchronizing_other_sources_when_one_fails(): void
    {
        $exception = new RuntimeException('Provider unavailable.');

        Log::shouldReceive('info')
            ->once()
            ->with('External events synchronized.', [
                'source' => 'working-source',
                'synced_events' => 5,
            ]);

        Log::shouldReceive('error')
            ->once()
            ->with(
                'Could not synchronize external events from provider.',
                Mockery::on(
                    fn (array $context): bool =>
                        $context['source'] === 'failing-source'
                        && $context['exception'] === $exception
                ),
            );

        $failingSource = Mockery::mock(EventsExternalSourceInterface::class);

        $failingSource
            ->shouldReceive('name')
            ->once()
            ->andReturn('failing-source');

        $failingSource
            ->shouldReceive('sync')
            ->once()
            ->andThrow($exception);

        $workingSource = Mockery::mock(EventsExternalSourceInterface::class);

        $workingSource
            ->shouldReceive('name')
            ->once()
            ->andReturn('working-source');

        $workingSource
            ->shouldReceive('sync')
            ->once()
            ->andReturn(5);

        $tester = $this->commandTester($failingSource, $workingSource);

        $exitCode = $tester->execute([]);

        $this->assertSame(Command::FAILURE, $exitCode);

        $output = $tester->getDisplay();

        $this->assertStringContainsString(
            'Could not synchronize external events from failing-source.',
            $output,
        );

        $this->assertStringContainsString(
            'Events synchronized from working-source: 5',
            $output,
        );
    }

    public function test_it_returns_success_when_there_are_no_external_sources(): void
    {
        Log::shouldReceive('info')->never();
        Log::shouldReceive('error')->never();

        $tester = $this->commandTester();

        $exitCode = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertSame('', trim($tester->getDisplay()));
    }

    private function commandTester(
        EventsExternalSourceInterface ...$externalSources,
    ): CommandTester {
        $command = new SyncExternalEventsCommand(...$externalSources);

        $command->setLaravel($this->app);

        return new CommandTester($command);
    }
}
