<?php

namespace Database\Factories\Clinical;

use App\Models\Clinical\GenomicVariant;
use App\Models\Clinical\VariantClassification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class VariantClassificationFactory extends Factory
{
    protected $model = VariantClassification::class;

    public function definition(): array
    {
        return [
            'genomic_variant_id' => GenomicVariant::factory(),
            'gene_symbol' => 'BRCA1',
            'computed_classification' => 'vus',
            'computed_points' => 0,
            'status' => 'computed',
            'ruleset_version' => 'acmg-2015-svi-2020',
            'created_by' => User::factory(),
        ];
    }
}
