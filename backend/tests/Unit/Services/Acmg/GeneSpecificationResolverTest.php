<?php

use App\Models\Clinical\AcmgGeneSpecification;
use App\Services\Genomics\Acmg\GeneSpecificationResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->resolver = new GeneSpecificationResolver);

it('returns baseline (no overrides) when no gene spec exists', function () {
    $spec = $this->resolver->resolve('UNKNOWNGENE');
    expect($spec['spec_id'])->toBeNull();
    expect($spec['overrides'])->toBe([]);
    expect($spec['af_threshold_ba1'])->toBe(0.05);
});

it('merges a gene-specific override and AF thresholds', function () {
    AcmgGeneSpecification::create([
        'gene_symbol' => 'MYH7',
        'vcep' => 'Cardiomyopathy VCEP',
        'spec_id' => 'GN001',
        'spec_version' => '1.0.0',
        'criteria_overrides' => [
            'BA1' => ['af_threshold' => 0.001],
            'BS1' => ['af_threshold' => 0.0002],
            'PM2' => ['af_threshold' => 0.00004],
            'PP2' => ['applicable' => false],
        ],
    ]);

    $spec = $this->resolver->resolve('MYH7');
    expect($spec['spec_id'])->toBe('GN001');
    expect($spec['af_threshold_ba1'])->toBe(0.001);
    expect($spec['af_threshold_bs1'])->toBe(0.0002);
    expect($spec['af_threshold_pm2'])->toBe(0.00004);
    expect($spec['overrides']['PP2']['applicable'])->toBeFalse();
});
