<?php

use App\Models\DiagnosticOdyssey;
use App\Models\PhenotypeFeature;
use App\Models\Clinical\GenomicVariant;
use App\Services\Matchmaker\MmeMatchService;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

it('scores and returns local matches for a request profile', function () {
    $od = DiagnosticOdyssey::factory()->create();
    PhenotypeFeature::factory()->create(['odyssey_id' => $od->id, 'hpo_id' => 'HP:0001250', 'excluded' => false]);
    GenomicVariant::factory()->create(['patient_id' => $od->patient_id, 'gene' => 'TESTGENEX']);

    $request = ['patient' => [
        'id' => 'ext-1',
        'features' => [['id' => 'HP:0001250', 'observed' => 'yes'], ['id' => 'HP:0009999', 'observed' => 'yes']],
        'genomicFeatures' => [['gene' => ['id' => 'TESTGENEX']]],
    ]];

    $results = app(MmeMatchService::class)->matchAgainstLocal($request);

    expect($results)->not->toBeEmpty();
    expect($results[0]['score']['patient'])->toBeGreaterThan(0.0)->toBeLessThanOrEqual(1.0);
    expect($results[0]['patient']['id'])->toBe('aurora-odyssey-'.$od->id);
});

it('returns nothing when request has no observed features and no genes', function () {
    $od = DiagnosticOdyssey::factory()->create();
    PhenotypeFeature::factory()->create(['odyssey_id' => $od->id, 'hpo_id' => 'HP:0001250']);
    expect(app(MmeMatchService::class)->matchAgainstLocal(['patient' => ['id' => 'x']]))->toBe([]);
});

it('excludes a non-overlapping odyssey', function () {
    $od = DiagnosticOdyssey::factory()->create();
    PhenotypeFeature::factory()->create(['odyssey_id' => $od->id, 'hpo_id' => 'HP:0008888', 'excluded' => false]);
    $results = app(MmeMatchService::class)->matchAgainstLocal(['patient' => ['id' => 'x', 'features' => [['id' => 'HP:0001250', 'observed' => 'yes']]]]);
    expect(collect($results)->pluck('patient.id'))->not->toContain('aurora-odyssey-'.$od->id);
});
