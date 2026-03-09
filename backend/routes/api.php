<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PatientController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Health check
Route::get('/health', fn () => response()->json([
    'status' => 'ok',
    'service' => 'aurora-api',
    'version' => '2.0.0',
    'timestamp' => now()->toISOString(),
]));

// Auth (public)
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Auth (protected)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/user', [AuthController::class, 'user']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/change-password', [AuthController::class, 'changePassword']);

    // Patient routes
    Route::prefix('patients')->group(function () {
        Route::get('/search', [PatientController::class, 'search']);
        Route::get('/{patient}/profile', [PatientController::class, 'profile']);
        Route::get('/{patient}/stats', [PatientController::class, 'stats']);
        Route::post('/', [PatientController::class, 'store']);
    });
});
