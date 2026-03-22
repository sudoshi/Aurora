<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AuthService
{
    /**
     * Register a new user with a temporary password sent via email.
     *
     * Returns the same success message regardless of whether the email
     * already exists (enumeration prevention).
     *
     * @param  array{name: string, email: string, phone?: string|null}  $data
     * @return array{message: string}
     */
    public function register(array $data): array
    {
        $successMessage = 'If your email is not already registered, you will receive your login credentials shortly. Please check your inbox.';

        $email = strtolower(trim($data['email']));

        // Check if user already exists - return same message to prevent enumeration
        if (User::where('email', $email)->exists()) {
            return ['message' => $successMessage];
        }

        // Generate temp password
        $tempPassword = $this->generateTempPassword();

        // Create user
        $user = User::create([
            'name' => trim($data['name']),
            'email' => $email,
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($tempPassword),
            'must_change_password' => true,
            'is_active' => true,
        ]);

        // Send temp password via email (non-fatal if it fails)
        $this->sendTempPasswordEmail($user->email, $user->name, $tempPassword);

        return ['message' => $successMessage];
    }

    /**
     * Authenticate a user and create an API token.
     *
     * @param  array{email: string, password: string}  $credentials
     * @return array{access_token: string, user: array}
     *
     * @throws \RuntimeException When credentials are invalid or account is inactive.
     */
    public function login(array $credentials): array
    {
        $user = User::where('email', strtolower($credentials['email']))
            ->with('roles.permissions')
            ->first();

        // Same error for "not found" and "wrong password" to prevent enumeration
        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw new \RuntimeException(
                'The provided credentials do not match our records.',
                401
            );
        }

        // Check if account is active
        if ($user->is_active === false) {
            throw new \RuntimeException(
                'Your account has been deactivated. Please contact support.',
                403
            );
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        // Update last_login_at
        $user->updateQuietly(['last_login_at' => now()]);

        return [
            'access_token' => $token,
            'user' => $this->formatUser($user),
        ];
    }

    /**
     * Change the authenticated user's password.
     *
     * Validates the current password, ensures the new one is different,
     * revokes all existing tokens, and issues a fresh one.
     *
     * @return array{message: string, access_token: string, user: array}
     *
     * @throws \RuntimeException When current password is wrong or new matches current.
     */
    public function changePassword(User $user, string $currentPassword, string $newPassword): array
    {
        // Verify current password
        if (! Hash::check($currentPassword, $user->password)) {
            throw new \RuntimeException('Current password is incorrect.', 422);
        }

        // Ensure new password differs from current
        if (Hash::check($newPassword, $user->password)) {
            throw new \RuntimeException(
                'New password must be different from your current password.',
                422
            );
        }

        // Update password and clear the must_change_password flag
        $user->update([
            'password' => Hash::make($newPassword),
            'must_change_password' => false,
        ]);

        // Revoke all existing tokens and issue a new one
        $user->tokens()->delete();
        $token = $user->createToken('auth_token')->plainTextToken;

        // Refresh user data
        $user->refresh()->load('roles.permissions');

        return [
            'message' => 'Password changed successfully.',
            'access_token' => $token,
            'user' => $this->formatUser($user),
        ];
    }

    /**
     * Logout user by revoking all tokens.
     */
    public function logout(User $user): void
    {
        $user->tokens()->delete();
    }

    /**
     * Format user data for API responses.
     *
     * @return array<string, mixed>
     */
    public function formatUser(User $user): array
    {
        $user->loadMissing('roles.permissions');

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'avatar' => $user->avatar,
            'must_change_password' => $user->must_change_password,
            'is_active' => $user->is_active,
            'last_login_at' => $user->last_login_at,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ];
    }

    /**
     * Generate a random temporary password.
     *
     * Produces a 12-character string using characters that exclude
     * visually ambiguous glyphs (I, l, O, 0).
     */
    public function generateTempPassword(int $length = 12): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789!@#$%^&*';
        $password = '';
        $max = strlen($chars) - 1;

        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, $max)];
        }

        return $password;
    }

    /**
     * Send temporary password to user via Resend API.
     */
    private function sendTempPasswordEmail(string $email, string $name, string $tempPassword): bool
    {
        try {
            $apiKey = config('services.resend.api_key');

            if (empty($apiKey)) {
                Log::warning('Resend API key is not configured — skipping temp password email', [
                    'email' => $email,
                ]);

                return false;
            }

            $html = '
            <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
                <div style="background: linear-gradient(135deg, #1e3a5f 0%, #2d5a87 100%); padding: 30px; border-radius: 10px 10px 0 0; text-align: center;">
                    <h1 style="color: #ffffff; margin: 0; font-size: 28px;">Aurora</h1>
                    <p style="color: #a0c4e8; margin-top: 5px;">Healthcare Collaboration Platform</p>
                </div>
                <div style="background: #ffffff; padding: 30px; border: 1px solid #e0e0e0; border-top: none; border-radius: 0 0 10px 10px;">
                    <h2 style="color: #1e3a5f; margin-top: 0;">Welcome, '.htmlspecialchars($name, ENT_QUOTES, 'UTF-8').'!</h2>
                    <p style="color: #333;">Your Aurora account has been created. Use the temporary password below to log in:</p>
                    <div style="background: #f5f7fa; border: 2px dashed #2d5a87; border-radius: 8px; padding: 20px; text-align: center; margin: 20px 0;">
                        <p style="color: #666; margin: 0 0 5px 0; font-size: 12px; text-transform: uppercase;">Temporary Password</p>
                        <p style="color: #1e3a5f; font-size: 24px; font-weight: bold; margin: 0; letter-spacing: 2px; font-family: monospace;">'.htmlspecialchars($tempPassword, ENT_QUOTES, 'UTF-8').'</p>
                    </div>
                    <p style="color: #e74c3c; font-weight: bold;">You will be required to change this password upon your first login.</p>
                    <p style="color: #333;">If you did not request this account, please ignore this email.</p>
                    <hr style="border: none; border-top: 1px solid #e0e0e0; margin: 20px 0;">
                    <p style="color: #999; font-size: 12px; text-align: center;">This is an automated message from Aurora. Please do not reply.</p>
                </div>
            </div>';

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$apiKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.resend.com/emails', [
                'from' => 'Aurora <noreply@acumenus.net>',
                'to' => [$email],
                'subject' => 'Your Aurora access credentials',
                'html' => $html,
            ]);

            if ($response->successful()) {
                Log::info('Temp password email sent successfully', ['email' => $email]);

                return true;
            }

            Log::error('Failed to send temp password email', [
                'email' => $email,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Exception sending temp password email', [
                'email' => $email,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
