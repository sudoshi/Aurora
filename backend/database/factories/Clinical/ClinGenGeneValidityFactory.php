<?php

namespace Database\Factories\Clinical;

use App\Models\Clinical\ClinGenGeneValidity;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClinGenGeneValidityFactory extends Factory
{
    protected $model = ClinGenGeneValidity::class;

    public function definition(): array
    {
        $classifications = ['Definitive', 'Strong', 'Moderate', 'Limited', 'Disputed', 'Refuted'];

        return [
            'gene_symbol' => strtoupper($this->faker->unique()->word()),
            'disease_label' => $this->faker->sentence(4),
            'disease_id' => 'MONDO:'.$this->faker->numerify('#######'),
            'moi' => $this->faker->randomElement(['AD', 'AR', 'XL', 'XLR']),
            'classification' => 'Definitive',
            'baseline_classification' => null,
            'classification_date' => $this->faker->dateTimeBetween('-5 years', 'now'),
            'report_url' => $this->faker->url(),
            'last_checked_at' => null,
        ];
    }
}
