<?php

namespace Database\Factories;

use App\Models\Session;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Session>
 */
class SessionFactory extends Factory
{
    protected $model = Session::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'scheduled_at' => now()->addDays(rand(1, 30)),
            'duration_minutes' => fake()->randomElement([30, 60, 90, 120]),
            'status' => 'scheduled',
            'session_type' => fake()->randomElement(['tumor_board', 'mdc', 'surgical_planning', 'grand_rounds', 'ad_hoc']),
            'created_by' => User::factory(),
        ];
    }
}
