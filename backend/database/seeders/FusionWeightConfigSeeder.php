<?php

namespace Database\Seeders;

use App\Models\Clinical\FusionWeightConfig;
use Illuminate\Database\Seeder;

class FusionWeightConfigSeeder extends Seeder
{
    public function run(): void
    {
        $presets = [
            [
                'name' => 'Balanced',
                'config_type' => 'preset',
                'genomic_weight' => 0.3400,
                'volumetric_weight' => 0.3300,
                'clinical_weight' => 0.3300,
                'outcome_weights' => [
                    'tumor_response' => 0.30,
                    'treatment_tolerance' => 0.20,
                    'lab_trajectory' => 0.20,
                    'disease_stability' => 0.15,
                    'care_intensity' => 0.15,
                ],
                'is_active' => true,
            ],
            [
                'name' => 'Genomics-First',
                'config_type' => 'preset',
                'genomic_weight' => 0.5000,
                'volumetric_weight' => 0.2500,
                'clinical_weight' => 0.2500,
                'outcome_weights' => [
                    'tumor_response' => 0.30,
                    'treatment_tolerance' => 0.20,
                    'lab_trajectory' => 0.20,
                    'disease_stability' => 0.15,
                    'care_intensity' => 0.15,
                ],
                'is_active' => false,
            ],
            [
                'name' => 'Volumetric',
                'config_type' => 'preset',
                'genomic_weight' => 0.2500,
                'volumetric_weight' => 0.5000,
                'clinical_weight' => 0.2500,
                'outcome_weights' => [
                    'tumor_response' => 0.40,
                    'treatment_tolerance' => 0.15,
                    'lab_trajectory' => 0.15,
                    'disease_stability' => 0.15,
                    'care_intensity' => 0.15,
                ],
                'is_active' => false,
            ],
        ];

        foreach ($presets as $preset) {
            FusionWeightConfig::updateOrCreate(
                ['name' => $preset['name'], 'config_type' => 'preset'],
                $preset
            );
        }

        $this->command->info('Seeded '.count($presets).' fusion weight presets.');
    }
}
