<?php

namespace Database\Factories\Clinical;

use App\Models\Clinical\GenomicCriteria;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Clinical\GenomicCriteria>
 */
class GenomicCriteriaFactory extends Factory
{
    protected $model = GenomicCriteria::class;

    public function definition(): array
    {
        $types = ['variant', 'gene', 'pathway', 'cohort'];
        $type = fake()->randomElement($types);

        $definitions = [
            'variant' => ['gene' => fake()->randomElement(['BRAF', 'EGFR', 'KRAS']), 'significance' => 'pathogenic'],
            'gene' => ['genes' => [fake()->randomElement(['BRCA1', 'BRCA2', 'TP53'])], 'min_evidence' => '2A'],
            'pathway' => ['pathway_name' => 'MAPK signaling', 'include_subtypes' => true],
            'cohort' => ['min_age' => 18, 'max_age' => 65, 'diagnosis' => fake()->word()],
        ];

        return [
            'name' => fake()->sentence(3),
            'criteria_type' => $type,
            'criteria_definition' => $definitions[$type],
            'description' => fake()->optional()->sentence(),
            'is_shared' => fake()->boolean(30),
            'created_by' => null,
        ];
    }
}
