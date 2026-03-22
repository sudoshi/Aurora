<?php

namespace App\Http\Controllers;

use App\Http\Helpers\ApiResponse;
use App\Models\Session;
use App\Models\SessionCase;
use App\Models\SessionParticipant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SessionController extends Controller
{
    /**
     * GET /api/sessions
     * List sessions (paginated, filterable by status/type, upcoming first).
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'status' => 'sometimes|string|in:scheduled,live,completed,cancelled',
            'session_type' => 'sometimes|string|in:tumor_board,mdc,surgical_planning,grand_rounds,ad_hoc',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        $query = Session::with(['creator:id,name'])
            ->withCount(['sessionCases', 'participants'])
            ->when($request->input('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->input('session_type'), fn ($q, $type) => $q->byType($type))
            ->orderByRaw("CASE WHEN status = 'live' THEN 0 WHEN status = 'scheduled' THEN 1 ELSE 2 END")
            ->orderBy('scheduled_at');

        $sessions = $query->paginate((int) $request->input('per_page', 20));

        return ApiResponse::paginated($sessions, 'Sessions retrieved');
    }

    /**
     * POST /api/sessions
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'scheduled_at' => 'required|date|after:now',
            'duration_minutes' => 'sometimes|integer|min:5|max:480',
            'session_type' => 'required|string|in:tumor_board,mdc,surgical_planning,grand_rounds,ad_hoc',
            'institution_id' => 'nullable|integer',
            'notes' => 'nullable|string',
        ]);

        $validated['created_by'] = $request->user()->id;
        $validated['status'] = 'scheduled';

        $session = Session::create($validated);
        $session->load('creator:id,name');

        return ApiResponse::success($session, 'Session created', 201);
    }

    /**
     * GET /api/sessions/{session}
     */
    public function show(Session $session): JsonResponse
    {
        $session->load([
            'sessionCases.clinicalCase',
            'sessionCases.presenter:id,name',
            'participants.user:id,name,email',
            'creator:id,name',
        ]);

        return ApiResponse::success($session, 'Session retrieved');
    }

    /**
     * PUT/PATCH /api/sessions/{session}
     */
    public function update(Request $request, Session $session): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'scheduled_at' => 'sometimes|date',
            'duration_minutes' => 'sometimes|integer|min:5|max:480',
            'session_type' => 'sometimes|string|in:tumor_board,mdc,surgical_planning,grand_rounds,ad_hoc',
            'institution_id' => 'nullable|integer',
            'notes' => 'nullable|string',
        ]);

        $session->update($validated);
        $session->load('creator:id,name');

        return ApiResponse::success($session, 'Session updated');
    }

    /**
     * DELETE /api/sessions/{session}
     */
    public function destroy(Session $session): JsonResponse
    {
        $session->delete();

        return ApiResponse::success(null, 'Session deleted');
    }

    /**
     * POST /api/sessions/{session}/start
     */
    public function start(Session $session): JsonResponse
    {
        if ($session->status !== 'scheduled') {
            return ApiResponse::error('Only scheduled sessions can be started', 422);
        }

        $session->update([
            'status' => 'live',
            'started_at' => now(),
        ]);

        return ApiResponse::success($session, 'Session started');
    }

    /**
     * POST /api/sessions/{session}/end
     */
    public function end(Session $session): JsonResponse
    {
        if ($session->status !== 'live') {
            return ApiResponse::error('Only live sessions can be ended', 422);
        }

        $session->update([
            'status' => 'completed',
            'ended_at' => now(),
        ]);

        return ApiResponse::success($session, 'Session ended');
    }

    /**
     * POST /api/sessions/{session}/cases
     */
    public function addCase(Request $request, Session $session): JsonResponse
    {
        $validated = $request->validate([
            'case_id' => 'required|integer|exists:app.cases,id',
            'order' => 'sometimes|integer|min:0',
            'presenter_id' => 'nullable|integer|exists:app.users,id',
            'time_allotted_minutes' => 'sometimes|integer|min:1|max:120',
        ]);

        $validated['session_id'] = $session->id;

        $existing = SessionCase::where('session_id', $session->id)
            ->where('case_id', $validated['case_id'])
            ->exists();

        if ($existing) {
            return ApiResponse::error('Case already added to this session', 422);
        }

        $sessionCase = SessionCase::create($validated);
        $sessionCase->load('clinicalCase', 'presenter:id,name');

        return ApiResponse::success($sessionCase, 'Case added to session', 201);
    }

    /**
     * PATCH /api/sessions/{session}/cases/{sessionCase}
     */
    public function updateCase(Request $request, Session $session, SessionCase $sessionCase): JsonResponse
    {
        if ($sessionCase->session_id !== $session->id) {
            return ApiResponse::error('Session case does not belong to this session', 404);
        }

        $validated = $request->validate([
            'order' => 'sometimes|integer|min:0',
            'presenter_id' => 'nullable|integer|exists:app.users,id',
            'time_allotted_minutes' => 'sometimes|integer|min:1|max:120',
            'status' => 'sometimes|string|in:pending,presenting,discussed,skipped',
        ]);

        $sessionCase->update($validated);

        return ApiResponse::success($sessionCase, 'Session case updated');
    }

    /**
     * DELETE /api/sessions/{session}/cases/{sessionCase}
     */
    public function removeCase(Session $session, SessionCase $sessionCase): JsonResponse
    {
        if ($sessionCase->session_id !== $session->id) {
            return ApiResponse::error('Session case does not belong to this session', 404);
        }

        $sessionCase->delete();

        return ApiResponse::success(null, 'Case removed from session');
    }

    /**
     * POST /api/sessions/{session}/join
     */
    public function join(Request $request, Session $session): JsonResponse
    {
        $validated = $request->validate([
            'role' => 'sometimes|string|in:moderator,presenter,reviewer,observer',
        ]);

        $userId = $request->user()->id;

        $existing = SessionParticipant::where('session_id', $session->id)
            ->where('user_id', $userId)
            ->first();

        if ($existing) {
            return ApiResponse::error('Already joined this session', 422);
        }

        $participant = SessionParticipant::create([
            'session_id' => $session->id,
            'user_id' => $userId,
            'role' => $validated['role'] ?? 'observer',
            'joined_at' => now(),
        ]);

        $participant->load('user:id,name,email');

        return ApiResponse::success($participant, 'Joined session', 201);
    }

    /**
     * POST /api/sessions/{session}/leave
     */
    public function leave(Request $request, Session $session): JsonResponse
    {
        $participant = SessionParticipant::where('session_id', $session->id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$participant) {
            return ApiResponse::error('Not a participant in this session', 404);
        }

        $participant->update(['left_at' => now()]);

        return ApiResponse::success($participant, 'Left session');
    }
}
