<?php

use App\Services\RareDisease\HpoService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Cache::flush();
    $this->service = new HpoService;
});

it('normalizes HPO search results and filters non-HP ids', function () {
    Http::fake([
        'ontology.jax.org/*' => Http::response([
            'terms' => [
                ['id' => 'HP:0001250', 'name' => 'Seizure', 'definition' => 'A seizure.', 'synonyms' => ['Seizures']],
                ['id' => 'NOTHP:1', 'name' => 'Bogus', 'definition' => null, 'synonyms' => []],
            ],
        ], 200),
    ]);

    $results = $this->service->search('seizure', 10);

    expect($results)->toHaveCount(1);
    expect($results[0])->toMatchArray(['id' => 'HP:0001250', 'label' => 'Seizure']);
    expect($results[0]['synonyms'])->toBe(['Seizures']);
});

it('returns an empty array for a blank query without calling the API', function () {
    Http::fake();
    expect($this->service->search('  ', 10))->toBe([]);
    Http::assertNothingSent();
});

it('returns an empty array when the upstream API fails', function () {
    Http::fake(['ontology.jax.org/*' => Http::response('err', 500)]);
    expect($this->service->search('seizure'))->toBe([]);
});

it('caches results so a second identical query hits no HTTP', function () {
    Http::fake([
        'ontology.jax.org/*' => Http::response(['terms' => [
            ['id' => 'HP:0001250', 'name' => 'Seizure', 'definition' => null, 'synonyms' => []],
        ]], 200),
    ]);

    $this->service->search('seizure', 5);
    $this->service->search('seizure', 5);

    Http::assertSentCount(1);
});
