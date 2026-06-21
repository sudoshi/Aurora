<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('exposes Prometheus metrics without sanctum auth when no token configured', function () {
    config(['services.metrics.token' => null]);

    $response = $this->get('/api/metrics');

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('text/plain');

    $body = $response->getContent();
    expect($body)->toContain('aurora_up 1');
    expect($body)->toContain('aurora_queue_pending_jobs');
    expect($body)->toContain('aurora_dependency_up{dependency="database"}');
});

it('emits HELP and TYPE lines for the core gauges', function () {
    config(['services.metrics.token' => null]);

    $body = $this->get('/api/metrics')->getContent();

    expect($body)->toContain('# TYPE aurora_up gauge');
    expect($body)->toContain('# TYPE aurora_build_info gauge');
    expect($body)->toContain('aurora_build_info{version=');
});

it('rejects scrapes without the bearer token when a token is configured', function () {
    config(['services.metrics.token' => 'secret']);

    $this->get('/api/metrics')->assertStatus(403);
});

it('allows scrapes with the correct bearer token when a token is configured', function () {
    config(['services.metrics.token' => 'secret']);

    $this->withHeaders(['Authorization' => 'Bearer secret'])
        ->get('/api/metrics')
        ->assertOk()
        ->assertSee('aurora_up 1');
});
