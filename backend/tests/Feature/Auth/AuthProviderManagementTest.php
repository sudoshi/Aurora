<?php

use App\Models\User;
use Database\Seeders\AuthProviderSeeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

function auroraAuthProviderSuperuser(): User
{
    Role::findOrCreate('super-admin', 'sanctum');
    Role::findOrCreate('admin', 'sanctum');

    $superuser = User::query()->updateOrCreate(
        ['email' => 'admin@acumenus.net'],
        [
            'name' => 'Aurora Admin',
            'password' => Hash::make('superuser'),
            'must_change_password' => false,
            'is_active' => true,
        ],
    );
    $superuser->syncRoles(['super-admin', 'admin']);

    app(AuthProviderSeeder::class)->run();

    return $superuser;
}

describe('Admin auth provider management', function () {
    it('allows super-admin users to list seeded auth providers', function () {
        $superuser = auroraAuthProviderSuperuser();

        $response = $this->actingAs($superuser, 'sanctum')
            ->getJson('/api/admin/auth-providers');

        $response->assertOk()
            ->assertJsonCount(4)
            ->assertJsonFragment([
                'provider_type' => 'oidc',
                'display_name' => 'Authentik OpenID Connect',
            ]);
    });

    it('allows super-admin users to merge and enable OIDC provider settings', function () {
        $superuser = auroraAuthProviderSuperuser();

        $update = $this->actingAs($superuser, 'sanctum')
            ->putJson('/api/admin/auth-providers/oidc', [
                'settings' => [
                    'client_id' => 'aurora-test-client',
                    'allowed_groups' => ['Aurora Admins', 'Aurora Clinicians'],
                ],
            ]);

        $update->assertOk()
            ->assertJsonPath('provider_type', 'oidc')
            ->assertJsonPath('settings.client_id', 'aurora-test-client')
            ->assertJsonPath('settings.redirect_uri', '/api/auth/oidc/callback');

        $enable = $this->actingAs($superuser, 'sanctum')
            ->postJson('/api/admin/auth-providers/oidc/enable');

        $enable->assertOk()
            ->assertJsonPath('provider_type', 'oidc')
            ->assertJsonPath('is_enabled', true);

        $this->getJson('/api/auth/providers')
            ->assertOk()
            ->assertJsonPath('oidc_enabled', true)
            ->assertJsonPath('oidc_label', 'Authentik OpenID Connect');
    });

    it('rejects auth provider management for non-super-admin admins', function () {
        Role::findOrCreate('admin', 'sanctum');

        $admin = User::query()->updateOrCreate(
            ['email' => 'admin-only@acumenus.net'],
            [
                'name' => 'Admin Only',
                'password' => Hash::make('password'),
                'must_change_password' => false,
                'is_active' => true,
            ],
        );
        $admin->syncRoles(['admin']);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/auth-providers')
            ->assertForbidden();
    });
});
