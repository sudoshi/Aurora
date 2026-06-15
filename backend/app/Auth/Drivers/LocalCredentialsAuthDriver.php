<?php

namespace App\Auth\Drivers;

use App\Contracts\AuthDriverInterface;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class LocalCredentialsAuthDriver implements AuthDriverInterface
{
    public function name(): string
    {
        return 'local';
    }

    public function isAvailable(): bool
    {
        return (bool) config('auth-drivers.local.enabled', true);
    }

    public function authenticate(array $credentials): AuthDriverResult
    {
        if (
            ! isset($credentials['email'], $credentials['password'])
            || ! is_string($credentials['email'])
            || ! is_string($credentials['password'])
        ) {
            throw new AuthDriverException(
                'Malformed credentials: expected string email and password',
                AuthDriverException::CODE_MALFORMED_CREDENTIALS,
                $this->name(),
            );
        }

        if (! $this->isAvailable()) {
            throw new AuthDriverException(
                'Local credentials are disabled',
                AuthDriverException::CODE_PROVIDER_UNREACHABLE,
                $this->name(),
            );
        }

        $email = strtolower(trim($credentials['email']));
        $user = User::query()
            ->with('roles.permissions')
            ->whereRaw('lower(email) = ?', [$email])
            ->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw new AuthDriverException(
                'Invalid credentials',
                AuthDriverException::CODE_INVALID_CREDENTIALS,
                $this->name(),
            );
        }

        if (! $user->is_active) {
            throw new AuthDriverException(
                'Account disabled',
                AuthDriverException::CODE_ACCOUNT_DISABLED,
                $this->name(),
            );
        }

        return new AuthDriverResult(
            user: $user,
            driverName: $this->name(),
            mustChangePassword: (bool) $user->must_change_password,
        );
    }
}
