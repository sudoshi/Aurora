<?php

use App\Models\Auth\AuthProviderSetting;
use Illuminate\Support\Facades\Http;

function enableAuroraOidcConfig(): void
{
    config([
        'services.oidc.enabled' => true,
        'services.oidc.client_id' => 'aurora-test-client',
        'services.oidc.client_secret' => 'aurora-test-secret',
        'services.oidc.redirect_uri' => 'https://aurora.acumenus.net/api/auth/oidc/callback',
        'services.oidc.discovery_url' => 'https://auth.acumenus.net/application/o/aurora-oidc/.well-known/openid-configuration',
    ]);

    AuthProviderSetting::query()->where('provider_type', 'oidc')->delete();
}

describe('OIDC routes are hidden when disabled', function () {
    beforeEach(function () {
        config(['services.oidc.enabled' => false]);
        AuthProviderSetting::query()->where('provider_type', 'oidc')->delete();
    });

    it('returns 404 for redirect', function () {
        $this->get('/api/auth/oidc/redirect')->assertNotFound();
    });

    it('returns 404 for callback', function () {
        $this->get('/api/auth/oidc/callback?state=x&code=y')->assertNotFound();
    });

    it('returns 404 for exchange', function () {
        $this->postJson('/api/auth/oidc/exchange', ['code' => 'x'])->assertNotFound();
    });
});

describe('OIDC routes when enabled', function () {
    it('issues a 302 authorize redirect carrying state, nonce, and PKCE challenge', function () {
        enableAuroraOidcConfig();

        Http::fake([
            'auth.acumenus.net/application/o/aurora-oidc/.well-known/openid-configuration' => Http::response([
                'issuer' => 'https://auth.acumenus.net/application/o/aurora-oidc/',
                'authorization_endpoint' => 'https://auth.acumenus.net/application/o/authorize/',
                'token_endpoint' => 'https://auth.acumenus.net/application/o/token/',
                'jwks_uri' => 'https://auth.acumenus.net/application/o/aurora-oidc/jwks/',
            ]),
            'auth.acumenus.net/application/o/aurora-oidc/jwks/' => Http::response(['keys' => []]),
        ]);

        $response = $this->get('/api/auth/oidc/redirect');

        $response->assertStatus(302);

        $location = (string) $response->headers->get('Location');
        expect($location)
            ->toContain('auth.acumenus.net/application/o/authorize/')
            ->toContain('state=')
            ->toContain('nonce=')
            ->toContain('code_challenge=')
            ->toContain('code_challenge_method=S256')
            ->toContain('client_id=aurora-test-client');
    });

    it('rejects a callback with missing parameters', function () {
        enableAuroraOidcConfig();

        $this->get('/api/auth/oidc/callback?code=abc')
            ->assertStatus(400)
            ->assertJson(['error' => 'oidc_failed', 'reason' => 'missing_parameters']);
    });

    it('rejects a callback with an unknown state', function () {
        enableAuroraOidcConfig();

        $this->get('/api/auth/oidc/callback?state=never-issued&code=abc')
            ->assertStatus(400)
            ->assertJson(['error' => 'oidc_failed', 'reason' => 'unknown_state']);
    });

    it('rejects an exchange with a missing code', function () {
        enableAuroraOidcConfig();

        $this->postJson('/api/auth/oidc/exchange', [])
            ->assertStatus(400)
            ->assertJson(['error' => 'oidc_failed', 'reason' => 'missing_code']);
    });
});
