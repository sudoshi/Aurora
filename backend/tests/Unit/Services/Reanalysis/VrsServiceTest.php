<?php

use App\Services\Genomics\Reanalysis\VrsService;
use Illuminate\Support\Facades\Http;

beforeEach(fn () => $this->vrs = new VrsService);

it('returns null immediately when anyvar.url is not configured (no HTTP request made)', function () {
    config(['services.anyvar.url' => '']);
    Http::fake();

    $result = $this->vrs->computeId('NC_000017.11:g.43045712G>A');

    expect($result)->toBeNull();
    Http::assertNothingSent();
});

it('returns the VRS id when AnyVar returns object_id at root', function () {
    config(['services.anyvar.url' => 'http://anyvar:8000']);
    Http::fake(['anyvar:8000/*' => Http::response(['object_id' => 'ga4gh:VA.xyz123', 'messages' => []], 200)]);

    $result = $this->vrs->computeId('NC_000017.11:g.43045712G>A');

    expect($result)->toBe('ga4gh:VA.xyz123');
});

it('returns the VRS id when AnyVar returns it nested under object.id', function () {
    config(['services.anyvar.url' => 'http://anyvar:8000']);
    Http::fake(['anyvar:8000/*' => Http::response([
        'messages' => [],
        'object' => ['id' => 'ga4gh:VA.nested456', 'type' => 'Allele'],
    ], 200)]);

    $result = $this->vrs->computeId('NC_000017.11:g.43045712G>A');

    expect($result)->toBe('ga4gh:VA.nested456');
});

it('returns null when AnyVar returns a 500 error (degrades gracefully)', function () {
    config(['services.anyvar.url' => 'http://anyvar:8000']);
    Http::fake(['anyvar:8000/*' => Http::response('Internal Server Error', 500)]);

    $result = $this->vrs->computeId('NC_000017.11:g.43045712G>A');

    expect($result)->toBeNull();
});

it('returns null when AnyVar returns success but no recognizable VRS id', function () {
    config(['services.anyvar.url' => 'http://anyvar:8000']);
    Http::fake(['anyvar:8000/*' => Http::response(['messages' => ['error parsing variant']], 200)]);

    $result = $this->vrs->computeId('bogus-hgvs');

    expect($result)->toBeNull();
});
