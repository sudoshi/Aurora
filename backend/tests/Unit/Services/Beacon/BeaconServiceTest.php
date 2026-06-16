<?php

use App\Models\Clinical\GenomicVariant;
use App\Services\Beacon\BeaconService;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

it('reports variant existence and count for g_variants', function () {
    GenomicVariant::factory()->count(2)->create(['gene' => 'TESTGENEX', 'chromosome' => '17', 'position' => 43045712, 'ref_allele' => 'G', 'alt_allele' => 'A']);
    $svc = app(BeaconService::class);

    $hit = $svc->queryGVariants(['referenceName' => '17', 'start' => 43045711, 'referenceBases' => 'G', 'alternateBases' => 'A'], 'count');
    expect($hit['responseSummary']['exists'])->toBeTrue();
    expect($hit['responseSummary']['numTotalResults'])->toBe(2);

    $miss = $svc->queryGVariants(['referenceName' => '17', 'start' => 1, 'referenceBases' => 'C', 'alternateBases' => 'T'], 'boolean');
    expect($miss['responseSummary']['exists'])->toBeFalse();
    expect($miss['responseSummary'])->not->toHaveKey('numTotalResults');
});

it('builds a spec-shaped info document', function () {
    $info = app(BeaconService::class)->info();
    expect($info['meta']['apiVersion'])->toBe('v2.0.0');
    expect($info['meta']['beaconId'])->toBe(config('services.beacon.id'));
    expect($info['response']['id'])->toBe(config('services.beacon.id'));
    expect($info['response']['name'])->toBe(config('services.beacon.name'));
});
