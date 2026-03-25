<?php

namespace Database\Factories\Clinical;

use App\Models\Clinical\ClinicalPatient;
use App\Models\Clinical\GenomicVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Clinical\GenomicVariant>
 */
class GenomicVariantFactory extends Factory
{
    protected $model = GenomicVariant::class;

    public function definition(): array
    {
        $genes = ['BRAF', 'EGFR', 'KRAS', 'TP53', 'ALK', 'BRCA1', 'BRCA2', 'PIK3CA'];
        $variantTypes = ['SNV', 'indel', 'fusion', 'CNV', 'rearrangement'];
        $significance = ['pathogenic', 'likely_pathogenic', 'VUS', 'likely_benign', 'benign'];
        $chromosomes = array_map(fn ($i) => (string) $i, range(1, 22));
        $chromosomes[] = 'X';
        $chromosomes[] = 'Y';

        return [
            'patient_id' => ClinicalPatient::factory(),
            'gene' => fake()->randomElement($genes),
            'variant' => fake()->optional()->lexify('????'),
            'variant_type' => fake()->randomElement($variantTypes),
            'chromosome' => fake()->randomElement($chromosomes),
            'position' => fake()->numberBetween(1000000, 250000000),
            'ref_allele' => fake()->randomElement(['A', 'T', 'G', 'C']),
            'alt_allele' => fake()->randomElement(['A', 'T', 'G', 'C']),
            'zygosity' => fake()->randomElement(['heterozygous', 'homozygous']),
            'allele_frequency' => fake()->randomFloat(6, 0.001, 0.999),
            'clinical_significance' => fake()->randomElement($significance),
            'actionability' => fake()->optional()->randomElement(['actionable', 'potentially_actionable', 'unknown']),
        ];
    }
}
