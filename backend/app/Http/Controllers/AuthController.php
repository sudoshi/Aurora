<?php

namespace App\Http\Controllers;

use App\Http\Helpers\ApiResponse;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
    ) {}

    /**
     * Register a new user.
     *
     * Generates a temporary password, creates the account, and emails
     * the credentials via Resend. Returns the same success message
     * regardless of whether the email already exists (enumeration
     * prevention).
     */
    public function register(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'name'  => 'required|string|max:255',
                'email' => 'required|string|email|max:255',
                'phone' => 'nullable|string|max:20',
            ]);

            $result = $this->authService->register($validatedData);

            return ApiResponse::success(null, $result['message']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ApiResponse::error('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            Log::error('Registration error', ['message' => $e->getMessage()]);
            return ApiResponse::error(
                'An unexpected error occurred. Please try again later.',
                500
            );
        }
    }

    /**
     * Login user and create token.
     */
    public function login(Request $request): JsonResponse
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

            $result = $this->authService->login($credentials);

            return response()->json($result);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ApiResponse::error('Validation failed', 422, $e->errors());
        } catch (\RuntimeException $e) {
            $code = $e->getCode();
            // Ensure we use a valid HTTP status code
            $statusCode = ($code >= 400 && $code < 600) ? $code : 401;
            return ApiResponse::error($e->getMessage(), $statusCode);
        } catch (\Exception $e) {
            Log::error('Login error', ['message' => $e->getMessage()]);
            return ApiResponse::error('An unexpected error occurred', 500);
        }
    }

    /**
     * Get authenticated user with formatted data.
     */
    public function user(Request $request): JsonResponse
    {
        $user = $request->user();
        $formatted = $this->authService->formatUser($user);

        return response()->json($formatted);
    }

    /**
     * Change password for authenticated user.
     */
    public function changePassword(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'current_password' => 'required|string',
                'password'         => 'required|string|min:8|confirmed',
            ]);

            $result = $this->authService->changePassword(
                $request->user(),
                $validatedData['current_password'],
                $validatedData['password'],
            );

            return response()->json($result);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ApiResponse::error('Validation failed', 422, $e->errors());
        } catch (\RuntimeException $e) {
            $code = $e->getCode();
            $statusCode = ($code >= 400 && $code < 600) ? $code : 422;
            return ApiResponse::error($e->getMessage(), $statusCode);
        } catch (\Exception $e) {
            Log::error('Change password error', ['message' => $e->getMessage()]);
            return ApiResponse::error('An unexpected error occurred.', 500);
        }
    }

    /**
     * Logout user (revoke all tokens).
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return ApiResponse::success(null, 'Successfully logged out');
    }
}
