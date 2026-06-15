<?php

use App\Models\User;
use App\Services\AuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->authService = new AuthService;
});

// ─── login ───────────────────────────────────────────────────────────

describe('AuthService::login', function () {
    it('returns access_token and user array for valid credentials', function () {
        User::factory()->create([
            'email' => 'valid@example.com',
            'password' => 'secret123',
            'is_active' => true,
        ]);

        $result = $this->authService->login([
            'email' => 'valid@example.com',
            'password' => 'secret123',
        ]);

        expect($result)->toBeArray()
            ->toHaveKeys(['access_token', 'user']);
        expect($result['access_token'])->toBeString()->not->toBeEmpty();
        expect($result['user'])->toBeArray()
            ->toHaveKeys(['id', 'name', 'email']);
    });

    it('throws RuntimeException for wrong email', function () {
        $this->authService->login([
            'email' => 'nobody@example.com',
            'password' => 'anything',
        ]);
    })->throws(\RuntimeException::class, 'credentials do not match');

    it('throws RuntimeException for wrong password', function () {
        User::factory()->create([
            'email' => 'user@example.com',
            'password' => 'correctpassword',
            'is_active' => true,
        ]);

        $this->authService->login([
            'email' => 'user@example.com',
            'password' => 'wrongpassword',
        ]);
    })->throws(\RuntimeException::class, 'credentials do not match');

    it('throws RuntimeException for inactive user', function () {
        User::factory()->create([
            'email' => 'inactive@example.com',
            'password' => 'secret123',
            'is_active' => false,
        ]);

        $this->authService->login([
            'email' => 'inactive@example.com',
            'password' => 'secret123',
        ]);
    })->throws(\RuntimeException::class, 'deactivated');

    it('updates last_login_at timestamp', function () {
        $user = User::factory()->create([
            'email' => 'login@example.com',
            'password' => 'secret123',
            'is_active' => true,
            'last_login_at' => null,
        ]);

        $this->authService->login([
            'email' => 'login@example.com',
            'password' => 'secret123',
        ]);

        $user->refresh();
        expect($user->last_login_at)->not->toBeNull();
    });
});

// ─── register ────────────────────────────────────────────────────────

describe('AuthService::register', function () {
    beforeEach(function () {
        config(['services.resend.api_key' => 'test-key']);
        Http::fake([
            'api.resend.com/*' => Http::response(['id' => 'msg_123'], 200),
        ]);
    });

    it('creates user with must_change_password=true for new email', function () {
        $result = $this->authService->register([
            'name' => 'New User',
            'email' => 'new@example.com',
        ]);

        expect($result)->toBeArray()->toHaveKey('message');

        $user = User::where('email', 'new@example.com')->first();
        expect($user)->not->toBeNull();
        expect($user->must_change_password)->toBeTrue();
        expect($user->is_active)->toBeTrue();
    });

    it('returns same success message for existing email (enumeration prevention)', function () {
        User::factory()->create(['email' => 'existing@example.com']);

        $result = $this->authService->register([
            'name' => 'Duplicate',
            'email' => 'existing@example.com',
        ]);

        expect($result)->toBeArray()->toHaveKey('message');
        // Verify no duplicate user created
        expect(User::where('email', 'existing@example.com')->count())->toBe(1);
    });

    it('returns identical message for new and existing registrations', function () {
        User::factory()->create(['email' => 'taken@example.com']);

        $newResult = $this->authService->register([
            'name' => 'New Person',
            'email' => 'fresh@example.com',
        ]);

        $existingResult = $this->authService->register([
            'name' => 'Existing Person',
            'email' => 'taken@example.com',
        ]);

        expect($newResult['message'])->toBe($existingResult['message']);
    });

    it('calls Resend API for new registrations', function () {
        $this->authService->register([
            'name' => 'Email Test',
            'email' => 'emailtest@example.com',
        ]);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'api.resend.com')
                && $request['to'] === ['emailtest@example.com']
                && str_contains($request['from'], 'noreply@acumenus.net');
        });
    });
});

// ─── changePassword ──────────────────────────────────────────────────

describe('AuthService::changePassword', function () {
    it('updates password and returns new token for valid current password', function () {
        $user = User::factory()->create([
            'password' => 'oldpassword',
            'must_change_password' => true,
        ]);

        $result = $this->authService->changePassword($user, 'oldpassword', 'newpassword123');

        expect($result)->toBeArray()
            ->toHaveKeys(['message', 'access_token', 'user']);
        expect($result['access_token'])->toBeString()->not->toBeEmpty();

        // Verify password actually changed
        $user->refresh();
        expect(Hash::check('newpassword123', $user->password))->toBeTrue();
    });

    it('throws RuntimeException for wrong current password', function () {
        $user = User::factory()->create([
            'password' => 'realpassword',
        ]);

        $this->authService->changePassword($user, 'wrongpassword', 'newone123');
    })->throws(\RuntimeException::class, 'incorrect');

    it('throws RuntimeException when new password is same as current', function () {
        $user = User::factory()->create([
            'password' => 'samepassword',
        ]);

        $this->authService->changePassword($user, 'samepassword', 'samepassword');
    })->throws(\RuntimeException::class, 'must be different');

    it('revokes all old tokens before issuing new one', function () {
        $user = User::factory()->create([
            'password' => 'oldpassword',
        ]);

        // Create some existing tokens
        $user->createToken('token1');
        $user->createToken('token2');
        expect($user->tokens()->count())->toBe(2);

        $this->authService->changePassword($user, 'oldpassword', 'newpassword123');

        // After change, only the new token should exist
        expect($user->tokens()->count())->toBe(1);
    });

    it('sets must_change_password to false', function () {
        $user = User::factory()->create([
            'password' => 'temppassword',
            'must_change_password' => true,
        ]);

        $this->authService->changePassword($user, 'temppassword', 'permanentpass');

        $user->refresh();
        expect($user->must_change_password)->toBeFalse();
    });
});

// ─── logout ──────────────────────────────────────────────────────────

describe('AuthService::logout', function () {
    it('deletes all user tokens', function () {
        $user = User::factory()->create();
        $user->createToken('session1');
        $user->createToken('session2');
        expect($user->tokens()->count())->toBe(2);

        $this->authService->logout($user);

        expect($user->tokens()->count())->toBe(0);
    });
});

// ─── generateTempPassword ────────────────────────────────────────────

describe('AuthService::generateTempPassword', function () {
    it('returns string of specified length', function () {
        $password = $this->authService->generateTempPassword(12);
        expect($password)->toBeString()->toHaveLength(12);

        $password8 = $this->authService->generateTempPassword(8);
        expect($password8)->toHaveLength(8);
    });

    it('excludes ambiguous characters (I, l, O, 0)', function () {
        $ambiguous = ['I', 'l', 'O', '0'];

        // Generate 50 passwords and check none contain ambiguous chars
        for ($i = 0; $i < 50; $i++) {
            $password = $this->authService->generateTempPassword(20);
            foreach ($ambiguous as $char) {
                expect(str_contains($password, $char))->toBeFalse(
                    "Password '{$password}' contains ambiguous character '{$char}'"
                );
            }
        }
    });
});

// ─── formatUser ──────────────────────────────────────────────────────

describe('AuthService::formatUser', function () {
    it('returns array with all expected keys', function () {
        $user = User::factory()->create();

        $result = $this->authService->formatUser($user);

        expect($result)->toBeArray()->toHaveKeys([
            'id', 'name', 'email', 'phone', 'avatar',
            'must_change_password', 'is_active', 'last_login_at',
            'roles', 'permissions', 'created_at', 'updated_at',
        ]);
        expect($result['email'])->toBe($user->email);
    });
});
