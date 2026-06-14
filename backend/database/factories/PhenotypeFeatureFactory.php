<?php

namespace Database\Factories;

use App\Models\DiagnosticOdyssey;
use App\Models\PhenotypeFeature;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PhenotypeFeatureFactory extends Factory
{
    protected $model = PhenotypeFeature::class;

    public function definition(): array
    {
        return [
            'odyssey_id' => DiagnosticOdyssey::factory(),
            'hpo_id' => 'HP:0001250', // Seizure
            'hpo_label' => 'Seizure',
            'excluded' => false,
            'recorded_by' => User::factory(),
        ];
    }
}
