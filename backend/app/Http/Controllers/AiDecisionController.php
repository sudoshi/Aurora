<?php

namespace App\Http\Controllers;

use App\Http\Helpers\ApiResponse;
use App\Models\ClinicalCase;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiDecisionController extends Controller
{
    use AuthorizesRequests;

    /**
     * POST /api/cases/{case}/decisions/draft
     *
     * Proxy a decision draft request to the AI service and return the raw response.
     */
    public function draft(int $case): JsonResponse
    {
        $c = ClinicalCase::findOrFail($case);

        // Drafting an AI recommendation requires case access (CasePolicy).
        $this->authorize('view', $c);

        try {
            $resp = Http::timeout(60)->acceptJson()->post(
                rtrim(config('services.ai.base_url'), '/').'/api/ai/abby/draft-decision',
                ['case_id' => $c->id, 'patient_id' => $c->patient_id, 'clinical_question' => $c->clinical_question],
            );

            if (! $resp->successful()) {
                return ApiResponse::error('Decision draft unavailable', 502);
            }

            return ApiResponse::success($resp->json());
        } catch (\Throwable $e) {
            Log::warning('AI decision draft failed: '.$e->getMessage());

            return ApiResponse::error('Decision draft unavailable', 502);
        }
    }
}
