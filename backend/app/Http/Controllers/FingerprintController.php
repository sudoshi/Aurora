<?php

namespace App\Http\Controllers;

use App\Http\Helpers\ApiResponse;
use App\Models\Clinical\FusionWeightConfig;
use App\Models\Clinical\OutcomeTrajectory;
use App\Models\Clinical\PatientFingerprint;
use App\Services\FingerprintService;
use App\Services\OutcomeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FingerprintController extends Controller
{
    public function __construct(
        private readonly FingerprintService $fingerprintService,
        private readonly OutcomeService $outcomeService,
    ) {}

    /**
     * POST /api/fingerprint/search
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'patient_id' => 'required|integer|exists:clinical.patients,id',
            'weights' => 'sometimes|array',
            'weights.genomic' => 'sometimes|numeric|min:0|max:1',
            'weights.volumetric' => 'sometimes|numeric|min:0|max:1',
            'weights.clinical' => 'sometimes|numeric|min:0|max:1',
            'limit' => 'sometimes|integer|min:1|max:50',
            'context' => 'sometimes|string|in:point_of_care,tumor_board,research',
        ]);

        $result = $this->fingerprintService->searchSimilar(
            patientId: $request->input('patient_id'),
            weights: $request->input('weights', []),
            limit: $request->input('limit', 10),
            context: $request->input('context', 'point_of_care'),
        );

        // Enrich results with outcome and patient data
        $enriched = $this->enrichSearchResults($result['results']);

        // Log the search
        $this->fingerprintService->logSearch(
            queryPatientId: $request->input('patient_id'),
            searchedBy: auth()->id(),
            weightsUsed: $result['meta']['weights_used'],
            weightsCustomized: $result['meta']['weights_customized'],
            context: $request->input('context', 'point_of_care'),
            results: $result['results'],
        );

        return ApiResponse::success([
            'results' => $enriched,
            'meta' => $result['meta'],
        ], 'Similar patients found');
    }

    /**
     * GET /api/fingerprint/patients/{id}
     */
    public function showFingerprint(int $id): JsonResponse
    {
        $fingerprint = PatientFingerprint::where('patient_id', $id)->first();

        if (! $fingerprint) {
            return ApiResponse::success([
                'patient_id' => $id,
                'has_fingerprint' => false,
                'dimensions' => ['genomic' => false, 'volumetric' => false, 'clinical' => false],
            ], 'No fingerprint for this patient');
        }

        return ApiResponse::success([
            'patient_id' => $id,
            'has_fingerprint' => true,
            'dimensions' => [
                'genomic' => $fingerprint->genomic_available,
                'volumetric' => $fingerprint->volumetric_available,
                'clinical' => $fingerprint->clinical_available,
            ],
            'confidence' => [
                'genomic' => $fingerprint->genomic_confidence,
                'volumetric' => $fingerprint->volumetric_confidence,
                'clinical' => $fingerprint->clinical_confidence,
            ],
            'encoded_at' => [
                'genomic' => $fingerprint->genomic_encoded_at,
                'volumetric' => $fingerprint->volumetric_encoded_at,
                'clinical' => $fingerprint->clinical_encoded_at,
            ],
            'encoder_version' => $fingerprint->encoder_version,
            'dimension_count' => $fingerprint->available_dimension_count,
        ], 'Fingerprint retrieved');
    }

    /**
     * POST /api/fingerprint/patients/{id}/encode
     */
    public function encode(int $id): JsonResponse
    {
        $fingerprint = $this->fingerprintService->encodePatient($id);

        return ApiResponse::success([
            'patient_id' => $id,
            'dimensions' => [
                'genomic' => $fingerprint->genomic_available,
                'volumetric' => $fingerprint->volumetric_available,
                'clinical' => $fingerprint->clinical_available,
            ],
            'confidence' => [
                'genomic' => $fingerprint->genomic_confidence,
                'volumetric' => $fingerprint->volumetric_confidence,
                'clinical' => $fingerprint->clinical_confidence,
            ],
            'dimension_count' => $fingerprint->available_dimension_count,
        ], 'Patient fingerprint encoded');
    }

    /**
     * POST /api/fingerprint/encode-batch
     */
    public function encodeBatch(Request $request): JsonResponse
    {
        $request->validate([
            'patient_ids' => 'required|array|min:1|max:100',
            'patient_ids.*' => 'integer|exists:clinical.patients,id',
        ]);

        $results = [];
        foreach ($request->input('patient_ids') as $patientId) {
            $fp = $this->fingerprintService->encodePatient($patientId);
            $results[] = [
                'patient_id' => $patientId,
                'dimension_count' => $fp->available_dimension_count,
            ];
        }

        return ApiResponse::success($results, count($results).' patients encoded');
    }

    /**
     * GET /api/fingerprint/patients/{id}/outcome
     */
    public function showOutcome(int $id): JsonResponse
    {
        $trajectory = $this->outcomeService->getTrajectory($id);

        if (! $trajectory) {
            return ApiResponse::success([
                'patient_id' => $id,
                'has_outcome' => false,
            ], 'No outcome data for this patient');
        }

        return ApiResponse::success(
            array_merge(['has_outcome' => true], $trajectory),
            'Outcome trajectory retrieved'
        );
    }

    /**
     * PUT /api/fingerprint/patients/{id}/outcome/assess
     */
    public function assessOutcome(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'clinician_rating' => 'required|string|in:excellent,good,mixed,poor,failure',
            'clinician_factors' => 'sometimes|string|max:5000',
            'decision_tags' => 'sometimes|array',
            'decision_tags.*' => 'string|max:50',
            'hindsight_note' => 'sometimes|string|max:5000',
        ]);

        $trajectory = $this->outcomeService->saveAssessment(
            patientId: $id,
            assessedBy: auth()->id(),
            data: $request->only(['clinician_rating', 'clinician_factors', 'decision_tags', 'hindsight_note']),
        );

        return ApiResponse::success([
            'patient_id' => $id,
            'clinician_rating' => $trajectory->clinician_rating,
            'assessed_at' => $trajectory->assessed_at,
        ], 'Outcome assessment saved');
    }

    /**
     * GET /api/fingerprint/weights
     */
    public function listWeights(): JsonResponse
    {
        $configs = FusionWeightConfig::presets()->get();

        return ApiResponse::success($configs, 'Weight presets retrieved');
    }

    /**
     * GET /api/fingerprint/weights/active
     */
    public function activeWeights(): JsonResponse
    {
        $active = FusionWeightConfig::active()->first();

        return ApiResponse::success($active, 'Active weight config retrieved');
    }

    /**
     * GET /api/fingerprint/stats
     */
    public function stats(): JsonResponse
    {
        return ApiResponse::success(
            $this->fingerprintService->getStats(),
            'Fingerprint stats retrieved'
        );
    }

    /**
     * Enrich search results with patient demographics and outcome data.
     */
    private function enrichSearchResults(array $results): array
    {
        if (empty($results)) {
            return [];
        }

        $patientIds = array_column($results, 'patient_id');

        $patients = \App\Models\Clinical\ClinicalPatient::whereIn('id', $patientIds)
            ->with(['conditions' => fn ($q) => $q->where('domain', 'oncology')->limit(3)])
            ->get()
            ->keyBy('id');

        $outcomes = OutcomeTrajectory::whereIn('patient_id', $patientIds)
            ->get()
            ->keyBy('patient_id');

        return array_map(function ($result) use ($patients, $outcomes) {
            $patient = $patients[$result['patient_id']] ?? null;
            $outcome = $outcomes[$result['patient_id']] ?? null;

            $result['patient'] = $patient ? [
                'id' => $patient->id,
                'mrn' => $patient->mrn,
                'first_name' => $patient->first_name,
                'last_name' => $patient->last_name,
                'sex' => $patient->sex,
                'date_of_birth' => $patient->date_of_birth,
                'primary_conditions' => $patient->conditions->pluck('concept_name')->toArray(),
            ] : null;

            $result['outcome'] = $outcome ? [
                'composite_score' => $outcome->composite_score,
                'clinician_rating' => $outcome->clinician_rating,
                'decision_tags' => $outcome->decision_tags ?? [],
                'hindsight_note' => $outcome->hindsight_note,
                'sub_scores' => $outcome->sub_scores,
            ] : null;

            return $result;
        }, $results);
    }
}
