<?php

namespace App\Http\Controllers;

use App\Http\Helpers\ApiResponse;
use App\Models\CaseAnnotation;
use App\Models\ClinicalCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CaseAnnotationController extends Controller
{
    /**
     * GET /api/cases/{case}/annotations
     * List annotations for a case.
     */
    public function index(int $case): JsonResponse
    {
        $clinicalCase = ClinicalCase::find($case);

        if (! $clinicalCase) {
            return ApiResponse::error('Case not found', 404);
        }

        $annotations = CaseAnnotation::where('case_id', $case)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        return ApiResponse::success($annotations, 'Annotations retrieved');
    }

    /**
     * POST /api/cases/{case}/annotations
     * Create an annotation.
     */
    public function store(Request $request, int $case): JsonResponse
    {
        $clinicalCase = ClinicalCase::find($case);

        if (! $clinicalCase) {
            return ApiResponse::error('Case not found', 404);
        }

        $validated = $request->validate([
            'domain' => 'required|string|in:condition,medication,procedure,measurement,observation,imaging,genomic,general',
            'record_ref' => 'nullable|string|max:255',
            'content' => 'required|string|max:5000',
            'anchored_to' => 'nullable|array',
            'anchored_to.type' => 'required_with:anchored_to|string',
            'anchored_to.id' => 'required_with:anchored_to|integer',
        ]);

        $annotation = CaseAnnotation::create([
            'case_id' => $case,
            'user_id' => $request->user()->id,
            'domain' => $validated['domain'],
            'record_ref' => $validated['record_ref'] ?? null,
            'content' => $validated['content'],
            'anchored_to' => $validated['anchored_to'] ?? null,
        ]);

        return ApiResponse::success(
            $annotation->load('user'),
            'Annotation created',
            201,
        );
    }
}
