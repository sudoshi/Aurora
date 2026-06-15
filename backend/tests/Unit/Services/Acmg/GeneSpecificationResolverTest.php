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
    // Test-only gene + spec id so this never collides with the AcmgGeneSpecificationSeeder
    // rows (MYH7/GN001, BRCA1/GN002), which can leak between tests because
    // DatabaseTruncation does not truncate the `clinical` schema.
    AcmgGeneSpecification::create([
        'gene_symbol' => 'ACMGTESTGENE',
        'vcep' => 'Test VCEP',
        'spec_id' => 'TST001',
        'spec_version' => '1.0.0',
        'criteria_overrides' => [
            'BA1' => ['af_threshold' => 0.001],
            'BS1' => ['af_threshold' => 0.0002],
            'PM2' => ['af_threshold' => 0.00004],
            'PP2' => ['applicable' => false],
        ],
    ]);

    $spec = $this->resolver->resolve('ACMGTESTGENE');
    expect($spec['spec_id'])->toBe('TST001');
    expect($spec['af_threshold_ba1'])->toBe(0.001);
    expect($spec['af_threshold_bs1'])->toBe(0.0002);
    expect($spec['af_threshold_pm2'])->toBe(0.00004);
    expect($spec['overrides']['PP2']['applicable'])->toBeFalse();
});
