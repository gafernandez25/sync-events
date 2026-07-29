<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition(): array
    {
        $startsAt = fake()->dateTimeBetween('now', '+1 month');
        $endsAt = fake()->dateTimeBetween($startsAt, '+2 months');

        $minPrice = fake()->randomFloat(2, 0, 50);
        $maxPrice = fake()->randomFloat(2, $minPrice, 150);

        return [
            'provider' => 'test_provider',
            'external_base_plan_id' => fake()->uuid(),
            'external_plan_id' => fake()->uuid(),
            'title' => fake()->sentence(4),
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'min_price' => $minPrice,
            'max_price' => $maxPrice,
            'last_synced_at' => now(),
        ];
    }
}
