<?php

namespace Tests\Feature\Http\Controllers\Api\V1;

use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SearchEventsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_retrieves_events(): void
    {
        // Arrange
        $eventBeforeRange = Event::factory()->create([
            'title' => 'Event before range',
            'starts_at' => '2026-01-05 10:00:00',
            'ends_at' => '2026-01-05 12:00:00',
        ]);

        $eventInsideRange = Event::factory()->create([
            'title' => 'Event inside range',
            'starts_at' => '2026-01-10 10:00:00',
            'ends_at' => '2026-01-10 12:00:00',
        ]);

        $eventAfterRange = Event::factory()->create([
            'title' => 'Event after range',
            'starts_at' => '2026-01-20 10:00:00',
            'ends_at' => '2026-01-20 12:00:00',
        ]);

        // Act
        $response = $this->getJson(
            '/api/v1/events/search?' . http_build_query([
                'starts_at' => '2026-01-10T00:00:00Z',
                'ends_at' => '2026-01-10T23:59:59Z',
            ])
        );

        // Assert
        $response
            ->assertOk()
            ->assertJsonPath('error', null)
            ->assertJsonCount(1, 'data.events')
            ->assertJsonPath('data.events.0.id', $eventInsideRange->id)
            ->assertJsonPath('data.events.0.title', $eventInsideRange->title)
            ->assertJsonPath('data.events.0.start_date', '2026-01-10')
            ->assertJsonPath('data.events.0.start_time', '10:00:00')
            ->assertJsonPath('data.events.0.end_date', '2026-01-10')
            ->assertJsonPath('data.events.0.end_time', '12:00:00')
            ->assertJsonPath('data.events.0.min_price', $eventInsideRange->min_price)
            ->assertJsonPath('data.events.0.max_price', $eventInsideRange->max_price);
    }

    #[DataProvider('invalidInputDataProvider')]
    public function test_it_fails_with_invalid_input(array $params): void
    {
        $response = $this->getJson(
            '/api/v1/events/search?' . http_build_query($params)
        );

        $response->assertBadRequest();
    }

    public static function invalidInputDataProvider(): array
    {
        return [
            'starts_at is missing' => [
                'params' => [
                    'ends_at' => '2026-01-10T23:59:59Z',
                ],
            ],
            'ends_at is missing' => [
                'params' => [
                    'starts_at' => '2026-01-10T00:00:00Z',
                ],
            ],
            'starts_at is invalid' => [
                'params' => [
                    'starts_at' => 'invalid-date',
                ],
            ],
            'ends_at is invalid' => [
                'params' => [
                    'ends_at' => 'invalid-date',
                ],
            ],
            'ends_at is before starts_at' => [
                'params' => [
                    'starts_at' => '2026-01-10T23:59:59Z',
                    'ends_at' => '2026-01-10T00:00:00Z',
                ],
            ],
        ];
    }
}
