<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

it('segments API rate limits by authenticated user and guest IP', function () {
    $limiter = RateLimiter::limiter('api');

    $authenticatedRequest = Request::create('/api/patients');
    $authenticatedRequest->setUserResolver(fn () => (object) ['id' => 123]);

    $authenticatedLimit = $limiter($authenticatedRequest);

    expect($authenticatedLimit->key)->toBe('user:123')
        ->and($authenticatedLimit->maxAttempts)->toBe(300)
        ->and($authenticatedLimit->decaySeconds)->toBe(60);

    $guestRequest = Request::create('/api/patients', server: ['REMOTE_ADDR' => '203.0.113.10']);

    $guestLimit = $limiter($guestRequest);

    expect($guestLimit->key)->toBe('ip:203.0.113.10')
        ->and($guestLimit->maxAttempts)->toBe(60)
        ->and($guestLimit->decaySeconds)->toBe(60);
});

it('applies the named API throttle after Sanctum authentication on protected routes', function () {
    $route = collect(app('router')->getRoutes())->first(
        fn ($route) => in_array('GET', $route->methods(), true)
            && $route->uri() === 'api/patients',
    );

    expect($route)->not->toBeNull();

    $middleware = $route->gatherMiddleware();

    expect($middleware)->toContain('auth:sanctum')
        ->and($middleware)->toContain('throttle:api');

    $authIndex = array_search('auth:sanctum', $middleware, true);
    $throttleIndex = array_search('throttle:api', $middleware, true);

    expect($authIndex)->toBeLessThan($throttleIndex);
});
