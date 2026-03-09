<?php

namespace Database\Factories;

use App\Models\ClinicalCase;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ClinicalCase>
 */
class ClinicalCaseFactory extends Factory
{
    protected $model = ClinicalCase::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'status' => fake()->randomElement(['open', 'in_review', 'closed']),
            'patient_id' => Patient::factory(),
            'created_by' => User::factory(),
        ];
    }
}
