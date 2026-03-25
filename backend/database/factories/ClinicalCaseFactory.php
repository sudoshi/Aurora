<?php

namespace Database\Factories;

use App\Models\Clinical\ClinicalPatient;
use App\Models\ClinicalCase;
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
            'specialty' => fake()->randomElement(['oncology', 'surgical', 'rare_disease', 'complex_medical']),
            'case_type' => fake()->randomElement(['tumor_board', 'surgical_review', 'rare_disease', 'medical_complex']),
            'status' => fake()->randomElement(['draft', 'active', 'in_review', 'closed']),
            'patient_id' => ClinicalPatient::factory(),
            'created_by' => User::factory(),
        ];
    }
}
