<?php

it('attaches a non-empty X-Request-Id header to responses', function () {
    $response = $this->get('/api/health');

    $id = $response->headers->get('X-Request-Id');

    expect($id)->not->toBeNull()->not->toBe('');
});

it('echoes back a client-supplied X-Request-Id', function () {
    $response = $this->withHeaders(['X-Request-Id' => 'test-correlation-123'])
        ->get('/api/health');

    $response->assertHeader('X-Request-Id', 'test-correlation-123');
});

it('generates distinct ids for requests without the header', function () {
    $first = $this->get('/api/health')->headers->get('X-Request-Id');
    $second = $this->get('/api/health')->headers->get('X-Request-Id');

    expect($first)->not->toBeNull();
    expect($second)->not->toBeNull();
    expect($first)->not->toBe($second);
});
