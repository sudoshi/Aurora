<?php

namespace Database\Factories\Clinical;

use App\Models\Clinical\GenomicVariant;
use App\Models\Clinical\KbChangeAlert;
use Illuminate\Database\Eloquent\Factories\Factory;

class KbChangeAlertFactory extends Factory
{
    protected $model = KbChangeAlert::class;

    public function definition(): array
    {
        $variant = GenomicVariant::factory()->create();

        return [
            'genomic_variant_id' => $variant->id,
            'patient_id' => $variant->patient_id,
            'source' => 'clinvar',
            'from_bucket' => 'vus',
            'to_bucket' => 'pathogenic',
            'from_stars' => 1,
            'to_stars' => 2,
            'severity' => 'high',
            'delta_hash' => $this->faker->unique()->sha256(),
            'status' => 'new',
        ];
    }
}
