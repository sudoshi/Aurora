<?php

namespace App\Http\Controllers;

use App\Http\Helpers\ApiResponse;
use App\Models\ClinicalCase;
use App\Models\Decision;
use App\Models\DecisionVote;
use App\Models\FollowUp;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DecisionController extends Controller
{
    /**
     * GET /api/decisions/dashboard — all decisions across cases
     */
    public function dashboard(Request $request): JsonResponse
    {
        $decisions = Decision::with(['proposer:id,name', 'clinicalCase:id,title'])
            ->withCount(['votes', 'followUps'])
            ->orderByDesc('created_at')
            ->paginate((int) $request->input('per_page', 20));

        return ApiResponse::success($decisions, 'Decisions retrieved');
    }

    /**
     * GET /api/cases/{case}/decisions
     */
    public function index(Request $request, int $case): JsonResponse
    {
        $clinicalCase = ClinicalCase::find($case);

        if (! $clinicalCase) {
            return ApiResponse::error('Case not found', 404);
        }

        $decisions = Decision::where('case_id', $case)
            ->with(['proposer:id,name'])
            ->withCount(['votes', 'followUps'])
            ->orderByDesc('created_at')
            ->paginate((int) $request->input('per_page', 20));

        return ApiResponse::paginated($decisions, 'Decisions retrieved');
    }

    /**
     * POST /api/cases/{case}/decisions
     */
    public function store(Request $request, int $case): JsonResponse
    {
        $clinicalCase = ClinicalCase::find($case);

        if (! $clinicalCase) {
            return ApiResponse::error('Case not found', 404);
        }

        $validated = $request->validate([
            'session_id' => 'nullable|integer|exists:app.sessions,id',
            'decision_type' => 'required|string|in:treatment_recommendation,diagnostic_workup,referral,monitoring_plan,palliative,other',
            'recommendation' => 'required|string',
            'rationale' => 'nullable|string',
            'guideline_reference' => 'nullable|string|max:255',
            'urgency' => 'sometimes|string|in:routine,urgent,emergent',
        ]);

        $validated['case_id'] = $case;
        $validated['proposed_by'] = $request->user()->id;
        $validated['status'] = 'proposed';

        $decision = Decision::create($validated);
        $decision->load('proposer:id,name', 'session:id,title');

        return ApiResponse::success($decision, 'Decision proposed', 201);
    }

    /**
     * PATCH /api/decisions/{decision}
     */
    public function update(Request $request, Decision $decision): JsonResponse
    {
        $validated = $request->validate([
            'recommendation' => 'sometimes|string',
            'rationale' => 'nullable|string',
            'guideline_reference' => 'nullable|string|max:255',
            'decision_type' => 'sometimes|string|in:treatment_recommendation,diagnostic_workup,referral,monitoring_plan,palliative,other',
            'urgency' => 'sometimes|string|in:routine,urgent,emergent',
        ]);

        $decision->update($validated);
        $decision->load('proposer:id,name');

        return ApiResponse::success($decision, 'Decision updated');
    }

    /**
     * POST /api/decisions/{decision}/vote
     */
    public function vote(Request $request, Decision $decision): JsonResponse
    {
        $validated = $request->validate([
            'vote' => 'required|string|in:agree,disagree,abstain',
            'comment' => 'nullable|string',
        ]);

        $userId = $request->user()->id;

        $vote = DecisionVote::updateOrCreate(
            [
                'decision_id' => $decision->id,
                'user_id' => $userId,
            ],
            [
                'vote' => $validated['vote'],
                'comment' => $validated['comment'] ?? null,
            ],
        );

        $vote->load('user:id,name');

        return ApiResponse::success($vote, 'Vote recorded');
    }

    /**
     * POST /api/decisions/{decision}/finalize
     */
    public function finalize(Request $request, Decision $decision): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|string|in:approved,rejected,deferred',
        ]);

        $decision->update([
            'status' => $validated['status'],
            'finalized_at' => now(),
            'finalized_by' => $request->user()->id,
        ]);

        $decision->load('finalizer:id,name', 'votes.user:id,name');

        return ApiResponse::success($decision, 'Decision finalized');
    }

    /**
     * POST /api/decisions/{decision}/follow-ups
     */
    public function addFollowUp(Request $request, Decision $decision): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'assigned_to' => 'nullable|integer|exists:app.users,id',
            'due_date' => 'nullable|date',
        ]);

        $validated['decision_id'] = $decision->id;
        $validated['status'] = 'pending';

        $followUp = FollowUp::create($validated);
        $followUp->load('assignee:id,name');

        return ApiResponse::success($followUp, 'Follow-up created', 201);
    }

    /**
     * PATCH /api/follow-ups/{followUp}
     */
    public function updateFollowUp(Request $request, FollowUp $followUp): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'assigned_to' => 'nullable|integer|exists:app.users,id',
            'due_date' => 'nullable|date',
            'status' => 'sometimes|string|in:pending,in_progress,completed,cancelled',
        ]);

        if (isset($validated['status']) && $validated['status'] === 'completed' && $followUp->status !== 'completed') {
            $validated['completed_at'] = now();
        }

        $followUp->update($validated);
        $followUp->load('assignee:id,name');

        return ApiResponse::success($followUp, 'Follow-up updated');
    }
}
