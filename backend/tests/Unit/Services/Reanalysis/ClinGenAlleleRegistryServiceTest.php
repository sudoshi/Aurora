<?php

use App\Services\Genomics\Reanalysis\ClinGenAlleleRegistryService;
use Illuminate\Support\Facades\Http;

beforeEach(fn () => $this->car = new ClinGenAlleleRegistryService);

it('resolves a CAID + ClinVar VariationID + dbSNP from an HGVS lookup', function () {
    Http::fake(['reg.clinicalgenome.org/*' => Http::response([
        '@id' => 'http://reg.clinicalgenome.org/allele/CA123456',
        'externalRecords' => [
            'ClinVarVariations' => [['variationId' => 7890]],
            'dbSNP' => [['rs' => 80357906]],
        ],
    ], 200)]);

    $r = $this->car->resolveByHgvs('NC_000017.11:g.43045712G>A');

    expect($r)->toMatchArray(['caid' => 'CA123456', 'clinvar_variation_id' => '7890', 'dbsnp_rs' => 'rs80357906']);
});

it('returns null fields when the registry has no cross-references', function () {
    Http::fake(['reg.clinicalgenome.org/*' => Http::response([
        '@id' => 'http://reg.clinicalgenome.org/allele/CA999', 'externalRecords' => [],
    ], 200)]);

    $r = $this->car->resolveByHgvs('NC_000017.11:g.43045712G>A');

    expect($r['caid'])->toBe('CA999');
    expect($r['clinvar_variation_id'])->toBeNull();
    expect($r['dbsnp_rs'])->toBeNull();
});

it('returns null on a registry error (degrades gracefully)', function () {
    Http::fake(['reg.clinicalgenome.org/*' => Http::response('error', 500)]);
    expect($this->car->resolveByHgvs('bogus'))->toBeNull();
});
