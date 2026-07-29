<?php

namespace Services;

use App\Models\Event;
use App\Services\ExternalEventsService;
use App\Services\FeverUpEventsExternalSourceStrategy;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Carbon;
use RuntimeException;
use Tests\TestCase;

class FeverUpEventsExternalSourceStrategyTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('services.fever_provider.url', 'https://provider.example.test/events');
    }

    public function test_it_returns_provider_name(): void
    {
        // Arrange
        $strategy = $this->app->make(FeverUpEventsExternalSourceStrategy::class);

        // Act
        $name = $strategy->name();

        // Assert
        $this->assertSame('fever_provider', $name);
    }

    public function test_it_synchronizes_online_events_from_provider(): void
    {
        // Arrange
        Carbon::setTestNow(Carbon::parse('2025-11-16 00:00:00'));

        $responseXml = <<<XML
            <?xml version="1.0" encoding="UTF-8"?>
            <eventList>
                <output>
                    <base_plan base_plan_id="base-plan-1" sell_mode="online" title="Online Event">
                        <plan plan_id="plan-1" plan_start_date="2026-07-10T10:00:00" plan_end_date="2026-07-10T12:00:00">
                            <zone zone_id="zone-1" price="10.50" />
                            <zone zone_id="zone-2" price="25.00" />
                        </plan>

                        <plan plan_id="plan-2" plan_start_date="2026-07-11T10:00:00" plan_end_date="2026-07-11T12:00:00">
                            <zone zone_id="zone-3" price="15.00" />
                        </plan>
                    </base_plan>
                </output>
            </eventList>
            XML;

        $externalEventsService = $this->mock(ExternalEventsService::class);
        $externalEventsService
            ->shouldReceive('fetchExternalEvents')
            ->with('https://provider.example.test/events')
            ->once()
            ->andReturn($responseXml);

        $strategy = $this->app->make(FeverUpEventsExternalSourceStrategy::class);

        // Act
        $syncedEvents = $strategy->sync();

        // Arrange
        $this->assertSame(2, $syncedEvents);
        $this->assertDatabaseCount('events', 2);

        $this->assertDatabaseHas('events', [
            [
                'provider' => 'fever_provider',
                'external_base_plan_id' => 'base-plan-1',
                'external_plan_id' => 'plan-1',
                'title' => 'Online Event',
                'starts_at' => '2026-07-10 10:00:00',
                'ends_at' => '2026-07-10 12:00:00',
                'min_price' => 10.50,
                'max_price' => 25.00,
                'last_synced_at' => '2025-11-16 00:00:00',
            ],
            [
                'provider' => 'fever_provider',
                'external_base_plan_id' => 'base-plan-1',
                'external_plan_id' => 'plan-2',
                'title' => 'Online Event',
                'starts_at' => '2026-07-11 10:00:00',
                'ends_at' => '2026-07-11 12:00:00',
                'min_price' => 15.00,
                'max_price' => 15.00,
                'last_synced_at' => '2025-11-16 00:00:00',
            ]
        ]);
    }

    public function test_it_ignores_offline_base_plans(): void
    {
        // Arrange
        $responseXml = <<<XML
                <?xml version="1.0" encoding="UTF-8"?>
                <eventList>
                    <output>
                        <base_plan base_plan_id="base-plan-1" sell_mode="offline" title="Offline Event">
                            <plan plan_id="plan-1" plan_start_date="2026-07-10T10:00:00" plan_end_date="2026-07-10T12:00:00">
                                <zone zone_id="zone-1" price="10.00" />
                            </plan>
                        </base_plan>
                    </output>
                </eventList>
                XML;

        $externalEventsService = $this->mock(ExternalEventsService::class);
        $externalEventsService
            ->shouldReceive('fetchExternalEvents')
            ->with('https://provider.example.test/events')
            ->once()
            ->andReturn($responseXml);

        $strategy = $this->app->make(FeverUpEventsExternalSourceStrategy::class);

        // Act
        $syncedEvents = $strategy->sync();

        // Assert
        $this->assertSame(0, $syncedEvents);
        $this->assertDatabaseCount('events', 0);
    }

    public function test_it_ignores_base_plans_without_required_attributes(): void
    {
        // Arrange
        $responseXml = <<<XML
                <?xml version="1.0" encoding="UTF-8"?>
                <eventList>
                    <output>
                        <base_plan sell_mode="online" title="Missing Base Plan ID">
                            <plan plan_id="plan-1" plan_start_date="2026-07-10T10:00:00" plan_end_date="2026-07-10T12:00:00">
                                <zone zone_id="zone-1" price="10.00" />
                            </plan>
                        </base_plan>

                        <base_plan base_plan_id="base-plan-2" sell_mode="online">
                            <plan plan_id="plan-2" plan_start_date="2026-07-10T10:00:00" plan_end_date="2026-07-10T12:00:00">
                                <zone zone_id="zone-1" price="20.00" />
                            </plan>
                        </base_plan>
                    </output>
                </eventList>
                XML;

        $externalEventsService = $this->mock(ExternalEventsService::class);
        $externalEventsService
            ->shouldReceive('fetchExternalEvents')
            ->with('https://provider.example.test/events')
            ->once()
            ->andReturn($responseXml);

        $strategy = $this->app->make(FeverUpEventsExternalSourceStrategy::class);

        // Act
        $syncedEvents = $strategy->sync();

        // Assert
        $this->assertSame(0, $syncedEvents);
        $this->assertDatabaseCount('events', 0);
    }

    public function test_it_ignores_plans_without_required_attributes(): void
    {
        // Arrange
        $responseXml = <<<XML
                <?xml version="1.0" encoding="UTF-8"?>
                <eventList>
                    <output>
                        <base_plan sell_mode="online" title="Missing Base Plan ID">
                            <plan plan_id="plan-1" plan_start_date="2026-07-10T10:00:00" plan_end_date="2026-07-10T12:00:00">
                                <zone zone_id="zone-1" price="10.00" />
                            </plan>
                        </base_plan>

                        <base_plan base_plan_id="base-plan-2" sell_mode="online">
                            <plan plan_id="plan-2" plan_start_date="2026-07-10T10:00:00" plan_end_date="2026-07-10T12:00:00">
                                <zone zone_id="zone-1" price="20.00" />
                            </plan>
                        </base_plan>
                    </output>
                </eventList>
                XML;

        $externalEventsService = $this->mock(ExternalEventsService::class);
        $externalEventsService
            ->shouldReceive('fetchExternalEvents')
            ->with('https://provider.example.test/events')
            ->once()
            ->andReturn($responseXml);

        $strategy = $this->app->make(FeverUpEventsExternalSourceStrategy::class);

        // Act
        $syncedEvents = $strategy->sync();

        // Assert
        $this->assertSame(0, $syncedEvents);
        $this->assertDatabaseCount('events', 0);
    }

    public function test_it_stores_null_prices_when_plan_has_no_valid_zone_prices(): void
    {
        // Arrange
        $responseXml = <<<XML
                <?xml version="1.0" encoding="UTF-8"?>
                <eventList>
                    <output>
                        <base_plan base_plan_id="base-plan-1" sell_mode="online" title="Online Event">
                            <plan plan_id="plan-1" plan_start_date="2026-07-10T10:00:00" plan_end_date="2026-07-10T12:00:00">
                                <zone zone_id="zone-1" price="" />
                                <zone zone_id="zone-2" price="invalid" />
                                <zone zone_id="zone-3" />
                            </plan>
                        </base_plan>
                    </output>
                </eventList>
                XML;

        $externalEventsService = $this->mock(ExternalEventsService::class);
        $externalEventsService
            ->shouldReceive('fetchExternalEvents')
            ->with('https://provider.example.test/events')
            ->once()
            ->andReturn($responseXml);

        $strategy = $this->app->make(FeverUpEventsExternalSourceStrategy::class);

        // Act
        $syncedEvents = $strategy->sync();

        // Assert
        $this->assertSame(1, $syncedEvents);

        $this->assertDatabaseHas('events', [
            'provider' => 'fever_provider',
            'external_base_plan_id' => 'base-plan-1',
            'external_plan_id' => 'plan-1',
            'title' => 'Online Event',
            'starts_at' => '2026-07-10 10:00:00',
            'ends_at' => '2026-07-10 12:00:00',
            'min_price' => null,
            'max_price' => null,
        ]);
    }

    public function test_it_updates_existing_events_without_creating_duplicates(): void
    {
        // Arrange
        Carbon::setTestNow();

        Event::factory()->create([
            'provider' => 'fever_provider',
            'external_base_plan_id' => 'base-plan-1',
            'external_plan_id' => 'plan-1',
            'title' => 'Old Title',
            'starts_at' => '2026-07-01 10:00:00',
            'ends_at' => '2026-07-01 12:00:00',
            'min_price' => 99.99,
            'max_price' => 199.99,
        ]);

        $responseXml = <<<XML
                <?xml version="1.0" encoding="UTF-8"?>
                <eventList>
                    <output>
                        <base_plan base_plan_id="base-plan-1" sell_mode="online" title="Updated Title">
                            <plan plan_id="plan-1" plan_start_date="2026-07-10T10:00:00" plan_end_date="2026-07-10T12:00:00">
                                <zone zone_id="zone-1" price="10.00" />
                                <zone zone_id="zone-2" price="20.00" />
                            </plan>
                        </base_plan>
                    </output>
                </eventList>
                XML;

        $externalEventsService = $this->mock(ExternalEventsService::class);
        $externalEventsService
            ->shouldReceive('fetchExternalEvents')
            ->with('https://provider.example.test/events')
            ->once()
            ->andReturn($responseXml);

        $strategy = $this->app->make(FeverUpEventsExternalSourceStrategy::class);

        // Act
        $syncedEvents = $strategy->sync();

        // Assert
        $this->assertSame(1, $syncedEvents);
        $this->assertDatabaseCount('events', 1);

        $this->assertDatabaseHas('events', [
            'provider' => 'fever_provider',
            'external_base_plan_id' => 'base-plan-1',
            'external_plan_id' => 'plan-1',
            'title' => 'Updated Title',
            'starts_at' => '2026-07-10 10:00:00',
            'ends_at' => '2026-07-10 12:00:00',
            'min_price' => 10.00,
            'max_price' => 20.00,
            'last_synced_at' => now(),
        ]);
    }

    public function test_it_throws_exception_when_provider_returns_invalid_xml(): void
    {
        // Arrange
        $responseXml = <<<XML
                <invalid-xml>
                XML;

        $externalEventsService = $this->mock(ExternalEventsService::class);
        $externalEventsService
            ->shouldReceive('fetchExternalEvents')
            ->with('https://provider.example.test/events')
            ->once()
            ->andReturn($responseXml);

        $strategy = $this->app->make(FeverUpEventsExternalSourceStrategy::class);

        // Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid XML received from external events provider.');

        // Act
        $strategy->sync();
    }
}
