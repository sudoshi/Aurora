<?php

it('serves the public liveness probe', function () {
    $this->getJson('/api/health')
        ->assertOk()
        ->assertJsonPath('status', 'ok')
        ->assertJsonPath('service', 'aurora-api');
});

it('serves a readiness probe reporting per-dependency status', function () {
    $response = $this->getJson('/api/health/ready');

    $response->assertJsonStructure([
        'status',
        'checks' => ['database', 'redis', 'cache'],
        'timestamp',
    ]);

    // The DB connection is always available under the test harness.
    expect($response->json('checks.database'))->toBe('up');
    // Endpoint returns 200 when ready, 503 when a hard dependency is down.
    expect($response->status())->toBeIn([200, 503]);
});
