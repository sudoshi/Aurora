<?php

namespace App\Http\Controllers;

use App\Http\Helpers\ApiResponse;
use App\Models\CaseDiscussion;
use App\Models\ClinicalCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CaseDiscussionController extends Controller
{
    /**
     * GET /api/cases/{case}/discussions
     * List discussions for a case (threaded: top-level with nested replies).
     */
    public function index(int $case): JsonResponse
    {
        $clinicalCase = ClinicalCase::find($case);

        if (! $clinicalCase) {
            return ApiResponse::error('Case not found', 404);
        }

        $discussions = CaseDiscussion::where('case_id', $case)
            ->whereNull('parent_id')
            ->with(['user', 'attachments', 'replies.user', 'replies.attachments'])
            ->orderBy('created_at', 'asc')
            ->get();

        return ApiResponse::success($discussions, 'Discussions retrieved');
    }

    /**
     * POST /api/cases/{case}/discussions
     * Create a discussion post (with optional parent_id for threading).
     */
    public function store(Request $request, int $case): JsonResponse
    {
        $clinicalCase = ClinicalCase::find($case);

        if (! $clinicalCase) {
            return ApiResponse::error('Case not found', 404);
        }

        $validated = $request->validate([
            'content' => 'required|string|max:5000',
            'parent_id' => 'nullable|integer|exists:app.case_discussions,id',
        ]);

        // If parent_id is provided, verify it belongs to this case
        if (! empty($validated['parent_id'])) {
            $parent = CaseDiscussion::where('id', $validated['parent_id'])
                ->where('case_id', $case)
                ->first();

            if (! $parent) {
                return ApiResponse::error('Parent discussion not found in this case', 422);
            }
        }

        $discussion = CaseDiscussion::create([
            'case_id' => $case,
            'user_id' => $request->user()->id,
            'parent_id' => $validated['parent_id'] ?? null,
            'content' => $validated['content'],
        ]);

        return ApiResponse::success(
            $discussion->load(['user', 'attachments']),
            'Discussion created',
            201,
        );
    }
}
