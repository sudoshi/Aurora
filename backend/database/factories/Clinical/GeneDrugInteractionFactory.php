<?php

namespace Database\Factories\Clinical;

use App\Models\Clinical\GeneDrugInteraction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Clinical\GeneDrugInteraction>
 */
class GeneDrugInteractionFactory extends Factory
{
    protected $model = GeneDrugInteraction::class;

    private static int $sequence = 0;

    public function definition(): array
    {
        $genes = ['BRAF', 'EGFR', 'KRAS', 'TP53', 'ALK', 'ROS1', 'BRCA1', 'BRCA2', 'PIK3CA', 'HER2'];
        $evidenceLevels = ['1', '2A', '2B', '3A', '3B', '4', 'R1', 'R2'];
        $relationships = ['sensitive', 'resistant', 'diagnostic', 'prognostic'];
        $sequence = ++self::$sequence;

        return [
            'gene' => fake()->randomElement($genes),
            'variant_pattern' => '*',
            'drug' => sprintf('FactoryDrug-%06d', $sequence),
            'drug_class' => fake()->optional()->randomElement(['kinase_inhibitor', 'PARP_inhibitor', 'checkpoint_inhibitor']),
            'relationship' => fake()->randomElement($relationships),
            'evidence_level' => fake()->randomElement($evidenceLevels),
            'indication' => fake()->optional()->sentence(),
            'mechanism' => fake()->optional()->sentence(),
            'source' => fake()->randomElement(['oncokb', 'manual', 'clinvar']),
            'source_url' => fake()->optional()->url(),
            'oncokb_last_synced_at' => fake()->optional()->dateTimeBetween('-30 days'),
            'last_verified_at' => fake()->optional()->dateTimeBetween('-90 days'),
        ];
    }
}
