<?php

it('sets hardening headers on API responses', function () {
    $response = $this->getJson('/api/health');

    $response->assertHeader('X-Content-Type-Options', 'nosniff');
    $response->assertHeader('X-Frame-Options', 'DENY');
    $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    $response->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');

    expect($response->headers->get('Content-Security-Policy'))
        ->toContain("default-src 'self'")
        // OHIF is embedded same-origin (/ohif/), so frame-src is locked to 'self';
        // frame-ancestors 'none' blocks Aurora itself from being framed (W4-T06).
        ->toContain("frame-src 'self'")
        ->toContain("frame-ancestors 'none'")
        ->toContain("object-src 'none'");
});
