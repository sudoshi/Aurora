<?php

namespace App\Http\Controllers;

use App\Http\Helpers\ApiResponse;
use App\Models\CaseTemplate;
use App\Models\ClinicalCase;
use App\Services\BoardTemplateService;
use App\Services\CaseService;
use App\Services\CaseStateMachine;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CaseController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly CaseService $caseService,
        private readonly BoardTemplateService $boardTemplateService,
        private readonly CaseStateMachine $caseStateMachine,
    ) {}

    /**
     * GET /api/cases
     * List cases for the authenticated user (paginated, filterable).
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'status' => 'sometimes|string|in:draft,active,in_review,closed,archived',
            'specialty' => 'sometimes|string|in:oncology,surgical,rare_disease,complex_medical',
            'urgency' => 'sometimes|string|in:routine,urgent,emergent',
            'search' => 'sometimes|string|max:255',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        $cases = $this->caseService->getCasesForUser(
            $request->user()->id,
            $request->only(['status', 'specialty', 'urgency', 'search', 'per_page']),
        );

        return ApiResponse::paginated($cases, 'Cases retrieved');
    }

    /**
     * POST /api/cases
     * Create a new clinical case.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'specialty' => 'required|string|in:oncology,surgical,rare_disease,complex_medical',
            'urgency' => 'sometimes|string|in:routine,urgent,emergent',
            'case_type' => 'required|string|in:tumor_board,surgical_review,rare_disease,medical_complex',
            'patient_id' => 'nullable|integer|exists:clinical.patients,id',
            'clinical_question' => 'nullable|string|max:5000',
            'summary' => 'nullable|string|max:10000',
            'institution_id' => 'nullable|integer',
            'scheduled_at' => 'nullable|date',
            'template_id' => ['nullable', 'integer', 'exists:app.case_templates,id'],
            'structured_data' => ['nullable', 'array', 'max:50'],
            'structured_data.*' => ['nullable', 'string', 'max:10000'],
        ]);

        // Soft template binding: resolve the board template (if any), run SOFT
        // validation (warnings only, never rejects), and seed the case's initial
        // state from the template's state machine. Stateless templates leave state null.
        $warnings = [];

        if (! empty($validated['template_id'])) {
            $template = CaseTemplate::find($validated['template_id']);

            if ($template !== null) {
                $warnings = $this->boardTemplateService->validate(
                    $template,
                    $validated['structured_data'] ?? [],
                );
                $validated['state'] = $this->caseStateMachine->initialState($template);
            }
        }

        $case = $this->caseService->createCase($validated, $request->user()->id);

        return ApiResponse::success($case, 'Case created', 201, ['warnings' => $warnings]);
    }

    /**
     * GET /api/cases/{case}
     * Show a case with all relations.
     */
    public function show(int $case): JsonResponse
    {
        $clinicalCase = ClinicalCase::with([
            'creator',
            'patient',
            'teamMembers.user',
            'annotations.user',
            'discussions.user',
            'discussions.replies.user',
            'documents',
            'decisions',
        ])->find($case);

        if (! $clinicalCase) {
            return ApiResponse::error('Case not found', 404);
        }

        $this->authorize('view', $clinicalCase);

        return ApiResponse::success($clinicalCase, 'Case retrieved');
    }

    /**
     * PUT /api/cases/{case}
     * Update a case.
     */
    public function update(Request $request, int $case): JsonResponse
    {
        $clinicalCase = ClinicalCase::find($case);

        if (! $clinicalCase) {
            return ApiResponse::error('Case not found', 404);
        }

        $this->authorize('update', $clinicalCase);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'specialty' => 'sometimes|string|in:oncology,surgical,rare_disease,complex_medical',
            'urgency' => 'sometimes|string|in:routine,urgent,emergent',
            'status' => 'sometimes|string|in:draft,active,in_review,closed,archived',
            'case_type' => 'sometimes|string|in:tumor_board,surgical_review,rare_disease,medical_complex',
            'patient_id' => 'nullable|integer|exists:clinical.patients,id',
            'clinical_question' => 'nullable|string|max:5000',
            'summary' => 'nullable|string|max:10000',
            'institution_id' => 'nullable|integer',
            'scheduled_at' => 'nullable|date',
        ]);

        $updated = $this->caseService->updateCase($clinicalCase, $validated);

        return ApiResponse::success($updated, 'Case updated');
    }

    /**
     * DELETE /api/cases/{case}
     * Soft delete / archive a case.
     */
    public function destroy(int $case): JsonResponse
    {
        $clinicalCase = ClinicalCase::find($case);

        if (! $clinicalCase) {
            return ApiResponse::error('Case not found', 404);
        }

        $this->authorize('delete', $clinicalCase);

        $this->caseService->archiveCase($clinicalCase);
        $clinicalCase->delete();

        return ApiResponse::success(null, 'Case archived');
    }

    /**
     * POST /api/cases/{case}/team
     * Add a team member to a case.
     */
    public function addTeamMember(Request $request, int $case): JsonResponse
    {
        $clinicalCase = ClinicalCase::find($case);

        if (! $clinicalCase) {
            return ApiResponse::error('Case not found', 404);
        }

        $this->authorize('manageTeam', $clinicalCase);

        $validated = $request->validate([
            'user_id' => 'required|integer|exists:app.users,id',
            'role' => 'required|string|in:presenter,reviewer,observer,coordinator',
        ]);

        try {
            $member = $this->caseService->addTeamMember(
                $clinicalCase,
                (int) $validated['user_id'],
                $validated['role'],
            );

            return ApiResponse::success($member->load('user'), 'Team member added', 201);
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), 409);
        }
    }

    /**
     * DELETE /api/cases/{case}/team/{user}
     * Remove a team member from a case.
     */
    public function removeTeamMember(int $case, int $user): JsonResponse
    {
        $clinicalCase = ClinicalCase::find($case);

        if (! $clinicalCase) {
            return ApiResponse::error('Case not found', 404);
        }

        $this->authorize('manageTeam', $clinicalCase);

        try {
            $this->caseService->removeTeamMember($clinicalCase, $user);

            return ApiResponse::success(null, 'Team member removed');
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }
    }
}
