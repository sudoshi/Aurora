<?php

namespace Database\Factories\Clinical;

use App\Models\Clinical\GenomicVariant;
use App\Models\Clinical\VariantCanonicalId;
use Illuminate\Database\Eloquent\Factories\Factory;

class VariantCanonicalIdFactory extends Factory
{
    protected $model = VariantCanonicalId::class;

    public function definition(): array
    {
        return [
            'genomic_variant_id' => GenomicVariant::factory(),
            'caid' => 'CA'.$this->faker->numberBetween(100000, 999999),
            'clinvar_variation_id' => (string) $this->faker->numberBetween(1000, 99999),
            'assembly' => 'GRCh38',
        ];
    }
}
