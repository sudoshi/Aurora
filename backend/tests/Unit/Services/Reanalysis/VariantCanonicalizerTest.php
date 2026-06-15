<?php

use App\Models\Clinical\ClinVarVariant;
use App\Models\Clinical\GenomicVariant;
use App\Services\Genomics\Reanalysis\ClinGenAlleleRegistryService;
use App\Services\Genomics\Reanalysis\VariantCanonicalizer;
use App\Services\Genomics\Reanalysis\VrsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->canon = new VariantCanonicalizer(new ClinGenAlleleRegistryService, new VrsService));

it('persists a canonical id + ClinVar baseline by coordinate-matching the synced ClinVar table', function () {
    Http::fake();
    $variant = GenomicVariant::factory()->create([
        'gene' => 'TESTGENEX', 'chromosome' => '17', 'position' => 43045712,
        'ref_allele' => 'G', 'alt_allele' => 'A',
    ]);
    ClinVarVariant::create([
        'variation_id' => '55555', 'chromosome' => '17', 'position' => 43045712,
        'reference_allele' => 'G', 'alternate_allele' => 'A', 'genome_build' => 'GRCh38',
        'gene_symbol' => 'TESTGENEX', 'clinical_significance' => 'Uncertain significance',
        'review_status' => 'criteria provided, single submitter', 'is_pathogenic' => false,
    ]);

    $canonical = $this->canon->canonicalize($variant->fresh());

    expect($canonical->clinvar_variation_id)->toBe('55555');
    expect($canonical->baseline_significance)->toBe('Uncertain significance');
    expect($canonical->baselined_at)->not->toBeNull();
    expect($variant->canonicalId()->exists())->toBeTrue();
});

it('uses the ClinGen Allele Registry CAID when an HGVS is available', function () {
    Http::fake(['reg.clinicalgenome.org/*' => Http::response([
        '@id' => 'http://reg.clinicalgenome.org/allele/CA222', 'externalRecords' => ['ClinVarVariations' => [['variationId' => 99]]],
    ], 200)]);
    $variant = GenomicVariant::factory()->create(['gene' => 'TESTGENEX', 'variant' => 'NC_000017.11:g.43045712G>A']);

    $canonical = $this->canon->canonicalize($variant->fresh());

    expect($canonical->caid)->toBe('CA222');
    expect($canonical->clinvar_variation_id)->toBe('99');
});

it('is idempotent — re-canonicalizing updates the same row', function () {
    Http::fake();
    $variant = GenomicVariant::factory()->create(['gene' => 'TESTGENEX', 'chromosome' => '1', 'position' => 1, 'ref_allele' => 'A', 'alt_allele' => 'T']);

    $first = $this->canon->canonicalize($variant->fresh());
    $second = $this->canon->canonicalize($variant->fresh());

    expect($second->id)->toBe($first->id);
    expect(\App\Models\Clinical\VariantCanonicalId::where('genomic_variant_id', $variant->id)->count())->toBe(1);
});

it('persists a VRS Allele ID when AnyVar is configured and the variant has an HGVS expression', function () {
    config(['services.anyvar.url' => 'http://anyvar:8000']);
    Http::fake([
        'reg.clinicalgenome.org/*' => Http::response([
            '@id' => 'http://reg.clinicalgenome.org/allele/CA333',
            'externalRecords' => [],
        ], 200),
        'anyvar:8000/*' => Http::response(['object_id' => 'ga4gh:VA.testid'], 200),
    ]);
    $variant = GenomicVariant::factory()->create([
        'gene' => 'TESTGENEX',
        'variant' => 'NC_000017.11:g.43045712G>A',
    ]);

    $canonical = $this->canon->canonicalize($variant->fresh());

    expect($canonical->vrs_id)->toBe('ga4gh:VA.testid');
});
