<?php

use App\Models\Clinical\GenomicVariant;

it('serves the beacon info document', function () {
    $this->getJson('/api/beacon/')
        ->assertStatus(200)
        ->assertJsonPath('meta.apiVersion', 'v2.0.0')
        ->assertJsonStructure(['meta' => ['beaconId'], 'response' => ['id', 'name']]);
});

it('answers a g_variants boolean query (exists true)', function () {
    GenomicVariant::factory()->create(['chromosome' => '17', 'position' => 43045712, 'ref_allele' => 'G', 'alt_allele' => 'A']);
    $this->getJson('/api/beacon/g_variants?referenceName=17&start=43045711&referenceBases=G&alternateBases=A')
        ->assertStatus(200)
        ->assertJsonPath('responseSummary.exists', true);
});

it('defaults g_variants to boolean granularity (no count leaked)', function () {
    $this->getJson('/api/beacon/g_variants?referenceName=17&start=1&referenceBases=C&alternateBases=T')
        ->assertStatus(200)
        ->assertJsonPath('responseSummary.exists', false)
        ->assertJsonMissingPath('responseSummary.numTotalResults');
});

it('honors count granularity when explicitly requested', function () {
    GenomicVariant::factory()->count(2)->create(['chromosome' => '7', 'position' => 140753336, 'ref_allele' => 'A', 'alt_allele' => 'T']);
    $this->getJson('/api/beacon/g_variants?referenceName=7&start=140753335&referenceBases=A&alternateBases=T&requestedGranularity=count')
        ->assertStatus(200)
        ->assertJsonPath('responseSummary.numTotalResults', 2);
});

it('never returns record-level granularity even if asked', function () {
    GenomicVariant::factory()->create(['chromosome' => '7', 'position' => 140753336, 'ref_allele' => 'A', 'alt_allele' => 'T']);
    $res = $this->getJson('/api/beacon/g_variants?referenceName=7&start=140753335&requestedGranularity=record');
    $res->assertStatus(200)->assertJsonMissingPath('response.resultSets');
    // record downgraded to count at most: numTotalResults may appear, but no record-level documents
});
