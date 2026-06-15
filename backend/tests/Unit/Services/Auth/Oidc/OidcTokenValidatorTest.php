<?php

use App\Services\Auth\Oidc\Exceptions\OidcTokenInvalidException;
use App\Services\Auth\Oidc\OidcDiscoveryService;
use App\Services\Auth\Oidc\OidcTokenValidator;
use Firebase\JWT\JWT;

beforeEach(function () {
    $res = openssl_pkey_new([
        'private_key_type' => OPENSSL_KEYTYPE_RSA,
        'private_key_bits' => 2048,
    ]);
    expect($res)->not->toBeFalse();
    $this->privateKey = $res;

    $details = openssl_pkey_get_details($res);
    $b64 = static fn (string $raw): string => rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');

    $this->issuer = 'https://auth.acumenus.net/application/o/aurora-oidc/';
    $this->audience = 'aurora-test-client';
    $kid = 'kid-aurora-1';

    $jwks = ['keys' => [[
        'kty' => 'RSA',
        'kid' => $kid,
        'alg' => 'RS256',
        'use' => 'sig',
        'n' => $b64($details['rsa']['n']),
        'e' => $b64($details['rsa']['e']),
    ]]];

    $discovery = Mockery::mock(OidcDiscoveryService::class);
    $discovery->shouldReceive('jwks')->andReturn($jwks);
    $discovery->shouldReceive('issuer')->andReturn($this->issuer);

    $this->validator = new OidcTokenValidator($discovery, $this->audience);
    $this->makeToken = fn (array $payload): string => JWT::encode($payload, $this->privateKey, 'RS256', $kid);
});

it('returns validated claims for a well-formed token', function () {
    $token = ($this->makeToken)([
        'iss' => $this->issuer,
        'aud' => $this->audience,
        'sub' => 'sub-123',
        'email' => 'sudoshi@acumenus.net',
        'name' => 'Sanjay Udoshi',
        'groups' => ['Aurora Admins', 'authentik Admins'],
        'nonce' => 'n-1',
        'exp' => time() + 300,
        'iat' => time(),
    ]);

    $claims = $this->validator->validate($token, 'n-1');

    expect($claims->sub)->toBe('sub-123')
        ->and($claims->email)->toBe('sudoshi@acumenus.net')
        ->and($claims->name)->toBe('Sanjay Udoshi')
        ->and($claims->groups)->toBe(['Aurora Admins', 'authentik Admins']);
});

it('rejects an expired token (beyond the clock-skew leeway)', function () {
    $token = ($this->makeToken)([
        'iss' => $this->issuer, 'aud' => $this->audience,
        'sub' => 's', 'email' => 'a@b.net', 'name' => 'n',
        'exp' => time() - 120, 'iat' => time() - 3600,
    ]);

    $this->validator->validate($token);
})->throws(OidcTokenInvalidException::class);

it('rejects a token with no exp claim (cannot validate indefinitely)', function () {
    $token = ($this->makeToken)([
        'iss' => $this->issuer, 'aud' => $this->audience,
        'sub' => 's', 'email' => 'a@b.net', 'name' => 'n',
        // exp deliberately omitted
    ]);

    $this->validator->validate($token);
})->throws(OidcTokenInvalidException::class);

it('rejects a token from the wrong issuer', function () {
    $token = ($this->makeToken)([
        'iss' => 'https://evil.example.com/', 'aud' => $this->audience,
        'sub' => 's', 'email' => 'a@b.net', 'name' => 'n',
        'exp' => time() + 300, 'iat' => time(),
    ]);

    $this->validator->validate($token);
})->throws(OidcTokenInvalidException::class);

it('rejects a token with the wrong audience', function () {
    $token = ($this->makeToken)([
        'iss' => $this->issuer, 'aud' => 'some-other-client',
        'sub' => 's', 'email' => 'a@b.net', 'name' => 'n',
        'exp' => time() + 300, 'iat' => time(),
    ]);

    $this->validator->validate($token);
})->throws(OidcTokenInvalidException::class);

it('rejects a token whose nonce does not match', function () {
    $token = ($this->makeToken)([
        'iss' => $this->issuer, 'aud' => $this->audience,
        'sub' => 's', 'email' => 'a@b.net', 'name' => 'n',
        'nonce' => 'actual-nonce',
        'exp' => time() + 300, 'iat' => time(),
    ]);

    $this->validator->validate($token, 'expected-different-nonce');
})->throws(OidcTokenInvalidException::class);

it('rejects a token missing a required claim', function () {
    $token = ($this->makeToken)([
        'iss' => $this->issuer, 'aud' => $this->audience,
        'sub' => 's', 'email' => 'a@b.net',
        // name claim deliberately omitted
        'exp' => time() + 300, 'iat' => time(),
    ]);

    $this->validator->validate($token);
})->throws(OidcTokenInvalidException::class);
