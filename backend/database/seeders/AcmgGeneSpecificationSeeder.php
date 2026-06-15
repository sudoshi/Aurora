<?php

namespace Database\Seeders;

use App\Models\Clinical\AcmgGeneSpecification;
use Illuminate\Database\Seeder;

class AcmgGeneSpecificationSeeder extends Seeder
{
    public function run(): void
    {
        $specs = [
            [
                'gene_symbol' => 'MYH7', 'disease' => 'Cardiomyopathy', 'vcep' => 'Cardiomyopathy VCEP',
                'spec_id' => 'GN001', 'spec_version' => '1.0.0',
                'criteria_overrides' => [
                    'BA1' => ['af_threshold' => 0.001],
                    'BS1' => ['af_threshold' => 0.0002],
                    'PM2' => ['af_threshold' => 0.00004],
                    'PP2' => ['applicable' => false],
                ],
                'source_url' => 'https://cspec.genome.network/cspec/ui/svi/',
            ],
            [
                'gene_symbol' => 'BRCA1', 'disease' => 'Hereditary breast and ovarian cancer', 'vcep' => 'ENIGMA BRCA1/2 VCEP',
                'spec_id' => 'GN002', 'spec_version' => '1.0.0',
                'criteria_overrides' => [
                    'BA1' => ['af_threshold' => 0.001],
                    'BS1' => ['af_threshold' => 0.0001],
                    'PM2' => ['af_threshold' => 0.00002],
                    'PP2' => ['applicable' => false],
                    'BP1' => ['applicable' => false],
                ],
                'source_url' => 'https://cspec.genome.network/cspec/ui/svi/',
            ],
        ];

        foreach ($specs as $spec) {
            AcmgGeneSpecification::updateOrCreate(
                ['gene_symbol' => $spec['gene_symbol'], 'spec_id' => $spec['spec_id'], 'spec_version' => $spec['spec_version']],
                $spec,
            );
        }
    }
}
