<?php

namespace Database\Factories\Clinical;

use App\Models\Clinical\ImagingCriteria;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Clinical\ImagingCriteria>
 */
class ImagingCriteriaFactory extends Factory
{
    protected $model = ImagingCriteria::class;

    public function definition(): array
    {
        return [
            'name' => 'Imaging Criterion '.$this->faker->unique()->numberBetween(1, 999999),
            'criteria_type' => $this->faker->randomElement(['modality', 'body_part', 'measurement', 'response']),
            'criteria_definition' => [
                'modality' => $this->faker->randomElement(['CT', 'MR', 'PT']),
            ],
            'description' => $this->faker->sentence(),
            'is_shared' => false,
            'created_by' => null,
        ];
    }
}
