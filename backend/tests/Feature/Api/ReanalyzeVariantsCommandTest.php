<?php

use App\Models\Clinical\ClinVarVariant;
use App\Models\Clinical\GenomicVariant;
use App\Models\Clinical\KbChangeAlert;
use App\Models\Clinical\VariantCanonicalId;

beforeEach(function () {
    KbChangeAlert::where('clinvar_variation_id', '55555')->delete();
    ClinVarVariant::where('variation_id', '55555')->delete();
    VariantCanonicalId::where('clinvar_variation_id', '55555')->delete();
    GenomicVariant::where('gene', 'TESTGENEX')->delete();

    app(\Database\Seeders\SuperuserSeeder::class)->run();
});

it('runs the reanalysis loop and reports the alert count', function () {
    $variant = GenomicVariant::factory()->create([
        'gene' => 'TESTGENEX', 'chromosome' => '17', 'position' => 43045712, 'ref_allele' => 'G', 'alt_allele' => 'A',
    ]);
    VariantCanonicalId::factory()->create([
        'genomic_variant_id' => $variant->id, 'clinvar_variation_id' => '55555',
        'baseline_significance' => 'Uncertain significance', 'baseline_review_status' => 'criteria provided, single submitter',
    ]);
    ClinVarVariant::create([
        'variation_id' => '55555', 'chromosome' => '17', 'position' => 43045712,
        'reference_allele' => 'G', 'alternate_allele' => 'A', 'genome_build' => 'GRCh38',
        'gene_symbol' => 'TESTGENEX', 'clinical_significance' => 'Pathogenic',
        'review_status' => 'reviewed by expert panel', 'is_pathogenic' => true,
    ]);

    $this->artisan('genomics:reanalyze-variants')
        ->expectsOutputToContain('1')
        ->assertSuccessful();

    expect(KbChangeAlert::where('clinvar_variation_id', '55555')->count())->toBe(1);
});
