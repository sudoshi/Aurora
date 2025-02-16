<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\CaseDiscussionController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Event routes (temporarily public for testing)
Route::apiResource('events', EventController::class);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // User routes
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('/logout', [AuthController::class, 'logout']);

    // Case Discussion routes
    Route::prefix('cases/{caseId}')->group(function () {
        Route::get('/discussions', [CaseDiscussionController::class, 'index']);
        Route::post('/discussions', [CaseDiscussionController::class, 'store']);
        Route::post('/attachments', [CaseDiscussionController::class, 'uploadAttachments']);
    });
});
