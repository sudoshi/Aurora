<?php

namespace Database\Factories\Clinical;

use App\Models\Clinical\ClinicalPatient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Clinical\ClinicalPatient>
 */
class ClinicalPatientFactory extends Factory
{
    protected $model = ClinicalPatient::class;

    public function definition(): array
    {
        return [
            'mrn' => fake()->unique()->numerify('MRN-######'),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'date_of_birth' => fake()->date('Y-m-d', '-20 years'),
            'sex' => fake()->randomElement(['male', 'female']),
            'race' => fake()->optional()->randomElement(['white', 'black', 'asian', 'other']),
            'ethnicity' => fake()->optional()->randomElement(['hispanic', 'non-hispanic']),
        ];
    }
}
