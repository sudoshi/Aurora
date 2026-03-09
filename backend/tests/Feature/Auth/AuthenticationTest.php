<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

describe('POST /api/login', function () {
    it('returns a token with valid credentials', function () {
        $user = User::factory()->create([
            'email' => 'doctor@acumenus.net',
            'password' => Hash::make('SecurePass123!'),
            'is_active' => true,
            'must_change_password' => false,
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'doctor@acumenus.net',
            'password' => 'SecurePass123!',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['access_token', 'user']);
    });

    it('returns 401 with invalid credentials', function () {
        User::factory()->create([
            'email' => 'doctor@acumenus.net',
            'password' => Hash::make('SecurePass123!'),
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'doctor@acumenus.net',
            'password' => 'WrongPassword!',
        ]);

        $response->assertStatus(401);
    });

    it('returns 422 when email is missing', function () {
        $response = $this->postJson('/api/login', [
            'password' => 'SomePassword!',
        ]);

        $response->assertStatus(422);
    });

    it('returns 422 when password is missing', function () {
        $response = $this->postJson('/api/login', [
            'email' => 'doctor@acumenus.net',
        ]);

        $response->assertStatus(422);
    });
});

describe('POST /api/register', function () {
    it('creates a user with must_change_password true', function () {
        Http::fake([
            'api.resend.com/*' => Http::response(['id' => 'fake-id'], 200),
        ]);

        $response = $this->postJson('/api/register', [
            'name' => 'Dr. New User',
            'email' => 'newuser@acumenus.net',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('dev.users', [
            'email' => 'newuser@acumenus.net',
            'must_change_password' => true,
        ]);
    });

    it('returns success even for existing email (enumeration prevention)', function () {
        User::factory()->create([
            'email' => 'existing@acumenus.net',
        ]);

        $response = $this->postJson('/api/register', [
            'name' => 'Dr. Duplicate',
            'email' => 'existing@acumenus.net',
        ]);

        $response->assertStatus(201);
    });

    it('validates required fields', function () {
        $response = $this->postJson('/api/register', []);

        $response->assertStatus(422);
    });
});

describe('POST /api/change-password', function () {
    it('updates password for authenticated user', function () {
        $user = User::factory()->create([
            'password' => Hash::make('OldPassword123!'),
            'must_change_password' => true,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/change-password', [
                'current_password' => 'OldPassword123!',
                'new_password' => 'NewSecurePass456!',
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['message', 'access_token', 'user']);
    });

    it('rejects wrong current password', function () {
        $user = User::factory()->create([
            'password' => Hash::make('OldPassword123!'),
            'is_active' => true,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/change-password', [
                'current_password' => 'WrongOldPass!',
                'new_password' => 'NewSecurePass456!',
            ]);

        $response->assertStatus(422);
    });

    it('requires authentication', function () {
        $response = $this->postJson('/api/change-password', [
            'current_password' => 'OldPassword123!',
            'new_password' => 'NewSecurePass456!',
        ]);

        $response->assertStatus(401);
    });
});

describe('POST /api/logout', function () {
    it('revokes token for authenticated user', function () {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/logout');

        $response->assertStatus(200);
    });

    it('requires authentication', function () {
        $response = $this->postJson('/api/logout');

        $response->assertStatus(401);
    });
});

describe('GET /api/user', function () {
    it('returns the authenticated user', function () {
        $user = User::factory()->create([
            'name' => 'Dr. Test User',
            'email' => 'testuser@acumenus.net',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/user');

        $response->assertStatus(200)
            ->assertJsonFragment(['email' => 'testuser@acumenus.net']);
    });

    it('returns 401 without authentication', function () {
        $response = $this->getJson('/api/user');

        $response->assertStatus(401);
    });
});
