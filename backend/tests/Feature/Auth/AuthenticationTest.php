<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\SuperuserSeeder']);
});

describe('POST /api/auth/login', function () {
    it('superuser can login', function () {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@acumenus.net',
            'password' => 'superuser',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['access_token', 'user'])
            ->assertJsonPath('user.must_change_password', false)
            ->assertJsonPath('user.email', 'admin@acumenus.net');
    });

    it('must_change_password flag returned in login', function () {
        $user = User::factory()->create([
            'email' => 'newdoc@acumenus.net',
            'password' => Hash::make('TempPass123!'),
            'must_change_password' => true,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'newdoc@acumenus.net',
            'password' => 'TempPass123!',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('user.must_change_password', true);
    });

    it('inactive user cannot login', function () {
        $user = User::factory()->create([
            'email' => 'inactive@acumenus.net',
            'password' => Hash::make('SecurePass123!'),
            'is_active' => false,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'inactive@acumenus.net',
            'password' => 'SecurePass123!',
        ]);

        $response->assertStatus(403);
    });

    it('login with wrong password returns 401', function () {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@acumenus.net',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401);
    });
});

describe('POST /api/auth/register', function () {
    it('registration creates user with temp password', function () {
        Http::fake([
            'api.resend.com/*' => Http::response(['id' => 'fake-id'], 200),
        ]);

        $response = $this->postJson('/api/auth/register', [
            'name' => 'Dr. New User',
            'email' => 'newuser@acumenus.net',
            'phone' => '555-0100',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('app.users', [
            'email' => 'newuser@acumenus.net',
            'must_change_password' => true,
        ]);
    });

    it('registration returns same message for existing email', function () {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Fake Admin',
            'email' => 'admin@acumenus.net',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    });
});

describe('POST /api/auth/change-password', function () {
    it('change password works and clears flag', function () {
        $user = User::factory()->create([
            'password' => Hash::make('OldPassword123!'),
            'must_change_password' => true,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/auth/change-password', [
                'current_password' => 'OldPassword123!',
                'password' => 'NewSecurePass456!',
                'password_confirmation' => 'NewSecurePass456!',
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['access_token', 'user']);

        $user->refresh();
        expect($user->must_change_password)->toBeFalse();
    });

    it('change password rejects wrong current password', function () {
        $user = User::factory()->create([
            'password' => Hash::make('OldPassword123!'),
            'is_active' => true,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/auth/change-password', [
                'current_password' => 'WrongOldPass!',
                'password' => 'NewSecurePass456!',
                'password_confirmation' => 'NewSecurePass456!',
            ]);

        $response->assertStatus(422);
    });
});

describe('POST /api/auth/logout', function () {
    it('logout revokes token', function () {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        // Create a real token
        $token = $user->createToken('auth_token')->plainTextToken;

        // Logout using the token
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->postJson('/api/auth/logout');

        $response->assertStatus(200);

        // Verify token was actually deleted from DB
        expect($user->tokens()->count())->toBe(0);

        // Reset auth guard state so it re-checks the token
        $this->app['auth']->forgetGuards();

        // Token should no longer work
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->getJson('/api/auth/user');

        $response->assertStatus(401);
    });
});

describe('GET /api/auth/user', function () {
    it('returns formatted user with roles and permissions', function () {
        $user = User::factory()->create([
            'name' => 'Dr. Test User',
            'email' => 'testuser@acumenus.net',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/auth/user');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id', 'name', 'email', 'must_change_password',
                'is_active', 'roles', 'permissions',
            ]);
    });
});

describe('User model', function () {
    it('superuser cannot be deleted via isSuperuser check', function () {
        $superuser = User::where('email', 'admin@acumenus.net')->first();

        expect($superuser)->not->toBeNull();
        expect($superuser->isSuperuser())->toBeTrue();
    });
});

describe('GET /api/health', function () {
    it('returns health check', function () {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'service', 'version', 'timestamp'])
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('service', 'aurora-api');
    });
});
