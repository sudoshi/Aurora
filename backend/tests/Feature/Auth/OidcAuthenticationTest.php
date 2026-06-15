<?php

use App\Models\Auth\AuthProviderSetting;
use App\Models\User;
use App\Services\Auth\Oidc\OidcHandshakeStore;
use Illuminate\Support\Str;

describe('GET /api/auth/providers', function () {
    it('returns public auth provider discovery with OIDC disabled by default', function () {
        config(['services.oidc.enabled' => false]);
        AuthProviderSetting::query()->where('provider_type', 'oidc')->delete();

        $response = $this->getJson('/api/auth/providers');

        $response->assertOk()
            ->assertJsonPath('oidc_enabled', false)
            ->assertJsonPath('oidc_label', 'Sign in with Authentik')
            ->assertJsonPath('oidc_redirect_path', '/api/auth/oidc/redirect');
    });

    it('returns public auth provider discovery when OIDC is enabled by environment', function () {
        config([
            'services.oidc.enabled' => true,
            'services.oidc.discovery_url' => 'https://auth.acumenus.net/application/o/aurora-oidc/.well-known/openid-configuration',
            'services.oidc.client_id' => 'aurora-env-client',
            'services.oidc.redirect_uri' => 'https://aurora.acumenus.net/api/auth/oidc/callback',
        ]);

        $response = $this->getJson('/api/auth/providers');

        $response->assertOk()
            ->assertJsonPath('oidc_enabled', true)
            ->assertJsonPath('oidc_redirect_path', '/api/auth/oidc/redirect');
    });

    it('returns public auth provider discovery when OIDC is enabled by admin provider settings', function () {
        config(['services.oidc.enabled' => false]);

        AuthProviderSetting::query()->updateOrCreate(
            ['provider_type' => 'oidc'],
            [
                'display_name' => 'Authentik OpenID Connect',
                'is_enabled' => true,
                'priority' => 40,
                'settings' => [
                    'client_id' => 'aurora-db-client',
                    'discovery_url' => 'https://auth.acumenus.net/application/o/aurora-oidc/.well-known/openid-configuration',
                    'redirect_uri' => 'https://aurora.acumenus.net/api/auth/oidc/callback',
                    'scopes' => ['openid', 'profile', 'email', 'groups'],
                    'allowed_groups' => ['Aurora Admins'],
                ],
            ],
        );

        $response = $this->getJson('/api/auth/providers');

        $response->assertOk()
            ->assertJsonPath('oidc_enabled', true)
            ->assertJsonPath('oidc_label', 'Authentik OpenID Connect')
            ->assertJsonPath('oidc_redirect_path', '/api/auth/oidc/redirect');
    });
});

describe('OIDC endpoints', function () {
    it('hides OIDC redirect and exchange routes when OIDC is disabled', function () {
        config(['services.oidc.enabled' => false]);
        AuthProviderSetting::query()->where('provider_type', 'oidc')->delete();

        $this->getJson('/api/auth/oidc/redirect')->assertNotFound();
        $this->postJson('/api/auth/oidc/exchange', ['code' => 'unused'])->assertNotFound();
    });

    it('exchanges a one-time OIDC callback code for the stored token and formatted user', function () {
        config([
            'services.oidc.enabled' => true,
            'services.oidc.discovery_url' => 'https://auth.acumenus.net/application/o/aurora-oidc/.well-known/openid-configuration',
            'services.oidc.client_id' => 'aurora-env-client',
            'services.oidc.redirect_uri' => 'https://aurora.acumenus.net/api/auth/oidc/callback',
        ]);

        $user = User::factory()->create([
            'name' => 'Dr. SSO User',
            'email' => 'sso-user+'.Str::uuid().'@acumenus.net',
            'is_active' => true,
            'must_change_password' => false,
        ]);

        $code = app(OidcHandshakeStore::class)->putCode($user->id, 'plain-text-sanctum-token');

        $response = $this->postJson('/api/auth/oidc/exchange', ['code' => $code]);

        $response->assertOk()
            ->assertJsonPath('token', 'plain-text-sanctum-token')
            ->assertJsonPath('access_token', 'plain-text-sanctum-token')
            ->assertJsonPath('user.email', $user->email)
            ->assertJsonPath('user.must_change_password', false);

        $this->postJson('/api/auth/oidc/exchange', ['code' => $code])
            ->assertStatus(400)
            ->assertJsonPath('reason', 'unknown_code');
    });
});
