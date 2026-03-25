<?php

namespace App\Services;

use App\Models\Clinical\OutcomeTrajectory;
use Illuminate\Support\Facades\Http;

class OutcomeService
{
    private string $aiBaseUrl;

    public function __construct()
    {
        $this->aiBaseUrl = rtrim(config('services.ai.url', 'http://localhost:8000'), '/');
    }

    /**
     * Compute trajectory sub-scores for a patient via Python AI service.
     */
    public function computeTrajectory(int $patientId): OutcomeTrajectory
    {
        $trajectory = OutcomeTrajectory::firstOrCreate(
            ['patient_id' => $patientId],
            ['computed_at' => now()]
        );

        try {
            $response = Http::timeout(30)->post("{$this->aiBaseUrl}/api/ai/fingerprint/outcome/compute", [
                'patient_id' => $patientId,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $trajectory->update([
                    'tumor_response_score' => $data['tumor_response'] ?? null,
                    'treatment_tolerance_score' => $data['treatment_tolerance'] ?? null,
                    'lab_trajectory_score' => $data['lab_trajectory'] ?? null,
                    'disease_stability_score' => $data['disease_stability'] ?? null,
                    'care_intensity_score' => $data['care_intensity'] ?? null,
                    'composite_score' => $data['composite'] ?? null,
                    'computed_at' => now(),
                ]);
            }
        } catch (\Exception $e) {
            \Log::warning("Outcome computation failed for patient {$patientId}: {$e->getMessage()}");
        }

        return $trajectory->fresh();
    }

    /**
     * Save a clinician's outcome assessment.
     */
    public function saveAssessment(int $patientId, int $assessedBy, array $data): OutcomeTrajectory
    {
        $trajectory = OutcomeTrajectory::firstOrCreate(
            ['patient_id' => $patientId],
            ['computed_at' => now()]
        );

        $trajectory->update([
            'clinician_rating' => $data['clinician_rating'],
            'clinician_factors' => $data['clinician_factors'] ?? null,
            'decision_tags' => $data['decision_tags'] ?? null,
            'hindsight_note' => $data['hindsight_note'] ?? null,
            'assessed_by' => $assessedBy,
            'assessed_at' => now(),
        ]);

        return $trajectory->fresh();
    }

    /**
     * Get outcome trajectory for a patient, including enrichment with patient context.
     */
    public function getTrajectory(int $patientId): ?array
    {
        $trajectory = OutcomeTrajectory::with('assessor')->where('patient_id', $patientId)->first();

        if (! $trajectory) {
            return null;
        }

        return [
            'patient_id' => $patientId,
            'computed' => [
                'composite_score' => $trajectory->composite_score,
                'sub_scores' => $trajectory->sub_scores,
                'computed_at' => $trajectory->computed_at,
            ],
            'assessment' => $trajectory->clinician_rating ? [
                'rating' => $trajectory->clinician_rating,
                'factors' => $trajectory->clinician_factors,
                'decision_tags' => $trajectory->decision_tags ?? [],
                'hindsight_note' => $trajectory->hindsight_note,
                'assessed_by' => $trajectory->assessor?->name,
                'assessed_at' => $trajectory->assessed_at,
            ] : null,
        ];
    }
}
