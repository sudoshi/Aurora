<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    /**
     * Generate a random temporary password.
     *
     * Produces a 12-character string using characters that exclude
     * visually ambiguous glyphs (I, l, O, 0).
     */
    private function generateTempPassword(int $length = 12): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz123456789!@#$%^&*';
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
                Log::error('Resend API key is not configured');
                return false;
            }

            $html = '
            <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
                <div style="background: linear-gradient(135deg, #1e3a5f 0%, #2d5a87 100%); padding: 30px; border-radius: 10px 10px 0 0; text-align: center;">
                    <h1 style="color: #ffffff; margin: 0; font-size: 28px;">Aurora</h1>
                    <p style="color: #a0c4e8; margin-top: 5px;">Healthcare Collaboration Platform</p>
                </div>
                <div style="background: #ffffff; padding: 30px; border: 1px solid #e0e0e0; border-top: none; border-radius: 0 0 10px 10px;">
                    <h2 style="color: #1e3a5f; margin-top: 0;">Welcome, ' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '!</h2>
                    <p style="color: #333;">Your Aurora account has been created. Use the temporary password below to log in:</p>
                    <div style="background: #f5f7fa; border: 2px dashed #2d5a87; border-radius: 8px; padding: 20px; text-align: center; margin: 20px 0;">
                        <p style="color: #666; margin: 0 0 5px 0; font-size: 12px; text-transform: uppercase;">Temporary Password</p>
                        <p style="color: #1e3a5f; font-size: 24px; font-weight: bold; margin: 0; letter-spacing: 2px; font-family: monospace;">' . htmlspecialchars($tempPassword, ENT_QUOTES, 'UTF-8') . '</p>
                    </div>
                    <p style="color: #e74c3c; font-weight: bold;">You will be required to change this password upon your first login.</p>
                    <p style="color: #333;">If you did not request this account, please ignore this email.</p>
                    <hr style="border: none; border-top: 1px solid #e0e0e0; margin: 20px 0;">
                    <p style="color: #999; font-size: 12px; text-align: center;">This is an automated message from Aurora. Please do not reply.</p>
                </div>
            </div>';

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type'  => 'application/json',
            ])->post('https://api.resend.com/emails', [
                'from'    => 'Aurora <noreply@acumenus.net>',
                'to'      => [$email],
                'subject' => 'Your Aurora access credentials',
                'html'    => $html,
            ]);

            if ($response->successful()) {
                Log::info('Temp password email sent successfully', ['email' => $email]);
                return true;
            }

            Log::error('Failed to send temp password email', [
                'email'    => $email,
                'status'   => $response->status(),
                'response' => $response->body(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Exception sending temp password email', [
                'email'   => $email,
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Register a new user.
     *
     * Generates a temporary password, creates the account, and emails
     * the credentials via Resend. Returns the same success message
     * regardless of whether the email already exists (enumeration
     * prevention).
     */
    public function register(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'name'  => 'required|string|max:255',
                'email' => 'required|string|email|max:255',
                'phone' => 'nullable|string|max:20',
            ]);

            $successMessage = 'If your email is not already registered, you will receive your login credentials shortly. Please check your inbox.';

            // Check if user already exists — return same message to prevent enumeration
            $existingUser = User::where('email', $validatedData['email'])->first();
            if ($existingUser) {
                return response()->json([
                    'message' => $successMessage,
                ], 201);
            }

            // Generate temp password
            $tempPassword = $this->generateTempPassword();

            // Create user
            $user = User::create([
                'name'                 => $validatedData['name'],
                'email'                => $validatedData['email'],
                'phone'                => $validatedData['phone'] ?? null,
                'password'             => Hash::make($tempPassword),
                'must_change_password' => true,
                'is_active'            => true,
            ]);

            // Send temp password via email
            $this->sendTempPasswordEmail($user->email, $user->name, $tempPassword);

            return response()->json([
                'message' => $successMessage,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Registration error', ['message' => $e->getMessage()]);
            return response()->json([
                'message' => 'An unexpected error occurred. Please try again later.',
            ], 500);
        }
    }

    /**
     * Login user and create token.
     */
    public function login(Request $request)
    {
        try {
            $credentials = $request->validate([
                'email'    => 'required|string|email',
                'password' => 'required|string',
            ], [
                'email.required'    => 'Email is required',
                'email.email'       => 'Please enter a valid email address',
                'password.required' => 'Password is required',
            ]);

            if (!Auth::attempt($credentials)) {
                return response()->json([
                    'message' => 'The provided credentials do not match our records',
                ], 401);
            }

            $user = Auth::user();

            // Check if account is active
            if ($user->is_active === false) {
                Auth::logout();
                return response()->json([
                    'message' => 'Your account has been deactivated. Please contact support.',
                ], 403);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'access_token' => $token,
                'user'         => $user,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An unexpected error occurred',
            ], 500);
        }
    }

    /**
     * Change password for authenticated user.
     */
    public function changePassword(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'current_password' => 'required|string',
                'new_password'     => 'required|string|min:8',
            ]);

            $user = $request->user();

            // Verify current password
            if (!Hash::check($validatedData['current_password'], $user->password)) {
                return response()->json([
                    'message' => 'Current password is incorrect.',
                ], 422);
            }

            // Ensure new password differs from current
            if (Hash::check($validatedData['new_password'], $user->password)) {
                return response()->json([
                    'message' => 'New password must be different from your current password.',
                ], 422);
            }

            // Update password and clear the must_change_password flag
            $user->password = Hash::make($validatedData['new_password']);
            $user->must_change_password = false;
            $user->save();

            // Revoke all existing tokens and issue a new one
            $user->tokens()->delete();
            $token = $user->createToken('auth_token')->plainTextToken;

            // Refresh user data
            $user->refresh();

            return response()->json([
                'message'      => 'Password changed successfully.',
                'access_token' => $token,
                'user'         => $user,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Change password error', ['message' => $e->getMessage()]);
            return response()->json([
                'message' => 'An unexpected error occurred.',
            ], 500);
        }
    }

    /**
     * Logout user (invalidate token).
     */
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Successfully logged out',
        ]);
    }

    /**
     * Get authenticated user.
     */
    public function user(Request $request)
    {
        return response()->json($request->user());
    }
}
