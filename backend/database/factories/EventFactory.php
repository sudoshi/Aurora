<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Event>
 */
class EventFactory extends Factory
{
    protected $model = Event::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'time' => fake()->dateTimeBetween('now', '+3 months'),
            'duration' => fake()->randomElement([30, 45, 60, 90, 120]),
            'location' => fake()->randomElement([
                'Conference Room A',
                'Conference Room B',
                'Virtual Meeting Room',
                'Boardroom',
            ]),
            'category' => fake()->randomElement([
                'clinical',
                'administrative',
                'educational',
                'research',
            ]),
            'description' => fake()->optional()->paragraph(),
            'team' => [],
            'related_items' => [],
        ];
    }
}
