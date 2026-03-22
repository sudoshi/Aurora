<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\Commons\ChannelController;
use App\Http\Controllers\Commons\MessageController;
use App\Http\Controllers\Commons\MemberController;
use App\Http\Controllers\Commons\AnnouncementController;
use App\Http\Controllers\Commons\AttachmentController;
use App\Http\Controllers\Commons\NotificationController;
use App\Http\Controllers\Commons\PinController;
use App\Http\Controllers\Commons\ReactionController;
use App\Http\Controllers\Commons\WikiController;
use App\Http\Controllers\Commons\ActivityController;
use App\Http\Controllers\Commons\DirectMessageController;
use App\Http\Controllers\Commons\ObjectReferenceController;
use App\Http\Controllers\Commons\ReviewRequestController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\DecisionController;
use App\Http\Controllers\CaseController;
use App\Http\Controllers\CaseDiscussionController;
use App\Http\Controllers\CaseAnnotationController;
use App\Http\Controllers\CaseDocumentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\AiProviderController;
use App\Http\Controllers\Admin\SystemHealthController;
use App\Http\Controllers\Admin\UserAuditController;
use App\Http\Controllers\AbbyController;
use App\Http\Controllers\AiProxyController;
use App\Http\Controllers\Admin\AppSettingsController;
use App\Http\Controllers\ImagingController;

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

// Auth (public — tightly throttled)
Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:3,1');
Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

// Auth (protected)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/user', [AuthController::class, 'user']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/change-password', [AuthController::class, 'changePassword']);

    // Dashboard
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);

    // AI Service Proxy (forwards to FastAPI — rate limited: 30/min)
    Route::prefix('ai')->middleware('throttle:30,1')->group(function () {
        Route::post('{path}', [AiProxyController::class, 'proxy'])->where('path', '.*');
        Route::get('{path}', [AiProxyController::class, 'proxyGet'])->where('path', '.*');
    });

    // Abby AI (conversation CRUD — handled by Laravel directly)
    Route::prefix('abby')->group(function () {
        Route::get('/conversations', [AbbyController::class, 'conversations']);
        Route::post('/conversations', [AbbyController::class, 'createConversation']);
        Route::get('/conversations/{id}', [AbbyController::class, 'showConversation']);
        Route::delete('/conversations/{id}', [AbbyController::class, 'deleteConversation']);
        Route::post('/chat', [AbbyController::class, 'chat']);
        Route::post('/conversations/{id}/title', [AbbyController::class, 'generateTitle']);
    });

    // Patient routes
    Route::prefix('patients')->group(function () {
        Route::get('/search', [PatientController::class, 'search']);
        Route::get('/{patient}/profile', [PatientController::class, 'profile']);
        Route::get('/{patient}/stats', [PatientController::class, 'stats']);
        Route::post('/', [PatientController::class, 'store']);
    });

    // ── Imaging ──────────────────────────────────────────────────────────
    Route::prefix('patients/{patient}/imaging')->group(function () {
        Route::get('/', [ImagingController::class, 'index']);
        Route::get('/response-assessments', [ImagingController::class, 'responseAssessments']);
        Route::get('/{study}', [ImagingController::class, 'show']);
        Route::post('/{study}/measurements', [ImagingController::class, 'storeMeasurement']);
    });

    // ── Cases ─────────────────────────────────────────────────────────────
    Route::apiResource('cases', CaseController::class);
    Route::post('cases/{case}/team', [CaseController::class, 'addTeamMember']);
    Route::delete('cases/{case}/team/{user}', [CaseController::class, 'removeTeamMember']);

    // Case sub-resources
    Route::get('cases/{case}/discussions', [CaseDiscussionController::class, 'index']);
    Route::post('cases/{case}/discussions', [CaseDiscussionController::class, 'store']);
    Route::get('cases/{case}/annotations', [CaseAnnotationController::class, 'index']);
    Route::post('cases/{case}/annotations', [CaseAnnotationController::class, 'store']);
    Route::get('cases/{case}/documents', [CaseDocumentController::class, 'index']);
    Route::post('cases/{case}/documents', [CaseDocumentController::class, 'store'])->middleware('throttle:10,1');
    Route::delete('documents/{document}', [CaseDocumentController::class, 'destroy']);

    // ── Sessions ─────────────────────────────────────────────────────────
    Route::apiResource('sessions', SessionController::class);
    Route::post('sessions/{session}/start', [SessionController::class, 'start']);
    Route::post('sessions/{session}/end', [SessionController::class, 'end']);
    Route::post('sessions/{session}/cases', [SessionController::class, 'addCase']);
    Route::patch('sessions/{session}/cases/{sessionCase}', [SessionController::class, 'updateCase']);
    Route::delete('sessions/{session}/cases/{sessionCase}', [SessionController::class, 'removeCase']);
    Route::post('sessions/{session}/join', [SessionController::class, 'join']);
    Route::post('sessions/{session}/leave', [SessionController::class, 'leave']);

    // ── Decisions ────────────────────────────────────────────────────────
    Route::get('cases/{case}/decisions', [DecisionController::class, 'index']);
    Route::post('cases/{case}/decisions', [DecisionController::class, 'store']);
    Route::patch('decisions/{decision}', [DecisionController::class, 'update']);
    Route::post('decisions/{decision}/vote', [DecisionController::class, 'vote']);
    Route::post('decisions/{decision}/finalize', [DecisionController::class, 'finalize']);
    Route::post('decisions/{decision}/follow-ups', [DecisionController::class, 'addFollowUp']);
    Route::patch('follow-ups/{followUp}', [DecisionController::class, 'updateFollowUp']);

    // ── Commons Workspace ────────────────────────────────────────────────
    Route::prefix('commons')->group(function () {
        // Channels
        Route::get('channels', [ChannelController::class, 'index']);
        Route::post('channels', [ChannelController::class, 'store']);
        Route::get('channels/unread', [MemberController::class, 'unreadCounts']);
        Route::get('channels/{slug}', [ChannelController::class, 'show']);
        Route::patch('channels/{slug}', [ChannelController::class, 'update']);
        Route::post('channels/{slug}/archive', [ChannelController::class, 'archive']);

        // Messages
        Route::get('channels/{slug}/messages', [MessageController::class, 'index']);
        Route::post('channels/{slug}/messages', [MessageController::class, 'store']);
        Route::get('messages/search', [MessageController::class, 'search']);
        Route::patch('messages/{id}', [MessageController::class, 'update']);
        Route::delete('messages/{id}', [MessageController::class, 'destroy']);
        Route::get('channels/{slug}/messages/{messageId}/replies', [MessageController::class, 'replies']);

        // Members
        Route::get('channels/{slug}/members', [MemberController::class, 'index']);
        Route::post('channels/{slug}/members', [MemberController::class, 'store']);
        Route::delete('channels/{slug}/members/{memberId}', [MemberController::class, 'destroy']);
        Route::patch('channels/{slug}/members/{memberId}', [MemberController::class, 'updatePreference']);
        Route::post('channels/{slug}/read', [MemberController::class, 'markRead']);

        // Reactions
        Route::post('messages/{id}/reactions', [ReactionController::class, 'toggle']);

        // Pinned messages
        Route::get('channels/{slug}/pins', [PinController::class, 'index']);
        Route::post('channels/{slug}/pins', [PinController::class, 'store']);
        Route::delete('channels/{slug}/pins/{pinId}', [PinController::class, 'destroy']);

        // Direct messages
        Route::get('dm', [DirectMessageController::class, 'index']);
        Route::post('dm', [DirectMessageController::class, 'store']);

        // Object references
        Route::get('objects/search', [ObjectReferenceController::class, 'search']);
        Route::get('objects/{type}/{id}/discussions', [ObjectReferenceController::class, 'discussions']);

        // File attachments
        Route::post('channels/{slug}/attachments', [AttachmentController::class, 'store']);
        Route::get('attachments/{id}/download', [AttachmentController::class, 'download']);
        Route::delete('attachments/{id}', [AttachmentController::class, 'destroy']);

        // Review requests
        Route::get('channels/{slug}/reviews', [ReviewRequestController::class, 'index']);
        Route::post('channels/{slug}/reviews', [ReviewRequestController::class, 'store']);
        Route::patch('reviews/{id}/resolve', [ReviewRequestController::class, 'resolve']);

        // Notifications
        Route::get('notifications', [NotificationController::class, 'index']);
        Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount']);
        Route::post('notifications/mark-read', [NotificationController::class, 'markRead']);

        // Activity feed
        Route::get('activities', [ActivityController::class, 'global']);
        Route::get('channels/{slug}/activities', [ActivityController::class, 'index']);

        // Announcements
        Route::get('announcements', [AnnouncementController::class, 'index']);
        Route::post('announcements', [AnnouncementController::class, 'store']);
        Route::patch('announcements/{id}', [AnnouncementController::class, 'update']);
        Route::delete('announcements/{id}', [AnnouncementController::class, 'destroy']);
        Route::post('announcements/{id}/bookmark', [AnnouncementController::class, 'bookmark']);

        // Wiki / Knowledge Base
        Route::get('wiki', [WikiController::class, 'index']);
        Route::post('wiki', [WikiController::class, 'store']);
        Route::get('wiki/{slug}', [WikiController::class, 'show']);
        Route::patch('wiki/{slug}', [WikiController::class, 'update']);
        Route::delete('wiki/{slug}', [WikiController::class, 'destroy']);
        Route::get('wiki/{slug}/revisions', [WikiController::class, 'revisions']);
    });

    // ── App Settings ─────────────────────────────────────────────────────
    Route::get('/app-settings', [AppSettingsController::class, 'index']);
    Route::patch('/app-settings', [AppSettingsController::class, 'update'])->middleware('role:super-admin');

    // ── Admin Panel (requires admin or super-admin role) ─────────────────
    Route::prefix('admin')->middleware('role:admin|super-admin')->group(function () {

        // User management
        Route::get('/users', [AdminUserController::class, 'index']);
        Route::post('/users', [AdminUserController::class, 'store']);
        Route::get('/users/roles', [AdminUserController::class, 'roles']);
        Route::get('/users/{user}', [AdminUserController::class, 'show']);
        Route::put('/users/{user}', [AdminUserController::class, 'update']);
        Route::delete('/users/{user}', [AdminUserController::class, 'destroy']);
        Route::put('/users/{user}/roles', [AdminUserController::class, 'syncRoles']);
        Route::get('/users/{user}/audit', [UserAuditController::class, 'forUser']);

        // User Audit Log
        Route::prefix('user-audit')->group(function () {
            Route::get('/', [UserAuditController::class, 'index']);
            Route::get('/summary', [UserAuditController::class, 'summary']);
        });

        // Role & permission management (super-admin only)
        Route::middleware('role:super-admin')->group(function () {
            Route::get('/roles', [RoleController::class, 'index']);
            Route::post('/roles', [RoleController::class, 'store']);
            Route::get('/roles/permissions', [RoleController::class, 'permissions']);
            Route::get('/roles/{role}', [RoleController::class, 'show']);
            Route::put('/roles/{role}', [RoleController::class, 'update']);
            Route::delete('/roles/{role}', [RoleController::class, 'destroy']);
        });

        // AI provider configuration (super-admin only)
        Route::middleware('role:super-admin')->prefix('ai-providers')->group(function () {
            Route::get('/', [AiProviderController::class, 'index']);
            Route::get('/{type}', [AiProviderController::class, 'show']);
            Route::put('/{type}', [AiProviderController::class, 'update']);
            Route::post('/{type}/enable', [AiProviderController::class, 'enable']);
            Route::post('/{type}/disable', [AiProviderController::class, 'disable']);
            Route::post('/{type}/activate', [AiProviderController::class, 'activate']);
            Route::post('/{type}/test', [AiProviderController::class, 'test']);
        });

        // System health (admin+)
        Route::get('/system-health', [SystemHealthController::class, 'index']);
        Route::get('/system-health/{key}', [SystemHealthController::class, 'show']);
    });
});
